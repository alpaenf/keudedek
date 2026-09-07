<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\BudgetImportStaging;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\ImportHistory;
use App\Models\RuleConfig;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\Services\EarlyWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Department $dept;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetVersion $budgetVersion;

    protected User $adminUser;

    protected EarlyWarningService $ewsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Jurusan Teknik Informatika', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->fiscalYear = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );

        $this->fundingSource = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->budgetVersion = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA Awal 2026',
            'status' => 'ACTIVE',
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'ADMIN',
        ]);

        $this->ewsService = app(EarlyWarningService::class);
    }

    /**
     * Test EWS-001: Saldo Kritis trigger and configurable threshold (X)
     */
    public function test_ews_001_saldo_kritis_triggers_with_configurable_threshold(): void
    {
        // Set dynamic parameter in rule_configs: warning at 15%, critical at 5%
        RuleConfig::updateOrCreate(
            ['rule_code' => 'EWS-001'],
            [
                'rule_name' => 'Saldo Kritis',
                'category' => 'BUDGET',
                'parameters' => [
                    'warning_ratio' => 0.15,
                    'critical_ratio' => 0.05,
                ],
                'is_active' => true,
            ]
        );

        // Bucket 1: 100M allocated, 4M available => 4% (<= 5% => CRITICAL)
        $criticalBucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan Kritis',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 96000000.00,
            'available_balance' => 4000000.00,
        ]);

        // Bucket 2: 100M allocated, 12M available => 12% (<= 15% => WARNING)
        $warningBucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521111',
            'account_name' => 'Belanja ATK Warning',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 88000000.00,
            'available_balance' => 12000000.00,
        ]);

        $this->ewsService->evaluateEws001SaldoKritis();

        $wCritical = EarlyWarning::where('rule_code', 'EWS-001')
            ->where('budget_bucket_id', $criticalBucket->id)
            ->first();

        $this->assertNotNull($wCritical);
        $this->assertEquals('CRITICAL', $wCritical->severity);
        $this->assertEquals('OPEN', $wCritical->lifecycle_state);
        $this->assertStringContainsString('Pos 521211', $wCritical->target_object);
        $this->assertNotNull($wCritical->reason);
        $this->assertNotNull($wCritical->first_triggered_at);

        $wWarning = EarlyWarning::where('rule_code', 'EWS-001')
            ->where('budget_bucket_id', $warningBucket->id)
            ->first();

        $this->assertNotNull($wWarning);
        $this->assertEquals('WARNING', $wWarning->severity);
        $this->assertEquals('OPEN', $wWarning->lifecycle_state);
    }

    /**
     * Test EWS-002: Stale Submission (DIAJUKAN > N hari tanpa perubahan)
     */
    public function test_ews_002_stale_submission_triggers_when_pending_exceeds_n_days(): void
    {
        RuleConfig::updateOrCreate(
            ['rule_code' => 'EWS-002'],
            [
                'rule_name' => 'Stale Submission',
                'category' => 'TRANSACTION',
                'parameters' => [
                    'stale_days' => 3,
                    'critical_days' => 7,
                ],
                'is_active' => true,
            ]
        );

        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 50000000.00,
            'available_balance' => 50000000.00,
        ]);

        // Stale submission: updated 5 days ago in PROCESSING (DIAJUKAN)
        $sub = Submission::create([
            'submission_number' => 'TRX/2026/001',
            'evidence_number' => 'BKT-001',
            'title' => 'Pengadaan Lisensi Tertahan',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $bucket->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->adminUser->id,
        ]);

        // Manipulate updated_at to 5 days ago
        $sub->timestamps = false;
        $sub->updated_at = now()->subDays(5);
        $sub->save();

        $this->ewsService->evaluateEws002StaleSubmission();

        $w = EarlyWarning::where('rule_code', 'EWS-002')
            ->where('submission_id', $sub->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('WARNING', $w->severity);
        $this->assertEquals('OPEN', $w->lifecycle_state);
        $this->assertEquals(5, (int) $w->current_value);
        $this->assertEquals(3, (int) $w->threshold_value);
        $this->assertStringContainsString('BKT-001', $w->target_object);
    }

    /**
     * Test EWS-003: Revision Conflict (budget baru < commitment + realization)
     */
    public function test_ews_003_revision_conflict_triggers_on_deficit(): void
    {
        $activeBucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 20000000.00,
            'reserved_budget' => 10000000.00, // Commitment
            'realized_budget' => 5000000.00,  // Realization => Total active load = 15M
            'available_balance' => 5000000.00,
        ]);

        // Draft Version
        $draftVersion = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'Usulan Pagu Turun',
            'status' => 'DRAFT',
        ]);

        // New allocated budget = 12M (Deficit of 3M compared to 15M active load)
        BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $draftVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 12000000.00,
            'available_balance' => 12000000.00,
        ]);

        $this->ewsService->evaluateEws003RevisionConflict();

        $w = EarlyWarning::where('rule_code', 'EWS-003')
            ->where('budget_bucket_id', $activeBucket->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('CRITICAL', $w->severity);
        $this->assertEquals('OPEN', $w->lifecycle_state);
        $this->assertEquals(12000000.00, (float) $w->current_value);
        $this->assertEquals(15000000.00, (float) $w->threshold_value);
        $this->assertStringContainsString('defisit', strtolower($w->reason));
    }

    /**
     * Test EWS-004: Unmapped Data detected from import staging
     */
    public function test_ews_004_unmapped_data_triggers_from_staging(): void
    {
        $history = ImportHistory::create([
            'user_id' => $this->adminUser->id,
            'filename' => 'unmapped_test.csv',
            'status' => 'PENDING',
        ]);

        BudgetImportStaging::create([
            'import_history_id' => $history->id,
            'department_code' => 'UNKNOWN_DEPT',
            'fiscal_year' => 2026,
            'funding_source_code' => 'RM',
            'account_code' => '999999',
            'account_name' => 'Akun Tidak Valid',
            'initial_budget' => 1000000.00,
            'status' => 'INVALID',
            'error_message' => 'Kode Jurusan tidak terdaftar',
        ]);

        $this->ewsService->evaluateEws004UnmappedData();

        $w = EarlyWarning::where('rule_code', 'EWS-004')->first();
        $this->assertNotNull($w);
        $this->assertEquals('WARNING', $w->severity);
        $this->assertEquals('OPEN', $w->lifecycle_state);
        $this->assertGreaterThanOrEqual(1, (int) $w->current_value);
    }

    /**
     * Test EWS-005: Repeated Return triggers when transaction returned >= N times
     */
    public function test_ews_005_repeated_return_triggers_on_multiple_rejections(): void
    {
        RuleConfig::updateOrCreate(
            ['rule_code' => 'EWS-005'],
            [
                'rule_name' => 'Repeated Return',
                'category' => 'COMPLIANCE',
                'parameters' => [
                    'threshold_return_count' => 2,
                ],
                'is_active' => true,
            ]
        );

        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 50000000.00,
            'available_balance' => 50000000.00,
        ]);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/005',
            'evidence_number' => 'BKT-005',
            'title' => 'Pengadaan Berulang Gagal SPJ',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $bucket->id,
            'amount' => 5000000.00,
            'status' => 'RETURNED',
            'created_by' => $this->adminUser->id,
        ]);

        // Record 2 returns in status histories
        SubmissionStatusHistory::create([
            'submission_id' => $sub->id,
            'from_status' => 'PROCESSING',
            'to_status' => 'RETURNED',
            'actor_id' => $this->adminUser->id,
            'role' => 'PTU',
            'notes' => 'Bukti kwitansi belum bermeterai',
        ]);

        SubmissionStatusHistory::create([
            'submission_id' => $sub->id,
            'from_status' => 'PROCESSING',
            'to_status' => 'RETURNED',
            'actor_id' => $this->adminUser->id,
            'role' => 'PTU',
            'notes' => 'Nomor invoice salah',
        ]);

        $this->ewsService->evaluateEws005RepeatedReturn();

        $w = EarlyWarning::where('rule_code', 'EWS-005')
            ->where('submission_id', $sub->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('HIGH', $w->severity);
        $this->assertEquals('OPEN', $w->lifecycle_state);
        $this->assertEquals(2, (int) $w->current_value);
    }

    /**
     * Test Warning Lifecycle: OPEN -> ACKNOWLEDGED -> RESOLVED
     */
    public function test_warning_lifecycle_transitions_via_controller(): void
    {
        $warning = EarlyWarning::create([
            'rule_code' => 'EWS-001',
            'target_object' => 'Pos 521211 - JTIF',
            'severity' => 'CRITICAL',
            'department_id' => $this->dept->id,
            'current_value' => 0.03,
            'threshold_value' => 0.05,
            'message' => 'Saldo Kritis',
            'reason' => 'Rasio sisa saldo <= 5%',
            'status' => 'ACTIVE',
            'lifecycle_state' => 'OPEN',
            'first_triggered_at' => now(),
        ]);

        $this->actingAs($this->adminUser);

        // 1. Transition to ACKNOWLEDGED
        $resAck = $this->post("/warnings/{$warning->id}/acknowledge");
        $resAck->assertSessionHasNoErrors();
        $this->assertEquals('ACKNOWLEDGED', $warning->fresh()->lifecycle_state);
        $this->assertEquals($this->adminUser->id, $warning->fresh()->acknowledged_by);

        // 2. Transition to RESOLVED
        $resRes = $this->post("/warnings/{$warning->id}/resolve");
        $resRes->assertSessionHasNoErrors();
        $this->assertEquals('RESOLVED', $warning->fresh()->lifecycle_state);
        $this->assertEquals('RESOLVED', $warning->fresh()->status);
    }
}
