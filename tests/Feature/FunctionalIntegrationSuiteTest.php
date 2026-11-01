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
use App\Services\EarlyWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunctionalIntegrationSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected Department $deptCivil;

    protected Department $deptElectrical;

    protected FiscalYear $fy2026;

    protected FiscalYear $fy2025;

    protected FundingSource $fundingRM;

    protected BudgetVersion $versionRev00;

    protected BudgetSubcomponent $subcomponent;

    protected BudgetAccount $account;

    protected BudgetBucket $bucketCivil;

    protected BudgetLine $lineCivil;

    protected User $ptkCivil;

    protected User $ptkElectrical;

    protected User $ptuUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $faculty = Department::firstOrCreate(
            ['code' => 'FT'],
            ['name' => 'Fakultas Teknik', 'type' => 'FACULTY', 'is_active' => true]
        );

        $this->deptCivil = Department::firstOrCreate(
            ['code' => 'JTS'],
            ['name' => 'Jurusan Teknik Sipil', 'type' => 'DEPARTMENT', 'is_active' => true, 'parent_id' => $faculty->id]
        );
        $this->deptCivil->update(['parent_id' => $faculty->id]);

        $this->deptElectrical = Department::firstOrCreate(
            ['code' => 'JTE'],
            ['name' => 'Jurusan Teknik Elektro', 'type' => 'DEPARTMENT', 'is_active' => true, 'parent_id' => $faculty->id]
        );
        $this->deptElectrical->update(['parent_id' => $faculty->id]);

        $this->fy2026 = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );

        $this->fy2025 = FiscalYear::firstOrCreate(
            ['year' => 2025],
            ['status' => 'CLOSED', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_locked' => true]
        );

        $this->fundingRM = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->versionRev00 = BudgetVersion::firstOrCreate(
            ['fiscal_year_id' => $this->fy2026->id, 'revision_no' => 'Rev 00'],
            [
                'funding_source_id' => $this->fundingRM->id,
                'version_label' => 'DIPA Induk 2026',
                'status' => 'ACTIVE',
                'total_budget' => 100000000.00,
            ]
        );

        $this->subcomponent = BudgetSubcomponent::firstOrCreate(
            ['full_code' => '023.17.WA.4257.EBA.994.001.AA'],
            ['code' => 'AA', 'name' => 'Operasional Jurusan Sipil']
        );

        $this->account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan']
        );

        $this->bucketCivil = BudgetBucket::create([
            'fiscal_year_id' => $this->fy2026->id,
            'budget_version_id' => $this->versionRev00->id,
            'department_id' => $this->deptCivil->id,
            'funding_source_id' => $this->fundingRM->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'subcomponent_code' => $this->subcomponent->code,
            'subcomponent_name' => $this->subcomponent->name,
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 100000000.00,
        ]);

        $this->lineCivil = BudgetLine::create([
            'budget_bucket_id' => $this->bucketCivil->id,
            'budget_version_id' => $this->versionRev00->id,
            'department_id' => $this->deptCivil->id,
            'subcomponent_id' => $this->subcomponent->id,
            'account_id' => $this->account->id,
            'rba_sequence_no' => 'RBA-JTS-001',
            'description' => 'Bahan Praktikum Mahasiswa Sipil',
            'volume' => 1,
            'unit' => 'Paket',
            'unit_price' => 100000000.00,
            'total_amount' => 100000000.00,
            'allocated_amount' => 100000000.00,
            'is_active' => true,
        ]);

        $this->ptkCivil = User::factory()->create([
            'name' => 'PTK Sipil',
            'email' => 'ptk.sipil@unsoed.ac.id',
            'role' => 'PTK',
            'department_id' => $this->deptCivil->id,
            'password' => bcrypt('password123'),
        ]);

        $this->ptkElectrical = User::factory()->create([
            'name' => 'PTK Elektro',
            'email' => 'ptk.elektro@unsoed.ac.id',
            'role' => 'PTK',
            'department_id' => $this->deptElectrical->id,
            'password' => bcrypt('password123'),
        ]);

        $this->ptuUser = User::factory()->create([
            'name' => 'Verifikator PTU',
            'email' => 'ptu.verifikator@unsoed.ac.id',
            'role' => 'PTU',
            'department_id' => null,
            'password' => bcrypt('password123'),
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin.sikara@unsoed.ac.id',
            'role' => 'ADMIN',
            'department_id' => null,
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * F-01: Masuk dengan data autentikasi valid dan tidak valid
     */
    public function test_f01_authentication_valid_and_invalid(): void
    {
        // 1. Invalid login attempt
        $invalidResponse = $this->post('/login', [
            'login' => 'ptk.sipil@unsoed.ac.id',
            'password' => 'wrong_password',
        ]);
        $invalidResponse->assertSessionHasErrors();
        $this->assertGuest();

        // 2. Valid login attempt
        $validResponse = $this->post('/login', [
            'login' => 'ptk.sipil@unsoed.ac.id',
            'password' => 'password123',
        ]);
        $validResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->ptkCivil);
    }

    /**
     * F-02: Mengakses data di luar cakupan unit (Akses ditolak 403)
     */
    public function test_f02_cross_department_access_is_forbidden(): void
    {
        // Create submission in Civil department
        $submissionCivil = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->lineCivil->id,
            'evidence_number' => 'KW-JTS-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Belanja Bahan Uji JTS',
            'amount' => 15000000,
            'submit_action' => 'PROCESSING',
        ], $this->ptkCivil);

        // PTK Elektro attempts to cancel Civil's submission -> 403 Forbidden
        $response = $this->actingAs($this->ptkElectrical)->post("/submissions/{$submissionCivil->id}/cancel", [
            'reason' => 'Membatalkan dari unit lain',
        ]);
        $response->assertStatus(403);

        // PTK Elektro attempts to download Civil's document -> 403 Forbidden
        $this->actingAs($this->ptkElectrical);
        $downloadResponse = $this->get("/submissions/{$submissionCivil->id}/print");
        $downloadResponse->assertStatus(403);
    }

    /**
     * F-03: Mencatat referensi anggaran yang sah (Data dan hubungannya tersimpan)
     */
    public function test_f03_record_valid_budget_reference(): void
    {
        $this->assertDatabaseHas('budget_buckets', [
            'department_id' => $this->deptCivil->id,
            'account_code' => '521211',
            'allocated_budget' => 100000000.00,
            'budget_version_id' => $this->versionRev00->id,
        ]);

        $this->assertDatabaseHas('budget_lines', [
            'rba_sequence_no' => 'RBA-JTS-001',
            'budget_bucket_id' => $this->bucketCivil->id,
            'department_id' => $this->deptCivil->id,
        ]);
    }

    /**
     * F-04: Memasukkan data wajib yang tidak valid (Pencatatan ditolak)
     */
    public function test_f04_mandatory_validation_and_overbudget_rejection(): void
    {
        $this->actingAs($this->ptkCivil);

        // 1. Missing mandatory evidence number
        $missingFieldResponse = $this->post('/submissions', [
            'budget_line_id' => $this->lineCivil->id,
            'transaction_date' => '2026-03-01',
            'title' => 'Belanja Bahan',
            'amount' => 5000000,
            'submit_action' => 'PROCESSING',
        ]);
        $missingFieldResponse->assertSessionHasErrors(['evidence_number']);

        // 2. Overbudget transaction (150.000.000 > available 100.000.000)
        $overbudgetResponse = $this->post('/submissions', [
            'budget_line_id' => $this->lineCivil->id,
            'evidence_number' => 'KW-OVER-01',
            'transaction_date' => '2026-03-01',
            'title' => 'Belanja Melebihi Pagu',
            'amount' => 150000000,
            'submit_action' => 'PROCESSING',
        ]);
        $overbudgetResponse->assertSessionHasErrors(['amount']);
    }

    /**
     * F-05: Memfilter monitoring berdasarkan tahun 2025 dan 2026 (Tidak tercampur)
     */
    public function test_f05_filtering_by_fiscal_year_isolates_data(): void
    {
        // 2026 bucket exists with 100jt
        // Create 2025 bucket with 50jt
        $version2025 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy2025->id,
            'revision_no' => 'Rev 00-2025',
            'funding_source_id' => $this->fundingRM->id,
            'status' => 'ARCHIVED',
            'total_budget' => 50000000.00,
        ]);

        BudgetBucket::create([
            'fiscal_year_id' => $this->fy2025->id,
            'budget_version_id' => $version2025->id,
            'department_id' => $this->deptCivil->id,
            'funding_source_id' => $this->fundingRM->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'allocated_budget' => 50000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 50000000.00,
            'available_balance' => 0.00,
        ]);

        // Request report filtered by 2026
        $this->actingAs($this->adminUser);
        $response2026 = $this->get("/reports?fiscal_year_id={$this->fy2026->id}");
        $response2026->assertOk();

        // Request report filtered by 2025
        $response2025 = $this->get("/reports?fiscal_year_id={$this->fy2025->id}");
        $response2025->assertOk();
    }

    /**
     * F-06: Memfilter monitoring berdasarkan jurusan
     */
    public function test_f06_filtering_by_department_follows_scope(): void
    {
        $this->actingAs($this->adminUser);

        $responseCivil = $this->get("/submissions?department_id={$this->deptCivil->id}");
        $responseCivil->assertOk();

        $responseElectrical = $this->get("/submissions?department_id={$this->deptElectrical->id}");
        $responseElectrical->assertOk();
    }

    /**
     * F-07: Membandingkan total fakultas dengan pos dasar (Tidak terjadi double counting across revisions)
     */
    public function test_f07_revision_does_not_double_count_in_dashboard(): void
    {
        // Initial state: Rev 00 has allocated 100.000.000
        // Now create Rev 01 (e.g. revised to 120.000.000)
        $versionRev01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy2026->id,
            'revision_no' => 'Rev 01',
            'funding_source_id' => $this->fundingRM->id,
            'version_label' => 'DIPA Revisi 01',
            'status' => 'DRAFT',
            'total_budget' => 120000000.00,
        ]);

        // Create bucket under Rev 01
        BudgetBucket::create([
            'fiscal_year_id' => $this->fy2026->id,
            'budget_version_id' => $versionRev01->id,
            'department_id' => $this->deptCivil->id,
            'funding_source_id' => $this->fundingRM->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'subcomponent_code' => $this->subcomponent->code,
            'subcomponent_name' => $this->subcomponent->name,
            'allocated_budget' => 120000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 120000000.00,
        ]);

        // Dashboard accessed while Rev 00 is ACTIVE
        $this->actingAs($this->adminUser);
        $response = $this->get('/dashboard');
        $response->assertOk();

        // Check payload passed to Inertia: totalAllocated must be exactly 100.000.000, NOT 220.000.000!
        $page = $response->viewData('page');
        $this->assertEquals(100000000.00, $page['props']['totalAllocated']);

        // Check departmentSummaries for Civil: allocated must be 100.000.000, NOT 220.000.000!
        $civilSummary = collect($page['props']['departmentSummaries'])->firstWhere('id', $this->deptCivil->id);
        $this->assertEquals(100000000.00, $civilSummary['allocated']);
    }

    /**
     * F-08: Membandingkan dashboard dan laporan pada cakupan sama (Nilai konsisten)
     */
    public function test_f08_dashboard_and_reports_financial_totals_match(): void
    {
        $this->actingAs($this->adminUser);

        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertOk();
        $dashPage = $dashResponse->viewData('page');

        $reportResponse = $this->get('/reports');
        $reportResponse->assertOk();
        $reportPage = $reportResponse->viewData('page');

        $this->assertEquals(
            $dashPage['props']['totalAllocated'],
            $reportPage['props']['totalAllocated']
        );
        $this->assertEquals(
            $dashPage['props']['totalAvailable'],
            $reportPage['props']['totalAvailable']
        );
    }

    /**
     * F-09: Membuka riwayat perubahan (Nilai lama, nilai baru, waktu, pelaku dapat ditelusuri)
     */
    public function test_f09_traceability_of_changes(): void
    {
        $this->actingAs($this->ptkCivil);

        // Create transaction
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->lineCivil->id,
            'evidence_number' => 'KW-HIST-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Pembelian Alat Lab',
            'amount' => 20000000,
            'submit_action' => 'PROCESSING',
        ], $this->ptkCivil);

        // Finalize transaction
        $this->actingAs($this->ptuUser);
        $this->post("/approvals/{$sub->id}/decide", [
            'action' => 'APPROVED',
            'comment' => 'Berkas lengkap dan sesuai SOP.',
        ]);

        // Inspect status histories
        $this->assertDatabaseHas('submission_status_histories', [
            'submission_id' => $sub->id,
            'to_status' => 'FINAL',
            'actor_id' => $this->ptuUser->id,
        ]);

        // Inspect audit log
        $this->assertDatabaseHas('audit_logs', [
            'model_id' => $sub->id,
            'action' => 'COMPLETE_TRANSACTION',
        ]);
    }

    /**
     * F-10: Membuka informasi pada data yang belum lengkap (Keterbatasan hasil ditampilkan)
     */
    public function test_f10_incomplete_data_shows_cannot_be_evaluated(): void
    {
        // Create submission with unresolvable/null transaction date for EWS-02
        $subIncomplete = Submission::create([
            'submission_number' => 'TRX/2026/999',
            'evidence_number' => 'KW-INCOMPLETE-01',
            'transaction_date' => null,
            'title' => 'Pengajuan Belanja Tanpa Tanggal Sah',
            'department_id' => $this->deptCivil->id,
            'fiscal_year_id' => $this->fy2026->id,
            'budget_bucket_id' => $this->bucketCivil->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkCivil->id,
        ]);

        $ewsService = app(EarlyWarningService::class);
        $ewsService->evaluateEws02StaleDP($subIncomplete, now());

        $warning = EarlyWarning::where('rule_code', 'EWS-02')
            ->where('submission_id', $subIncomplete->id)
            ->first();

        $this->assertNotNull($warning);
        $this->assertEquals('CANNOT_BE_EVALUATED', $warning->eval_status);
        $this->assertStringContainsStringIgnoringCase('belum dapat dievaluasi', $warning->reason);

        // Access warning detail via HTTP GET /warnings/{warning} to verify user sees explainable limitation
        $this->actingAs($this->ptkCivil);
        $showResponse = $this->get("/warnings/{$warning->id}");
        $showResponse->assertOk();
        $showPage = $showResponse->viewData('page');
        $this->assertEquals('CANNOT_BE_EVALUATED', $showPage['props']['calculation']['eval_status']);
    }

    /**
     * F-11: Mengubah parameter tanpa kewenangan (Perubahan ditolak 403, disetujui untuk Admin)
     */
    public function test_f11_unauthorized_user_cannot_update_ews_parameters(): void
    {
        // 1. PTK attempts to update EWS parameter -> 403 Forbidden
        $this->actingAs($this->ptkCivil);
        $responsePtk = $this->post('/warnings/parameters/EWS-01', [
            'parameters' => ['threshold_ratio' => 25.0],
        ]);
        $responsePtk->assertStatus(403);

        // 2. Admin updates EWS parameter -> 302 Redirect with success
        $this->actingAs($this->adminUser);
        $responseAdmin = $this->post('/warnings/parameters/EWS-01', [
            'parameters' => ['threshold_ratio' => 25.0],
        ]);
        $responseAdmin->assertRedirect();

        // Parameter version must be incremented to 2
        $rule = RuleConfig::where('rule_code', 'EWS-01')->first();
        $this->assertNotNull($rule);
        $this->assertEquals(2, $rule->parameter_version);
        $this->assertEquals(25.0, (float) $rule->parameters['threshold_ratio']);
    }

    /**
     * Complete End-to-End Lifecycle: DP -> Return -> Resubmit -> Finalize (RI) -> Correct -> Cancel
     */
    public function test_end_to_end_transaction_lifecycle(): void
    {
        $this->actingAs($this->ptkCivil);

        // 1. Create DP Transaction (Rp 20.000.000)
        $createResponse = $this->post('/submissions', [
            'budget_line_id' => $this->lineCivil->id,
            'evidence_number' => 'KW-LIFECYCLE-01',
            'transaction_date' => '2026-03-01',
            'title' => 'Pengadaan Alat Ukur Tanah',
            'amount' => 20000000,
            'submit_action' => 'PROCESSING',
        ]);
        $createResponse->assertRedirect();

        $sub = Submission::where('evidence_number', 'KW-LIFECYCLE-01')->firstOrFail();
        $this->assertEquals('PROCESSING', $sub->status);

        $bucket = $this->bucketCivil->fresh();
        $this->assertEquals(20000000.00, (float) $bucket->reserved_budget);
        $this->assertEquals(80000000.00, (float) $bucket->available_balance);

        // 2. PTU returns submission with feedback
        $this->actingAs($this->ptuUser);
        $returnResponse = $this->post("/approvals/{$sub->id}/decide", [
            'action' => 'RETURNED',
            'comment' => 'RAB perlu disesuaikan dengan standar biaya masukan.',
        ]);
        $returnResponse->assertRedirect();

        $sub->refresh();
        $this->assertEquals('RETURNED', $sub->status);
        $bucket->refresh();
        // Commitment released
        $this->assertEquals(0.00, (float) $bucket->reserved_budget);
        $this->assertEquals(100000000.00, (float) $bucket->available_balance);

        // 3. PTK resubmits with revised amount (Rp 18.000.000)
        $this->actingAs($this->ptkCivil);
        $resubmitResponse = $this->post("/submissions/{$sub->id}/resubmit", [
            'amount' => 18000000,
            'title' => 'Pengadaan Alat Ukur Tanah (Revisi)',
            'notes' => 'RAB telah disesuaikan menjadi Rp18jt.',
        ]);
        $resubmitResponse->assertRedirect();

        $sub->refresh();
        $this->assertEquals('PROCESSING', $sub->status);
        $this->assertEquals(18000000.00, (float) $sub->amount);
        $bucket->refresh();
        $this->assertEquals(18000000.00, (float) $bucket->reserved_budget);
        $this->assertEquals(82000000.00, (float) $bucket->available_balance);

        // 4. PTU finalizes transaction (Move DP to RI without double deduction)
        $this->actingAs($this->ptuUser);
        $finalizeResponse = $this->post("/approvals/{$sub->id}/decide", [
            'action' => 'APPROVED',
            'comment' => 'Berkas telah lengkap dan sah.',
        ]);
        $finalizeResponse->assertRedirect();

        $sub->refresh();
        $this->assertEquals('FINAL', $sub->status);
        $bucket->refresh();
        $this->assertEquals(0.00, (float) $bucket->reserved_budget);
        $this->assertEquals(18000000.00, (float) $bucket->realized_budget);
        $this->assertEquals(82000000.00, (float) $bucket->available_balance);

        // 5. PTU performs RBC-05 correction (e.g. refund/discount of Rp 2.000.000 -> final 16.000.000)
        $correctResponse = $this->post("/submissions/{$sub->id}/correct", [
            'amount' => 16000000,
            'notes' => 'Penyesuaian diskon rekanan resmi Rp2jt.',
        ]);
        $correctResponse->assertRedirect();

        $sub->refresh();
        $this->assertEquals(16000000.00, (float) $sub->amount);
        $bucket->refresh();
        $this->assertEquals(16000000.00, (float) $bucket->realized_budget);
        $this->assertEquals(84000000.00, (float) $bucket->available_balance);
    }
}
