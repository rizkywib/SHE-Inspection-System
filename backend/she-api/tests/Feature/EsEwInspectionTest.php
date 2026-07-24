<?php

namespace Tests\Feature;

use App\Models\EsEwInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EsEwInspectionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_unauthenticated_user_cannot_access_es_ew_api(): void
    {
        $this->getJson('/api/es-ew')->assertUnauthorized();
        $this->postJson('/api/es-ew', [])->assertUnauthorized();
    }

    public function test_authenticated_user_can_open_index_and_master_data(): void
    {
        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/es-ew')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);

        $this->getJson('/api/es-ew/master-data')
            ->assertOk()
            ->assertJsonStructure(['areas', 'inspectors', 'points']);
    }

    public function test_valid_inspection_with_one_point_item_can_be_created(): void
    {
        $payload = $this->validPayload();
        $point = DB::table('point')->where('id', $payload['point_id'])->first();

        $response = $this->actingAs($this->makeUser(), 'sanctum')
            ->post('/api/es-ew', $payload, ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.reference_no', 'ESEW-000001')
            ->assertJsonPath('data.items.0.name', $point->name_point)
            ->assertJsonPath('data.items.0.type', $point->ket1)
            ->assertJsonPath('data.items.0.location_detail', $point->ket2)
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('es_ew_inspections', ['id' => $response->json('data.id')]);
        $this->assertDatabaseCount('es_ew_items', 1);
    }

    public function test_more_than_one_item_is_rejected(): void
    {
        $payload = $this->validPayloadWithoutFiles();
        $payload['items'][] = $this->validItem();

        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/es-ew', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_required_header_item_and_conditions_are_validated(): void
    {
        $this->actingAs($this->makeUser(), 'sanctum')
            ->postJson('/api/es-ew', ['items' => [['name' => 'Incomplete']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'inspection_date', 'area_id', 'point_id',
                'items.0.water_flow_es', 'items.0.sewer_condition',
            ]);
    }

    public function test_inspection_and_items_can_be_updated(): void
    {
        $inspection = $this->makeInspection();
        $inspection->items()->create($this->validItem(['name' => 'Old Unit']));
        $payload = $this->validPayloadWithoutFiles();
        $payload['reference_no'] = $inspection->reference_no;
        $payload['items'][0]['water_flow_es'] = 0;
        $payload['items'][0]['remark'] = 'Updated remark';

        $this->actingAs($this->makeUser(), 'sanctum')
            ->putJson("/api/es-ew/{$inspection->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.items.0.water_flow_es', false)
            ->assertJsonPath('data.items.0.remark', 'Updated remark');

        $this->assertDatabaseHas('es_ew_items', [
            'inspection_id' => $inspection->id,
            'remark' => 'Updated remark',
            'water_flow_es' => 0,
        ]);
    }

    public function test_old_photo_is_deleted_when_replaced(): void
    {
        $inspection = $this->makeInspection();
        $oldPath = 'storage/es-ew-inspections/old.png';
        $inspection->items()->create($this->validItem(['photo_before' => $oldPath]));
        Storage::disk('public')->put('es-ew-inspections/old.png', 'old-photo');
        $payload = $this->validPayloadWithoutFiles();
        $payload['_method'] = 'PUT';
        $payload['reference_no'] = $inspection->reference_no;
        $payload['items'][0]['photo_before'] = $this->fakeImage('new.png');

        $response = $this->actingAs($this->makeUser(), 'sanctum')
            ->post("/api/es-ew/{$inspection->id}", $payload, ['Accept' => 'application/json'])
            ->assertOk();

        Storage::disk('public')->assertMissing('es-ew-inspections/old.png');
        Storage::disk('public')->assertExists(
            str_replace('storage/', '', $response->json('data.items.0.photo_before'))
        );
    }

    public function test_inspection_items_and_photos_are_deleted(): void
    {
        $inspection = $this->makeInspection();
        $inspection->items()->create($this->validItem([
            'photo_before' => 'storage/es-ew-inspections/delete.png',
        ]));
        Storage::disk('public')->put('es-ew-inspections/delete.png', 'photo');

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson("/api/es-ew/{$inspection->id}")
            ->assertOk();

        $this->assertDatabaseMissing('es_ew_inspections', ['id' => $inspection->id]);
        $this->assertDatabaseMissing('es_ew_items', ['inspection_id' => $inspection->id]);
        Storage::disk('public')->assertMissing('es-ew-inspections/delete.png');
    }

    public function test_search_and_filters_return_matching_inspection(): void
    {
        $this->makeInspection([
            'reference_no' => 'ESEW-SEARCH',
            'inspection_date' => '2026-07-20',
        ]);
        $this->makeInspection([
            'reference_no' => 'ESEW-OTHER',
            'inspection_date' => '2026-06-01',
        ]);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/es-ew?search=SEARCH&date_from=2026-07-01&date_to=2026-07-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference_no', 'ESEW-SEARCH');
    }

    private function validPayload(): array
    {
        $payload = $this->validPayloadWithoutFiles();
        $payload['items'][0]['photo_before'] = $this->fakeImage('before.png');

        return $payload;
    }

    private function validPayloadWithoutFiles(): array
    {
        return [
            'reference_no' => '',
            'inspection_date' => '2026-07-24',
            'area_id' => DB::table('areas')->where('is_active', true)->value('id'),
            'inspector_id' => null,
            'point_id' => DB::table('point')->where('status', 1)->value('id'),
            'items' => [$this->validItem()],
        ];
    }

    private function validItem(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Emergency Shower Unit 1',
            'type' => 'Combination Unit',
            'location_detail' => 'North side',
            'area_detail' => 'Production',
            'water_flow_es' => 1,
            'water_flow_ew' => 1,
            'water_condition' => 1,
            'actual_valve_es' => 1,
            'actual_valve_ew' => 1,
            'physical_condition_es' => 1,
            'physical_condition_ew' => 1,
            'sign_board_condition' => 1,
            'housekeeping_condition' => 1,
            'road_access_condition' => 1,
            'sewer_condition' => 1,
            'remark' => null,
        ], $overrides);
    }

    private function makeInspection(array $overrides = []): EsEwInspection
    {
        return EsEwInspection::create(array_merge([
            'reference_no' => 'ESEW-' . strtoupper(uniqid()),
            'location_id' => DB::table('locations')->where('is_active', true)->value('id'),
            'area_id' => DB::table('areas')->where('is_active', true)->value('id'),
            'inspection_date' => '2026-07-24',
            'inspector_id' => $this->makeUser()->id,
            'notes' => null,
            'status' => 'draft',
        ], $overrides));
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'ES EW Test User',
            'username' => 'es_ew_' . uniqid(),
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
