<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireExtinguisherLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FireExtinguisherLocationController extends Controller
{
    public function index(Request $request)
    {
        $data = FireExtinguisherLocation::orderBy('id_location')->get();
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

        $location = FireExtinguisherLocation::create($validator->validated());

        return response()->json(['data' => $location], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => FireExtinguisherLocation::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $location = FireExtinguisherLocation::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $location->update($validator->validated());

        return response()->json(['data' => $location]);
    }

    public function destroy($id)
    {
        FireExtinguisherLocation::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
