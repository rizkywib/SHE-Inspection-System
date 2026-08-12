<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class PointController extends Controller
{
    public function index(Request $request)
    {
        $data = Point::orderBy('id')->get();
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_point' => 'required|string|max:200',
            'lat' => 'nullable|string|max:200',
            'lng' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
            'ket1' => 'nullable|string',
            'ket2' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $point = Point::create($validator->validated());
        $this->generateQrCode($point);

        return response()->json(['data' => $point], 201);
    }

    public function show($id)
    {
        return response()->json(['data' => Point::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $point = Point::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name_point' => 'required|string|max:200',
            'lat' => 'nullable|string|max:200',
            'lng' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
            'ket1' => 'nullable|string',
            'ket2' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $nameChanged = $point->name_point !== $data['name_point'];

        $point->update($data);

        if ($nameChanged || empty($point->qr_code)) {
            $this->generateQrCode($point);
        }

        return response()->json(['data' => $point]);
    }

    public function destroy($id)
    {
        Point::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    private function generateQrCode(Point $point): void
    {
        $parts = array_values(array_filter([
            trim((string) $point->name_point),
            trim((string) $point->ket1),
            trim((string) $point->ket2),
        ], fn ($value) => $value !== ''));

        $point->forceFill([
            'qr_code' => 'POINT-' . $point->id . '-' . strtoupper(implode('-', $parts)),
            'qr_generated_at' => now(),
        ])->save();
    }
}
