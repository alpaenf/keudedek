<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetAccount;
use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Submission;
use App\Models\User;
use App\Services\BudgetControlService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportingModulesTest extends TestCase
{
    use RefreshDatabase;

    protected Department $dept;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetVersion $budgetVersion;

    protected BudgetBucket $bucket;

    protected BudgetLine $line;

    protected User $adminUser;

    protected User $ptuUser;

    protected User $ptkUser;

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

        $this->bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 100000000.00,
        ]);

        $account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan', 'type' => 'EXPENSE', 'data_status' => 'ACTIVE', 'status' => 'ACTIVE']
        );

        $this->line = BudgetLine::create([
            'budget_version_id' => $this->budgetVersion->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_account_id' => $account->id,
            'rba_sequence_no' => '001',
            'description' => 'Pembelian Kertas & Tinta Ujian',
            'volume' => 10,
            'unit' => 'Paket',
            'unit_price' => 1000000.00,
            'budget_amount' => 10000000.00,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin Keuangan',
            'role' => 'ADMIN',
            'department_id' => $this->dept->id,
        ]);

        $this->ptuUser = User::factory()->create([
            'name' => 'Bendahara PTU',
            'role' => 'PTU',
            'department_id' => $this->dept->id,
        ]);

        $this->ptkUser = User::factory()->create([
            'name' => 'Pengusul PTK IF',
            'role' => 'PTK',
            'department_id' => $this->dept->id,
        ]);
    }

    /**
     * Test 1: Report MVP Numbers Reconciliation with Dashboard under the same filter.
     */
    public function test_report_mvp_numbers_reconcile_with_dashboard(): void
    {
        // 1. Record transactions: 1 In Process (DIAJUKAN = 15M) and 1 Selesai (FINAL = 25M)
        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'budget_bucket_id' => $this->bucket->id,
            'evidence_number' => 'BKT-IF-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Pengadaan ATK Ujian Tengah Semester',
            'department_id' => $this->dept->id,
            'amount' => 15000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $completedSub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'budget_bucket_id' => $this->bucket->id,
            'evidence_number' => 'BKT-IF-002',
            'transaction_date' => '2026-03-05',
            'title' => 'Honor Penguji Sidang',
            'department_id' => $this->dept->id,
            'amount' => 25000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        // Transition BKT-IF-002 to FINAL (SELESAI)
        BudgetControlService::transitionStatus($completedSub, 'FINAL', $this->ptuUser, 'Selesai diverifikasi.');

        $this->bucket->refresh();

        // 2. Fetch Dashboard Payload
        $dashboard = DashboardService::getPayload($this->adminUser, (string) $this->dept->id);

        // 3. Fetch Report via Inertia response
        $this->actingAs($this->adminUser);
        $res = $this->get("/reports?department_id={$this->dept->id}&fiscal_year_id={$this->fiscalYear->id}");
        $res->assertStatus(200);

        $reportProps = $res->original->getData()['page']['props'];

        // 4. Assert Exact Reconciliation (Total Allocated, Reserved/Diajukan, Realized/Selesai, Available/Saldo)
        $this->assertEquals($dashboard['totalAllocated'], $reportProps['totalAllocated']);
        $this->assertEquals($dashboard['totalReserved'], $reportProps['totalReserved']);
        $this->assertEquals($dashboard['totalRealized'], $reportProps['totalRealized']);
        $this->assertEquals($dashboard['totalAvailable'], $reportProps['totalAvailable']);

        // Check report datasets exist
        $this->assertNotEmpty($reportProps['reportPaguVsReal']);
        $this->assertNotEmpty($reportProps['reportByAccount']);
        $this->assertNotEmpty($reportProps['reportByStatus']);
        $this->assertNotEmpty($reportProps['reportTransactions']);
        $this->assertNotEmpty($reportProps['reportBudgetBalances']);
    }

    /**
     * Test 2: Print Transaction view displays all 9 required fields and configurable signers.
     */
    public function test_print_transaction_displays_required_fields_and_configurable_signers(): void
    {
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'budget_bucket_id' => $this->bucket->id,
            'evidence_number' => 'FRA/2026/009',
            'transaction_date' => '2026-04-10',
            'title' => 'Pembelian Lisensi Software Lab',
            'department_id' => $this->dept->id,
            'amount' => 5000000.00,
            'beneficiary_name' => 'PT Solusi Informatika',
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->actingAs($this->ptkUser);

        $response = $this->get("/submissions/{$sub->id}/print?submitter_title=Ketua+Panitia&verifier_name=Drs.+Supriyanto&city=Purwokerto");
        $response->assertStatus(200);

        $pageProps = $response->original->getData()['page']['props'];

        // 9 Required Fields Verification
        $this->assertEquals('FRA/2026/009', $pageProps['submission']['evidence_number']);
        $this->assertStringContainsString('2026-04-10', (string) $pageProps['submission']['transaction_date']);
        $this->assertEquals($this->ptkUser->id, $pageProps['submission']['creator']['id']);
        $this->assertEquals($this->dept->id, $pageProps['submission']['department']['id']);
        $this->assertEquals('001', $pageProps['submission']['budget_line']['rba_sequence_no']);
        $this->assertNotNull($pageProps['submission']['budget_line']['account']);
        $this->assertEquals('Pembelian Lisensi Software Lab', $pageProps['submission']['title']);
        $this->assertEquals(5000000.00, (float) $pageProps['submission']['amount']);
        $this->assertEquals('PROCESSING', $pageProps['submission']['status']);

        // Configurable Signers
        $this->assertEquals('Ketua Panitia', $pageProps['signers']['submitter_title']);
        $this->assertEquals('Drs. Supriyanto', $pageProps['signers']['verifier_name']);
        $this->assertEquals('Purwokerto', $pageProps['signers']['city']);
    }

    /**
     * Test 3: Export PDF and XLSX succeed with filters.
     */
    public function test_export_pdf_and_xlsx_succeed(): void
    {
        $this->actingAs($this->adminUser);

        $resPdf = $this->get("/reports/export-pdf?department_id={$this->dept->id}&fiscal_year_id={$this->fiscalYear->id}");
        $resPdf->assertStatus(200);
        $this->assertTrue(str_contains($resPdf->headers->get('content-type'), 'application/pdf'));

        $resXlsx = $this->get("/reports/export-xlsx?department_id={$this->dept->id}&fiscal_year_id={$this->fiscalYear->id}");
        $resXlsx->assertStatus(200);
        $this->assertTrue(str_contains($resXlsx->headers->get('content-type'), 'application/vnd.ms-excel'));

        // Verify audit log recorded for export
        $this->assertTrue(
            AuditLog::where('action', 'EXPORT_PDF_REPORT')->exists()
        );
        $this->assertTrue(
            AuditLog::where('action', 'EXPORT_XLSX_REPORT')->exists()
        );
    }

    /**
     * Test 4: Financial Transactions cannot be hard-deleted (SoftDeletes enforced).
     */
    public function test_financial_transactions_cannot_be_hard_deleted(): void
    {
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'budget_bucket_id' => $this->bucket->id,
            'evidence_number' => 'BKT-DEL-001',
            'transaction_date' => '2026-05-01',
            'title' => 'Transaksi Uji Hapus',
            'department_id' => $this->dept->id,
            'amount' => 2000000.00,
            'submit_action' => 'DRAFT',
        ], $this->ptkUser);

        $subId = $sub->id;

        // Perform Eloquent delete
        $sub->delete();

        // Must still exist in database with non-null deleted_at
        $this->assertSoftDeleted('submissions', ['id' => $subId]);
        $this->assertDatabaseHas('submissions', ['id' => $subId]);
        $this->assertNotNull(Submission::withTrashed()->find($subId)->deleted_at);
    }

    /**
     * Test 5: Full Audit Events lifecycle tracking.
     */
    public function test_audit_logs_record_critical_events(): void
    {
        // 1. Create/Submit
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'budget_bucket_id' => $this->bucket->id,
            'evidence_number' => 'BKT-AUDIT-001',
            'transaction_date' => '2026-06-01',
            'title' => 'Uji Audit Trail Siklus Lengkap',
            'department_id' => $this->dept->id,
            'amount' => 4000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->assertTrue(AuditLog::where('action', 'SUBMIT_TRANSACTION')->where('model_id', $sub->id)->exists());

        // 2. Return
        BudgetControlService::transitionStatus($sub, 'RETURNED', $this->ptuUser, 'Kwitansi kurang materai');
        $this->assertTrue(AuditLog::where('action', 'RETURN_TRANSACTION')->where('model_id', $sub->id)->exists());

        // 3. Resubmit
        BudgetControlService::transitionStatus($sub, 'PROCESSING', $this->ptkUser, 'Materai sudah dibubuhkan');
        $this->assertTrue(AuditLog::where('action', 'RESUBMIT_TRANSACTION')->where('model_id', $sub->id)->exists());

        // 4. Complete
        BudgetControlService::transitionStatus($sub, 'FINAL', $this->ptuUser, 'Pemeriksaan tuntas');
        $this->assertTrue(AuditLog::where('action', 'COMPLETE_TRANSACTION')->where('model_id', $sub->id)->exists());

        // 5. Role/Scope Change
        $this->actingAs($this->adminUser);
        $resUser = $this->put("/users/{$this->ptkUser->id}", [
            'name' => $this->ptkUser->name,
            'email' => $this->ptkUser->email,
            'department_id' => $this->dept->id,
            'role' => 'KAJUR',
        ]);
        $resUser->assertSessionHasNoErrors();
        $this->assertTrue(AuditLog::where('action', 'ROLE_OR_SCOPE_CHANGE')->where('model_id', $this->ptkUser->id)->exists());
    }
}
