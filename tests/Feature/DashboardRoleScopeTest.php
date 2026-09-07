<?php

namespace Tests\Feature;

use App\Models\BudgetAccount;
use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\BudgetControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRoleScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Department $deptA;

    protected Department $deptB;

    protected StudyProgram $prodiA;

    protected FiscalYear $fiscalYear;

    protected BudgetVersion $version;

    protected FundingSource $fundingSource;

    protected BudgetBucket $bucketA;

    protected BudgetBucket $bucketB;

    protected BudgetLine $lineA;

    protected BudgetLine $lineB;

    protected User $ptkUser;

    protected User $kajurUser;

    protected User $kaprodiUser;

    protected User $ptuUser;

    protected User $kabagUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptA = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Jurusan Teknik Informatika', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->deptB = Department::firstOrCreate(
            ['code' => 'JTE'],
            ['name' => 'Jurusan Teknik Elektro', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->prodiA = StudyProgram::firstOrCreate(
            ['code' => 'IF-S1'],
            ['name' => 'S1 Informatika', 'department_id' => $this->deptA->id, 'is_active' => true]
        );

        $this->fiscalYear = FiscalYear::firstOrCreate(
            ['year' => 2026],
            [
                'status' => 'ACTIVE',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
            ]
        );

        $this->fundingSource = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->version = BudgetVersion::firstOrCreate(
            ['fiscal_year_id' => $this->fiscalYear->id, 'revision_no' => 'Rev 00'],
            [
                'funding_source_id' => $this->fundingSource->id,
                'version_label' => 'DIPA Induk 2026',
                'status' => 'ACTIVE',
                'total_budget' => 60000000.00,
            ]
        );

        $subcomp = BudgetSubcomponent::firstOrCreate(
            ['full_code' => 'WA.2134.BMA.001.051.A'],
            ['code' => 'A', 'name' => 'Operasional Kantor Jurusan']
        );

        $account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan']
        );

        // Dept A Bucket: 20M allocated
        $this->bucketA = BudgetBucket::firstOrCreate(
            [
                'fiscal_year_id' => $this->fiscalYear->id,
                'budget_version_id' => $this->version->id,
                'department_id' => $this->deptA->id,
                'account_code' => '521211',
            ],
            [
                'funding_source_id' => $this->fundingSource->id,
                'budget_bucket_name' => 'Belanja Bahan Informatika',
                'account_name' => 'Belanja Bahan',
                'subcomponent_full_code' => 'WA.2134.BMA.001.051.A',
                'subcomponent_code' => 'A',
                'subcomponent_name' => 'Operasional Kantor Jurusan',
                'allocated_budget' => 20000000.00,
                'reserved_budget' => 0.00,
                'realized_budget' => 0.00,
                'available_balance' => 20000000.00,
            ]
        );

        // Dept B Bucket: 40M allocated
        $this->bucketB = BudgetBucket::firstOrCreate(
            [
                'fiscal_year_id' => $this->fiscalYear->id,
                'budget_version_id' => $this->version->id,
                'department_id' => $this->deptB->id,
                'account_code' => '521211',
            ],
            [
                'funding_source_id' => $this->fundingSource->id,
                'budget_bucket_name' => 'Belanja Bahan Elektro',
                'account_name' => 'Belanja Bahan',
                'subcomponent_full_code' => 'WA.2134.BMA.001.051.A',
                'subcomponent_code' => 'A',
                'subcomponent_name' => 'Operasional Kantor Jurusan',
                'allocated_budget' => 40000000.00,
                'reserved_budget' => 0.00,
                'realized_budget' => 0.00,
                'available_balance' => 40000000.00,
            ]
        );

        $this->lineA = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->deptA->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucketA->id,
            'rba_sequence_no' => '001',
            'budget_subcomponent_id' => $subcomp->id,
            'budget_account_id' => $account->id,
            'description' => 'Snack Rapat Informatika',
            'volume' => 1.00,
            'unit' => 'Kegiatan',
            'unit_price' => 20000000.00,
            'budget_amount' => 20000000.00,
            'status' => 'ACTIVE',
        ]);

        $this->lineB = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->deptB->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucketB->id,
            'rba_sequence_no' => '002',
            'budget_subcomponent_id' => $subcomp->id,
            'budget_account_id' => $account->id,
            'description' => 'Snack Rapat Elektro',
            'volume' => 1.00,
            'unit' => 'Kegiatan',
            'unit_price' => 40000000.00,
            'budget_amount' => 40000000.00,
            'status' => 'ACTIVE',
        ]);

        $this->ptkUser = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->deptA->id,
        ]);

        $this->kajurUser = User::factory()->create([
            'role' => 'KAJUR',
            'department_id' => $this->deptA->id,
        ]);

        $this->kaprodiUser = User::factory()->create([
            'role' => 'KAPRODI',
            'department_id' => $this->deptA->id,
            'study_program_id' => $this->prodiA->id,
        ]);

        $this->ptuUser = User::factory()->create([
            'role' => 'PTU',
        ]);

        $this->kabagUser = User::factory()->create([
            'role' => 'KABAG',
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'ADMIN',
        ]);
    }

    /**
     * Test 1: PTK Dashboard Scoped to Department A
     */
    public function test_ptk_dashboard_is_scoped_to_own_department(): void
    {
        // Dept A transaction: 5M
        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->lineA->id,
            'evidence_number' => 'FRA/PTK-A',
            'transaction_date' => '2026-09-03',
            'title' => 'Trx Dept A',
            'amount' => 5000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $response = $this->actingAs($this->ptkUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('userRole', 'PTK')
            ->where('totalAllocated', 20000000) // Only Dept A, not 60M
            ->where('totalReserved', 5000000)
            ->where('totalAvailable', 15000000)
        );
    }

    /**
     * Test 2: KAJUR Dashboard is Read-Only and Scoped to Department A
     */
    public function test_kajur_dashboard_is_read_only_and_scoped(): void
    {
        $response = $this->actingAs($this->kajurUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('userRole', 'KAJUR')
            ->where('totalAllocated', 20000000) // Dept A only
        );
    }

    /**
     * Test 3: KAPRODI Dashboard Shows Transactions and does NOT display Pagu Prodi
     */
    public function test_kaprodi_dashboard_shows_no_pagu_and_scopes_transactions(): void
    {
        // 1 transaction with study_program_id
        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->lineA->id,
            'study_program_id' => $this->prodiA->id,
            'evidence_number' => 'FRA/PRODI-A',
            'transaction_date' => '2026-09-03',
            'title' => 'Trx Prodi A',
            'amount' => 3000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $response = $this->actingAs($this->kaprodiUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('userRole', 'KAPRODI')
            ->where('totalAllocated', 0) // Strictly 0: No Pagu Prodi!
            ->where('totalReserved', 3000000)
        );
    }

    /**
     * Test 4: KABAG Dashboard has Faculty-Wide Scope (Dept A + Dept B)
     */
    public function test_kabag_dashboard_has_faculty_scope(): void
    {
        $response = $this->actingAs($this->kabagUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('userRole', 'KABAG')
            ->where('totalAllocated', 60000000) // 20M + 40M
        );
    }

    /**
     * Test 5: ADMIN Dashboard returns System Foundation & Quality metrics
     */
    public function test_admin_dashboard_returns_system_foundation_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('userRole', 'ADMIN')
            ->has('adminMetrics.active_fiscal_year')
            ->has('adminMetrics.active_revision')
            ->has('adminMetrics.total_budget_lines')
            ->has('adminMetrics.unmapped_count')
            ->has('adminMetrics.active_users_count')
        );
    }
}
