<?php

namespace Tests\Feature;

use App\Models\FireAlarmInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FireAlarmItemTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edit_item_page_renders_in_edit_mode(): void
    {
        $response = $this->get('/dashboard/fire-alarms/1/items/2/edit')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="alarmItemForm"', $html);
        $this->assertStringContainsString('const mode = "edit";', $html);
        $this->assertStringContainsString('const itemId = 2;', $html);
        $this->assertStringContainsString('Edit Fire Alarm Item', $html);

        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_create_item_page_renders_in_create_mode(): void
    {
        $response = $this->get('/dashboard/fire-alarms/1/items/create')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('const mode = "create";', $html);
        $this->assertStringContainsString('Create Fire Alarm Item', $html);
    }

    public function test_unauthenticated_user_cannot_update_fire_alarm_item(): void
    {
        $this->putJson('/api/fire-alarms/1/items/1', ['name' => 'X'])->assertUnauthorized();
    }

    public function test_owner_can_update_fire_alarm_item(): void
    {
        $owner = $this->makeUser('inspector');
        $inspection = $this->makeInspection($owner);
        $item = $inspection->items()->create([
            'name' => 'Old Name',
            'alarm_number' => 'A-1',
            'condition_good' => 0,
            'correction_needed' => 0,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/fire-alarms/{$inspection->id}/items/{$item->id}", [
                'name' => 'New Name',
                'alarm_number' => 'A-2',
                'type' => 'Heat Detector',
                'location_detail' => 'Zone 1',
                'condition_good' => 1,
                'correction_needed' => 0,
                'remark' => 'Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('fire_alarm_items', [
            'id' => $item->id,
            'name' => 'New Name',
            'alarm_number' => 'A-2',
            'type' => 'Heat Detector',
            'location_detail' => 'Zone 1',
            'remark' => 'Updated',
        ]);
    }

    public function test_non_owner_non_admin_cannot_update_fire_alarm_item(): void
    {
        $owner = $this->makeUser('inspector');
        $inspection = $this->makeInspection($owner);
        $item = $inspection->items()->create([
            'name' => 'Old Name',
            'condition_good' => 0,
            'correction_needed' => 0,
        ]);

        $this->actingAs($this->makeUser('inspector'), 'sanctum')
            ->putJson("/api/fire-alarms/{$inspection->id}/items/{$item->id}", ['name' => 'New Name'])
            ->assertForbidden();

        $this->assertDatabaseHas('fire_alarm_items', ['id' => $item->id, 'name' => 'Old Name']);
    }

    public function test_owner_can_delete_fire_alarm_item(): void
    {
        $owner = $this->makeUser('inspector');
        $inspection = $this->makeInspection($owner);
        $item = $inspection->items()->create([
            'name' => 'To Delete',
            'condition_good' => 1,
            'correction_needed' => 0,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/fire-alarms/{$inspection->id}/items/{$item->id}")
            ->assertOk();

        $this->assertDatabaseMissing('fire_alarm_items', ['id' => $item->id]);
    }

    public function test_non_owner_non_admin_cannot_delete_fire_alarm_item(): void
    {
        $owner = $this->makeUser('inspector');
        $inspection = $this->makeInspection($owner);
        $item = $inspection->items()->create([
            'name' => 'Keep Me',
            'condition_good' => 1,
            'correction_needed' => 0,
        ]);

        $this->actingAs($this->makeUser('inspector'), 'sanctum')
            ->deleteJson("/api/fire-alarms/{$inspection->id}/items/{$item->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('fire_alarm_items', ['id' => $item->id]);
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Fire Alarm User',
            'username' => 'fire_alarm_' . $role . '_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeInspection(User $inspector): FireAlarmInspection
    {
        return FireAlarmInspection::create([
            'reference_no' => 'FA-TEST-' . uniqid(),
            'inspection_date' => now()->toDateString(),
            'inspector_id' => $inspector->id,
            'status' => 'draft',
        ]);
    }
}
