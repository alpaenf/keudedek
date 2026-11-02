<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_master_organisasi(): void
    {
        $ptkUser = User::factory()->create(['role' => 'PTK']);

        $response = $this->actingAs($ptkUser)->get('/master/departments');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_master_organisasi_both_tabs(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        // Tab 1: Departments
        $responseDept = $this->actingAs($adminUser)->get('/master/departments?tab=departments');
        $responseDept->assertStatus(200);

        // Tab 2: Study Programs
        $responseProdi = $this->actingAs($adminUser)->get('/master/departments?tab=study-programs');
        $responseProdi->assertStatus(200);
    }

    public function test_admin_can_create_update_toggle_department(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($adminUser);

        // 1. Create Department
        $createResponse = $this->post('/master/departments', [
            'code' => 'UNIT-TEST',
            'official_code' => '023.17.TEST',
            'name' => 'Unit Pelayanan Teknis Komputasi',
            'type' => 'DEPARTMENT',
            'is_active' => true,
            'source_type' => 'INTERNAL',
            'effective_year' => 2026,
        ]);
        $createResponse->assertRedirect();

        $dept = Department::where('code', 'UNIT-TEST')->first();
        $this->assertNotNull($dept);
        $this->assertEquals('DEPARTMENT', $dept->type);
        $this->assertTrue($dept->is_active);

        // 2. Update Department
        $updateResponse = $this->put("/master/departments/{$dept->id}", [
            'code' => 'UNIT-TEST',
            'official_code' => '023.17.TEST',
            'name' => 'Unit Pelayanan Teknis Komputasi & AI',
            'type' => 'DEPARTMENT',
            'is_active' => true,
            'source_type' => 'OFFICIAL_DOCUMENT',
            'effective_year' => 2026,
        ]);
        $updateResponse->assertRedirect();
        $dept->refresh();
        $this->assertEquals('Unit Pelayanan Teknis Komputasi & AI', $dept->name);

        // 3. Toggle Active
        $this->post("/master/departments/{$dept->id}/toggle-active");
        $dept->refresh();
        $this->assertFalse($dept->is_active);
        $this->assertEquals('INACTIVE', $dept->status);

        // 4. Delete Unreferenced Department
        $deleteResponse = $this->delete("/master/departments/{$dept->id}");
        $deleteResponse->assertRedirect();
        $this->assertNull(Department::where('code', 'UNIT-TEST')->first());
    }

    public function test_admin_can_create_update_toggle_study_program(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        $parentDept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Teknik Informatika', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->actingAs($adminUser);

        // 1. Create Study Program
        $createResponse = $this->post('/master/study-programs', [
            'code' => 'PRODI-TEST-01',
            'official_code' => '55999',
            'name' => 'S1 Rekayasa Kecerdasan Artifisial',
            'department_id' => $parentDept->id,
            'is_active' => true,
            'source_type' => 'OFFICIAL_DOCUMENT',
            'effective_year' => 2026,
        ]);
        $createResponse->assertRedirect();

        $prodi = StudyProgram::where('code', 'PRODI-TEST-01')->first();
        $this->assertNotNull($prodi);
        $this->assertEquals($parentDept->id, $prodi->department_id);

        // 2. Update Study Program
        $updateResponse = $this->put("/master/study-programs/{$prodi->id}", [
            'code' => 'PRODI-TEST-01',
            'official_code' => '55999',
            'name' => 'S1 Rekayasa AI & Data Science',
            'department_id' => $parentDept->id,
            'is_active' => true,
            'source_type' => 'OFFICIAL_DOCUMENT',
            'effective_year' => 2026,
        ]);
        $updateResponse->assertRedirect();
        $prodi->refresh();
        $this->assertEquals('S1 Rekayasa AI & Data Science', $prodi->name);

        // 3. Toggle Active
        $this->post("/master/study-programs/{$prodi->id}/toggle-active");
        $prodi->refresh();
        $this->assertFalse($prodi->is_active);

        // 4. Delete Unreferenced Prodi
        $deleteResponse = $this->delete("/master/study-programs/{$prodi->id}");
        $deleteResponse->assertRedirect();
        $this->assertNull(StudyProgram::where('code', 'PRODI-TEST-01')->first());
    }

    public function test_delete_safety_blocks_referenced_department_deletion(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );
        $fs = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true]
        );
        $deptWithBudget = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Teknik Informatika', 'type' => 'DEPARTMENT', 'is_active' => true]
        );
        $v = BudgetVersion::create([
            'fiscal_year_id' => $fy->id,
            'funding_source_id' => $fs->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);

        BudgetBucket::create([
            'fiscal_year_id' => $fy->id,
            'budget_version_id' => $v->id,
            'department_id' => $deptWithBudget->id,
            'funding_source_id' => $fs->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 10000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 10000000,
        ]);

        $response = $this->actingAs($adminUser)->delete("/master/departments/{$deptWithBudget->id}");
        $response->assertSessionHas('error');
        $this->assertNotNull(Department::find($deptWithBudget->id));
    }
}
