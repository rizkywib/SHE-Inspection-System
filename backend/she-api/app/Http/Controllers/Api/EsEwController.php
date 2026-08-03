<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEsEwInspectionRequest;
use App\Http\Requests\StoreEsEwItemRequest;
use App\Http\Requests\UpdateEsEwInspectionRequest;
use App\Models\EsEwInspection;
use App\Models\EsEwArea;
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
            ->with(['inspector', 'signer', 'area', 'items'])
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
            'areas' => EsEwArea::orderBy('id')->get(['id', 'name']),
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
        $inspection = EsEwInspection::with(['inspector', 'signer', 'location', 'area', 'items'])->findOrFail($id);

        return response()->json(['data' => $inspection]);
    }

    public function update(UpdateEsEwInspectionRequest $request, int $id): JsonResponse
    {
        $inspection = EsEwInspection::with('items')->findOrFail($id);
        $data = $request->validated();
        if (blank($data['reference_no'] ?? null)) {
            unset($data['reference_no']);
        }
        $point = Point::findOrFail($data['point_id']);
        unset($data['point_id']);
        $items = $data['items'];
        unset($data['items']);
        $items[0]['name'] = $point->name_point;
        $items[0]['type'] = $point->ket1;
        $items[0]['location_detail'] = $point->ket2;
        $data['inspector_id'] = $data['inspector_id'] ?? $request->user()->id;
        $storedPaths = [];
        $existingItem = $inspection->items->first();
        $oldPaths = collect([$existingItem?->photo_before, $existingItem?->photo_after])
            ->filter()
            ->values()
            ->all();

        try {
            $items = $this->prepareItems($request, $items, $storedPaths, $inspection);
            DB::transaction(function () use ($inspection, $existingItem, $data, $items) {
                $inspection->update($data);
                if ($existingItem) {
                    $existingItem->update($items[0]);
                } else {
                    $inspection->items()->create($items[0]);
                }
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

    public function storeItem(StoreEsEwItemRequest $request, int $inspectionId): JsonResponse
    {
        $inspection = EsEwInspection::findOrFail($inspectionId);
        $storedPaths = [];

        try {
            $itemData = $this->prepareItemData($request, $storedPaths);
            $item = DB::transaction(fn () => $inspection->items()->create($itemData));
        } catch (Throwable $exception) {
            $this->deletePhotos($storedPaths);
            report($exception);

            return response()->json(['message' => 'Item ES&EW gagal disimpan. Silakan coba lagi.'], 500);
        }

        return response()->json([
            'message' => 'Item ES&EW berhasil disimpan.',
            'data' => $item,
        ], 201);
    }

    public function updateItem(
        StoreEsEwItemRequest $request,
        int $inspectionId,
        int $itemId
    ): JsonResponse {
        $inspection = EsEwInspection::findOrFail($inspectionId);
        $item = $inspection->items()->findOrFail($itemId);
        $oldPaths = collect([$item->photo_before, $item->photo_after])->filter()->all();
        $storedPaths = [];

        try {
            $itemData = $this->prepareItemData($request, $storedPaths, $item);
            DB::transaction(fn () => $item->update($itemData));
        } catch (Throwable $exception) {
            $this->deletePhotos($storedPaths);
            report($exception);

            return response()->json(['message' => 'Item ES&EW gagal diperbarui. Silakan coba lagi.'], 500);
        }

        $retainedPaths = collect([$itemData['photo_before'] ?? null, $itemData['photo_after'] ?? null])
            ->filter()
            ->all();
        $this->deletePhotos(array_values(array_diff($oldPaths, $retainedPaths)));

        return response()->json([
            'message' => 'Item ES&EW berhasil diperbarui.',
            'data' => $item->fresh(),
        ]);
    }

    public function destroyItem(int $inspectionId, int $itemId): JsonResponse
    {
        $inspection = EsEwInspection::findOrFail($inspectionId);
        $item = $inspection->items()->findOrFail($itemId);
        $paths = collect([$item->photo_before, $item->photo_after])->filter()->all();

        DB::transaction(fn () => $item->delete());
        $this->deletePhotos($paths);

        return response()->json(['message' => 'Item ES&EW berhasil dihapus.']);
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
        $user = $request->user();

        if (empty($user->signature_path)) {
            return response()->json([
                'message' => 'User login belum memiliki tanda tangan yang terdaftar.',
            ], 422);
        }

        if ($inspection->signed_by) {
            return response()->json([
                'message' => 'Inspeksi ini sudah ditandatangani.',
            ], 409);
        }

        $inspection->update([
            'signed_at' => now(),
            'signed_by' => $user->id,
            'status' => 'signed',
        ]);

        return response()->json([
            'message' => 'Inspeksi ES&EW berhasil ditandatangani.',
            'data' => $inspection->load('signer'),
        ]);
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

    private function prepareItemData(
        StoreEsEwItemRequest $request,
        array &$storedPaths,
        ?\App\Models\EsEwItem $existingItem = null
    ): array {
        $data = $request->validated();
        $point = Point::findOrFail($data['point_id']);
        unset($data['point_id'], $data['photo_before'], $data['photo_after']);

        $data['name'] = $point->name_point;
        $data['type'] = $point->ket1;
        $data['location_detail'] = $point->ket2;

        foreach (['photo_before', 'photo_after'] as $field) {
            $file = $request->file($field);
            if ($file) {
                $path = $file->store('es-ew-inspections', 'public');
                $data[$field] = 'storage/' . $path;
                $storedPaths[] = $data[$field];
            } elseif ($existingItem?->{$field}) {
                $data[$field] = $existingItem->{$field};
            }
        }

        return $data;
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
