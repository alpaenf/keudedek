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
use App\Models\Submission;
use App\Models\User;
use App\Services\BudgetCalculationService;
use App\Services\BudgetControlService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbcEquationFourBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetVersion $version;

    protected User $ptkUser;

    protected User $ptuUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::firstOrCreate(
            ['code' => 'JTS'],
            ['name' => 'Jurusan Teknik Sipil', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->fiscalYear = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );

        $this->fundingSource = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->version = BudgetVersion::firstOrCreate(
            ['fiscal_year_id' => $this->fiscalYear->id, 'revision_no' => 'Rev 00'],
            [
                'funding_source_id' => $this->fundingSource->id,
                'version_label' => 'DIPA Awal 2026',
                'status' => 'ACTIVE',
                'total_budget' => 100000000.00,
            ]
        );

        $this->ptkUser = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->department->id,
        ]);

        $this->ptuUser = User::factory()->create([
            'role' => 'PTU',
            'department_id' => $this->department->id,
        ]);
    }

    /**
     * Skenario A:
     * P = 100.000.000, C = 20.000.000, R = 70.000.000
     * Expected S = 10.000.000 (S = P - C - R)
     */
    public function test_skenario_a_positive_balance(): void
    {
        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 20000000.00,
            'realized_budget' => 70000000.00,
            'available_balance' => 0.00,
        ]);

        $bucket->recalculateAvailableBalance();
        $bucket->refresh();

        $this->assertEquals(100000000.00, (float) $bucket->allocated_budget);
        $this->assertEquals(20000000.00, (float) $bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $bucket->realized_budget);
        $this->assertEquals(10000000.00, (float) $bucket->available_balance);

        // Verify DashboardService matches exact sum
        $payload = DashboardService::getPayload($this->ptuUser);
        $this->assertEquals(10000000.00, (float) $payload['totalAvailable']);
    }

    /**
     * Skenario B:
     * P = 85.000.000, C = 20.000.000, R = 70.000.000
     * Expected S = -5.000.000 (S = P - C - R, defisit tidak boleh di-clamp ke 0)
     */
    public function test_skenario_b_negative_balance_represented_faithfully(): void
    {
        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 85000000.00,
            'reserved_budget' => 20000000.00,
            'realized_budget' => 70000000.00,
            'available_balance' => 0.00,
        ]);

        $bucket->recalculateAvailableBalance();
        $bucket->refresh();

        $this->assertEquals(85000000.00, (float) $bucket->allocated_budget);
        $this->assertEquals(20000000.00, (float) $bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $bucket->realized_budget);
        // S = 85M - 20M - 70M = -5M
        $this->assertEquals(-5000000.00, (float) $bucket->available_balance);

        // Verify DashboardService retains negative available balance without max(0, ...)
        $payload = DashboardService::getPayload($this->ptuUser);
        $this->assertEquals(-5000000.00, (float) $payload['totalAvailable']);
    }

    /**
     * Skenario C:
     * Dari kondisi B (P=85M, C=20M, R=70M, S=-5M),
     * transaksi DP Rp10.000.000 dipindahkan menjadi RI pada pos yang sama via transitionStatus.
     * Expected C = 10.000.000
     * Expected R = 80.000.000
     * Expected S = -5.000.000
     */
    public function test_skenario_c_transition_dp_to_ri_with_negative_balance(): void
    {
        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 85000000.00,
            'reserved_budget' => 20000000.00,
            'realized_budget' => 70000000.00,
            'available_balance' => -5000000.00,
        ]);

        // Create a synthetic DP transaction of 10.000.000 on this bucket
        $submission = Submission::create([
            'submission_number' => 'TRX/2026/09/001',
            'evidence_number' => 'FRA/SKEN-C',
            'transaction_date' => '2026-09-10',
            'title' => 'Pengadaan Bahan Praktikum',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $bucket->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        // Transition DP to RI (FINAL) via BudgetControlService
        BudgetControlService::transitionStatus(
            submission: $submission,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Penyelesaian SPJ DP ke RI'
        );

        $bucket->refresh();

        // C berkurang 10M: 20M - 10M = 10M
        $this->assertEquals(10000000.00, (float) $bucket->reserved_budget);
        // R bertambah 10M: 70M + 10M = 80M
        $this->assertEquals(80000000.00, (float) $bucket->realized_budget);
        // S = P - C - R = 85M - 10M - 80M = -5M (tetap konsisten)
        $this->assertEquals(-5000000.00, (float) $bucket->available_balance);
        $this->assertEquals('FINAL', $submission->refresh()->status);
    }

    /**
     * Skenario D:
     * P = 100.000.000, C = 30.000.000, R = 70.000.000
     * Expected S = 0
     */
    public function test_skenario_d_zero_balance(): void
    {
        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 30000000.00,
            'realized_budget' => 70000000.00,
            'available_balance' => 0.00,
        ]);

        $bucket->recalculateAvailableBalance();
        $bucket->refresh();

        $this->assertEquals(100000000.00, (float) $bucket->allocated_budget);
        $this->assertEquals(30000000.00, (float) $bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $bucket->realized_budget);
        $this->assertEquals(0.00, (float) $bucket->available_balance);
    }

    /**
     * Test Line Snapshot in BudgetCalculationService also reflects S = P - C - R without clamp.
     */
    public function test_line_financial_snapshot_reflects_equation_four(): void
    {
        $subcomp = BudgetSubcomponent::firstOrCreate(
            ['full_code' => 'WA.2134.BMA.001.051.A'],
            ['code' => 'A', 'name' => 'Operasional Kantor Jurusan']
        );

        $account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan']
        );

        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => 'WA.2134.BMA.001.051.A',
            'allocated_budget' => 85000000.00,
            'reserved_budget' => 20000000.00,
            'realized_budget' => 70000000.00,
            'available_balance' => -5000000.00,
        ]);

        $line = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $bucket->id,
            'rba_sequence_no' => '001',
            'budget_subcomponent_id' => $subcomp->id,
            'budget_account_id' => $account->id,
            'description' => 'Bahan Riset',
            'volume' => 1.00,
            'unit' => 'Paket',
            'unit_price' => 85000000.00,
            'budget_amount' => 85000000.00,
            'status' => 'ACTIVE',
        ]);

        // 20M DP on line
        Submission::create([
            'submission_number' => 'TRX/LINE/001',
            'evidence_number' => 'FRA/LINE-01',
            'transaction_date' => '2026-09-10',
            'title' => 'DP Line',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $bucket->id,
            'budget_line_id' => $line->id,
            'amount' => 20000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        // 70M RI on line
        Submission::create([
            'submission_number' => 'TRX/LINE/002',
            'evidence_number' => 'FRA/LINE-02',
            'transaction_date' => '2026-09-10',
            'title' => 'RI Line',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $bucket->id,
            'budget_line_id' => $line->id,
            'amount' => 70000000.00,
            'status' => 'FINAL',
            'created_by' => $this->ptkUser->id,
        ]);

        $snapshot = BudgetCalculationService::getLineFinancialSnapshot($line);

        $this->assertEquals(-5000000.00, (float) $snapshot['line_saldo']);
        $this->assertEquals(-5000000.00, (float) $snapshot['bucket_available']);
    }
}
