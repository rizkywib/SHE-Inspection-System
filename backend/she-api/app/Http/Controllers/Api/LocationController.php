<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::query()->orderByDesc('created_at');

        if ($request->boolean('simple')) {
            $data = $query
                ->select([
                    'id',
                    'name',
                    'address',
                    'keterangan1',
                    'keterangan2',
                    'status',
                    'latitude',
                    'longitude',
                    'created_at',
                ])
                ->get();

            return response()->json(['data' => $data]);
        }

        $data = $query
            ->with(['company', 'branch', 'locationType'])
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'location_type_id' => 'nullable|exists:location_types,id',
            'address' => 'nullable|string',
            'keterangan1' => 'nullable|string',
            'keterangan2' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location = Location::create($validator->validated());

        return response()->json(['data' => $location], 201);
    }

    public function show($id)
    {
        $location = Location::with(['company', 'branch', 'locationType'])->findOrFail($id);
        return response()->json(['data' => $location]);
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'location_type_id' => 'nullable|exists:location_types,id',
            'address' => 'nullable|string',
            'keterangan1' => 'nullable|string',
            'keterangan2' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location->update($validator->validated());

        return response()->json(['data' => $location]);
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
