<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AuthorizesOwnerOrAdmin;
use App\Http\Requests\StoreSafeWorkPermitInspectionRequest;
use App\Http\Requests\UpdateSafeWorkPermitInspectionRequest;
use App\Models\PermitMainArea;
use App\Models\PermitJobPerformance;
use App\Models\PermitSubArea;
use App\Models\PermitType;
use App\Models\SafeWorkPermitInspection;
use App\Models\SupervisionArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SafeWorkPermitInspectionController extends Controller
{
    use AuthorizesOwnerOrAdmin;

    public function index(Request $request): JsonResponse
    {
        $query = SafeWorkPermitInspection::query()
            ->with(['inspector', 'legacyInspector', 'permitType', 'supervisionArea', 'mainArea', 'subArea'])
            ->search($request->string('search')->toString())
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('permit_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('permit_date', '<=', $request->date_to))
            ->when($request->filled('inspector_id'), fn (Builder $query) => $query->where('inspector_id', $request->inspector_id))
            ->when($request->filled('permit_type_id'), fn (Builder $query) => $query->where('permit_type_id', $request->permit_type_id))
            ->when($request->filled('supervision_area_id'), fn (Builder $query) => $query->where('supervision_area_id', $request->supervision_area_id))
            ->when($request->filled('main_area_id'), fn (Builder $query) => $query->where('main_area_id', $request->main_area_id))
            ->when($request->filled('sub_area_id'), fn (Builder $query) => $query->where('sub_area_id', $request->sub_area_id))
            ->when($request->finding_status === 'with', fn (Builder $query) => $query->whereNotNull('permit_findings')->where('permit_findings', '<>', ''))
            ->when($request->finding_status === 'without', fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('permit_findings')->orWhere('permit_findings', '')))
            ->orderByDesc('permit_date')
            ->orderByDesc('id');

        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function masterData(): JsonResponse
    {
        return response()->json([
            'inspectors' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'permit_types' => PermitType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'supervision_areas' => SupervisionArea::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'main_areas' => PermitMainArea::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sub_areas' => PermitSubArea::where('is_active', true)->orderBy('name')->get(['id', 'main_area_id', 'name']),
            'job_performances' => PermitJobPerformance::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreSafeWorkPermitInspectionRequest $request): JsonResponse
    {
        $inspection = SafeWorkPermitInspection::create($request->validated());

        return response()->json([
            'message' => 'Data Permit Matrix berhasil disimpan.',
            'data' => $inspection->load(['inspector', 'legacyInspector', 'permitType', 'supervisionArea', 'mainArea', 'subArea']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $inspection = SafeWorkPermitInspection::with([
            'inspector',
            'legacyInspector',
            'permitType',
            'supervisionArea',
            'mainArea',
            'subArea',
        ])->findOrFail($id);

        return response()->json(['data' => $inspection]);
    }

    public function update(UpdateSafeWorkPermitInspectionRequest $request, int $id): JsonResponse
    {
        $inspection = SafeWorkPermitInspection::findOrFail($id);

        if ($forbidden = $this->authorizeAdminOnly($request)) {
            return $forbidden;
        }

        $inspection->update($request->validated());

        return response()->json([
            'message' => 'Data Permit Matrix berhasil diperbarui.',
            'data' => $inspection->load(['inspector', 'legacyInspector', 'permitType', 'supervisionArea', 'mainArea', 'subArea']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $inspection = SafeWorkPermitInspection::findOrFail($id);

        if ($forbidden = $this->authorizeAdminOnly($request)) {
            return $forbidden;
        }

        $inspection->delete();

        return response()->json(['message' => 'Data Permit Matrix berhasil dihapus.']);
    }
}
