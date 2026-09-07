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
use App\Services\BudgetControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionExaminationWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected FiscalYear $fiscalYear;

    protected BudgetVersion $version;

    protected FundingSource $fundingSource;

    protected BudgetBucket $bucket;

    protected BudgetLine $budgetLine;

    protected User $ptkUser;

    protected User $ptuUser;

    protected User $dekanUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Jurusan Teknik Informatika', 'type' => 'DEPARTMENT', 'is_active' => true]
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
                'total_budget' => 50000000.00,
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

        $this->bucket = BudgetBucket::firstOrCreate(
            [
                'fiscal_year_id' => $this->fiscalYear->id,
                'budget_version_id' => $this->version->id,
                'department_id' => $this->department->id,
                'account_code' => '521211',
            ],
            [
                'funding_source_id' => $this->fundingSource->id,
                'budget_bucket_name' => 'Belanja Bahan Jurusan Informatika',
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

        $this->budgetLine = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucket->id,
            'rba_sequence_no' => '001',
            'budget_subcomponent_id' => $subcomp->id,
            'budget_account_id' => $account->id,
            'description' => 'Snack Rapat Jurusan',
            'volume' => 1.00,
            'unit' => 'Kegiatan',
            'unit_price' => 20000000.00,
            'budget_amount' => 20000000.00,
            'status' => 'ACTIVE',
        ]);

        $this->ptkUser = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->department->id,
        ]);

        $this->ptuUser = User::factory()->create([
            'role' => 'PTU',
            'department_id' => $this->department->id,
        ]);

        $this->dekanUser = User::factory()->create([
            'role' => 'DEKAN',
        ]);
    }

    /**
     * Test 1: Default Queue is DIAJUKAN
     */
    public function test_default_queue_is_diajukan(): void
    {
        // Create 1 DIAJUKAN submission and 1 DRAFT submission
        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/Q-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Transaksi Diajukan',
            'amount' => 2000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/Q-02',
            'transaction_date' => '2026-09-03',
            'title' => 'Transaksi Draft',
            'amount' => 1000000.00,
            'submit_action' => 'DRAFT',
        ], $this->ptkUser);

        $response = $this->actingAs($this->ptuUser)->get(route('approvals.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Approvals/Index')
            ->where('activeTab', 'DIAJUKAN')
            ->has('submissions.data', 1)
            ->where('submissions.data.0.evidence_number', 'FRA/Q-01')
        );
    }

    /**
     * Test 2: Aksi KEMBALIKAN (Wajib alasan, status -> DIKEMBALIKAN, commitment dilepas)
     */
    public function test_action_kembalikan_requires_reason_and_releases_commitment(): void
    {
        $submission = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/RET-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Transaksi Diperiksa',
            'amount' => 5000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(5000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(15000000.00, (float) $this->bucket->available_balance);

        // 2a. Without reason -> MUST FAIL with validation error
        $resFail = $this->actingAs($this->ptuUser)->post("/approvals/{$submission->id}/decide", [
            'action' => 'KEMBALIKAN',
            'comment' => '',
        ]);
        $resFail->assertSessionHasErrors(['comment']);

        // 2b. With reason -> SUCCESS, commitment released
        $resSuccess = $this->actingAs($this->ptuUser)->post("/approvals/{$submission->id}/decide", [
            'action' => 'KEMBALIKAN',
            'comment' => 'Kuitansi stempel toko belum terbaca jelas.',
        ]);
        $resSuccess->assertRedirect();

        $this->bucket->refresh();
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(20000000.00, (float) $this->bucket->available_balance);

        $this->assertEquals('RETURNED', $submission->refresh()->status);
        $this->assertDatabaseHas('submission_status_histories', [
            'submission_id' => $submission->id,
            'from_status' => 'PROCESSING',
            'to_status' => 'RETURNED',
            'actor_id' => $this->ptuUser->id,
        ]);
    }

    /**
     * Test 3: Aksi TOLAK (Wajib alasan, status -> DITOLAK, commitment dilepas)
     */
    public function test_action_tolak_requires_reason_and_releases_commitment(): void
    {
        $submission = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/REJ-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Transaksi Ditolak',
            'amount' => 4000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        // 3a. Without reason -> MUST FAIL
        $resFail = $this->actingAs($this->ptuUser)->post("/approvals/{$submission->id}/decide", [
            'action' => 'TOLAK',
            'comment' => '',
        ]);
        $resFail->assertSessionHasErrors(['comment']);

        // 3b. With reason -> SUCCESS, status REJECTED, commitment released
        $resSuccess = $this->actingAs($this->ptuUser)->post("/approvals/{$submission->id}/decide", [
            'action' => 'TOLAK',
            'comment' => 'Kegiatan tidak sesuai dengan peruntukan alokasi RM.',
        ]);
        $resSuccess->assertRedirect();

        $this->bucket->refresh();
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(20000000.00, (float) $this->bucket->available_balance);
        $this->assertEquals('REJECTED', $submission->refresh()->status);
    }

    /**
     * Test 4: Aksi SELESAI (Hanya permission diizinkan, status -> SELESAI, commitment -> internal realization)
     */
    public function test_action_selesai_moves_commitment_to_realization_with_permission_check(): void
    {
        $submission = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/OK-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Transaksi Selesai',
            'amount' => 6000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        // 4a. User without permission (e.g. PTK) cannot decide/finalize
        $resUnauthorized = $this->actingAs($this->ptkUser)->post("/approvals/{$submission->id}/decide", [
            'action' => 'SELESAI',
        ]);
        $resUnauthorized->assertForbidden();

        // 4b. Authorized PTU executes SELESAI
        $resOk = $this->actingAs($this->ptuUser)->post("/approvals/{$submission->id}/decide", [
            'action' => 'SELESAI',
            'comment' => 'Berkas lengkap dan tagihan telah dibayarkan lunas.',
        ]);
        $resOk->assertRedirect();

        $this->bucket->refresh();
        // Commitment drops, realization increases, available balance invariant is preserved!
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(6000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(14000000.00, (float) $this->bucket->available_balance);
        $this->assertEquals('FINAL', $submission->refresh()->status);

        $this->assertDatabaseHas('submission_status_histories', [
            'submission_id' => $submission->id,
            'from_status' => 'PROCESSING',
            'to_status' => 'FINAL',
            'actor_id' => $this->ptuUser->id,
        ]);
    }
}
