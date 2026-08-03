<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireExtinguisherInspection;
use App\Models\FireHydrantInspection;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Point;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_inspections' => Incident::count(),
                'open_inspections' => Incident::whereNotIn('status', ['close', 'closed'])->count(),
                'fire_hydrants' => FireHydrantInspection::count(),
                'fire_extinguishers' => FireExtinguisherInspection::count(),
                'inspection_points' => Point::where('status', true)->count(),
                'active_inspectors' => User::where('is_active', true)
                    ->where('role', 'inspector')
                    ->count(),
            ],
        ]);
    }

    public function recentInspections(): JsonResponse
    {
        $inspections = Incident::query()
            ->with(['incidentType:id,name'])
            ->latest('incident_date')
            ->latest('incident_time')
            ->latest('id')
            ->limit(6)
            ->get([
                'id',
                'reference_no',
                'incident_type_id',
                'incident_date',
                'incident_time',
                'location_text',
                'status',
            ]);

        return response()->json(['data' => $inspections]);
    }

    public function incidentSummary(): JsonResponse
    {
        $types = IncidentType::query()
            ->withCount('incidents')
            ->orderByDesc('incidents_count')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn (IncidentType $type) => $type->incidents_count > 0)
            ->values()
            ->map(fn (IncidentType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'total' => $type->incidents_count,
            ]);

        return response()->json([
            'data' => [
                'total' => Incident::count(),
                'types' => $types,
            ],
        ]);
    }
}
