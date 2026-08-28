<?php

namespace Tests\Feature;

use App\Models\SafeWorkPermitInspection;
use App\Models\User;
use Database\Seeders\PermitMatrixMasterSeeder;
use Database\Seeders\PermitMatrixPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SafeWorkPermitInspectionTest extends TestCase
{
    use DatabaseTransactions;

    private User $selectableInspector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermitMatrixMasterSeeder::class);
        $this->seed(PermitMatrixPermissionSeeder::class);
        $this->selectableInspector = $this->makeUser('inspector', [
            'name' => 'Active Permit Inspector ' . uniqid(),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_permit_matrix_api(): void
    {
        $this->getJson('/api/safe-work-permit-inspections')->assertUnauthorized();
        $this->postJson('/api/safe-work-permit-inspections', [])->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_view_or_manipulate_data(): void
    {
        $user = $this->makeUser('user_dept_head');
        $inspection = SafeWorkPermitInspection::create($this->validPayload());

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/safe-work-permit-inspections')
            ->assertForbidden();
        $this->postJson('/api/safe-work-permit-inspections', $this->validPayload('SWP-NO-PERMISSION'))
            ->assertForbidden();
        $this->putJson("/api/safe-work-permit-inspections/{$inspection->id}", $this->validPayload())
            ->assertForbidden();
        $this->deleteJson("/api/safe-work-permit-inspections/{$inspection->id}")
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_open_index(): void
    {
        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.view']), 'sanctum')
            ->getJson('/api/safe-work-permit-inspections')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'current_page', 'last_page', 'total']);
    }

    public function test_master_data_inspectors_are_loaded_from_active_users(): void
    {
        $inactiveUser = $this->makeUser('inspector', [
            'name' => 'Inactive Permit Inspector ' . uniqid(),
            'is_active' => false,
        ]);
        $legacyName = 'Legacy Permit Inspector ' . uniqid();
        DB::table('permit_inspectors')->insert([
            'name' => $legacyName,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(
            $this->permittedUser(['safe-work-permit-inspection.view']),
            'sanctum'
        )->getJson('/api/safe-work-permit-inspections/master-data')->assertOk();

        $response->assertJsonFragment([
            'id' => $this->selectableInspector->id,
            'name' => $this->selectableInspector->name,
        ]);
        $response->assertJsonMissing(['id' => $inactiveUser->id, 'name' => $inactiveUser->name]);
        $response->assertJsonMissing(['name' => $legacyName]);
    }

    public function test_super_admin_can_complete_full_crud_without_group_mapping(): void
    {
        $user = $this->makeUser('super_admin');
        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonFragment(['safe-work-permit-inspection.create']);

        $inspectionId = $this->postJson(
            '/api/safe-work-permit-inspections',
            $this->validPayload('SWP-FULL-CRUD')
        )->assertCreated()->json('data.id');

        $this->getJson("/api/safe-work-permit-inspections/{$inspectionId}")
            ->assertOk()
            ->assertJsonPath('data.permit_number', 'SWP-FULL-CRUD');

        $updatedPayload = $this->validPayload('SWP-FULL-CRUD');
        $updatedPayload['contractor_name'] = 'KONTRAKTOR CRUD';
        $this->putJson("/api/safe-work-permit-inspections/{$inspectionId}", $updatedPayload)
            ->assertOk()
            ->assertJsonPath('data.contractor_name', 'KONTRAKTOR CRUD');

        $this->deleteJson("/api/safe-work-permit-inspections/{$inspectionId}")
            ->assertOk();
        $this->assertDatabaseMissing('safe_work_permit_inspections', ['id' => $inspectionId]);
    }

    public function test_valid_data_is_stored_and_permit_number_is_trimmed(): void
    {
        $payload = $this->validPayload('  SWP-001  ');

        $this->actingAs($this->permittedUser([
            'safe-work-permit-inspection.view',
            'safe-work-permit-inspection.create',
        ]), 'sanctum')
            ->postJson('/api/safe-work-permit-inspections', $payload)
            ->assertCreated()
            ->assertJsonPath('data.permit_number', 'SWP-001')
            ->assertJsonPath('data.inspector.id', $this->selectableInspector->id)
            ->assertJsonPath('data.finding_status', 'Tidak Ada Temuan');

        $this->assertDatabaseHas('safe_work_permit_inspections', ['permit_number' => 'SWP-001']);
    }

    public function test_required_inputs_are_rejected(): void
    {
        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.create']), 'sanctum')
            ->postJson('/api/safe-work-permit-inspections', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'permit_date',
                'inspector_id',
                'permit_number',
                'permit_type_id',
                'supervision_area_id',
                'main_area_id',
                'sub_area_id',
                'section_equipment',
                'job_performance',
                'authorized_craftman',
                'authorized_facility',
                'contractor_name',
                'work_description',
            ]);
    }

    public function test_invalid_or_inactive_master_ids_are_rejected(): void
    {
        $payload = $this->validPayload();
        $payload['inspector_id'] = 999999;
        DB::table('permit_types')->where('id', $payload['permit_type_id'])->update(['is_active' => false]);

        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.create']), 'sanctum')
            ->postJson('/api/safe-work-permit-inspections', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['inspector_id', 'permit_type_id']);
    }

    public function test_mapped_sub_area_must_match_selected_main_area(): void
    {
        $payload = $this->validPayload();
        $otherMainAreaId = DB::table('permit_main_areas')
            ->where('id', '<>', $payload['main_area_id'])
            ->value('id');
        DB::table('permit_sub_areas')
            ->where('id', $payload['sub_area_id'])
            ->update(['main_area_id' => $otherMainAreaId]);

        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.create']), 'sanctum')
            ->postJson('/api/safe-work-permit-inspections', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sub_area_id');
    }

    public function test_data_can_be_updated(): void
    {
        $inspection = SafeWorkPermitInspection::create($this->validPayload());
        $payload = $this->validPayload();
        $payload['contractor_name'] = 'KONTRAKTOR DIPERBARUI';

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->putJson("/api/safe-work-permit-inspections/{$inspection->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.contractor_name', 'KONTRAKTOR DIPERBARUI');
    }

    public function test_data_can_be_deleted(): void
    {
        $inspection = SafeWorkPermitInspection::create($this->validPayload());

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson("/api/safe-work-permit-inspections/{$inspection->id}")
            ->assertOk();

        $this->assertDatabaseMissing('safe_work_permit_inspections', ['id' => $inspection->id]);
    }

    public function test_non_admin_with_update_permission_cannot_update_or_delete(): void
    {
        $inspection = SafeWorkPermitInspection::create($this->validPayload());

        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.update']), 'sanctum')
            ->putJson("/api/safe-work-permit-inspections/{$inspection->id}", $this->validPayload())
            ->assertForbidden();

        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.delete']), 'sanctum')
            ->deleteJson("/api/safe-work-permit-inspections/{$inspection->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('safe_work_permit_inspections', ['id' => $inspection->id]);
    }

    public function test_search_and_filters_return_matching_data(): void
    {
        $first = SafeWorkPermitInspection::create($this->validPayload('SWP-SEARCH-ONE'));
        $secondPayload = $this->validPayload('SWP-OTHER');
        $secondPayload['contractor_name'] = 'KONTRAKTOR LAIN';
        $secondPayload['permit_findings'] = 'Ada pelanggaran permit';
        SafeWorkPermitInspection::create($secondPayload);

        $url = '/api/safe-work-permit-inspections?search=SEARCH'
            ."&inspector_id={$first->inspector_id}"
            ."&permit_type_id={$first->permit_type_id}"
            .'&date_from=2026-07-01&date_to=2026-07-31'
            .'&finding_status=without';

        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.view']), 'sanctum')
            ->getJson($url)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.permit_number', 'SWP-SEARCH-ONE');
    }

    public function test_finding_status_is_exposed_correctly(): void
    {
        SafeWorkPermitInspection::create($this->validPayload('SWP-WITHOUT'));
        $withFinding = $this->validPayload('SWP-WITH');
        $withFinding['permit_findings'] = 'APD tidak lengkap';
        SafeWorkPermitInspection::create($withFinding);

        $response = $this->actingAs($this->permittedUser(['safe-work-permit-inspection.view']), 'sanctum')
            ->getJson('/api/safe-work-permit-inspections')
            ->assertOk();

        $statuses = collect($response->json('data'))->pluck('finding_status', 'permit_number');
        $this->assertSame('Tidak Ada Temuan', $statuses['SWP-WITHOUT']);
        $this->assertSame('Ada Temuan', $statuses['SWP-WITH']);
    }

    public function test_permit_number_must_be_unique_after_trimming(): void
    {
        SafeWorkPermitInspection::create($this->validPayload('SWP-UNIQUE'));

        $this->actingAs($this->permittedUser(['safe-work-permit-inspection.create']), 'sanctum')
            ->postJson('/api/safe-work-permit-inspections', $this->validPayload(' SWP-UNIQUE '))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('permit_number');
    }

    public function test_seeders_can_run_twice_without_duplicates(): void
    {
        $this->seed(PermitMatrixMasterSeeder::class);
        $this->seed(PermitMatrixPermissionSeeder::class);
        $firstCounts = $this->seededCounts();

        $this->seed(PermitMatrixMasterSeeder::class);
        $this->seed(PermitMatrixPermissionSeeder::class);

        $this->assertSame($firstCounts, $this->seededCounts());
        $this->assertSame(19, DB::table('permit_inspectors')->count());
        $this->assertSame(0, DB::table('permissions')->where('module', 'safe-work-permit-inspection')->count());
    }

    private function validPayload(string $permitNumber = 'SWP-TEST'): array
    {
        return [
            'permit_date' => '2026-07-23',
            'inspector_id' => $this->selectableInspector->id,
            'permit_number' => $permitNumber,
            'permit_type_id' => DB::table('permit_types')->where('is_active', true)->value('id'),
            'supervision_area_id' => DB::table('supervision_areas')->where('is_active', true)->value('id'),
            'main_area_id' => DB::table('permit_main_areas')->where('is_active', true)->value('id'),
            'sub_area_id' => DB::table('permit_sub_areas')->where('is_active', true)->value('id'),
            'section_equipment' => 'Boiler 1',
            'job_performance' => 'Pemeriksaan pekerjaan hot work',
            'authorized_craftman' => 'Craftman A',
            'authorized_facility' => 'Facility A',
            'contractor_name' => 'PT KONTRAKTOR',
            'work_description' => 'Pengelasan pada area kerja',
            'permit_findings' => null,
        ];
    }

    private function makeUser(string $role, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Permit Matrix Test User',
            'username' => 'permit_test_' . $role . '_' . uniqid(),
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    private function permittedUser(array $permissions): User
    {
        $user = $this->makeUser('inspector');
        $groupId = DB::table('user_groups')->insertGetId([
            'name' => 'Permit Test Group ' . uniqid(),
            'is_active' => true,
            'created_at' => now(),
        ]);
        DB::table('user_group_members')->insert(['user_id' => $user->id, 'group_id' => $groupId]);
        DB::table('permissions')->insert([
            'group_id' => $groupId,
            'module' => 'safe-work-permit-inspection',
            'can_view' => in_array('safe-work-permit-inspection.view', $permissions, true),
            'can_create' => in_array('safe-work-permit-inspection.create', $permissions, true),
            'can_edit' => in_array('safe-work-permit-inspection.update', $permissions, true),
            'can_delete' => in_array('safe-work-permit-inspection.delete', $permissions, true),
            'can_approve' => false,
        ]);

        return $user;
    }

    private function seededCounts(): array
    {
        return [
            DB::table('permit_inspectors')->count(),
            DB::table('permit_types')->count(),
            DB::table('supervision_areas')->count(),
            DB::table('permit_main_areas')->count(),
            DB::table('permit_sub_areas')->count(),
            DB::table('permissions')->where('module', 'safe-work-permit-inspection')->count(),
        ];
    }
}
