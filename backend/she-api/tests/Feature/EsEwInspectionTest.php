<?php

namespace Tests\Feature;

use App\Models\EsEwArea;
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

    public function test_index_list_uses_area_date_and_inspected_by_columns(): void
    {
        $response = $this->get('/dashboard/es-ew-inspections')->assertOk();

        $response->assertSeeText('Area');
        $response->assertSeeText('Inspection Date');
        $response->assertSeeText('Inspected By');
        $response->assertSee('id="sidebarBrandLogo"', false);
        $response->assertSee('id="mobileBrandLogo"', false);
        $response->assertSee('/images/ecogreen-logo-print.png', false);
        $response->assertDontSee('fas fa-hard-hat', false);
        $response->assertDontSee('<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Reference</th>', false);
        $response->assertDontSee('<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Name</th>', false);
        $response->assertSee('onclick="exportInspection(${row.id})"', false);
        $response->assertSee('onclick="printInspection(${row.id})"', false);
        $response->assertSee('onclick="signInspection(${row.id})"', false);
        $response->assertDontSee('title="View"', false);
        $response->assertDontSee('fa-eye mr-1"></i>View', false);
        $response->assertSee("row.signed_by ? 'Signed' : 'Signature'", false);
        $response->assertSee('async function exportInspection(id)', false);
        $response->assertSee('async function printInspection(id)', false);
        $response->assertSee('async function signInspection(id)', false);
        $response->assertSee('application/vnd.ms-excel', false);
        $response->assertSee('window.open', false);
        $response->assertSee('/images/ecogreen-logo-print.png', false);
        $response->assertSeeText('EOB-Saf-007 Rev. 4 31/12/2018');
        $response->assertSeeText('EMERGENCY SHOWER & EYE WASH STATION MONTHLY INSPECTION');
        $response->assertSeeText('Kondisi air ES & EW Station');
        $response->assertSeeText('Akses jalan & lokasi');
        $response->assertSeeText('Saluran pembuangan');
        $response->assertSee('<th colspan="15">', false);
        $response->assertDontSeeText('Reference No.');
    }

    public function test_area_column_links_to_simple_item_checklist(): void
    {
        $index = $this->get('/dashboard/es-ew-inspections')->assertOk();
        $index->assertSee('title="Open item detail"', false);
        $index->assertSee('/dashboard/es-ew-inspections/${encodeURIComponent(row.id)}', false);

        $detail = $this->get('/dashboard/es-ew-inspections/1')->assertOk();
        $detail->assertSeeText('ES&EW List Item Detail');
        $detail->assertSeeText('Back to Inspections');
        $detail->assertSeeText('New ES&EW Item Detail');
        $detail->assertDontSeeText('Edit Inspection');
        $detail->assertDontSeeText('Export Excel');
        $detail->assertDontSeeText('Print');
        $detail->assertDontSeeText('Signature');
        $detail->assertSeeText('Water Flow ES');
        $detail->assertSeeText('Physical Condition EW');
        $detail->assertSeeText('Eye Wash');
        $detail->assertSeeText('Emergency Shower');
        $detail->assertSeeText('Last Inspection');
        $detail->assertSeeText('Actions');
        $detail->assertSee('bg-white rounded-xl shadow-lg overflow-hidden', false);
        $detail->assertSee('h-10 w-14 object-cover rounded border border-gray-200', false);
        $detail->assertSee("'fa-check text-green-600'", false);
        $detail->assertSee("'fa-times text-red-500'", false);
        $detail->assertDontSee("isYes ? 'YES' : 'NO'", false);
        $detail->assertSee('items.map((item, index)', false);
        $detail->assertSee('/items/${item.id}/edit', false);
        $detail->assertSee('/items/${itemId}', false);
        $detail->assertDontSee('function exportInspection', false);
        $detail->assertDontSee('function printInspection', false);
        $detail->assertDontSee('function signInspection', false);
        $detail->assertDontSee('application/vnd.ms-excel', false);
        $detail->assertDontSeeText('Reference No.');
    }

    public function test_item_form_hides_reference_and_uses_readonly_header_date(): void
    {
        $newItem = $this->get('/dashboard/es-ew-inspections/1/items/create')->assertOk();
        $newItem->assertSeeText('New ES&EW Item Detail');
        $newItem->assertSeeText('Inspection Header');
        $newItem->assertSee('id="inspection_date" type="date" readonly', false);
        $newItem->assertSee('inspection.inspection_date', false);
        $newItem->assertDontSeeText('Reference No.');

        $inspectionForm = $this->get('/dashboard/es-ew-inspections/1/edit')->assertOk();
        preg_match('/<input id="inspection_date"[^>]*>/', $inspectionForm->getContent(), $editDateInput);
        $this->assertNotEmpty($editDateInput);
        $this->assertStringContainsString('readonly', $editDateInput[0]);
        $inspectionForm->assertDontSeeText('Reference No.');
        $inspectionForm->assertDontSee('/api/es-ew/next-reference', false);

        $newInspection = $this->get('/dashboard/es-ew-inspections/create')->assertOk();
        preg_match('/<input id="inspection_date"[^>]*>/', $newInspection->getContent(), $createDateInput);
        $this->assertNotEmpty($createDateInput);
        $this->assertStringContainsString('required', $createDateInput[0]);
        $this->assertStringNotContainsString('readonly', $createDateInput[0]);
        $this->assertStringContainsString('bg-white', $createDateInput[0]);
    }

    public function test_authenticated_user_can_open_index_and_master_data(): void
    {
        $area = EsEwArea::firstOrCreate(['name' => 'ES EW Master Data Test Area']);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->getJson('/api/es-ew')
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);

        $this->getJson('/api/es-ew/master-data')
            ->assertOk()
            ->assertJsonStructure(['areas', 'inspectors', 'points'])
            ->assertJsonFragment([
                'id' => $area->id,
                'name' => 'ES EW Master Data Test Area',
            ]);
    }

    public function test_valid_inspection_with_one_point_item_can_be_created(): void
    {
        $payload = $this->validPayload();
        $point = DB::table('point')->where('id', $payload['point_id'])->first();

        $response = $this->actingAs($this->makeUser(), 'sanctum')
            ->post('/api/es-ew', $payload, ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.items.0.name', $point->name_point)
            ->assertJsonPath('data.items.0.type', $point->ket1)
            ->assertJsonPath('data.items.0.location_detail', $point->ket2)
            ->assertJsonCount(1, 'data.items');

        $this->assertMatchesRegularExpression(
            '/^ESEW-\d{6}$/',
            $response->json('data.reference_no')
        );
        $this->assertDatabaseHas('es_ew_inspections', ['id' => $response->json('data.id')]);
        $this->assertDatabaseHas('es_ew_items', [
            'inspection_id' => $response->json('data.id'),
        ]);
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
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id]);
        $inspection->items()->create($this->validItem(['name' => 'Old Unit']));
        $retainedItem = $inspection->items()->create($this->validItem([
            'name' => 'Retained Unit',
            'remark' => 'Must remain',
        ]));
        $payload = $this->validPayloadWithoutFiles();
        $payload['items'][0]['water_flow_es'] = 0;
        $payload['items'][0]['remark'] = 'Updated remark';

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/es-ew/{$inspection->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.items.0.water_flow_es', false)
            ->assertJsonPath('data.items.0.remark', 'Updated remark');

        $this->assertDatabaseHas('es_ew_items', [
            'inspection_id' => $inspection->id,
            'remark' => 'Updated remark',
            'water_flow_es' => 0,
        ]);
        $this->assertDatabaseHas('es_ew_items', [
            'id' => $retainedItem->id,
            'inspection_id' => $inspection->id,
            'remark' => 'Must remain',
        ]);
        $this->assertCount(2, $inspection->fresh()->items);
    }

    public function test_non_owner_cannot_update_or_delete_inspection(): void
    {
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id]);
        $other = $this->makeUser(['name' => 'ES EW Other User']);

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/es-ew/{$inspection->id}", $this->validPayloadWithoutFiles())
            ->assertForbidden();

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/es-ew/{$inspection->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('es_ew_inspections', ['id' => $inspection->id]);
    }

    public function test_admin_can_update_and_delete_others_inspection(): void
    {
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id]);
        $admin = $this->makeUser(['name' => 'ES EW Admin', 'role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/es-ew/{$inspection->id}")
            ->assertOk();

        $this->assertDatabaseMissing('es_ew_inspections', ['id' => $inspection->id]);
    }

    public function test_item_can_be_added_to_existing_header_without_changing_inspection_date(): void
    {
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id, 'inspection_date' => '2026-07-18']);
        $inspection->items()->create($this->validItem(['name' => 'Existing Unit']));
        $payload = $this->validItemPayload(['remark' => 'New item detail']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/es-ew/{$inspection->id}/items", $payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Item ES&EW berhasil disimpan.')
            ->assertJsonPath('data.remark', 'New item detail');

        $this->assertDatabaseHas('es_ew_items', [
            'id' => $response->json('data.id'),
            'inspection_id' => $inspection->id,
            'remark' => 'New item detail',
        ]);
        $this->assertCount(2, $inspection->fresh()->items);
        $this->assertSame('2026-07-18', $inspection->fresh()->inspection_date->format('Y-m-d'));
    }

    public function test_existing_item_can_be_updated_and_deleted_independently(): void
    {
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id]);
        $retainedItem = $inspection->items()->create($this->validItem(['name' => 'Retained Unit']));
        $targetItem = $inspection->items()->create($this->validItem(['name' => 'Target Unit']));
        $payload = $this->validItemPayload([
            'water_flow_es' => 0,
            'remark' => 'Updated item only',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/es-ew/{$inspection->id}/items/{$targetItem->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.water_flow_es', false)
            ->assertJsonPath('data.remark', 'Updated item only');

        $this->assertDatabaseHas('es_ew_items', [
            'id' => $retainedItem->id,
            'inspection_id' => $inspection->id,
        ]);

        $this->deleteJson("/api/es-ew/{$inspection->id}/items/{$targetItem->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Item ES&EW berhasil dihapus.');

        $this->assertDatabaseMissing('es_ew_items', ['id' => $targetItem->id]);
        $this->assertDatabaseHas('es_ew_items', ['id' => $retainedItem->id]);
        $this->assertDatabaseHas('es_ew_inspections', ['id' => $inspection->id]);
    }

    public function test_signature_requires_registered_signature_and_can_only_be_added_once(): void
    {
        $inspection = $this->makeInspection();
        $unsignedUser = $this->makeUser();

        $this->actingAs($unsignedUser, 'sanctum')
            ->postJson("/api/es-ew/{$inspection->id}/sign")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'User login belum memiliki tanda tangan yang terdaftar.');

        $signer = $this->makeUser([
            'name' => 'ES EW Signer',
            'signature_path' => 'storage/signatures/es-ew-signer.png',
        ]);

        $this->actingAs($signer, 'sanctum')
            ->postJson("/api/es-ew/{$inspection->id}/sign")
            ->assertOk()
            ->assertJsonPath('message', 'Inspeksi ES&EW berhasil ditandatangani.')
            ->assertJsonPath('data.signed_by', $signer->id)
            ->assertJsonPath('data.signer.name', 'ES EW Signer');

        $this->assertDatabaseHas('es_ew_inspections', [
            'id' => $inspection->id,
            'signed_by' => $signer->id,
            'status' => 'signed',
        ]);
        $this->assertNotNull($inspection->fresh()->signed_at);

        $this->postJson("/api/es-ew/{$inspection->id}/sign")
            ->assertConflict()
            ->assertJsonPath('message', 'Inspeksi ini sudah ditandatangani.');
    }

    public function test_old_photo_is_deleted_when_replaced(): void
    {
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id]);
        $oldPath = 'storage/es-ew-inspections/old.png';
        $inspection->items()->create($this->validItem(['photo_before' => $oldPath]));
        Storage::disk('public')->put('es-ew-inspections/old.png', 'old-photo');
        $payload = $this->validPayloadWithoutFiles();
        $payload['_method'] = 'PUT';
        $payload['reference_no'] = $inspection->reference_no;
        $payload['items'][0]['photo_before'] = $this->fakeImage('new.png');

        $response = $this->actingAs($owner, 'sanctum')
            ->post("/api/es-ew/{$inspection->id}", $payload, ['Accept' => 'application/json'])
            ->assertOk();

        Storage::disk('public')->assertMissing('es-ew-inspections/old.png');
        Storage::disk('public')->assertExists(
            str_replace('storage/', '', $response->json('data.items.0.photo_before'))
        );
    }

    public function test_inspection_items_and_photos_are_deleted(): void
    {
        $owner = $this->makeUser();
        $inspection = $this->makeInspection(['inspector_id' => $owner->id]);
        $inspection->items()->create($this->validItem([
            'photo_before' => 'storage/es-ew-inspections/delete.png',
        ]));
        Storage::disk('public')->put('es-ew-inspections/delete.png', 'photo');

        $this->actingAs($owner, 'sanctum')
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
            'area_id' => $this->esEwAreaId(),
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

    private function validItemPayload(array $overrides = []): array
    {
        return array_merge([
            'point_id' => DB::table('point')->where('status', 1)->value('id'),
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
            'area_id' => $this->esEwAreaId(),
            'inspection_date' => '2026-07-24',
            'inspector_id' => $this->makeUser()->id,
            'notes' => null,
            'status' => 'draft',
        ], $overrides));
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'ES EW Test User',
            'username' => 'es_ew_' . uniqid(),
            'password_hash' => Hash::make('password123'),
            'role' => 'inspector',
            'is_active' => true,
        ], $overrides));
    }

    private function esEwAreaId(): int
    {
        return EsEwArea::firstOrCreate([
            'name' => 'ES EW Test Area',
        ])->id;
    }

    private function fakeImage(string $name): UploadedFile
    {
        $content = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
