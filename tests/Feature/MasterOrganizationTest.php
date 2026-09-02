<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\StudyProgram;
use App\Models\User;
use Tests\TestCase;

class MasterOrganizationTest extends TestCase
{
    public function test_non_admin_cannot_access_master_organisasi(): void
    {
        $ptkUser = User::where('role', 'PTK')->first();
        if (! $ptkUser) {
            $this->markTestSkipped('No PTK user found');
        }

        $response = $this->actingAs($ptkUser)->get('/master/departments');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_master_organisasi_both_tabs(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        // Tab 1: Departments
        $responseDept = $this->actingAs($adminUser)->get('/master/departments?tab=departments');
        $responseDept->assertStatus(200);

        // Tab 2: Study Programs
        $responseProdi = $this->actingAs($adminUser)->get('/master/departments?tab=study-programs');
        $responseProdi->assertStatus(200);
    }

    public function test_admin_can_create_update_toggle_department(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

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
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        $parentDept = Department::where('type', 'DEPARTMENT')->first() ?? Department::first();

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
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        // Department with relations (e.g. JTIF or FT)
        $deptWithBudget = Department::whereHas('budgetBuckets')->first();
        if (! $deptWithBudget) {
            $this->markTestSkipped('No department with budget buckets found');
        }

        $response = $this->actingAs($adminUser)->delete("/master/departments/{$deptWithBudget->id}");
        $response->assertSessionHas('error');
        $this->assertNotNull(Department::find($deptWithBudget->id));
    }
}
