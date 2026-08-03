<?php

namespace Tests\Feature;

use App\Models\EsEwArea;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EsEwAreaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_page_renders_script_after_required_dom_elements(): void
    {
        $response = $this->get('/dashboard/es-ew-areas')->assertOk();
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

    public function test_unauthenticated_user_cannot_access_es_ew_area_api(): void
    {
        $this->getJson('/api/es-ew-areas')->assertUnauthorized();
        $this->postJson('/api/es-ew-areas', ['name' => 'Production'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_es_ew_areas(): void
    {
        EsEwArea::create(['name' => 'Loading Dock']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/es-ew-areas')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Loading Dock']);
    }

    public function test_authenticated_user_can_create_update_and_delete_es_ew_area(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/es-ew-areas', ['name' => '  Production Area  '])
            ->assertCreated()
            ->assertJsonPath('message', 'ES&EW area saved successfully.')
            ->assertJsonPath('data.name', 'Production Area');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('es_ew_areas', [
            'id' => $id,
            'name' => 'Production Area',
        ]);

        $this->putJson("/api/es-ew-areas/{$id}", ['name' => 'Warehouse'])
            ->assertOk()
            ->assertJsonPath('message', 'ES&EW area updated successfully.')
            ->assertJsonPath('data.name', 'Warehouse');

        $this->deleteJson("/api/es-ew-areas/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'ES&EW area deleted successfully.');

        $this->assertDatabaseMissing('es_ew_areas', ['id' => $id]);
    }

    public function test_name_is_required_when_saving_es_ew_area(): void
    {
        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/es-ew-areas', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'ES EW Area Test User',
            'username' => 'es_ew_area_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
