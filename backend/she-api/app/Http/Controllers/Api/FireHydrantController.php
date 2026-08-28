<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AuthorizesOwnerOrAdmin;
use App\Models\FireHydrantInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FireHydrantController extends Controller
{
    use AuthorizesOwnerOrAdmin;

    public function index(Request $request)
    {
        $query = FireHydrantInspection::with(['inspector', 'signer', 'location', 'area', 'items'])
            ->orderByDesc('inspection_date')
            ->orderByDesc('id');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $data = $query->get();

        return response()->json(['data' => $data]);
    }

    public function nextReference()
    {
        return response()->json([
            'data' => [
                'reference_no' => $this->buildReferenceNo($this->nextRunningNumber(), $this->nextHydrantId()),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_no' => 'nullable|string|max:40',
            'inspection_date' => 'required|date',
            'location_id' => 'nullable|exists:fire_hydrant_location,id_location',
            'area_id' => 'nullable|exists:areas,id',
            'qr_code_id' => 'nullable|exists:asset_qr_codes,id',
            'inspector_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id',
            'checkin_lat' => 'nullable|numeric',
            'checkin_lng' => 'nullable|numeric',
            'checked_in_at' => 'nullable|date',
            'signed_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.hydrant_number' => 'required_with:items|string|max:200',
            'items.*.name' => 'required_with:items|string|max:100',
            'items.*.location_detail' => 'nullable|string|max:200',
            'items.*.hose_condition' => 'nullable|boolean',
            'items.*.nozzle_condition' => 'nullable|boolean',
            'items.*.coupling_condition' => 'nullable|boolean',
            'items.*.wrench_condition' => 'nullable|boolean',
            'items.*.valve_condition' => 'nullable|boolean',
            'items.*.coupling_extra_condition' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
            'items.*.photo_before' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'items.*.photo_after' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'items.*.item_lat' => 'nullable|numeric',
            'items.*.item_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $items = $data['items'] ?? [];
        unset($data['items'], $data['reference_no']);
        $items = $this->mergeItemPhotos($items, $this->storeItemPhotos($request));

        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $data['checked_in_at'] = $data['checked_in_at'] ?? now();

        $inspection = DB::transaction(function () use ($data, $items) {
            $inspection = FireHydrantInspection::create($data);
            $this->assignReferenceNo($inspection);
            $this->syncItems($inspection, $items);

            return $inspection->load('items');
        });

        return response()->json(['data' => $inspection], 201);
    }

    public function show($id)
    {
        $item = FireHydrantInspection::with(['inspector', 'signer', 'location', 'area', 'items'])->findOrFail($id);
        return response()->json(['data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $inspection = FireHydrantInspection::findOrFail($id);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'reference_no' => 'nullable|string|max:40|unique:fire_hydrant_inspections,reference_no,' . $id,
            'inspection_date' => 'required|date',
            'location_id' => 'nullable|exists:fire_hydrant_location,id_location',
            'area_id' => 'nullable|exists:areas,id',
            'qr_code_id' => 'nullable|exists:asset_qr_codes,id',
            'inspector_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id',
            'checkin_lat' => 'nullable|numeric',
            'checkin_lng' => 'nullable|numeric',
            'checked_in_at' => 'nullable|date',
            'signed_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.hydrant_number' => 'required_with:items|string|max:200',
            'items.*.name' => 'required_with:items|string|max:100',
            'items.*.location_detail' => 'nullable|string|max:200',
            'items.*.hose_condition' => 'nullable|boolean',
            'items.*.nozzle_condition' => 'nullable|boolean',
            'items.*.coupling_condition' => 'nullable|boolean',
            'items.*.wrench_condition' => 'nullable|boolean',
            'items.*.valve_condition' => 'nullable|boolean',
            'items.*.coupling_extra_condition' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
            'items.*.photo_before' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'items.*.photo_after' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'items.*.item_lat' => 'nullable|numeric',
            'items.*.item_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $hasItems = $request->has('items');
        $items = $data['items'] ?? [];
        unset($data['items'], $data['reference_no']);
        if ($hasItems) {
            $items = $this->mergeItemPhotos($items, $this->storeItemPhotos($request));
            $items = $this->carryExistingItemPhotos($inspection, $items);
        }

        if (array_key_exists('checkin_lat', $data) && $data['checkin_lat'] === null) {
            unset($data['checkin_lat']);
        }
        if (array_key_exists('checkin_lng', $data) && $data['checkin_lng'] === null) {
            unset($data['checkin_lng']);
        }

        $data['checked_in_at'] = $inspection->checked_in_at ?: now();

        DB::transaction(function () use ($inspection, $data, $items, $hasItems) {
            $inspection->update($data);
            if (empty($inspection->reference_no)) {
                $this->assignReferenceNo($inspection);
            }
            if ($hasItems) {
                $this->syncItems($inspection, $items);
            }
        });

        $inspection->load('items');

        return response()->json(['data' => $inspection]);
    }

    private function syncItems(FireHydrantInspection $inspection, array $items): void
    {
        $inspection->items()->delete();

        foreach ($items as $item) {
            $inspection->items()->create([
                'hydrant_number' => $item['hydrant_number'],
                'name' => $item['name'],
                'location_detail' => $item['location_detail'] ?? null,
                'hose_condition' => $item['hose_condition'] ?? false,
                'nozzle_condition' => $item['nozzle_condition'] ?? false,
                'coupling_condition' => $item['coupling_condition'] ?? false,
                'wrench_condition' => $item['wrench_condition'] ?? false,
                'valve_condition' => $item['valve_condition'] ?? false,
                'coupling_extra_condition' => $item['coupling_extra_condition'] ?? false,
                'remark' => $item['remark'] ?? null,
                'photo_before' => $item['photo_before'] ?? null,
                'photo_after' => $item['photo_after'] ?? null,
                'item_lat' => $item['item_lat'] ?? null,
                'item_lng' => $item['item_lng'] ?? null,
            ]);
        }
    }

    private function assignReferenceNo(FireHydrantInspection $inspection): void
    {
        $inspection->forceFill([
            'reference_no' => $this->buildReferenceNo($this->runningNumberFor($inspection), $inspection->id),
        ])->save();
    }

    private function buildReferenceNo(int $runningNumber, int $hydrantId): string
    {
        return 'FH-' . $runningNumber . 'F' . $hydrantId;
    }

    private function nextRunningNumber(): int
    {
        return FireHydrantInspection::count() + 1;
    }

    private function runningNumberFor(FireHydrantInspection $inspection): int
    {
        return FireHydrantInspection::where('id', '<=', $inspection->id)->count();
    }

    private function nextHydrantId(): int
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$database, 'fire_hydrant_inspections']
        );

        return (int) ($row->next_id ?? (FireHydrantInspection::max('id') + 1));
    }

    private function storeItemPhotos(Request $request): array
    {
        $stored = [];
        $items = $request->file('items', []);
        if (!is_array($items)) {
            return $stored;
        }

        $targetPath = public_path('images');
        File::ensureDirectoryExists($targetPath);

        foreach ($items as $index => $item) {
            foreach (['photo_before', 'photo_after'] as $field) {
                if (!isset($item[$field]) || !$item[$field]->isValid()) {
                    continue;
                }

                $file = $item[$field];
                $extension = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = sprintf(
                    'fire_hydrant_%s_item_%s_%s.%s',
                    $field === 'photo_before' ? 'before' : 'after',
                    $index + 1,
                    now()->format('YmdHisv'),
                    $extension
                );

                $file->move($targetPath, $filename);
                $stored[$index][$field] = 'images/' . $filename;
            }
        }

        return $stored;
    }

    private function mergeItemPhotos(array $items, array $photos): array
    {
        foreach ($photos as $index => $fields) {
            foreach ($fields as $field => $path) {
                $items[$index][$field] = $path;
            }
        }

        return $items;
    }

    private function carryExistingItemPhotos(FireHydrantInspection $inspection, array $items): array
    {
        $existingItems = $inspection->items()->orderBy('id')->get()->values();

        foreach ($items as $index => $item) {
            $existing = $existingItems->get($index);
            if (!$existing) {
                continue;
            }

            foreach (['photo_before', 'photo_after'] as $field) {
                if (empty($items[$index][$field]) && !empty($existing->{$field})) {
                    $items[$index][$field] = $existing->{$field};
                }
            }
        }

        return $items;
    }

    public function destroy(Request $request, $id)
    {
        $inspection = FireHydrantInspection::findOrFail($id);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $inspection->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function checkin(Request $request, $id)
    {
        $inspection = FireHydrantInspection::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'checkin_lat' => 'required|numeric',
            'checkin_lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inspection->update([
            'checkin_lat' => $request->checkin_lat,
            'checkin_lng' => $request->checkin_lng,
            'checked_in_at' => now(),
        ]);

        return response()->json(['data' => $inspection]);
    }

    public function sign(Request $request, $id)
    {
        $inspection = FireHydrantInspection::findOrFail($id);
        $user = $request->user();

        if (empty($user->signature_path)) {
            return response()->json([
                'message' => 'User login belum memiliki tanda tangan yang terdaftar.',
            ], 422);
        }

        if ($inspection->signed_by) {
            return response()->json([
                'message' => 'Inspeksi ini sudah ditandatangani.',
            ], 409);
        }

        $inspection->update([
            'signed_at' => now(),
            'signed_by' => $user->id,
        ]);

        return response()->json(['data' => $inspection->load('signer')]);
    }

    public function deleteItem(Request $request, $inspectionId, $itemIndex)
    {
        $inspection = FireHydrantInspection::findOrFail($inspectionId);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $items = $inspection->items()->orderBy('id')->get()->values();

        if ($itemIndex < 0 || $itemIndex >= $items->count()) {
            return response()->json(['message' => 'Invalid item index'], 404);
        }

        $items->get($itemIndex)->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}
