<?php

namespace Tests\Feature;

use App\Models\SafeWorkPermitInspection;
use App\Models\SupervisionArea;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisionAreaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_page_renders_script_after_required_dom_elements(): void
    {
        $response = $this->get('/dashboard/supervision-areas')->assertOk();
        $html = $response->getContent();
        $tablePosition = strpos($html, 'id="areaTable"');
        $scriptPosition = strpos($html, "const API_URL = '/api';");

        $this->assertNotFalse($tablePosition);
        $this->assertNotFalse($scriptPosition);
        $this->assertGreaterThan($tablePosition, $scriptPosition);
        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_unauthenticated_user_cannot_access_supervision_area_api(): void
    {
        $this->getJson('/api/supervision-areas')->assertUnauthorized();
        $this->postJson('/api/supervision-areas', ['code' => '1', 'name' => 'Area 1'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_supervision_areas(): void
    {
        SupervisionArea::create(['code' => 'T-LIST-1', 'name' => 'Area 1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/supervision-areas')
            ->assertOk()
            ->assertJsonFragment(['code' => 'T-LIST-1']);
    }

    public function test_authenticated_user_can_create_update_and_delete_supervision_area(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/supervision-areas', ['code' => ' T-CRUD-1 ', 'name' => '  Area Satu  '])
            ->assertCreated()
            ->assertJsonPath('message', 'Area Pengawasan saved successfully.')
            ->assertJsonPath('data.code', 'T-CRUD-1')
            ->assertJsonPath('data.name', 'Area Satu');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('supervision_areas', [
            'id' => $id,
            'code' => 'T-CRUD-1',
            'name' => 'Area Satu',
        ]);

        $this->putJson("/api/supervision-areas/{$id}", ['code' => 'T-CRUD-2', 'name' => 'Area Dua'])
            ->assertOk()
            ->assertJsonPath('message', 'Area Pengawasan updated successfully.')
            ->assertJsonPath('data.code', 'T-CRUD-2');

        $this->deleteJson("/api/supervision-areas/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Area Pengawasan deleted successfully.');

        $this->assertDatabaseMissing('supervision_areas', ['id' => $id]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        SupervisionArea::create(['code' => 'T-DUP-1', 'name' => 'Area 1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/supervision-areas', ['code' => 'T-DUP-1', 'name' => 'Area lain'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_area_used_by_permit_cannot_be_deleted(): void
    {
        $area = SupervisionArea::create(['code' => 'T-USE-1', 'name' => 'Area 1']);

        SafeWorkPermitInspection::create([
            'permit_date' => '2026-08-01',
            'inspector_id' => $this->makeUser()->id,
            'permit_number' => 'SWP-TEST-001',
            'permit_type_id' => 1,
            'supervision_area_id' => $area->id,
            'main_area_id' => 1,
            'sub_area_id' => 1,
            'section_equipment' => 'X',
            'job_performance' => 'Y',
            'authorized_craftman' => 'A',
            'authorized_facility' => 'B',
            'contractor_name' => 'C',
            'work_description' => 'D',
        ]);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson("/api/supervision-areas/{$area->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('supervision_areas', ['id' => $area->id]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Supervision Area Test User',
            'username' => 'supervision_area_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
