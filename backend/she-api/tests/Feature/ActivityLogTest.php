<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_activity_log_page_renders(): void
    {
        $response = $this->get('/dashboard/activity-logs')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="logTable"', $html);
        $this->assertStringContainsString("const API_URL = '/api';", $html);

        $cacheControl = $response->headers->get('Cache-Control', '');
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_unauthenticated_user_cannot_access_activity_log_api(): void
    {
        $this->getJson('/api/activity-logs')->assertUnauthorized();
    }

    public function test_non_admin_cannot_access_activity_log_api(): void
    {
        $this->actingAs($this->makeUser('inspector'), 'sanctum')
            ->getJson('/api/activity-logs')
            ->assertForbidden();
    }

    public function test_admin_can_list_activity_logs(): void
    {
        $admin = $this->makeUser('admin');

        ActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'user_role' => $admin->role,
            'method' => 'POST',
            'path' => 'api/permit-types',
            'module' => 'permit-types',
            'action' => 'create',
            'status_code' => 201,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/activity-logs')
            ->assertOk()
            ->assertJsonPath('data.0.module', 'permit-types')
            ->assertJsonPath('data.0.action', 'create')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page'], 'filters']);
    }

    public function test_mutating_request_is_recorded_as_activity_log(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/permit-types', ['name' => 'T-LOG-' . uniqid()])
            ->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'module' => 'permit-types',
            'action' => 'create',
            'method' => 'POST',
        ]);
    }

    public function test_login_is_recorded_with_user_attribution(): void
    {
        $user = $this->makeUser('admin');

        $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'password123',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'module' => 'auth',
            'action' => 'login',
        ]);
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Activity Log User',
            'username' => 'activity_log_' . $role . '_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
