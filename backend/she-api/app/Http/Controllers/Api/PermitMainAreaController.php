<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermitMainArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermitMainAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = PermitMainArea::query();

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where('name', 'like', "%{$search}%");
        }

        $data = $query->orderBy('name')->orderBy('id')->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permit_main_areas,name',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $area = PermitMainArea::create($data);

        return response()->json([
            'message' => 'Main Area saved successfully.',
            'data' => $area,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => PermitMainArea::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $area = PermitMainArea::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permit_main_areas,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $area->update($data);

        return response()->json([
            'message' => 'Main Area updated successfully.',
            'data' => $area,
        ]);
    }

    public function destroy($id)
    {
        $area = PermitMainArea::findOrFail($id);

        if ($area->permitInspections()->exists()) {
            return response()->json([
                'message' => 'Main Area is used by a permit and cannot be deleted.',
            ], 409);
        }

        $area->delete();

        return response()->json(['message' => 'Main Area deleted successfully.']);
    }
}
