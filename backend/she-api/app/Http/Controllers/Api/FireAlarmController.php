<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AuthorizesOwnerOrAdmin;
use App\Models\FireAlarmInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class FireAlarmController extends Controller
{
    use AuthorizesOwnerOrAdmin;

    public function index(Request $request)
    {
        $data = FireAlarmInspection::with(['inspector', 'location', 'area'])
            ->orderByDesc('inspection_date')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_no' => 'nullable|string|max:40|unique:fire_alarm_inspections,reference_no',
            'inspection_date' => 'required|date',
            'location_id' => 'nullable|exists:fire_alarm_location,id_location',
            'area_id' => 'nullable|exists:areas,id',
            'qr_code_id' => 'nullable|exists:asset_qr_codes,id',
            'inspector_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id',
            'checkin_lat' => 'nullable|numeric',
            'checkin_lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,completed,signed',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:100',
            'items.*.alarm_number' => 'nullable|string|max:200',
            'items.*.type' => 'nullable|string|max:200',
            'items.*.location_detail' => 'nullable|string|max:299',
            'items.*.condition_good' => 'nullable|boolean',
            'items.*.correction_needed' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
            'items.*.photo_before' => 'nullable|image|max:5120',
            'items.*.photo_after' => 'nullable|image|max:5120',
            'items.*.item_lat' => 'nullable|numeric',
            'items.*.item_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $this->storeItemPhotos($request);
        $data['reference_no'] = $data['reference_no'] ?? $this->generateReferenceNo();
        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';
        $data['checked_in_at'] = now();
        if ($data['status'] === 'signed') {
            $data['signed_at'] = now();
        }

        $inspection = DB::transaction(function () use ($data, $items) {
            $inspection = FireAlarmInspection::create($data);
            $this->syncItems($inspection, $items);

            return $inspection->load('items');
        });

        return response()->json(['data' => $inspection], 201);
    }

    public function show($id)
    {
        $item = FireAlarmInspection::with(['inspector', 'location', 'area', 'items'])->findOrFail($id);
        return response()->json(['data' => $item]);
    }

    public function storeItem(Request $request, $id)
    {
        $inspection = FireAlarmInspection::findOrFail($id);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'alarm_number' => 'nullable|string|max:200',
            'type' => 'nullable|string|max:200',
            'location_detail' => 'nullable|string|max:299',
            'condition_good' => 'nullable|boolean',
            'correction_needed' => 'nullable|boolean',
            'remark' => 'nullable|string',
            'photo_before' => 'nullable|image|max:5120',
            'photo_after' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['photo_before'] = $this->storeItemPhoto($request, 'photo_before');
        $data['photo_after'] = $this->storeItemPhoto($request, 'photo_after');

        $item = $inspection->items()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function updateItem(Request $request, $inspectionId, $itemId)
    {
        $inspection = FireAlarmInspection::findOrFail($inspectionId);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $item = $inspection->items()->findOrFail($itemId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'alarm_number' => 'nullable|string|max:200',
            'type' => 'nullable|string|max:200',
            'location_detail' => 'nullable|string|max:299',
            'condition_good' => 'nullable|boolean',
            'correction_needed' => 'nullable|boolean',
            'remark' => 'nullable|string',
            'photo_before' => 'nullable|image|max:5120',
            'photo_after' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $oldPhotos = [];

        foreach (['photo_before', 'photo_after'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $data[$field] = $this->storeItemPhoto($request, $field);
                $oldPhotos[] = $item->{$field};
            } else {
                unset($data[$field]);
            }
        }

        $item->update($data);

        foreach ($oldPhotos as $oldPhoto) {
            $this->deleteItemPhoto($oldPhoto);
        }

        return response()->json([
            'message' => 'Item Fire Alarm berhasil diperbarui.',
            'data' => $item->fresh(),
        ]);
    }

    public function destroyItem(Request $request, $inspectionId, $itemId)
    {
        $inspection = FireAlarmInspection::findOrFail($inspectionId);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $item = $inspection->items()->findOrFail($itemId);
        $photos = [$item->photo_before, $item->photo_after];

        $item->delete();

        foreach ($photos as $photo) {
            $this->deleteItemPhoto($photo);
        }

        return response()->json(['message' => 'Item Fire Alarm berhasil dihapus.']);
    }

    public function update(Request $request, $id)
    {
        $inspection = FireAlarmInspection::findOrFail($id);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $validator = Validator::make($request->all(), [
            'reference_no' => 'nullable|string|max:40|unique:fire_alarm_inspections,reference_no,' . $id,
            'inspection_date' => 'required|date',
            'location_id' => 'nullable|exists:fire_alarm_location,id_location',
            'area_id' => 'nullable|exists:areas,id',
            'qr_code_id' => 'nullable|exists:asset_qr_codes,id',
            'inspector_id' => 'nullable|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id',
            'checkin_lat' => 'nullable|numeric',
            'checkin_lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,completed,signed',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:100',
            'items.*.alarm_number' => 'nullable|string|max:200',
            'items.*.type' => 'nullable|string|max:200',
            'items.*.location_detail' => 'nullable|string|max:299',
            'items.*.condition_good' => 'nullable|boolean',
            'items.*.correction_needed' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
            'items.*.photo_before' => 'nullable|image|max:5120',
            'items.*.photo_after' => 'nullable|image|max:5120',
            'items.*.item_lat' => 'nullable|numeric',
            'items.*.item_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $this->storeItemPhotos($request);
        if (blank($data['reference_no'] ?? null)) {
            unset($data['reference_no']);
        }
        if (array_key_exists('checkin_lat', $data) && $data['checkin_lat'] === null) {
            unset($data['checkin_lat']);
        }
        if (array_key_exists('checkin_lng', $data) && $data['checkin_lng'] === null) {
            unset($data['checkin_lng']);
        }
        $data['checked_in_at'] = $inspection->checked_in_at ?: now();
        if (($data['status'] ?? $inspection->status) === 'signed') {
            $data['signed_at'] = $inspection->signed_at ?: now();
        }

        DB::transaction(function () use ($inspection, $data, $items) {
            $inspection->update($data);
            $this->syncItems($inspection, $items);
        });

        $inspection->load('items');

        return response()->json(['data' => $inspection]);
    }

    private function syncItems(FireAlarmInspection $inspection, array $items): void
    {
        $inspection->items()->delete();

        foreach ($items as $item) {
            $inspection->items()->create([
                'name' => $item['name'],
                'alarm_number' => $item['alarm_number'] ?? null,
                'type' => $item['type'] ?? null,
                'location_detail' => $item['location_detail'] ?? null,
                'condition_good' => $item['condition_good'] ?? false,
                'correction_needed' => $item['correction_needed'] ?? false,
                'remark' => $item['remark'] ?? null,
                'item_lat' => $item['item_lat'] ?? null,
                'item_lng' => $item['item_lng'] ?? null,
            ]);
        }
    }

    private function generateReferenceNo(): string
    {
        $nextId = (int) FireAlarmInspection::max('id') + 1;

        do {
            $referenceNo = 'FA-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (FireAlarmInspection::where('reference_no', $referenceNo)->exists());

        return $referenceNo;
    }

    private function storeItemPhoto(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field) || !$request->file($field)->isValid()) {
            return null;
        }

        $targetPath = public_path('images');
        File::ensureDirectoryExists($targetPath);

        $file = $request->file($field);
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = sprintf(
            'fire_alarm_%s_%s.%s',
            $field === 'photo_before' ? 'before' : 'after',
            now()->format('YmdHisv'),
            $extension
        );

        $file->move($targetPath, $filename);

        return 'images/' . $filename;
    }

    private function deleteItemPhoto(?string $path): void
    {
        $normalized = str_replace('\\', '/', ltrim((string) $path, '/'));

        if (!str_starts_with($normalized, 'images/')) {
            return;
        }

        $filename = substr($normalized, strlen('images/'));
        if ($filename === '' || basename($filename) !== $filename) {
            return;
        }

        File::delete(public_path('images/' . $filename));
    }

    private function storeItemPhotos(Request $request): void
    {
        $items = $request->file('items', []);
        if (!is_array($items)) {
            return;
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
                    'fire_alarm_%s_item_%s_%s.%s',
                    $field === 'photo_before' ? 'before' : 'after',
                    $index + 1,
                    now()->format('YmdHisv'),
                    $extension
                );

                $file->move($targetPath, $filename);
            }
        }
    }

    public function destroy(Request $request, $id)
    {
        $inspection = FireAlarmInspection::findOrFail($id);

        if ($forbidden = $this->authorizeOwnerOrAdmin($request, $inspection, 'inspector_id')) {
            return $forbidden;
        }

        $inspection->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function checkin(Request $request, $id)
    {
        $inspection = FireAlarmInspection::findOrFail($id);

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
        $inspection = FireAlarmInspection::findOrFail($id);
        $inspection->update(['signed_at' => now(), 'status' => 'signed']);

        return response()->json(['data' => $inspection]);
    }
}
