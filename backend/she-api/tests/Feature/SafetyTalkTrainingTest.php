<?php

namespace Tests\Feature;

use App\Models\SafetyTalkTraining;
use App\Models\User;
use Database\Seeders\SafetyTalkSpeakerSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SafetyTalkTrainingTest extends TestCase
{
    use DatabaseTransactions;

    private User $selectableSpeaker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(SafetyTalkSpeakerSeeder::class);
        $this->selectableSpeaker = $this->makeUser('inspector', [
            'name' => 'Active Safety Talk Speaker ' . uniqid(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_safety_talk_api(): void
    {
        $this->getJson('/api/safety-talk-trainings')->assertUnauthorized();
        $this->post('/api/safety-talk-trainings', [], ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_permitted_user_can_open_index(): void
    {
        $user = $this->permittedUser(['view']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonFragment(['safety-talk-training.view']);

        $this->getJson('/api/safety-talk-trainings')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_master_data_speakers_are_loaded_from_active_users(): void
    {
        $inactiveUser = $this->makeUser('inspector', [
            'name' => 'Inactive Safety Talk Speaker ' . uniqid(),
            'is_active' => false,
        ]);
        $legacyName = 'Legacy Safety Talk Speaker ' . uniqid();
        DB::table('safety_talk_speakers')->insert([
            'name' => $legacyName,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->permittedUser(['view']), 'sanctum')
            ->getJson('/api/safety-talk-trainings/master-data')
            ->assertOk();

        $response->assertJsonFragment([
            'id' => $this->selectableSpeaker->id,
            'name' => $this->selectableSpeaker->name,
        ]);
        $response->assertJsonMissing(['id' => $inactiveUser->id, 'name' => $inactiveUser->name]);
        $response->assertJsonMissing(['name' => $legacyName]);
    }

    public function test_user_without_permission_is_rejected(): void
    {
        $user = $this->makeUser('user_dept_head');
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/safety-talk-trainings')
            ->assertForbidden();
        $this->post('/api/safety-talk-trainings', $this->validPayload(), ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_valid_data_is_stored_with_photo(): void
    {
        $response = $this->actingAs($this->permittedUser(['create']), 'sanctum')
            ->post('/api/safety-talk-trainings', $this->validPayload(), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.speaker.id', $this->selectableSpeaker->id)
            ->assertJsonPath('data.total_participants', 15);

        $path = $response->json('data.activity_photo_path');
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('safety_talk_trainings', ['topic' => 'Materi keselamatan kerja']);
    }

    public function test_required_fields_are_rejected(): void
    {
        $this->actingAs($this->permittedUser(['create']), 'sanctum')
            ->post('/api/safety-talk-trainings', [], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'speaker_id', 'implementation_date', 'topic',
                'ecogreen_participants', 'outsourcing_participants',
                'contractor_participants', 'duration_minutes',
                'implementation_area', 'activity_photo',
            ]);
    }

    public function test_negative_participant_counts_are_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['ecogreen_participants'] = -1;

        $this->actingAs($this->permittedUser(['create']), 'sanctum')
            ->post('/api/safety-talk-trainings', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ecogreen_participants');
    }

    public function test_area_outside_one_to_six_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['implementation_area'] = 7;

        $this->actingAs($this->permittedUser(['create']), 'sanctum')
            ->post('/api/safety-talk-trainings', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('implementation_area');
    }

    public function test_non_image_file_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['activity_photo'] = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->permittedUser(['create']), 'sanctum')
            ->post('/api/safety-talk-trainings', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activity_photo');
    }

    public function test_image_larger_than_five_mb_is_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['activity_photo'] = $this->fakeImage('large.png', 5121);

        $this->actingAs($this->permittedUser(['create']), 'sanctum')
            ->post('/api/safety-talk-trainings', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activity_photo');
    }

    public function test_data_can_be_updated_without_replacing_photo(): void
    {
        $training = $this->makeTraining();
        Storage::disk('public')->put($training->activity_photo_path, 'old-photo');
        $payload = $this->validPayloadWithoutPhoto();
        $payload['topic'] = 'Materi yang diperbarui';

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->putJson("/api/safety-talk-trainings/{$training->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.topic', 'Materi yang diperbarui')
            ->assertJsonPath('data.activity_photo_path', $training->activity_photo_path);

        Storage::disk('public')->assertExists($training->activity_photo_path);
    }

    public function test_old_photo_is_deleted_after_replacement(): void
    {
        $training = $this->makeTraining();
        Storage::disk('public')->put($training->activity_photo_path, 'old-photo');
        $payload = $this->validPayloadWithoutPhoto();
        $payload['_method'] = 'PUT';
        $payload['activity_photo'] = $this->fakeImage('replacement.png');

        $response = $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->post("/api/safety-talk-trainings/{$training->id}", $payload, ['Accept' => 'application/json'])
            ->assertOk();

        Storage::disk('public')->assertMissing($training->activity_photo_path);
        Storage::disk('public')->assertExists($response->json('data.activity_photo_path'));
    }

    public function test_data_and_photo_are_deleted(): void
    {
        $training = $this->makeTraining();
        Storage::disk('public')->put($training->activity_photo_path, 'photo');

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson("/api/safety-talk-trainings/{$training->id}")
            ->assertOk();

        $this->assertDatabaseMissing('safety_talk_trainings', ['id' => $training->id]);
        Storage::disk('public')->assertMissing($training->activity_photo_path);
    }

    public function test_non_admin_with_update_permission_cannot_update_or_delete(): void
    {
        $training = $this->makeTraining();
        Storage::disk('public')->put($training->activity_photo_path, 'old-photo');

        $this->actingAs($this->permittedUser(['update']), 'sanctum')
            ->putJson("/api/safety-talk-trainings/{$training->id}", $this->validPayloadWithoutPhoto())
            ->assertForbidden();

        $this->actingAs($this->permittedUser(['delete']), 'sanctum')
            ->deleteJson("/api/safety-talk-trainings/{$training->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('safety_talk_trainings', ['id' => $training->id]);
    }

    public function test_total_participants_is_calculated_correctly(): void
    {
        $training = $this->makeTraining([
            'ecogreen_participants' => 7,
            'outsourcing_participants' => 8,
            'contractor_participants' => 9,
        ]);

        $this->assertSame(24, $training->total_participants);
        $this->assertArrayNotHasKey('total_participants', $training->getAttributes());
    }

    public function test_search_and_filters_return_matching_data(): void
    {
        $matching = $this->makeTraining([
            'topic' => 'Lock out tag out',
            'implementation_date' => '2026-07-20',
            'implementation_area' => 2,
        ]);
        $this->makeTraining([
            'topic' => 'Materi lainnya',
            'implementation_date' => '2026-06-01',
            'implementation_area' => 5,
        ]);

        $this->actingAs($this->permittedUser(['view']), 'sanctum')
            ->getJson("/api/safety-talk-trainings?search=Lock&date_from=2026-07-01&date_to=2026-07-31&implementation_area=2&speaker_id={$matching->speaker_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.topic', 'Lock out tag out');
    }

    public function test_speaker_seeder_does_not_create_duplicates(): void
    {
        $this->seed(SafetyTalkSpeakerSeeder::class);
        $firstCount = DB::table('safety_talk_speakers')->count();
        $this->seed(SafetyTalkSpeakerSeeder::class);

        $this->assertSame(19, $firstCount);
        $this->assertSame($firstCount, DB::table('safety_talk_speakers')->count());
    }

    private function validPayload(): array
    {
        return $this->validPayloadWithoutPhoto() + [
            'activity_photo' => $this->fakeImage(),
        ];
    }

    private function validPayloadWithoutPhoto(): array
    {
        return [
            'speaker_id' => $this->selectableSpeaker->id,
            'implementation_date' => '2026-07-23',
            'topic' => 'Materi keselamatan kerja',
            'ecogreen_participants' => 5,
            'outsourcing_participants' => 4,
            'contractor_participants' => 6,
            'duration_minutes' => 30,
            'implementation_area' => 1,
        ];
    }

    private function makeTraining(array $overrides = []): SafetyTalkTraining
    {
        $user = $this->makeUser('super_admin');

        return SafetyTalkTraining::create(array_merge($this->validPayloadWithoutPhoto(), [
            'activity_photo_path' => 'safety-talk-trainings/original.jpg',
            'created_by' => $user->id,
        ], $overrides));
    }

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Safety Talk Test User',
            'username' => 'safety_talk_' . uniqid(),
            'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    private function permittedUser(array $actions): User
    {
        $user = $this->makeUser('inspector');
        $groupId = DB::table('user_groups')->insertGetId([
            'name' => 'Safety Talk Test Group ' . uniqid(),
            'is_active' => true,
            'created_at' => now(),
        ]);
        DB::table('user_group_members')->insert(['user_id' => $user->id, 'group_id' => $groupId]);
        DB::table('permissions')->insert([
            'group_id' => $groupId,
            'module' => 'safety-talk-training',
            'can_view' => in_array('view', $actions, true),
            'can_create' => in_array('create', $actions, true),
            'can_edit' => in_array('update', $actions, true),
            'can_delete' => in_array('delete', $actions, true),
            'can_approve' => false,
        ]);

        return $user;
    }

    private function fakeImage(string $name = 'activity.png', ?int $kilobytes = null): UploadedFile
    {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        if ($kilobytes !== null) {
            $content = str_pad($content, $kilobytes * 1024, "\0");
        }

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
