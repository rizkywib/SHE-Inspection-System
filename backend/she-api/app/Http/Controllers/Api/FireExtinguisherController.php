<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireExtinguisherInspection;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class FireExtinguisherController extends Controller
{
    public function index(Request $request)
    {
        $query = FireExtinguisherInspection::with(['inspector', 'signer', 'location', 'items']);

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }

        $data = $query
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function nextReference()
    {
        return response()->json([
            'data' => [
                'reference_no' => 'FE-' . str_pad((string) (FireExtinguisherInspection::max('id') + 1), 6, '0', STR_PAD_LEFT),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference_no' => 'nullable|string|max:40|unique:fire_extinguisher_inspections,reference_no',
            'inspection_date' => 'required|date',
            'location_id' => 'required|exists:fire_extinguisher_location,id_location',
            'inspector_id' => 'nullable|exists:users,id',
            'checkin_lat' => 'nullable|numeric',
            'checkin_lng' => 'nullable|numeric',
            'point_id' => 'required|exists:point,id',
            'item.pressure_condition' => 'nullable|boolean',
            'item.seal_condition' => 'nullable|boolean',
            'item.nozzle_condition' => 'nullable|boolean',
            'item.remark' => 'nullable|string',
            'item.expiry_date' => 'nullable|date',
            'item.photo_before' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'item.photo_after' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'item.item_lat' => 'nullable|numeric',
            'item.item_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (empty($data['reference_no']) || $data['reference_no'] === 'Generating...') {
            $data['reference_no'] = $this->generateReferenceNo();
        }
        $point = Point::findOrFail($data['point_id']);
        unset($data['point_id']);

        $item = $request->input('item', []);
        $item['name'] = $point->name_point;
        $item['type'] = $point->ket1;
        $item['location_detail'] = $point->ket2;

        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $data['checked_in_at'] = now();

        $item = array_merge($item, $this->storeItemPhotos($request));

        $inspection = DB::transaction(function () use ($data, $item) {
            $inspection = FireExtinguisherInspection::create($data);
            $inspection->items()->create($item);

            return $inspection->load('items');
        });

        return response()->json(['data' => $inspection], 201);
    }

    public function show($id)
    {
        $item = FireExtinguisherInspection::with(['inspector', 'signer', 'location', 'items'])->findOrFail($id);
        return response()->json(['data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $inspection = FireExtinguisherInspection::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reference_no' => 'required|string|max:40|unique:fire_extinguisher_inspections,reference_no,' . $id,
            'inspection_date' => 'required|date',
            'location_id' => 'required|exists:fire_extinguisher_location,id_location',
            'inspector_id' => 'nullable|exists:users,id',
            'checkin_lat' => 'nullable|numeric',
            'checkin_lng' => 'nullable|numeric',
            'point_id' => 'nullable|exists:point,id',
            'item.pressure_condition' => 'nullable|boolean',
            'item.seal_condition' => 'nullable|boolean',
            'item.nozzle_condition' => 'nullable|boolean',
            'item.remark' => 'nullable|string',
            'item.expiry_date' => 'nullable|date',
            'item.photo_before' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'item.photo_after' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif|max:10240',
            'item.item_lat' => 'nullable|numeric',
            'item.item_lng' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $storedPhotos = $this->storeItemPhotos($request);
        if (array_key_exists('checkin_lat', $data) && $data['checkin_lat'] === null) {
            unset($data['checkin_lat']);
        }
        if (array_key_exists('checkin_lng', $data) && $data['checkin_lng'] === null) {
            unset($data['checkin_lng']);
        }
        $data['checked_in_at'] = $inspection->checked_in_at ?: now();

        $pointId = $data['point_id'] ?? null;
        unset($data['point_id']);

        $itemInput = array_merge($request->input('item', []), $storedPhotos);
        $existingItem = $inspection->items()->first();
        foreach (['photo_before', 'photo_after'] as $photoField) {
            if (empty($itemInput[$photoField]) && $existingItem?->{$photoField}) {
                $itemInput[$photoField] = $existingItem->{$photoField};
            }
        }

        DB::transaction(function () use ($inspection, $data, $pointId, $itemInput) {
            $inspection->update($data);

            if ($pointId) {
                $point = Point::findOrFail($pointId);
                $itemInput['name'] = $point->name_point;
                $itemInput['type'] = $point->ket1;
                $itemInput['location_detail'] = $point->ket2;

                $inspection->items()->delete();
                $inspection->items()->create($itemInput);
            }
        });

        $inspection->load('items');

        return response()->json(['data' => $inspection]);
    }

    private function storeItemPhotos(Request $request): array
    {
        $stored = [];
        $item = $request->file('item', []);
        if (!is_array($item)) {
            return $stored;
        }

        $targetPath = public_path('images');
        File::ensureDirectoryExists($targetPath);

        foreach (['photo_before', 'photo_after'] as $field) {
            if (!isset($item[$field]) || !$item[$field]->isValid()) {
                continue;
            }

            $file = $item[$field];
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = sprintf(
                'fire_extinguisher_%s_item_%s.%s',
                $field === 'photo_before' ? 'before' : 'after',
                now()->format('YmdHisv'),
                $extension
            );

            $file->move($targetPath, $filename);
            $stored[$field] = 'images/' . $filename;
        }

        return $stored;
    }

    private function generateReferenceNo(): string
    {
        $nextId = (int) FireExtinguisherInspection::max('id') + 1;

        do {
            $referenceNo = 'FE-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (FireExtinguisherInspection::where('reference_no', $referenceNo)->exists());

        return $referenceNo;
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
        $inspection->update([
            'signed_at' => now(),
            'signed_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $inspection->load('signer')]);
    }
}
