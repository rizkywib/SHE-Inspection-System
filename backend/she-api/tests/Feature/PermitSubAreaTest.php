<?php

namespace Tests\Feature;

use App\Models\PermitMainArea;
use App\Models\PermitSubArea;
use App\Models\SafeWorkPermitInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermitSubAreaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_page_renders_script_after_required_dom_elements(): void
    {
        $response = $this->get('/dashboard/permit-sub-areas')->assertOk();
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

    public function test_unauthenticated_user_cannot_access_sub_area_api(): void
    {
        $this->getJson('/api/permit-sub-areas')->assertUnauthorized();
        $this->postJson('/api/permit-sub-areas', ['main_area_id' => 1, 'name' => 'T-SUB-1'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_sub_areas(): void
    {
        $mainArea = PermitMainArea::create(['name' => 'T-MAIN-1']);
        PermitSubArea::create(['main_area_id' => $mainArea->id, 'name' => 'T-SUB-1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/permit-sub-areas')
            ->assertOk()
            ->assertJsonFragment(['name' => 'T-SUB-1']);
    }

    public function test_authenticated_user_can_create_update_and_delete_sub_area(): void
    {
        $user = $this->makeUser();
        $mainArea = PermitMainArea::create(['name' => 'T-MAIN-1']);
        $mainArea2 = PermitMainArea::create(['name' => 'T-MAIN-2']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/permit-sub-areas', ['main_area_id' => $mainArea->id, 'name' => '  T-SUB-1  '])
            ->assertCreated()
            ->assertJsonPath('message', 'Sub Area saved successfully.')
            ->assertJsonPath('data.name', 'T-SUB-1');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('permit_sub_areas', [
            'id' => $id,
            'main_area_id' => $mainArea->id,
            'name' => 'T-SUB-1',
        ]);

        $this->putJson("/api/permit-sub-areas/{$id}", ['main_area_id' => $mainArea2->id, 'name' => 'T-SUB-2'])
            ->assertOk()
            ->assertJsonPath('message', 'Sub Area updated successfully.')
            ->assertJsonPath('data.name', 'T-SUB-2');

        $this->deleteJson("/api/permit-sub-areas/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Sub Area deleted successfully.');

        $this->assertDatabaseMissing('permit_sub_areas', ['id' => $id]);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $mainArea = PermitMainArea::create(['name' => 'T-MAIN-1']);
        PermitSubArea::create(['main_area_id' => $mainArea->id, 'name' => 'T-SUB-1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/permit-sub-areas', ['main_area_id' => $mainArea->id, 'name' => 'T-SUB-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_sub_area_requires_existing_main_area(): void
    {
        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/permit-sub-areas', ['main_area_id' => 999999, 'name' => 'T-SUB-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('main_area_id');
    }

    public function test_sub_area_used_by_permit_cannot_be_deleted(): void
    {
        $mainArea = PermitMainArea::create(['name' => 'T-MAIN-1']);
        $subArea = PermitSubArea::create(['main_area_id' => $mainArea->id, 'name' => 'T-SUB-1']);

        SafeWorkPermitInspection::create([
            'permit_date' => '2026-08-01',
            'inspector_id' => $this->makeUser()->id,
            'permit_number' => 'SWP-SUB-TEST-001',
            'permit_type_id' => 1,
            'supervision_area_id' => 1,
            'main_area_id' => $mainArea->id,
            'sub_area_id' => $subArea->id,
            'section_equipment' => 'X',
            'job_performance' => 'Y',
            'authorized_craftman' => 'A',
            'authorized_facility' => 'B',
            'contractor_name' => 'C',
            'work_description' => 'D',
        ]);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson("/api/permit-sub-areas/{$subArea->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('permit_sub_areas', ['id' => $subArea->id]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Permit Sub Area Test User',
            'username' => 'permit_sub_area_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
