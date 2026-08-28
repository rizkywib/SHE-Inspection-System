<?php

namespace Tests\Feature;

use App\Models\PermitJobPerformance;
use App\Models\SafeWorkPermitInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermitJobPerformanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_page_renders_script_after_required_dom_elements(): void
    {
        $response = $this->get('/dashboard/permit-job-performances')->assertOk();
        $html = $response->getContent();
        $tablePosition = strpos($html, 'id="jpTable"');
        $scriptPosition = strpos($html, "const API_URL = '/api';");

        $this->assertNotFalse($tablePosition);
        $this->assertNotFalse($scriptPosition);
        $this->assertGreaterThan($tablePosition, $scriptPosition);
        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_unauthenticated_user_cannot_access_job_performance_api(): void
    {
        $this->getJson('/api/permit-job-performances')->assertUnauthorized();
        $this->postJson('/api/permit-job-performances', ['name' => 'T-JP-1'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_job_performances(): void
    {
        PermitJobPerformance::create(['name' => 'T-JP-1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/permit-job-performances')
            ->assertOk()
            ->assertJsonFragment(['name' => 'T-JP-1']);
    }

    public function test_authenticated_user_can_create_update_and_delete_job_performance(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/permit-job-performances', ['name' => '  T-JP-1  '])
            ->assertCreated()
            ->assertJsonPath('message', 'Job Performance saved successfully.')
            ->assertJsonPath('data.name', 'T-JP-1');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('permit_job_performances', [
            'id' => $id,
            'name' => 'T-JP-1',
        ]);

        $this->putJson("/api/permit-job-performances/{$id}", ['name' => 'T-JP-2'])
            ->assertOk()
            ->assertJsonPath('message', 'Job Performance updated successfully.')
            ->assertJsonPath('data.name', 'T-JP-2');

        $this->deleteJson("/api/permit-job-performances/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Job Performance deleted successfully.');

        $this->assertDatabaseMissing('permit_job_performances', ['id' => $id]);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        PermitJobPerformance::create(['name' => 'T-JP-1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/permit-job-performances', ['name' => 'T-JP-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_job_performance_used_by_permit_cannot_be_deleted(): void
    {
        $jobPerformance = PermitJobPerformance::create(['name' => 'T-JP-USED']);

        SafeWorkPermitInspection::create([
            'permit_date' => '2026-08-01',
            'inspector_id' => $this->makeUser()->id,
            'permit_number' => 'SWP-JP-TEST-001',
            'permit_type_id' => 1,
            'supervision_area_id' => 1,
            'main_area_id' => 1,
            'sub_area_id' => 1,
            'section_equipment' => 'X',
            'job_performance' => $jobPerformance->name,
            'authorized_craftman' => 'A',
            'authorized_facility' => 'B',
            'contractor_name' => 'C',
            'work_description' => 'D',
        ]);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson("/api/permit-job-performances/{$jobPerformance->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('permit_job_performances', ['id' => $jobPerformance->id]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Permit Job Performance Test User',
            'username' => 'permit_jp_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}