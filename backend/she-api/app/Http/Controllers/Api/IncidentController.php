<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\IncidentImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IncidentController extends Controller
{
    public function index()
    {
        $incidents = Incident::with(['reporter', 'incidentType', 'location', 'images'])
            ->latest('incident_date')
            ->latest('incident_time')
            ->get();

        return response()->json(['data' => $incidents]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'incident_date' => 'required|date_format:Y-m-d',
            'incident_time' => 'required|date_format:H:i',
            'location_text' => 'required|string|max:255',
            'incident_type_id' => 'required|integer|exists:incident_types,id',
            'description' => 'required|string|max:5000',
            'status' => 'required|string|in:open,close,reported,closed',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data inspection belum valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $imagePath = null;

        try {
            $incident = DB::transaction(function () use ($request, $user, &$imagePath) {
                $status = in_array($request->input('status'), ['close', 'closed'], true)
                    ? 'closed'
                    : 'reported';

                $incident = Incident::create([
                    'reference_no' => $this->makeReferenceNumber(),
                    'reporter_id' => $user->id,
                    'incident_type_id' => $request->integer('incident_type_id'),
                    'location_text' => trim($request->input('location_text')),
                    'incident_date' => $request->input('incident_date'),
                    'incident_time' => $request->input('incident_time'),
                    'description' => trim($request->input('description')),
                    'status' => $status,
                    'is_medical' => false,
                ]);

                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('incident-images', 'public');
                    IncidentImage::create([
                        'incident_id' => $incident->id,
                        'image_path' => 'storage/' . $imagePath,
                        'uploaded_by' => $user->id,
                    ]);
                }

                return $incident;
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }
            report($exception);

            return response()->json([
                'message' => 'Inspection gagal disimpan. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'message' => 'Inspection berhasil disimpan.',
            'data' => $incident->load(['incidentType', 'location', 'images']),
        ], 201);
    }

    public function show($id)
    {
        $incident = Incident::with([
            'reporter',
            'incidentType',
            'location',
            'area',
            'images',
            'investigations',
        ])->findOrFail($id);

        return response()->json(['data' => $incident]);
    }

    public function update(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'incident_date' => 'sometimes|required|date_format:Y-m-d',
            'incident_time' => 'sometimes|required|date_format:H:i',
            'location_text' => 'sometimes|required|string|max:255',
            'incident_type_id' => 'sometimes|required|integer|exists:incident_types,id',
            'description' => 'sometimes|required|string|max:5000',
            'status' => 'sometimes|required|string|in:open,close,reported,closed',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        unset($data['image']);
        if (array_key_exists('location_text', $data)) {
            $data['location_text'] = trim($data['location_text']);
        }
        if (array_key_exists('description', $data)) {
            $data['description'] = trim($data['description']);
        }
        if (array_key_exists('status', $data)) {
            $data['status'] = in_array($data['status'], ['close', 'closed'], true)
                ? 'closed'
                : 'reported';
        }

        $newImagePath = null;
        $oldImagePath = null;
        try {
            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('incident-images', 'public');
            }

            DB::transaction(function () use (
                $incident,
                $data,
                $newImagePath,
                $request,
                &$oldImagePath
            ) {
                $incident->update($data);

                if ($newImagePath !== null) {
                    $image = $incident->images()->oldest('id')->first();
                    if ($image !== null) {
                        $oldImagePath = $image->image_path;
                        $image->update([
                            'image_path' => 'storage/' . $newImagePath,
                            'uploaded_by' => $request->user()->id,
                        ]);
                    } else {
                        IncidentImage::create([
                            'incident_id' => $incident->id,
                            'image_path' => 'storage/' . $newImagePath,
                            'uploaded_by' => $request->user()->id,
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }
            report($exception);

            return response()->json([
                'message' => 'Inspection gagal diperbarui. Silakan coba lagi.',
            ], 500);
        }

        if ($oldImagePath !== null) {
            Storage::disk('public')->delete(Str::after($oldImagePath, 'storage/'));
        }

        return response()->json([
            'message' => 'Inspection berhasil diperbarui.',
            'data' => $incident->fresh()->load(['incidentType', 'images']),
        ]);
    }

    public function destroy($id)
    {
        $incident = Incident::with('images')->findOrFail($id);
        foreach ($incident->images as $image) {
            Storage::disk('public')->delete(Str::after($image->image_path, 'storage/'));
        }
        $incident->delete();

        return response()->json(['message' => 'Inspection berhasil dihapus.']);
    }

    public function uploadImage(Request $request, $id)
    {
        $incident = Incident::findOrFail($id);
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('image')->store('incident-images', 'public');
        $image = IncidentImage::create([
            'incident_id' => $incident->id,
            'image_path' => 'storage/' . $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $image], 201);
    }

    private function makeReferenceNumber(): string
    {
        return 'INC-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
    }
}
