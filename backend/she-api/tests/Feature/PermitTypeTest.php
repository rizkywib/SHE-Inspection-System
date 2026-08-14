<?php

namespace Tests\Feature;

use App\Models\PermitType;
use App\Models\SafeWorkPermitInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermitTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_page_renders_script_after_required_dom_elements(): void
    {
        $response = $this->get('/dashboard/permit-types')->assertOk();
        $html = $response->getContent();
        $tablePosition = strpos($html, 'id="typeTable"');
        $scriptPosition = strpos($html, "const API_URL = '/api';");

        $this->assertNotFalse($tablePosition);
        $this->assertNotFalse($scriptPosition);
        $this->assertGreaterThan($tablePosition, $scriptPosition);
        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_unauthenticated_user_cannot_access_permit_type_api(): void
    {
        $this->getJson('/api/permit-types')->assertUnauthorized();
        $this->postJson('/api/permit-types', ['name' => 'T-PERMIT-1'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_permit_types(): void
    {
        PermitType::create(['name' => 'T-PERMIT-1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/permit-types')
            ->assertOk()
            ->assertJsonFragment(['name' => 'T-PERMIT-1']);
    }

    public function test_authenticated_user_can_create_update_and_delete_permit_type(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/permit-types', ['name' => '  T-PERMIT-1  '])
            ->assertCreated()
            ->assertJsonPath('message', 'Type Permit saved successfully.')
            ->assertJsonPath('data.name', 'T-PERMIT-1');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('permit_types', [
            'id' => $id,
            'name' => 'T-PERMIT-1',
        ]);

        $this->putJson("/api/permit-types/{$id}", ['name' => 'T-PERMIT-2'])
            ->assertOk()
            ->assertJsonPath('message', 'Type Permit updated successfully.')
            ->assertJsonPath('data.name', 'T-PERMIT-2');

        $this->deleteJson("/api/permit-types/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Type Permit deleted successfully.');

        $this->assertDatabaseMissing('permit_types', ['id' => $id]);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        PermitType::create(['name' => 'T-PERMIT-1']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/permit-types', ['name' => 'T-PERMIT-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_type_used_by_permit_cannot_be_deleted(): void
    {
        $type = PermitType::create(['name' => 'T-PERMIT-1']);

        SafeWorkPermitInspection::create([
            'permit_date' => '2026-08-01',
            'inspector_id' => $this->makeUser()->id,
            'permit_number' => 'SWP-TYPE-TEST-001',
            'permit_type_id' => $type->id,
            'supervision_area_id' => 1,
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
            ->deleteJson("/api/permit-types/{$type->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('permit_types', ['id' => $type->id]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Permit Type Test User',
            'username' => 'permit_type_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
