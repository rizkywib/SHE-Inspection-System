<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Dashboard Test User',
            'username' => 'dashboard_test_' . uniqid(),
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'inspector',
            'is_active' => true,
        ]);
    }

    public function test_dashboard_api_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
        $this->getJson('/api/dashboard/recent-inspections')->assertUnauthorized();
        $this->getJson('/api/dashboard/incident-summary')->assertUnauthorized();
    }

    public function test_dashboard_stats_return_real_database_counts(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/stats')
            ->assertOk();

        $response
            ->assertJsonPath('data.total_inspections', DB::table('incidents')->count())
            ->assertJsonPath(
                'data.open_inspections',
                DB::table('incidents')->whereNotIn('status', ['close', 'closed'])->count()
            )
            ->assertJsonPath('data.fire_hydrants', DB::table('fire_hydrant_inspections')->count())
            ->assertJsonPath('data.fire_extinguishers', DB::table('fire_extinguisher_inspections')->count())
            ->assertJsonPath('data.inspection_points', DB::table('point')->where('status', true)->count())
            ->assertJsonPath(
                'data.active_inspectors',
                DB::table('users')->where('is_active', true)->where('role', 'inspector')->count()
            );
    }

    public function test_incident_summary_classifies_inspections_by_incident_type(): void
    {
        $type = IncidentType::create([
            'name' => 'Dashboard Classification ' . uniqid(),
            'level' => '1',
            'is_active' => true,
        ]);
        $this->makeInspection($type, 'DASHBOARD-CHART-1');
        $this->makeInspection($type, 'DASHBOARD-CHART-2');

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/incident-summary')
            ->assertOk();

        $summary = collect($response->json('data.types'))->firstWhere('id', $type->id);
        $this->assertNotNull($summary);
        $this->assertSame($type->name, $summary['name']);
        $this->assertSame(2, $summary['total']);
        $this->assertSame(DB::table('incidents')->count(), $response->json('data.total'));
    }

    public function test_recent_inspections_are_returned_in_latest_order(): void
    {
        $type = IncidentType::create([
            'name' => 'Dashboard Recent ' . uniqid(),
            'level' => '1',
            'is_active' => true,
        ]);
        $latest = $this->makeInspection($type, 'DASHBOARD-LATEST', [
            'incident_date' => '2099-12-31',
            'incident_time' => '23:59:00',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/recent-inspections')
            ->assertOk()
            ->assertJsonPath('data.0.id', $latest->id)
            ->assertJsonPath('data.0.incident_type.name', $type->name);
    }

    public function test_dashboard_page_contains_chart_and_has_no_quick_actions(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertSeeText('Inspection Berdasarkan Incident Type')
            ->assertSee('id="totalInspectionCount"', false)
            ->assertSee('id="incidentTypeChart"', false)
            ->assertSee("dashboardRequest('stats')", false)
            ->assertSee("dashboardRequest('incident-summary')", false)
            ->assertDontSeeText('Quick Actions');
    }

    private function makeInspection(
        IncidentType $type,
        string $reference,
        array $overrides = []
    ): Incident {
        return Incident::create(array_merge([
            'reference_no' => $reference . '-' . uniqid(),
            'reporter_id' => $this->user->id,
            'incident_type_id' => $type->id,
            'location_text' => 'Dashboard Test Area',
            'incident_date' => '2026-07-29',
            'incident_time' => '08:00:00',
            'description' => 'Dashboard test inspection',
            'status' => 'reported',
            'is_medical' => false,
        ], $overrides));
    }
}
