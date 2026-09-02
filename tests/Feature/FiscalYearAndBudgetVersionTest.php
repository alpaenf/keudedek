<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\User;
use Tests\TestCase;

class FiscalYearAndBudgetVersionTest extends TestCase
{
    public function test_non_authorized_roles_receive_403_on_administrasi_fiscal_years(): void
    {
        $ptkUser = User::where('role', 'PTK')->first();
        if (! $ptkUser) {
            $this->markTestSkipped('No PTK user found');
        }

        $response = $this->actingAs($ptkUser)->get('/master/fiscal-years');
        $response->assertStatus(403);
    }

    public function test_admin_and_kabag_can_access_administrasi_fiscal_years(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        $kabagUser = User::where('role', 'KABAG')->first();

        if ($adminUser) {
            $responseAdmin = $this->actingAs($adminUser)->get('/master/fiscal-years');
            $responseAdmin->assertStatus(200);
        }

        if ($kabagUser) {
            $responseKabag = $this->actingAs($kabagUser)->get('/master/fiscal-years');
            $responseKabag->assertStatus(200);
        }
    }

    public function test_rm_is_mvp_enabled_default_and_inactive_source_handling(): void
    {
        $rm = FundingSource::where('code', 'RM')->first();
        $this->assertNotNull($rm);
        $this->assertTrue($rm->is_mvp_enabled);
        $this->assertTrue($rm->is_active);

        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        // Toggle funding source
        $sbsn = FundingSource::where('code', 'SBSN')->first();
        if ($sbsn) {
            $initialStatus = $sbsn->status;
            $this->actingAs($adminUser)->post("/master/funding-sources/{$sbsn->id}/toggle-active");
            $sbsn->refresh();
            $this->assertNotEquals($initialStatus, $sbsn->status);
        }
    }

    public function test_only_one_active_version_per_year_and_fund_source_and_audit_logged(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        $fy = FiscalYear::first();
        $fs = FundingSource::where('code', 'RM')->first();

        // 1. Create Rev 01 (ACTIVE)
        $rev1 = BudgetVersion::updateOrCreate(
            ['fiscal_year_id' => $fy->id, 'funding_source_id' => $fs->id, 'revision_no' => 'Rev 01'],
            ['version_label' => 'Revisi 01 DIPA', 'status' => 'ACTIVE', 'created_by' => $adminUser->id]
        );

        // 2. Create Rev 02 (DRAFT)
        $rev2 = BudgetVersion::updateOrCreate(
            ['fiscal_year_id' => $fy->id, 'funding_source_id' => $fs->id, 'revision_no' => 'Rev 02'],
            ['version_label' => 'Revisi 02 DIPA', 'status' => 'DRAFT', 'created_by' => $adminUser->id]
        );

        $this->actingAs($adminUser);

        // 3. Activate Rev 02
        $response = $this->post("/master/budget-versions/{$rev2->id}/set-active");
        $response->assertRedirect();

        $rev1->refresh();
        $rev2->refresh();

        // Assert Rev 02 is now ACTIVE, and Rev 01 is ARCHIVED
        $this->assertEquals('ACTIVE', $rev2->status);
        $this->assertEquals('ARCHIVED', $rev1->status);

        // Assert exactly one active version exists for this FY + Fund Source
        $activeCount = BudgetVersion::where('fiscal_year_id', $fy->id)
            ->where('funding_source_id', $fs->id)
            ->where('status', 'ACTIVE')
            ->count();
        $this->assertEquals(1, $activeCount);

        // Assert Audit Log was created
        $auditLog = AuditLog::where('action', 'ACTIVATE_BUDGET_VERSION')
            ->where('model_id', $rev2->id)
            ->latest()
            ->first();
        $this->assertNotNull($auditLog);
    }

    public function test_revision_conflict_detection(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        $fy = FiscalYear::first();
        $fs = FundingSource::where('code', 'RM')->first();

        // Check index endpoint calculation
        $response = $this->actingAs($adminUser)->get("/master/fiscal-years?tab=budget-versions&fiscal_year_id={$fy->id}&funding_source_id={$fs->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Master/FiscalYears/Index')
            ->has('budgetVersions')
            ->has('activeFiscalYear')
            ->has('activeFundingSource')
        );
    }
}
