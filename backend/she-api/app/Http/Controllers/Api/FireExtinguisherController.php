<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireExtinguisherInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class FireExtinguisherController extends Controller
{
    public function index(Request $request)
    {
        $data = FireExtinguisherInspection::with(['inspector', 'location', 'area'])
            ->orderByDesc('inspection_date')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_no' => 'required|string|max:40|unique:fire_extinguisher_inspections,reference_no',
            'inspection_date' => 'required|date',
            'location_id' => 'nullable|exists:locations,id',
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
            'items.*.type' => 'nullable|string|max:200',
            'items.*.location_detail' => 'nullable|string|max:200',
            'items.*.pressure_condition' => 'nullable|boolean',
            'items.*.seal_condition' => 'nullable|boolean',
            'items.*.nozzle_condition' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
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
        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';
        $data['checked_in_at'] = now();
        if ($data['status'] === 'signed') {
            $data['signed_at'] = now();
        }

        $inspection = DB::transaction(function () use ($data, $items) {
            $inspection = FireExtinguisherInspection::create($data);
            $this->syncItems($inspection, $items);

            return $inspection->load('items');
        });

        return response()->json(['data' => $inspection], 201);
    }

    public function show($id)
    {
        $item = FireExtinguisherInspection::with(['inspector', 'location', 'area', 'items'])->findOrFail($id);
        return response()->json(['data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $inspection = FireExtinguisherInspection::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reference_no' => 'required|string|max:40|unique:fire_extinguisher_inspections,reference_no,' . $id,
            'inspection_date' => 'required|date',
            'location_id' => 'nullable|exists:locations,id',
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
            'items.*.type' => 'nullable|string|max:200',
            'items.*.location_detail' => 'nullable|string|max:200',
            'items.*.pressure_condition' => 'nullable|boolean',
            'items.*.seal_condition' => 'nullable|boolean',
            'items.*.nozzle_condition' => 'nullable|boolean',
            'items.*.remark' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
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

    private function syncItems(FireExtinguisherInspection $inspection, array $items): void
    {
        $inspection->items()->delete();

        foreach ($items as $item) {
            $inspection->items()->create([
                'name' => $item['name'],
                'type' => $item['type'] ?? null,
                'location_detail' => $item['location_detail'] ?? null,
                'pressure_condition' => $item['pressure_condition'] ?? false,
                'seal_condition' => $item['seal_condition'] ?? false,
                'nozzle_condition' => $item['nozzle_condition'] ?? false,
                'remark' => $item['remark'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'item_lat' => $item['item_lat'] ?? null,
                'item_lng' => $item['item_lng'] ?? null,
            ]);
        }
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
                    'fire_extinguisher_%s_item_%s_%s.%s',
                    $field === 'photo_before' ? 'before' : 'after',
                    $index + 1,
                    now()->format('YmdHisv'),
                    $extension
                );

                $file->move($targetPath, $filename);
            }
        }
    }

    public function destroy($id)
    {
        $inspection = FireExtinguisherInspection::findOrFail($id);
        $inspection->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function checkin(Request $request, $id)
    {
        $inspection = FireExtinguisherInspection::findOrFail($id);

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
        $inspection = FireExtinguisherInspection::findOrFail($id);
        $inspection->update(['signed_at' => now(), 'status' => 'signed']);

        return response()->json(['data' => $inspection]);
    }
}
