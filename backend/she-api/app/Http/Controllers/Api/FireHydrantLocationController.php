<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireHydrantLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FireHydrantLocationController extends Controller
{
    public function index(Request $request)
    {
        $data = FireHydrantLocation::orderBy('id_location')->get();
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

        $location = FireHydrantLocation::create($validator->validated());

        return response()->json(['data' => $location], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => FireHydrantLocation::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $location = FireHydrantLocation::findOrFail($id);

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
        FireHydrantLocation::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
