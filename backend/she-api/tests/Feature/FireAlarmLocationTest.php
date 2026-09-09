<?php

namespace Tests\Feature;

use App\Models\FireAlarmLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FireAlarmLocationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_page_renders_script_after_required_dom_elements(): void
    {
        $response = $this->get('/dashboard/fire-alarm-locations')->assertOk();
        $html = $response->getContent();
        $tablePosition = strpos($html, 'id="locationTable"');
        $scriptPosition = strpos($html, "const API_URL = '/api';");

        $this->assertNotFalse($tablePosition);
        $this->assertNotFalse($scriptPosition);
        $this->assertGreaterThan($tablePosition, $scriptPosition);
    }

    public function test_unauthenticated_user_cannot_access_fire_alarm_location_api(): void
    {
        $this->getJson('/api/fire-alarm-locations')->assertUnauthorized();
        $this->postJson('/api/fire-alarm-locations', ['name' => 'Area A'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_fire_alarm_locations(): void
    {
        FireAlarmLocation::create(['name' => 'Loading Dock']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/fire-alarm-locations')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Loading Dock']);
    }

    public function test_authenticated_user_can_create_update_and_delete_fire_alarm_location(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/fire-alarm-locations', ['name' => 'Production Area'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Production Area');

        $id = $response->json('data.id_location');
        $this->assertDatabaseHas('fire_alarm_location', [
            'id_location' => $id,
            'name' => 'Production Area',
        ]);

        $this->putJson("/api/fire-alarm-locations/{$id}", ['name' => 'Warehouse'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Warehouse');

        $this->deleteJson("/api/fire-alarm-locations/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Deleted successfully');

        $this->assertDatabaseMissing('fire_alarm_location', ['id_location' => $id]);
    }

    public function test_name_is_required_when_saving_fire_alarm_location(): void
    {
        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/fire-alarm-locations', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Fire Alarm Location Test User',
            'username' => 'fire_alarm_location_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
