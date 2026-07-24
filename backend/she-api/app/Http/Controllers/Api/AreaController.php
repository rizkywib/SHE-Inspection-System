<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $query = Area::query()->orderBy('name');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area = Area::create($validator->validated());

        return response()->json(['data' => $area], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => Area::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $area->update($validator->validated());

        return response()->json(['data' => $area]);
    }

    public function destroy($id)
    {
        Area::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'location_id' => 'nullable|exists:locations,id',
            'name' => 'required|string|max:200',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'group_code' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
