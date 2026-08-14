<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupervisionArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupervisionAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = SupervisionArea::query();

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('code')->orderBy('id')->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:supervision_areas,code',
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['code'] = trim($data['code']);
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $area = SupervisionArea::create($data);

        return response()->json([
            'message' => 'Area Pengawasan saved successfully.',
            'data' => $area,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => SupervisionArea::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $area = SupervisionArea::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:supervision_areas,code,' . $id,
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['code'] = trim($data['code']);
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $area->update($data);

        return response()->json([
            'message' => 'Area Pengawasan updated successfully.',
            'data' => $area,
        ]);
    }

    public function destroy($id)
    {
        $area = SupervisionArea::findOrFail($id);

        if ($area->permitInspections()->exists()) {
            return response()->json([
                'message' => 'Area Pengawasan is used by a permit and cannot be deleted.',
            ], 409);
        }

        $area->delete();

        return response()->json(['message' => 'Area Pengawasan deleted successfully.']);
    }
}
