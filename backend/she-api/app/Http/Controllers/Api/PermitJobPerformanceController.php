<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PermitJobPerformance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermitJobPerformanceController extends Controller
{
    public function index(Request $request)
    {
        $query = PermitJobPerformance::query();

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
            'name' => 'required|string|max:255|unique:permit_job_performances,name',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $jobPerformance = PermitJobPerformance::create($data);

        return response()->json([
            'message' => 'Job Performance saved successfully.',
            'data' => $jobPerformance,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => PermitJobPerformance::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $jobPerformance = PermitJobPerformance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permit_job_performances,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['name'] = trim($data['name']);
        $data['is_active'] = $data['is_active'] ?? true;

        $jobPerformance->update($data);

        return response()->json([
            'message' => 'Job Performance updated successfully.',
            'data' => $jobPerformance,
        ]);
    }

    public function destroy($id)
    {
        $jobPerformance = PermitJobPerformance::findOrFail($id);

        if ($jobPerformance->permits()->exists()) {
            return response()->json([
                'message' => 'Job Performance is used by a permit and cannot be deleted.',
            ], 409);
        }

        $jobPerformance->delete();

        return response()->json(['message' => 'Job Performance deleted successfully.']);
    }
}