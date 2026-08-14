<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermitTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = PermitType::query();

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
            'name' => 'required|string|max:255|unique:permit_types,name',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $type = PermitType::create($data);

        return response()->json([
            'message' => 'Type Permit saved successfully.',
            'data' => $type,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => PermitType::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $type = PermitType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permit_types,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $type->update($data);

        return response()->json([
            'message' => 'Type Permit updated successfully.',
            'data' => $type,
        ]);
    }

    public function destroy($id)
    {
        $type = PermitType::findOrFail($id);

        if ($type->permitInspections()->exists()) {
            return response()->json([
                'message' => 'Type Permit is used by a permit and cannot be deleted.',
            ], 409);
        }

        $type->delete();

        return response()->json(['message' => 'Type Permit deleted successfully.']);
    }
}
