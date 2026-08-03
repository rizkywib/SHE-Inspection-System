<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EsEwArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EsEwAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = EsEwArea::query();

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where('name', 'like', "%{$search}%");
        }

        $data = $query
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $area = EsEwArea::create($data);

        return response()->json([
            'message' => 'ES&EW area saved successfully.',
            'data' => $area,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => EsEwArea::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $area = EsEwArea::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $area->update($data);

        return response()->json([
            'message' => 'ES&EW area updated successfully.',
            'data' => $area,
        ]);
    }

    public function destroy($id)
    {
        $area = EsEwArea::findOrFail($id);

        if ($area->inspections()->exists()) {
            return response()->json([
                'message' => 'ES&EW area is used by an inspection and cannot be deleted.',
            ], 409);
        }

        $area->delete();

        return response()->json(['message' => 'ES&EW area deleted successfully.']);
    }
}
