<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IncidentTypeController extends Controller
{
    public function index(Request $request)
    {
        $data = IncidentType::orderBy('id')->get();
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:incident_types,name',
            'level' => 'nullable|string|max:1|in:A,B,C',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = IncidentType::create($validator->validated());

        return response()->json(['data' => $type], 201);
    }

    public function show($id)
    {
        $type = IncidentType::findOrFail($id);
        return response()->json(['data' => $type]);
    }

    public function update(Request $request, $id)
    {
        $type = IncidentType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:incident_types,name,' . $id,
            'level' => 'nullable|string|max:1|in:A,B,C',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type->update($validator->validated());

        return response()->json(['data' => $type]);
    }

    public function destroy($id)
    {
        $type = IncidentType::findOrFail($id);
        $type->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}