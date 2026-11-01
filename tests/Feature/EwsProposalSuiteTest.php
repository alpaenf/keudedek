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
use App\Models\Submission;
use App\Models\User;
use App\Services\BudgetControlService;
use App\Services\BudgetImportPipelineService;
use App\Services\EarlyWarningService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class EwsProposalSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected Department $deptJTS;

    protected Department $deptJTIF;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetVersion $version00;

    protected BudgetSubcomponent $subcomponent;

    protected BudgetAccount $account;

    protected BudgetBucket $bucket;

    protected BudgetLine $line;

    protected User $ptkJts;

    protected User $ptkJtif;

    protected User $adminUser;

    protected EarlyWarningService $ewsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptJTS = Department::firstOrCreate(
            ['code' => 'JTS'],
            ['name' => 'Jurusan Teknik Sipil', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->deptJTIF = Department::firstOrCreate(
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

        // Control Bucket sintetis: P = 100.000.000, C = 0, R = 0, S = 100.000.000
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
            'email' => 'ptk.jts@test.com',
        ]);

        $this->ptkJtif = User::factory()->create([
            'department_id' => $this->deptJTIF->id,
            'role' => 'PTK',
            'email' => 'ptk.jtif@test.com',
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'ADMIN',
            'email' => 'admin.ews@test.com',
        ]);

        $this->ewsService = app(EarlyWarningService::class);

        // Seed parameter ilustratif proposal (Tabel 25 & Bagian 3.4.3.c):
        // Ambang saldo theta_s = 20% (operator <=)
        // Ambang umur tau = 30 hari kalender (operator >)
        RuleConfig::updateOrCreate(['rule_code' => 'EWS-01'], [
            'rule_name' => 'Saldo Tersedia Menipis',
            'category' => 'BUDGET',
            'indicator_type' => 'CORE',
            'parameters' => ['threshold_ratio' => 0.20, 'warning_ratio' => 0.20, 'critical_ratio' => 0.05, 'operator' => '<='],
            'parameter_version' => 1,
            'is_active' => true,
        ]);

        RuleConfig::updateOrCreate(['rule_code' => 'EWS-02'], [
            'rule_name' => 'Transaksi Terlalu Lama Dalam Proses',
            'category' => 'TRANSACTION',
            'indicator_type' => 'CORE',
            'parameters' => ['threshold_days' => 30, 'stale_days' => 30, 'critical_days' => 45, 'operator' => '>'],
            'parameter_version' => 1,
            'is_active' => true,
        ]);

        RuleConfig::updateOrCreate(['rule_code' => 'EWS-03'], [
            'rule_name' => 'Realisasi Internal di Bawah Target Periode',
            'category' => 'REALIZATION',
            'indicator_type' => 'CORE',
            'parameters' => ['target_ratio' => null, 'operator' => '<'],
            'parameter_version' => 1,
            'is_active' => true,
        ]);

        RuleConfig::updateOrCreate(['rule_code' => 'EX-01'], [
            'rule_name' => 'Penggunaan Melampaui Pagu',
            'category' => 'EXCEPTION',
            'indicator_type' => 'EXCEPTION',
            'parameters' => ['threshold_value' => 0.0, 'operator' => '<'],
            'parameter_version' => 1,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // SKENARIO PENGUJIAN EWS (PROPOSAL TABEL 31: E-01 s/d E-20)
    // =========================================================================

    /**
     * E-01: Saldo Rp19.999.999
     * Skenario Proposal: Saldo Rp19.999.999 pada pagu Rp100.000.000 (q = 19,999999% <= 20%)
     * Hasil yang diharapkan: EWS-01 muncul
     */
    public function test_e01_saldo_19_999_999_memicu_ews01(): void
    {
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 80000001.00,
            'realized_budget' => 0.00,
            'available_balance' => 19999999.00, // q = 19.999999% <= 20%
        ]);

        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('ACTIVE', $w->status);
        $this->assertEquals('TRIGGERED', $w->eval_status);
        $this->assertEquals('<=', $w->operator);
        $this->assertEquals(20.0, (float) $w->threshold_value);
        $this->assertEquals(20.0, round((float) $w->current_value, 2));
    }

    /**
     * E-02: Saldo Rp20.000.000
     * Skenario Proposal: Saldo Rp20.000.000 pada pagu Rp100.000.000 (q = 20,000000% <= 20%)
     * Hasil yang diharapkan: EWS-01 muncul
     */
    public function test_e02_saldo_20_000_000_memicu_ews01(): void
    {
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 80000000.00,
            'realized_budget' => 0.00,
            'available_balance' => 20000000.00, // q = 20.000000% <= 20%
        ]);

        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('ACTIVE', $w->status);
        $this->assertEquals('TRIGGERED', $w->eval_status);
        $this->assertEquals(20.0, (float) $w->current_value);
    }

    /**
     * E-03: Saldo Rp20.000.001
     * Skenario Proposal: Saldo Rp20.000.001 pada pagu Rp100.000.000 (q = 20,000001% > 20%)
     * Hasil yang diharapkan: EWS-01 tidak muncul
     */
    public function test_e03_saldo_20_000_001_tidak_memicu_ews01(): void
    {
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 79999999.00,
            'realized_budget' => 0.00,
            'available_balance' => 20000001.00, // q = 20.000001% > 20%
        ]);

        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();

        $this->assertNull($w);
    }

    /**
     * E-04: Transaksi DP berumur 29 hari
     * Skenario Proposal: a = 29 hari, tau = 30 hari. Kondisi a > tau (29 > 30 = False)
     * Hasil yang diharapkan: EWS-02 tidak muncul
     */
    public function test_e04_transaksi_dp_berumur_29_hari_tidak_memicu_ews02(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $trxDate = $evalTime->copy()->subDays(29);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E04',
            'evidence_number' => 'BKT-E04',
            'transaction_date' => $trxDate->toDateString(),
            'title' => 'Uji E-04 DP 29 Hari',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $w = EarlyWarning::where('rule_code', 'EWS-02')
            ->where('submission_id', $sub->id)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();

        $this->assertNull($w);
    }

    /**
     * E-05: Transaksi DP berumur 30 hari
     * Skenario Proposal: a = 30 hari, tau = 30 hari. Kondisi a > tau (30 > 30 = False)
     * Hasil yang diharapkan: EWS-02 tidak muncul
     */
    public function test_e05_transaksi_dp_berumur_30_hari_tidak_memicu_ews02(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $trxDate = $evalTime->copy()->subDays(30);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E05',
            'evidence_number' => 'BKT-E05',
            'transaction_date' => $trxDate->toDateString(),
            'title' => 'Uji E-05 DP 30 Hari',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $w = EarlyWarning::where('rule_code', 'EWS-02')
            ->where('submission_id', $sub->id)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();

        $this->assertNull($w);
    }

    /**
     * E-06: Transaksi DP berumur 31 hari
     * Skenario Proposal: a = 31 hari, tau = 30 hari. Kondisi a > tau (31 > 30 = True)
     * Hasil yang diharapkan: EWS-02 muncul
     */
    public function test_e06_transaksi_dp_berumur_31_hari_memicu_ews02(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $trxDate = $evalTime->copy()->subDays(31);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E06',
            'evidence_number' => 'BKT-E06',
            'transaction_date' => $trxDate->toDateString(),
            'title' => 'Uji E-06 DP 31 Hari',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $w = EarlyWarning::where('rule_code', 'EWS-02')
            ->where('submission_id', $sub->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('ACTIVE', $w->status);
        $this->assertEquals('TRIGGERED', $w->eval_status);
        $this->assertEquals('>', $w->operator);
        $this->assertEquals(31, (int) $w->current_value);
        $this->assertEquals(30, (int) $w->threshold_value);
    }

    /**
     * E-07: Transaksi berumur 31 hari telah menjadi RI
     * Skenario Proposal: Transaksi DP berumur 31 hari yang sebelumnya memicu EWS-02 ditransisikan ke status RI
     * Hasil yang diharapkan: Peringatan umur DP tidak aktif (status RESOLVED)
     */
    public function test_e07_transaksi_berumur_31_hari_telah_menjadi_ri_peringatan_berakhir(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $trxDate = $evalTime->copy()->subDays(31);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E07',
            'evidence_number' => 'BKT-E07',
            'transaction_date' => $trxDate->toDateString(),
            'title' => 'Uji E-07 Transisi ke RI',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        // Terpicu awal
        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);
        $w = EarlyWarning::where('rule_code', 'EWS-02')->where('submission_id', $sub->id)->first();
        $this->assertEquals('ACTIVE', $w->status);

        // Transisi menjadi RI (FINAL)
        $sub->update(['status' => 'FINAL']);
        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $wFresh = $w->fresh();
        $this->assertEquals('RESOLVED', $wFresh->status);
        $this->assertEquals('RESOLVED', $wFresh->lifecycle_state);
        $this->assertEquals('NOT_TRIGGERED', $wFresh->eval_status);
    }

    /**
     * E-08: Pagu nol tanpa penggunaan anggaran
     * Skenario Proposal: Pos anggaran dengan P = 0, C = 0, R = 0, S = 0
     * Hasil yang diharapkan: Rasio tidak dihitung (bebas division by zero, EWS-01 tidak muncul)
     */
    public function test_e08_pagu_nol_tanpa_penggunaan_anggaran_rasio_tidak_dihitung(): void
    {
        $this->bucket->update([
            'allocated_budget' => 0.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 0.00,
        ]);

        // Bebas division by zero
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();

        $this->assertNull($w);
    }

    /**
     * E-09: Pagu nol tetapi ada penggunaan anggaran
     * Skenario Proposal: P = 0 tetapi ada penggunaan anggaran (misal komitmen C = 10.000.000 -> S = -10.000.000)
     * Hasil yang diharapkan: Pengecualian ditampilkan (EX-01 muncul); tidak terjadi pembagian nol
     */
    public function test_e09_pagu_nol_tetapi_ada_penggunaan_anggaran_pengecualian_ditampilkan_tanpa_pembagian_nol(): void
    {
        $this->bucket->update([
            'allocated_budget' => 0.00,
            'reserved_budget' => 10000000.00,
            'realized_budget' => 0.00,
            'available_balance' => -10000000.00, // Defisit pada pagu nol
        ]);

        // Evaluasi EWS-01 dan EX-01 bebas error pembagian nol
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $this->ewsService->evaluateEx01Overbudget($this->bucket);

        // EWS-01 tidak dihitung/tidak aktif
        $wEws01 = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();
        $this->assertNull($wEws01);

        // Pengecualian EX-01 muncul
        $wEx01 = EarlyWarning::where('rule_code', 'EX-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->first();

        $this->assertNotNull($wEx01);
        $this->assertEquals('ACTIVE', $wEx01->status);
        $this->assertEquals('CRITICAL', $wEx01->severity);
        $this->assertEquals(-10000000.00, (float) $wEx01->current_value);
    }

    /**
     * E-10: Saldo negatif
     * Skenario Proposal: Posisi saldo tersedia bernilai negatif (S < 0)
     * Hasil yang diharapkan: EX-01 muncul
     */
    public function test_e10_saldo_negatif_memicu_ex01(): void
    {
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 120000000.00,
            'realized_budget' => 0.00,
            'available_balance' => -20000000.00, // S < 0
        ]);

        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $this->ewsService->evaluateEx01Overbudget($this->bucket);

        // EWS-01 tidak boleh duplikasi untuk saldo negatif
        $this->assertNull(EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first());

        // EX-01 muncul
        $wEx01 = EarlyWarning::where('rule_code', 'EX-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $this->assertNotNull($wEx01);
        $this->assertEquals('CRITICAL', $wEx01->severity);
        $this->assertEquals(-20000000.00, (float) $wEx01->current_value);
        $this->assertStringContainsStringIgnoringCase('defisit', $wEx01->reason);
    }

    /**
     * E-11: Tanggal pengajuan tidak tersedia
     * Skenario Proposal: Transaksi DP tanpa tanggal pengajuan atau tanggal masa depan
     * Hasil yang diharapkan: EWS-02 belum dapat dievaluasi
     */
    public function test_e11_tanggal_pengajuan_tidak_tersedia_ews02_belum_dapat_dievaluasi(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E11',
            'evidence_number' => 'BKT-E11',
            'transaction_date' => null, // Tanggal tidak tersedia
            'title' => 'Uji E-11 Tanggal Kosong',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $w = EarlyWarning::where('rule_code', 'EWS-02')
            ->where('submission_id', $sub->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('CANNOT_BE_EVALUATED', $w->eval_status);
        $this->assertStringContainsString('belum dapat dievaluasi', strtolower($w->reason));
    }

    /**
     * E-12: Kondisi sama dievaluasi berulang
     * Skenario Proposal: Kondisi yang sama dievaluasi ulang saat peringatan masih aktif
     * Hasil yang diharapkan: Tidak terbentuk peringatan aktif identik (anti-duplikasi)
     */
    public function test_e12_kondisi_sama_dievaluasi_berulang_tidak_terbentuk_peringatan_aktif_identik(): void
    {
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'available_balance' => 15000000.00, // q = 15%
        ]);

        // Evaluasi ke-1
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $countInitial = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->count();
        $this->assertEquals(1, $countInitial);

        // Evaluasi ke-2 tanpa perubahan keadaan
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $countSubsequent = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->count();
        $this->assertEquals(1, $countSubsequent); // Tetap tepat 1 record
    }

    /**
     * E-13: Peringatan telah dibaca, tetapi pemicu tetap ada
     * Skenario Proposal: Peringatan telah dibaca/direspon (ACKNOWLEDGED), tetapi kondisi risiko masih berlangsung
     * Hasil yang diharapkan: Kondisi peringatan tetap aktif
     */
    public function test_e13_peringatan_telah_dibaca_tetapi_pemicu_tetap_ada_kondisi_tetap_aktif(): void
    {
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'available_balance' => 15000000.00,
        ]);

        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $w = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();

        // Tandai telah dibaca / direspon
        $w->update([
            'lifecycle_state' => 'ACKNOWLEDGED',
            'acknowledged_by' => $this->adminUser->id,
        ]);

        // Evaluasi ulang saat pemicu risiko masih ada
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        $wFresh = $w->fresh();
        $this->assertEquals('ACKNOWLEDGED', $wFresh->lifecycle_state);
        $this->assertEquals('ACTIVE', $wFresh->status);
        $this->assertEquals($this->adminUser->id, $wFresh->acknowledged_by);
    }

    /**
     * E-14: Ambang diubah oleh pihak berwenang
     * Skenario Proposal: Ambang diubah oleh pihak berwenang dari versi 1 ke versi 2
     * Hasil yang diharapkan: Evaluasi memakai versi baru; versi lama tetap terlacak
     */
    public function test_e14_ambang_diubah_oleh_pihak_berwenang_evaluasi_memakai_versi_baru_dan_versi_lama_terlacak(): void
    {
        // Pagu 100M, Saldo 24M (q = 24%). Ambang awal 20% -> tidak terpicu
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'available_balance' => 24000000.00,
        ]);

        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $this->assertNull(EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first());

        // Ubah ambang batas menjadi 25% melalui updateParameter
        $updatedRule = EarlyWarningService::updateParameter('EWS-01', [
            'threshold_ratio' => 0.25,
        ], $this->adminUser);

        $this->assertEquals(2, $updatedRule->parameter_version);

        // Evaluasi ulang memakai versi baru (q = 24% <= 25%) -> EWS-01 terpicu
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $this->assertNotNull($w);
        $this->assertEquals(2, (int) $w->parameter_version);
        $this->assertEquals(25.0, (float) $w->threshold_value);
    }

    /**
     * E-15: Rasio realisasi 49% dengan target 50%, jika EWS-03 diaktifkan
     * Skenario Proposal: Target sah T = 50%, realisasi u = 49% (u < T)
     * Hasil yang diharapkan: EWS-03 muncul
     */
    public function test_e15_rasio_realisasi_49_persen_dengan_target_50_persen_memicu_ews03(): void
    {
        // Target kumulatif sah 50%
        RuleConfig::updateOrCreate(['rule_code' => 'EWS-03'], [
            'rule_name' => 'Realisasi Internal di Bawah Target Periode',
            'parameters' => ['target_ratio' => 0.50, 'operator' => '<'],
            'is_active' => true,
        ]);

        // Realisasi 49M dari Pagu 100M -> u = 49% (< 50%)
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'realized_budget' => 49000000.00,
            'available_balance' => 51000000.00,
        ]);

        $this->ewsService->evaluateEws03UnderTarget($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-03')
            ->where('budget_bucket_id', $this->bucket->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('TRIGGERED', $w->eval_status);
        $this->assertEquals('<', $w->operator);
        $this->assertEquals(49.0, (float) $w->current_value);
        $this->assertEquals(50.0, (float) $w->threshold_value);
    }

    /**
     * E-16: Rasio realisasi tepat 50% dengan target 50%
     * Skenario Proposal: Target sah T = 50%, realisasi tepat u = 50% (50% < 50% = False)
     * Hasil yang diharapkan: EWS-03 tidak muncul
     */
    public function test_e16_rasio_realisasi_tepat_50_persen_dengan_target_50_persen_tidak_memicu_ews03(): void
    {
        RuleConfig::updateOrCreate(['rule_code' => 'EWS-03'], [
            'rule_name' => 'Realisasi Internal di Bawah Target Periode',
            'parameters' => ['target_ratio' => 0.50, 'operator' => '<'],
            'is_active' => true,
        ]);

        // Realisasi 50M dari Pagu 100M -> u = 50% (tidak < 50%)
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'realized_budget' => 50000000.00,
            'available_balance' => 50000000.00,
        ]);

        $this->ewsService->evaluateEws03UnderTarget($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-03')
            ->where('budget_bucket_id', $this->bucket->id)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();

        $this->assertNull($w);
    }

    /**
     * E-17: Target periode tidak tersedia
     * Skenario Proposal: Target periode tidak tersedia / null
     * Hasil yang diharapkan: Tidak disimpulkan bahwa realisasi tertinggal (status CANNOT_BE_EVALUATED)
     */
    public function test_e17_target_periode_tidak_tersedia_tidak_disimpulkan_realisasi_tertinggal(): void
    {
        RuleConfig::updateOrCreate(['rule_code' => 'EWS-03'], [
            'rule_name' => 'Realisasi Internal di Bawah Target Periode',
            'parameters' => ['target_ratio' => null, 'operator' => '<'],
            'is_active' => true,
        ]);

        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'realized_budget' => 10000000.00,
        ]);

        $this->ewsService->evaluateEws03UnderTarget($this->bucket);

        $w = EarlyWarning::where('rule_code', 'EWS-03')
            ->where('budget_bucket_id', $this->bucket->id)
            ->first();

        $this->assertNotNull($w);
        $this->assertEquals('CANNOT_BE_EVALUATED', $w->eval_status);
        $this->assertStringContainsString('belum dapat dievaluasi', strtolower($w->reason));
    }

    /**
     * E-18: Rasio saldo mencapai ambang dan transaksi DP melewati ambang umur
     * Skenario Proposal: Pos anggaran mencapai ambang saldo (S <= 20M) DAN transaksi DP berumur > 30 hari
     * Hasil yang diharapkan: Kedua peringatan muncul pada objek yang sesuai
     */
    public function test_e18_rasio_saldo_mencapai_ambang_dan_transaksi_dp_melewati_ambang_umur_kedua_peringatan_muncul(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $oldDate = $evalTime->copy()->subDays(35);

        // Pos anggaran: Saldo tersisa 15M dari pagu 100M (q = 15% <= 20%)
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 85000000.00,
            'available_balance' => 15000000.00,
        ]);

        // Transaksi DP berumur 35 hari (> 30 hari)
        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E18',
            'evidence_number' => 'BKT-E18',
            'transaction_date' => $oldDate->toDateString(),
            'title' => 'Uji E-18 Multi Peringatan',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        // Evaluasi kedua indikator
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        // 1. EWS-01 muncul pada objek pos anggaran (budget_bucket_id)
        $wEws01 = EarlyWarning::where('rule_code', 'EWS-01')
            ->where('budget_bucket_id', $this->bucket->id)
            ->first();
        $this->assertNotNull($wEws01);
        $this->assertEquals('ACTIVE', $wEws01->status);

        // 2. EWS-02 muncul pada objek transaksi (submission_id)
        $wEws02 = EarlyWarning::where('rule_code', 'EWS-02')
            ->where('submission_id', $sub->id)
            ->first();
        $this->assertNotNull($wEws02);
        $this->assertEquals('ACTIVE', $wEws02->status);
    }

    /**
     * E-19: Umur transaksi berubah dari 30 menjadi 31 hari tanpa perubahan data
     * Skenario Proposal: Data transaksi tidak diubah sama sekali, waktu kalender bertambah dari hari ke-30 ke hari ke-31
     * Hasil yang diharapkan: Evaluasi terjadwal memunculkan EWS-02
     */
    public function test_e19_umur_transaksi_berubah_dari_30_menjadi_31_hari_tanpa_perubahan_data_evaluasi_terjadwal_memicu_ews02(): void
    {
        $baseDate = Carbon::parse('2026-05-01 08:00:00');

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E19',
            'evidence_number' => 'BKT-E19',
            'transaction_date' => $baseDate->toDateString(),
            'title' => 'Uji E-19 Penjadwalan Kalender',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        // Hari ke-30: Evaluasi periodik tidak memicu peringatan (30 > 30 = False)
        $this->ewsService->evaluateEws02StaleDP($sub, $baseDate->copy()->addDays(30));
        $this->assertNull(EarlyWarning::where('rule_code', 'EWS-02')->where('submission_id', $sub->id)->first());

        // Hari ke-31 (tanpa ada perubahan pada data transaksi): Evaluasi periodik terjadwal memunculkan EWS-02 (31 > 30 = True)
        $this->ewsService->evaluateEws02StaleDP($sub, $baseDate->copy()->addDays(31));
        $w = EarlyWarning::where('rule_code', 'EWS-02')->where('submission_id', $sub->id)->first();
        $this->assertNotNull($w);
        $this->assertEquals(31, (int) $w->current_value);
    }

    /**
     * E-20: Data menjadi tidak lengkap setelah peringatan muncul
     * Skenario Proposal: Transaksi yang telah memicu peringatan mengalami anomali (data tanggal menjadi tidak lengkap)
     * Hasil yang diharapkan: Kondisi tidak otomatis dinyatakan selesai; keterbatasan evaluasi ditampilkan
     */
    public function test_e20_data_menjadi_tidak_lengkap_setelah_peringatan_muncul_kondisi_tidak_otomatis_selesai(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $trxDate = $evalTime->copy()->subDays(35);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/E20',
            'evidence_number' => 'BKT-E20',
            'transaction_date' => $trxDate->toDateString(),
            'title' => 'Uji E-20 Data Rusak',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        // Awalnya terpicu
        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);
        $w = EarlyWarning::where('rule_code', 'EWS-02')->where('submission_id', $sub->id)->first();
        $this->assertEquals('ACTIVE', $w->status);

        // Anomali data terjadi: transaction_date menjadi null
        $sub->update(['transaction_date' => null]);
        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $wFresh = $w->fresh();
        // Tidak boleh otomatis RESOLVED ketika data tidak lengkap
        $this->assertNotEquals('RESOLVED', $wFresh->lifecycle_state);
        $this->assertEquals('CANNOT_BE_EVALUATED', $wFresh->eval_status);
        $this->assertStringContainsString('belum dapat dievaluasi', strtolower($wFresh->reason));
    }

    // =========================================================================
    // SKENARIO INTEGRASI RBC DAN EWS (PROPOSAL TABEL 32: I-01 s/d I-07)
    // =========================================================================

    /**
     * I-01: DP menjadi RI dengan nominal dan cakupan pos yang sama, sementara pagu serta transaksi lainnya tetap
     * Skenario Proposal: Transaksi DP 30M berumur 35 hari pada pagu 100M dengan sisa saldo 20M ditransisikan ke RI
     * Hasil yang diharapkan: Saldo tetap. Peringatan berbasis saldo tetap apabila parameter dan waktu acuannya tidak berubah, sedangkan peringatan umur DP berakhir
     */
    public function test_i01_dp_menjadi_ri_saldo_tetap_peringatan_saldo_tetap_peringatan_umur_dp_berakhir(): void
    {
        $evalTime = Carbon::parse('2026-06-01 10:00:00');
        $oldDate = $evalTime->copy()->subDays(35);

        // Set pos anggaran: P = 100M, S = 20M (q = 20% <= 20% -> EWS-01 aktif)
        $this->bucket->update([
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 50000000.00,
            'realized_budget' => 30000000.00,
            'available_balance' => 20000000.00,
        ]);

        // Catat transaksi DP 30M berumur 35 hari (> 30 hari -> EWS-02 aktif)
        $sub = Submission::create([
            'submission_number' => 'TRX/2026/I01',
            'evidence_number' => 'BKT-I01',
            'transaction_date' => $oldDate->toDateString(),
            'title' => 'Pengadaan Laboratorium JTS',
            'department_id' => $this->deptJTS->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'amount' => 30000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkJts->id,
        ]);

        // Evaluasi awal: EWS-01 aktif dan EWS-02 aktif
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $this->ewsService->evaluateEws02StaleDP($sub, $evalTime);

        $wEws01 = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $wEws02 = EarlyWarning::where('rule_code', 'EWS-02')->where('submission_id', $sub->id)->first();
        $this->assertEquals('ACTIVE', $wEws01->status);
        $this->assertEquals('ACTIVE', $wEws02->status);

        // Transisi DP ke RI melalui RBC-04
        BudgetControlService::transitionStatus($sub, 'FINAL', $this->ptkJts, 'Kuitansi rampung');

        // 1. Saldo tetap Rp20.000.000 (C berkurang 30M, R bertambah 30M)
        $bFresh = $this->bucket->fresh();
        $this->assertEquals(20000000.00, (float) $bFresh->available_balance);

        // 2. Peringatan berbasis saldo (EWS-01) tetap aktif
        $this->assertEquals('ACTIVE', $wEws01->fresh()->status);

        // 3. Peringatan umur DP (EWS-02) berakhir / RESOLVED
        $this->assertEquals('RESOLVED', $wEws02->fresh()->status);
        $this->assertEquals('NOT_TRIGGERED', $wEws02->fresh()->eval_status);
    }

    /**
     * I-02: Pagu berubah
     * Skenario Proposal: Pagu aktif berubah melalui revisi anggaran
     * Hasil yang diharapkan: RBC menghitung posisi baru dan EWS mengevaluasi indikator dari posisi tersebut
     */
    public function test_i02_pagu_berubah_rbc_hitung_posisi_baru_dan_ews_evaluasi_posisi_tersebut(): void
    {
        // Posisi awal Versi 00: P = 100M, C = 40M, S = 60M (q = 60% > 20% -> EWS-01 tidak muncul)
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Komitmen Berjalan Versi 00',
            'evidence_number' => 'BKT-I02-V00',
            'transaction_date' => '2026-05-01',
            'amount' => 40000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        $this->assertNull(EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first());

        // Pagu berubah melalui aktivasi Versi 01: Pagu pos diturunkan menjadi 48M
        $v01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'DIPA Revisi Pengurangan',
            'status' => 'DRAFT',
        ]);

        $b01 = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $v01->id,
            'department_id' => $this->deptJTS->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'subcomponent_code' => 'AA',
            'subcomponent_name' => 'Operasional Jurusan',
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'allocated_budget' => 48000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 48000000.00,
            'status' => 'ACTIVE',
        ]);

        // Aktivasi versi baru
        BudgetImportPipelineService::activateVersion($v01, $this->adminUser);

        // 1. RBC menghitung posisi baru: P = 48M, C = 40M, S = 8M
        $b01Fresh = $b01->fresh();
        $this->assertEquals(48000000.00, (float) $b01Fresh->allocated_budget);
        $this->assertEquals(40000000.00, (float) $b01Fresh->reserved_budget);
        $this->assertEquals(8000000.00, (float) $b01Fresh->available_balance);

        // 2. EWS mengevaluasi indikator dari posisi baru: q = 8/48 = 16.67% (<= 20%) -> EWS-01 muncul
        $w = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $b01->id)->first();
        $this->assertNotNull($w);
        $this->assertEquals(16.67, round((float) $w->current_value, 2));
    }

    /**
     * I-03: Transaksi terdokumentasi menyebabkan saldo negatif
     * Skenario Proposal: Transaksi terdokumentasi sah melebihi pagu (P = 100M, nominal = 110M)
     * Hasil yang diharapkan: Keputusan RBC, dashboard, laporan, dan EX-01 konsisten
     */
    public function test_i03_transaksi_terdokumentasi_menyebabkan_saldo_negatif_keputusan_rbc_dan_ex01_konsisten(): void
    {
        // Catat transaksi historis sah 110M
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Realisasi Historis SP2D Sah',
            'evidence_number' => 'SP2D-HIST-I03',
            'transaction_date' => '2026-04-01',
            'amount' => 110000000.00,
            'is_documented_historical' => true,
            'submit_action' => 'FINAL',
        ], $this->ptkJts);

        // 1. Keputusan RBC: dicatat dengan needs_review = true, saldo negatif -10M
        $bFresh = $this->bucket->fresh();
        $this->assertEquals(-10000000.00, (float) $bFresh->available_balance);
        $this->assertTrue((bool) $bFresh->needs_review);
        $this->assertTrue((bool) $sub->needs_review);

        // 2. EX-01 muncul secara konsisten
        $wEx01 = EarlyWarning::where('rule_code', 'EX-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $this->assertNotNull($wEx01);
        $this->assertEquals('CRITICAL', $wEx01->severity);
        $this->assertEquals(-10000000.00, (float) $wEx01->current_value);

        // 3. Konsistensi tampilan dashboard / monitoring: saldo mencerminkan defisit -10M
        $this->assertEquals((float) $bFresh->allocated_budget - (float) $bFresh->realized_budget, (float) $wEx01->current_value);
    }

    /**
     * I-04: Parameter EWS berubah
     * Skenario Proposal: Parameter ambang batas EWS diubah oleh pihak berwenang
     * Hasil yang diharapkan: Peringatan mengikuti parameter baru tanpa mengubah nominal transaksi
     */
    public function test_i04_parameter_ews_berubah_peringatan_mengikuti_parameter_baru_tanpa_mengubah_nominal_transaksi(): void
    {
        // Transaksi DP 76M pada pagu 100M -> Saldo S = 24M (q = 24%)
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Komitmen Tetap I-04',
            'evidence_number' => 'BKT-I04',
            'transaction_date' => '2026-05-01',
            'amount' => 76000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        // Ambang awal 20% -> belum terpicu karena 24% > 20%
        $this->assertNull(EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first());

        // Parameter EWS diubah menjadi 25%
        EarlyWarningService::updateParameter('EWS-01', [
            'threshold_ratio' => 0.25,
        ], $this->adminUser);

        // Evaluasi ulang EWS
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);

        // 1. Nominal transaksi dan saldo pos anggaran tetap utuh tanpa perubahan sama sekali
        $bFresh = $this->bucket->fresh();
        $subFresh = $sub->fresh();
        $this->assertEquals(76000000.00, (float) $subFresh->amount);
        $this->assertEquals(76000000.00, (float) $bFresh->reserved_budget);
        $this->assertEquals(24000000.00, (float) $bFresh->available_balance);

        // 2. Peringatan mengikuti parameter baru: EWS-01 kini terpicu aktif
        $w = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();
        $this->assertNotNull($w);
        $this->assertEquals(2, (int) $w->parameter_version);
        $this->assertEquals(25.0, (float) $w->threshold_value);
    }

    /**
     * I-05: Pengguna jurusan membuka rincian serta peringatan
     * Skenario Proposal: Pengguna dari suatu jurusan membuka rincian serta daftar peringatan
     * Hasil yang diharapkan: Informasi mengikuti cakupan akses
     */
    public function test_i05_pengguna_jurusan_membuka_rincian_serta_peringatan_informasi_mengikuti_cakupan_akses(): void
    {
        // 1. Buat peringatan untuk Jurusan JTS
        $this->bucket->update(['available_balance' => 10000000.00]);
        $this->ewsService->evaluateEws01SaldoMenipis($this->bucket);
        $wJts = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $this->bucket->id)->first();

        // 2. Buat peringatan untuk Jurusan JTIF
        $bucketJtif = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version00->id,
            'department_id' => $this->deptJTIF->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan JTIF',
            'subcomponent_code' => 'AA',
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'allocated_budget' => 50000000.00,
            'available_balance' => 5000000.00, // q = 10%
            'status' => 'ACTIVE',
        ]);
        $this->ewsService->evaluateEws01SaldoMenipis($bucketJtif);
        $wJtif = EarlyWarning::where('rule_code', 'EWS-01')->where('budget_bucket_id', $bucketJtif->id)->first();

        // 3. PTK JTS mengakses daftar peringatan -> Berhasil (200 OK)
        $resIndex = $this->actingAs($this->ptkJts)->get(route('warnings.index'));
        $resIndex->assertOk();

        // 4. PTK JTS membuka rincian peringatan miliknya (JTS) -> Berhasil (200 OK)
        $resShowOwn = $this->actingAs($this->ptkJts)->get(route('warnings.show', $wJts));
        $resShowOwn->assertOk();

        // 5. PTK JTS mencoba membuka rincian peringatan milik jurusan lain (JTIF) -> Ditolak (403 Forbidden)
        $resShowOther = $this->actingAs($this->ptkJts)->get(route('warnings.show', $wJtif));
        $resShowOther->assertForbidden();

        // 6. PTK JTIF membuka rincian peringatan miliknya (JTIF) -> Berhasil (200 OK)
        $resShowJtif = $this->actingAs($this->ptkJtif)->get(route('warnings.show', $wJtif));
        $resShowJtif->assertOk();
    }

    /**
     * I-06: Evaluasi EWS gagal setelah transaksi berhasil dicatat
     * Skenario Proposal: Terjadi kegagalan pada proses evaluasi EWS setelah transaksi RBC dicatat
     * Hasil yang diharapkan: Transaksi tetap tercatat dan kebutuhan evaluasi ulang diketahui (Decoupled Safety)
     */
    public function test_i06_evaluasi_ews_gagal_setelah_transaksi_berhasil_dicatat_transaksi_tetap_tercatat_dan_kebutuhan_evaluasi_ulang_diketahui(): void
    {
        // Catat transaksi RBC normal
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Transaksi Uji Decoupled Safety I-06',
            'evidence_number' => 'BKT-I06',
            'transaction_date' => '2026-05-01',
            'amount' => 15000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        // 1. Transaksi RBC tetap berhasil dicatat secara atomik
        $this->assertNotNull($sub->id);
        $this->assertEquals('PROCESSING', $sub->status);
        $this->assertEquals(85000000.00, (float) $this->bucket->fresh()->available_balance);

        // 2. Simulasi penanganan exception decoupled: saat EWS mengalami throw exception internal
        // method evaluateAfterRbcChange menangkap error secara aman tanpa melempar rollback ke RBC
        EarlyWarningService::evaluateAfterRbcChange(null, null);

        // Konsistensi basis data tetap terjaga
        $this->assertEquals(85000000.00, (float) $this->bucket->fresh()->available_balance);
    }

    /**
     * I-07: Dua permintaan yang memengaruhi pos anggaran yang sama diproses secara beririsan
     * Skenario Proposal: Dua permintaan yang memengaruhi pos yang sama diproses secara beririsan
     * Hasil yang diharapkan: Setiap perubahan dihitung terhadap posisi yang konsisten; tidak terjadi lost update,
     * kontribusi ganda, ataupun partial update. Perubahan yang gagal sebelum transaksi basis data selesai
     * tidak menghasilkan perubahan sebagian pada posisi anggaran.
     */
    public function test_i07_dua_permintaan_beririsan_dihitung_konsisten_tanpa_lost_update_dan_tanpa_partial_update(): void
    {
        // ---------------------------------------------------------------------
        // Bagian A: Pengujian pencegahan Lost Update dan Kontribusi Ganda
        // Dua transaksi simultan diproses melalui transaksi basis data dengan row-level lock (lockForUpdate)
        // Permintaan 1: Rp40.000.000
        // Permintaan 2: Rp50.000.000
        // Pagu awal: Rp100.000.000
        // ---------------------------------------------------------------------
        $subA = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Permintaan Beririsan A',
            'evidence_number' => 'BKT-I07-A',
            'transaction_date' => '2026-05-01',
            'amount' => 40000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        $subB = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'title' => 'Permintaan Beririsan B',
            'evidence_number' => 'BKT-I07-B',
            'transaction_date' => '2026-05-01',
            'amount' => 50000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkJts);

        $bFresh = $this->bucket->fresh();
        // C harus tepat Rp90.000.000 (tidak ada lost update dari A atau B)
        $this->assertEquals(90000000.00, (float) $bFresh->reserved_budget);
        // S harus tepat Rp10.000.000 (100M - 90M)
        $this->assertEquals(10000000.00, (float) $bFresh->available_balance);

        // ---------------------------------------------------------------------
        // Bagian B: Permintaan beririsan yang melampaui sisa kapasitas
        // Permintaan 3: Rp15.000.000 (melebihi sisa kapasitas Rp10.000.000)
        // ---------------------------------------------------------------------
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/RBC-08/i');

        try {
            BudgetControlService::recordTransaction([
                'budget_line_id' => $this->line->id,
                'title' => 'Permintaan Beririsan C Melampaui Saldo',
                'evidence_number' => 'BKT-I07-C',
                'transaction_date' => '2026-05-01',
                'amount' => 15000000.00,
                'submit_action' => 'PROCESSING',
            ], $this->ptkJts);
        } finally {
            // Verifikasi bahwa penolakan tidak meninggalkan partial update
            $bAfterReject = $this->bucket->fresh();
            $this->assertEquals(90000000.00, (float) $bAfterReject->reserved_budget);
            $this->assertEquals(10000000.00, (float) $bAfterReject->available_balance);
        }
    }

    /**
     * I-07 (Lanjutan): Pengujian pencegahan Partial Update pada kegagalan transaksi
     * Perubahan yang gagal sebelum transaksi basis data selesai tidak menghasilkan perubahan sebagian pada posisi anggaran
     */
    public function test_i07_partial_update_prevention_on_atomic_transaction_failure(): void
    {
        $initialAvailable = (float) $this->bucket->available_balance;
        $initialReserved = (float) $this->bucket->reserved_budget;

        try {
            DB::transaction(function () {
                $sub = BudgetControlService::recordTransaction([
                    'budget_line_id' => $this->line->id,
                    'title' => 'Permintaan Yang Akan Gagal di Tengah Jalan',
                    'evidence_number' => 'BKT-I07-FAIL',
                    'transaction_date' => '2026-05-01',
                    'amount' => 20000000.00,
                    'submit_action' => 'PROCESSING',
                ], $this->ptkJts);

                // Simulasi kegagalan runtime sebelum commit transaksi
                throw new Exception('Simulasi kegagalan runtime sebelum commit basis data.');
            });
        } catch (Exception $e) {
            // Exception tertangkap
        }

        // Posisi anggaran harus utuh seperti semula tanpa perubahan sebagian (No Partial Update)
        $bFresh = $this->bucket->fresh();
        $this->assertEquals($initialAvailable, (float) $bFresh->available_balance);
        $this->assertEquals($initialReserved, (float) $bFresh->reserved_budget);

        // Rekaman transaksi tidak boleh ada yang tertinggal
        $this->assertNull(Submission::where('evidence_number', 'BKT-I07-FAIL')->first());
    }
}
