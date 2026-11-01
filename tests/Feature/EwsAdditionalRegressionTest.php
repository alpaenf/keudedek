<?php

namespace Tests\Feature;

use App\Models\BudgetAccount;
use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\RuleConfig;
use App\Models\User;
use App\Services\BudgetControlService;
use App\Services\EarlyWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EwsAdditionalRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected Department $deptJTS;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetVersion $version00;

    protected BudgetSubcomponent $subcomponent;

    protected BudgetAccount $account;

    protected BudgetBucket $bucket;

    protected BudgetLine $line;

    protected User $ptkJts;

    protected User $adminUser;

    protected EarlyWarningService $ewsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptJTS = Department::firstOrCreate(
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

        $this->version00 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA Awal 2026',
            'status' => 'ACTIVE',
            'total_budget' => 500000000.00,
        ]);

        $this->subcomponent = BudgetSubcomponent::firstOrCreate(
            ['full_code' => '023.17.WA.4257.EBA.994.001.AA'],
            ['code' => 'AA', 'name' => 'Operasional Jurusan']
        );

        $this->account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan']
        );

        $this->bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version00->id,
            'department_id' => $this->deptJTS->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'subcomponent_code' => 'AA',
            'subcomponent_name' => 'Operasional Jurusan',
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 100000000.00,
            'status' => 'ACTIVE',
        ]);

        $this->line = BudgetLine::create([
            'budget_version_id' => $this->version00->id,
            'department_id' => $this->deptJTS->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'rba_sequence_no' => '001',
            'description' => 'Alokasi Belanja Bahan Jurusan',
            'volume' => 1.00,
            'unit' => 'Paket',
            'unit_price' => 100000000.00,
            'budget_amount' => 100000000.00,
            'total_amount' => 100000000.00,
            'status' => 'ACTIVE',
        ]);

        $this->ptkJts = User::factory()->create([
            'department_id' => $this->deptJTS->id,
            'role' => 'PTK',
            'email' => 'ptk.jts.reg@test.com',
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'ADMIN',
            'email' => 'admin.reg@test.com',
        ]);

        $this->ewsService = app(EarlyWarningService::class);

        RuleConfig::updateOrCreate(['rule_code' => 'EWS-01'], [
            'rule_name' => 'Saldo Tersedia Menipis',
            'parameters' => ['threshold_ratio' => 0.20, 'warning_ratio' => 0.20, 'critical_ratio' => 0.05, 'operator' => '<='],
            'is_active' => true,
        ]);
    }

    /**
     * Uji Regresi Tambahan: Koreksi nominal transaksi yang meningkatkan beban anggaran dan menurunkan saldo
     * RBC mencatat koreksi (RBC-05) dan EWS otomatis mendeteksi rasio saldo menipis (EWS-01)
     */
    public function test_additional_koreksi_transaksi_memperberat_beban_dan_memicu_ews01(): void
    {
        // Catat DP 50M -> S = 50M (q = 50% > 20% -> belum terpicu)
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Honor Tim Evaluasi',
            'evidence_number' => 'BKT-REG-01',
            'transaction_date' => '2026-05-01',
            'amount' => 50000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        $this->assertNull(EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first());

        // Koreksi nominal naik menjadi 85M: S turun ke 15M (q = 15% <= 20%)
        BudgetControlService::correctTransaction($sub, 85000000.00, null, $this->ptkJts, 'Koreksi volume kegiatan');

        $bFresh = $this->bucket->fresh();
        $this->assertEquals(15000000.00, (float) $bFresh->available_balance);

        // EWS-01 otomatis terpicu via integration hook
        $w = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $this->assertNotNull($w);
        $this->assertEquals(15.0, (float) $w->current_value);
    }

    /**
     * Uji Regresi Tambahan: Pembatalan transaksi (CANCELLED) memulihkan saldo dan menyelesaikan EWS-01
     */
    public function test_additional_pembatalan_transaksi_memulihkan_saldo_dan_menyelesaikan_ews01(): void
    {
        // Catat DP 85M -> S = 15M (q = 15% <= 20% -> EWS-01 terpicu)
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Pengadaan Bahan Mendesak',
            'evidence_number' => 'BKT-REG-02',
            'transaction_date' => '2026-05-01',
            'amount' => 85000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        $w = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $this->assertNotNull($w);
        $this->assertEquals('ACTIVE', $w->status);

        // Batalkan transaksi
        BudgetControlService::transitionStatus($sub, 'CANCELLED', $this->ptkJts, 'Pembatalan pengadaan');

        $bFresh = $this->bucket->fresh();
        $this->assertEquals(100000000.00, (float) $bFresh->available_balance);

        // EWS-01 otomatis terselesaikan (RESOLVED) karena saldo kembali aman (q = 100%)
        $wFresh = $w->fresh();
        $this->assertEquals('RESOLVED', $wFresh->status);
    }
}
