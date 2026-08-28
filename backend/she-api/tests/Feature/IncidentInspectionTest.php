<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentInspectionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_unauthenticated_user_cannot_access_incident_api(): void
    {
        $this->getJson('/api/incidents')->assertUnauthorized();
        $this->post('/api/incidents', [], ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_incident_can_be_stored_with_finding_and_repair_photos(): void
    {
        $type = IncidentType::create(['name' => 'Incident Type Test']);
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/incidents', $this->payload($type->id), ['Accept' => 'application/json'])
            ->assertCreated();

        $incidentId = $response->json('data.id');
        $this->assertCount(2, Incident::find($incidentId)->images);

        $finding = Incident::find($incidentId)->images()->where('kind', 'finding')->first();
        $repair = Incident::find($incidentId)->images()->where('kind', 'repair')->first();

        $this->assertNotNull($finding);
        $this->assertNotNull($repair);
        Storage::disk('public')->assertExists(Str::after($finding->image_path, 'storage/'));
        Storage::disk('public')->assertExists(Str::after($repair->image_path, 'storage/'));
    }

    public function test_finding_photo_is_mandatory_but_repair_photo_is_optional(): void
    {
        $type = IncidentType::create(['name' => 'Incident Type Test']);
        $user = $this->makeUser();

        $payload = $this->payload($type->id);
        unset($payload['image']);

        $this->actingAs($user, 'sanctum')
            ->post('/api/incidents', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $payload = $this->payload($type->id);
        unset($payload['repair_photo']);

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/incidents', $payload, ['Accept' => 'application/json'])
            ->assertCreated();

        $incidentId = $response->json('data.id');
        $this->assertCount(1, Incident::find($incidentId)->images);
        $this->assertDatabaseHas('incident_images', [
            'incident_id' => $incidentId,
            'kind' => 'finding',
        ]);
    }

    public function test_repair_photo_can_be_updated_and_old_file_is_deleted(): void
    {
        $type = IncidentType::create(['name' => 'Incident Type Test']);
        $user = $this->makeUser();

        $incidentId = $this->actingAs($user, 'sanctum')
            ->post('/api/incidents', $this->payload($type->id), ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.id');

        $repair = Incident::find($incidentId)->images()->where('kind', 'repair')->first();
        $oldPath = Str::after($repair->image_path, 'storage/');

        $this->actingAs($user, 'sanctum')
            ->post("/api/incidents/{$incidentId}", [
                '_method' => 'PUT',
                'incident_date' => '2026-08-01',
                'incident_time' => '08:00',
                'location_text' => 'Lokasi update',
                'incident_type_id' => $type->id,
                'description' => 'Keterangan update',
                'status' => 'open',
                'repair_photo' => $this->fakeImage('repair-update.png'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertCount(2, Incident::find($incidentId)->images);
        Storage::disk('public')->assertMissing($oldPath);
        $newRepair = Incident::find($incidentId)->images()->where('kind', 'repair')->first();
        Storage::disk('public')->assertExists(Str::after($newRepair->image_path, 'storage/'));
    }

    private function payload(int $typeId): array
    {
        return [
            'incident_date' => '2026-08-01',
            'incident_time' => '08:00',
            'location_text' => 'Lokasi test',
            'incident_type_id' => $typeId,
            'description' => 'Keterangan test',
            'status' => 'open',
            'image' => $this->fakeImage('finding.png'),
            'repair_photo' => $this->fakeImage('repair.png'),
        ];
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Incident Test User',
            'username' => 'incident_test_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'inspector',
            'is_active' => true,
        ]);
    }

    private function fakeImage(string $name): UploadedFile
    {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}