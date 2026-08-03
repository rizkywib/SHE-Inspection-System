<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSafetyTalkTrainingRequest;
use App\Http\Requests\UpdateSafetyTalkTrainingRequest;
use App\Models\SafetyTalkTraining;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SafetyTalkTrainingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SafetyTalkTraining::query()
            ->with(['speaker', 'legacySpeaker', 'creator'])
            ->search($request->string('search')->toString())
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('implementation_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('implementation_date', '<=', $request->date_to))
            ->when($request->filled('implementation_area'), fn (Builder $query) => $query->where('implementation_area', $request->implementation_area))
            ->when($request->filled('speaker_id'), fn (Builder $query) => $query->where('speaker_id', $request->speaker_id))
            ->orderByDesc('implementation_date')
            ->orderByDesc('id');

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function masterData(): JsonResponse
    {
        return response()->json([
            'speakers' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'areas' => SafetyTalkTraining::IMPLEMENTATION_AREAS,
        ]);
    }

    public function store(StoreSafetyTalkTrainingRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['activity_photo']);
        $photoPath = null;

        try {
            $photoPath = $request->file('activity_photo')->store('safety-talk-trainings', 'public');
            $data['activity_photo_path'] = $photoPath;
            $data['created_by'] = $request->user()->id;

            $training = DB::transaction(fn () => SafetyTalkTraining::create($data));
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            report($exception);

            return response()->json(['message' => 'Data Safety Talk gagal disimpan. Silakan coba lagi.'], 500);
        }

        return response()->json([
            'message' => 'Data Safety Talk berhasil disimpan.',
            'data' => $training->load(['speaker', 'legacySpeaker', 'creator']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $training = SafetyTalkTraining::with(['speaker', 'legacySpeaker', 'creator', 'updater'])->findOrFail($id);

        return response()->json(['data' => $training]);
    }

    public function update(UpdateSafetyTalkTrainingRequest $request, int $id): JsonResponse
    {
        $training = SafetyTalkTraining::findOrFail($id);
        $data = $request->validated();
        unset($data['activity_photo']);
        $newPhotoPath = null;
        $oldPhotoPath = $training->activity_photo_path;

        try {
            if ($request->hasFile('activity_photo')) {
                $newPhotoPath = $request->file('activity_photo')->store('safety-talk-trainings', 'public');
                $data['activity_photo_path'] = $newPhotoPath;
            }
            $data['updated_by'] = $request->user()->id;

            DB::transaction(fn () => $training->update($data));
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }
            report($exception);

            return response()->json(['message' => 'Data Safety Talk gagal diperbarui. Silakan coba lagi.'], 500);
        }

        if ($newPhotoPath && $oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        return response()->json([
            'message' => 'Data Safety Talk berhasil diperbarui.',
            'data' => $training->fresh()->load(['speaker', 'legacySpeaker', 'creator', 'updater']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $training = SafetyTalkTraining::findOrFail($id);
        $photoPath = $training->activity_photo_path;

        try {
            DB::transaction(fn () => $training->delete());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Data Safety Talk gagal dihapus. Silakan coba lagi.'], 500);
        }

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return response()->json(['message' => 'Data Safety Talk berhasil dihapus.']);
    }
}
