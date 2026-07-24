<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserPhoneTest extends TestCase
{
    use DatabaseTransactions;

    public function test_phone_is_stored_when_user_is_created(): void
    {
        $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/users', [
                'name' => 'User Phone Create',
                'username' => 'phone_create_' . uniqid(),
                'password' => 'password123',
                'phone' => ' 081234567890 ',
                'role' => 'inspector',
            ])
            ->assertCreated()
            ->assertJsonPath('data.phone', '081234567890');

        $this->assertDatabaseHas('users', [
            'name' => 'User Phone Create',
            'phone' => '081234567890',
        ]);
    }

    public function test_phone_can_be_updated_and_has_a_length_limit(): void
    {
        $admin = $this->makeAdmin();
        $user = User::create([
            'name' => 'User Phone Update',
            'username' => 'phone_update_' . uniqid(),
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'phone' => '081111111111',
            'role' => 'inspector',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'name' => $user->name,
                'username' => $user->username,
                'phone' => '+62 812-3456-7890',
                'role' => $user->role,
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '+62 812-3456-7890');

        $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'username' => $user->username,
            'phone' => str_repeat('1', 31),
            'role' => $user->role,
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'User Phone Test Admin',
            'username' => 'phone_admin_' . uniqid(),
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }
}
