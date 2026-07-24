<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEsEwInspectionRequest;
use App\Http\Requests\UpdateEsEwInspectionRequest;
use App\Models\Area;
use App\Models\EsEwInspection;
use App\Models\Point;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EsEwController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EsEwInspection::query()
            ->with(['inspector', 'area', 'items'])
            ->withCount('items')
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(function (Builder $query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('inspector', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('inspection_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('inspection_date', '<=', $request->date_to))
            ->orderByDesc('inspection_date')
            ->orderByDesc('id');

        return response()->json($query->paginate(15)->withQueryString());
    }

    public function masterData(): JsonResponse
    {
        return response()->json([
            'areas' => Area::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'inspectors' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'points' => Point::where('status', 1)->orderBy('name_point')->get(['id', 'name_point', 'ket1', 'ket2']),
        ]);
    }

    public function nextReference(): JsonResponse
    {
        return response()->json(['reference_no' => $this->generateReferenceNo()]);
    }

    public function store(StoreEsEwInspectionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $point = Point::findOrFail($data['point_id']);
        unset($data['point_id']);
        $items = $data['items'];
        unset($data['items']);
        $items[0]['name'] = $point->name_point;
        $items[0]['type'] = $point->ket1;
        $items[0]['location_detail'] = $point->ket2;
        $data['reference_no'] = $data['reference_no'] ?: $this->generateReferenceNo();
        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $storedPaths = [];

        try {
            $items = $this->prepareItems($request, $items, $storedPaths);
            $inspection = DB::transaction(function () use ($data, $items) {
                $inspection = EsEwInspection::create($data);
                $inspection->items()->createMany($items);

                return $inspection;
            });
        } catch (Throwable $exception) {
            $this->deletePhotos($storedPaths);
            report($exception);

            return response()->json(['message' => 'Inspeksi ES&EW gagal disimpan. Silakan coba lagi.'], 500);
        }

        return response()->json([
            'message' => 'Inspeksi ES&EW berhasil disimpan.',
            'data' => $inspection->load(['inspector', 'location', 'area', 'items']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $inspection = EsEwInspection::with(['inspector', 'location', 'area', 'items'])->findOrFail($id);

        return response()->json(['data' => $inspection]);
    }

    public function update(UpdateEsEwInspectionRequest $request, int $id): JsonResponse
    {
        $inspection = EsEwInspection::with('items')->findOrFail($id);
        $data = $request->validated();
        $point = Point::findOrFail($data['point_id']);
        unset($data['point_id']);
        $items = $data['items'];
        unset($data['items']);
        $items[0]['name'] = $point->name_point;
        $items[0]['type'] = $point->ket1;
        $items[0]['location_detail'] = $point->ket2;
        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $storedPaths = [];
        $oldPaths = $inspection->items
            ->flatMap(fn ($item) => [$item->photo_before, $item->photo_after])
            ->filter()
            ->values()
            ->all();

        try {
            $items = $this->prepareItems($request, $items, $storedPaths, $inspection);
            DB::transaction(function () use ($inspection, $data, $items) {
                $inspection->update($data);
                $inspection->items()->delete();
                $inspection->items()->createMany($items);
            });
        } catch (Throwable $exception) {
            $this->deletePhotos($storedPaths);
            report($exception);

            return response()->json(['message' => 'Inspeksi ES&EW gagal diperbarui. Silakan coba lagi.'], 500);
        }

        $retainedPaths = collect($items)
            ->flatMap(fn ($item) => [$item['photo_before'] ?? null, $item['photo_after'] ?? null])
            ->filter()
            ->all();
        $this->deletePhotos(array_values(array_diff($oldPaths, $retainedPaths)));

        return response()->json([
            'message' => 'Inspeksi ES&EW berhasil diperbarui.',
            'data' => $inspection->fresh()->load(['inspector', 'location', 'area', 'items']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $inspection = EsEwInspection::with('items')->findOrFail($id);
        $paths = $inspection->items
            ->flatMap(fn ($item) => [$item->photo_before, $item->photo_after])
            ->filter()
            ->values()
            ->all();

        DB::transaction(fn () => $inspection->delete());
        $this->deletePhotos($paths);

        return response()->json(['message' => 'Inspeksi ES&EW berhasil dihapus.']);
    }

    public function checkin(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'checkin_lat' => ['required', 'numeric', 'between:-90,90'],
            'checkin_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);
        $inspection = EsEwInspection::findOrFail($id);
        $inspection->update($data + ['checked_in_at' => now()]);

        return response()->json(['data' => $inspection]);
    }

    public function sign(Request $request, int $id): JsonResponse
    {
        $inspection = EsEwInspection::findOrFail($id);
        $inspection->update(['signed_at' => now(), 'status' => 'signed']);

        return response()->json(['data' => $inspection]);
    }

    private function generateReferenceNo(): string
    {
        $next = (int) EsEwInspection::max('id') + 1;

        do {
            $reference = 'ESEW-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (EsEwInspection::where('reference_no', $reference)->exists());

        return $reference;
    }

    private function prepareItems(
        Request $request,
        array $items,
        array &$storedPaths,
        ?EsEwInspection $inspection = null
    ): array {
        $existingItems = $inspection?->items?->values() ?? collect();

        foreach ($items as $index => &$item) {
            unset($item['photo_before'], $item['photo_after']);

            foreach (['photo_before', 'photo_after'] as $field) {
                $file = $request->file("items.{$index}.{$field}");
                if ($file) {
                    $path = $file->store('es-ew-inspections', 'public');
                    $item[$field] = 'storage/' . $path;
                    $storedPaths[] = $item[$field];
                } elseif ($existingItems->get($index)?->{$field}) {
                    $item[$field] = $existingItems->get($index)->{$field};
                }
            }
        }

        return $items;
    }

    private function deletePhotos(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if ($path) {
                Storage::disk('public')->delete(Str::after($path, 'storage/'));
            }
        }
    }
}
