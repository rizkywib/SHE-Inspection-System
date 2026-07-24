<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $this->putJson('/api/auth/profile', [])->assertUnauthorized();
    }

    public function test_profile_page_is_available(): void
    {
        $this->get('/dashboard/profile')
            ->assertOk()
            ->assertSee('Edit Profil');
    }

    public function test_authenticated_user_can_update_own_profile_only(): void
    {
        $user = $this->makeUser('profile_owner');
        $otherUser = $this->makeUser('profile_other');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', [
                'name' => 'Nama Profil Baru',
                'username' => 'profile_owner_new',
                'phone' => ' 081234567890 ',
                'position' => 'Safety Inspector',
                'role' => 'super_admin',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nama Profil Baru')
            ->assertJsonPath('user.phone', '081234567890')
            ->assertJsonPath('user.role', 'inspector');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'profile_owner_new',
            'position' => 'Safety Inspector',
            'role' => 'inspector',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => $otherUser->name,
        ]);
    }

    public function test_duplicate_username_is_rejected(): void
    {
        $user = $this->makeUser('profile_unique_owner');
        $otherUser = $this->makeUser('profile_unique_other');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', [
                'name' => $user->name,
                'username' => $otherUser->username,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = $this->makeUser('profile_wrong_password');
        $oldHash = $user->password_hash;

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'current_password' => 'password-salah',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertSame($oldHash, $user->fresh()->password_hash);
    }

    public function test_password_can_be_changed_with_current_password_and_confirmation(): void
    {
        $user = $this->makeUser('profile_password');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/profile', [
                'name' => $user->name,
                'username' => $user->username,
                'current_password' => 'password123',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('password-baru', $user->fresh()->password_hash));
    }

    public function test_signature_can_be_replaced_and_old_file_is_deleted(): void
    {
        $user = $this->makeUser('profile_signature');
        $oldFilename = 'profile_test_old_' . uniqid() . '.png';
        $oldPath = 'images/' . $oldFilename;
        File::ensureDirectoryExists(public_path('images'));
        File::put(public_path($oldPath), 'old-signature');
        $user->update(['signature_path' => $oldPath]);
        $newPath = null;

        try {
            $response = $this->actingAs($user, 'sanctum')
                ->post('/api/auth/profile', [
                    '_method' => 'PUT',
                    'name' => $user->name,
                    'username' => $user->username,
                    'signature' => $this->fakeImage(),
                ], ['Accept' => 'application/json'])
                ->assertOk();

            $newPath = $response->json('user.signature_path');
            $this->assertNotSame($oldPath, $newPath);
            $this->assertFileDoesNotExist(public_path($oldPath));
            $this->assertFileExists(public_path($newPath));
        } finally {
            File::delete(public_path($oldPath));
            if ($newPath) {
                File::delete(public_path($newPath));
            }
        }
    }

    private function makeUser(string $suffix): User
    {
        return User::create([
            'name' => 'Profile Test User ' . $suffix,
            'username' => $suffix . '_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'inspector',
            'is_active' => true,
        ]);
    }

    private function fakeImage(): UploadedFile
    {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent('signature.png', $content);
    }
}
