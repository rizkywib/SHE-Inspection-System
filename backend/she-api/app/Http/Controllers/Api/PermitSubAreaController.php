<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermitMainArea;
use App\Models\PermitSubArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermitSubAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = PermitSubArea::query()->with('mainArea');

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
            'main_area_id' => 'required|exists:permit_main_areas,id',
            'name' => 'required|string|max:255|unique:permit_sub_areas,name',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $area = PermitSubArea::create($data);

        return response()->json([
            'message' => 'Sub Area saved successfully.',
            'data' => $area->load('mainArea'),
        ], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => PermitSubArea::with('mainArea')->findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $area = PermitSubArea::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'main_area_id' => 'required|exists:permit_main_areas,id',
            'name' => 'required|string|max:255|unique:permit_sub_areas,name,' . $id,
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
            'message' => 'Sub Area updated successfully.',
            'data' => $area->load('mainArea'),
        ]);
    }

    public function destroy($id)
    {
        $area = PermitSubArea::findOrFail($id);

        if ($area->permitInspections()->exists()) {
            return response()->json([
                'message' => 'Sub Area is used by a permit and cannot be deleted.',
            ], 409);
        }

        $area->delete();

        return response()->json(['message' => 'Sub Area deleted successfully.']);
    }
}
