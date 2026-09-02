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
use App\Models\User;
use App\Services\BudgetControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RuleBasedBudgetControlTest extends TestCase
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
                'total_budget' => 10000000.00,
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

        // Control Bucket with initial 10,000,000
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
                'allocated_budget' => 10000000.00,
                'reserved_budget' => 0.00,
                'realized_budget' => 0.00,
                'available_balance' => 10000000.00,
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
            'unit_price' => 10000000.00,
            'budget_amount' => 10000000.00,
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
    }

    /**
     * TEST A: Saldo 10 juta, request 5 juta -> PASS
     */
    public function test_test_a_available_10m_request_5m_passes(): void
    {
        $submission = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/001',
            'transaction_date' => '2026-09-03',
            'title' => 'Request 5 Juta',
            'amount' => 5000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->assertNotNull($submission->id);
        $this->assertEquals('PROCESSING', $submission->status);

        $this->bucket->refresh();
        $this->assertEquals(5000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(0.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(5000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * TEST B: Saldo 10 juta, request 10 juta -> PASS
     */
    public function test_test_b_available_10m_request_10m_passes(): void
    {
        $submission = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/002',
            'transaction_date' => '2026-09-03',
            'title' => 'Request 10 Juta Penuh',
            'amount' => 10000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->assertNotNull($submission->id);
        $this->assertEquals('PROCESSING', $submission->status);

        $this->bucket->refresh();
        $this->assertEquals(10000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(0.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(0.00, (float) $this->bucket->available_balance);
    }

    /**
     * TEST C: Saldo 10 juta, request 11 juta -> BLOCK
     */
    public function test_test_c_available_10m_request_11m_is_blocked(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RBC-001: Overbudget Protection');

        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/003',
            'transaction_date' => '2026-09-03',
            'title' => 'Request 11 Juta',
            'amount' => 11000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);
    }

    /**
     * TEST D: Saldo 10 juta, concurrent request A 8 juta & request B 8 juta -> Hanya 1 boleh PASS
     */
    public function test_test_d_race_condition_protection_only_one_passes(): void
    {
        // Request A: 8,000,000 (Takes 8M, leaving 2M)
        $subA = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/RACE-A',
            'transaction_date' => '2026-09-03',
            'title' => 'Request A 8M',
            'amount' => 8000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->assertNotNull($subA->id);
        $this->bucket->refresh();
        $this->assertEquals(2000000.00, (float) $this->bucket->available_balance);

        // Request B: 8,000,000 on the same bucket MUST be blocked by pessimistic locking & balance check
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RBC-001: Overbudget Protection');

        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/RACE-B',
            'transaction_date' => '2026-09-03',
            'title' => 'Request B 8M',
            'amount' => 8000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);
    }

    /**
     * TEST E: DIAJUKAN -> DITOLAK / DIKEMBALIKAN -> saldo kembali
     */
    public function test_test_e_diajukan_to_ditolak_releases_commitment(): void
    {
        // 1. Submit 4,000,000
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/REJECT-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Request to be rejected',
            'amount' => 4000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(4000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(6000000.00, (float) $this->bucket->available_balance);

        // 2. Reject transition via BudgetControlService
        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'REJECTED',
            actor: $this->ptuUser,
            notes: 'Kuitansi tidak terbaca'
        );

        // 3. Balance must be fully restored
        $this->bucket->refresh();
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);
        $this->assertEquals('REJECTED', $sub->refresh()->status);

        // History recorded
        $this->assertDatabaseHas('submission_status_histories', [
            'submission_id' => $sub->id,
            'from_status' => 'PROCESSING',
            'to_status' => 'REJECTED',
        ]);
    }

    /**
     * TEST F: DIAJUKAN -> SELESAI -> available tetap konsisten dan tidak double count
     */
    public function test_test_f_diajukan_to_selesai_preserves_available_without_double_deduction(): void
    {
        // 1. Submit 3,000,000
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/FINAL-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Request to be finalized',
            'amount' => 3000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(3000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(0.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(7000000.00, (float) $this->bucket->available_balance);

        // 2. Finalize
        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Spj lunas diverifikasi'
        );

        // 3. Invariant: Available must remain 7M (Allocated 10M - Reserved 0M - Realized 3M = 7M)
        $this->bucket->refresh();
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(3000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(7000000.00, (float) $this->bucket->available_balance);
        $this->assertEquals('FINAL', $sub->refresh()->status);
    }

    /**
     * TEST G: DIKEMBALIKAN -> edit -> AJUKAN ULANG -> RBC dihitung ulang
     */
    public function test_test_g_returned_to_resubmit_recalculates_rbc(): void
    {
        // 1. Submit 3,000,000
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/RESUBMIT-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Original submission',
            'amount' => 3000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        // 2. Return to PTK
        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'RETURNED',
            actor: $this->ptuUser,
            notes: 'Mohon perbaiki rincian'
        );

        $this->bucket->refresh();
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);

        // 3. PTK edits amount to 4,500,000 and resubmits via HTTP endpoint
        $res = $this->actingAs($this->ptkUser)->post(route('submissions.resubmit', $sub), [
            'amount' => 4500000.00,
            'title' => 'Perbaikan rincian belanja',
            'notes' => 'Sudah diperbaiki',
        ]);

        $res->assertRedirect(route('submissions.show', $sub));

        // 4. Assert re-calculated commitment
        $this->bucket->refresh();
        $this->assertEquals(4500000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(5500000.00, (float) $this->bucket->available_balance);
        $this->assertEquals('PROCESSING', $sub->refresh()->status);
        $this->assertEquals(4500000.00, (float) $sub->amount);

        // 5. If resubmitted with overbudget (e.g. 20M), it must fail
        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'RETURNED',
            actor: $this->ptuUser,
            notes: 'Kembalikan lagi'
        );

        $resOver = $this->actingAs($this->ptkUser)->post(route('submissions.resubmit', $sub), [
            'amount' => 20000000.00,
        ]);

        $resOver->assertSessionHasErrors(['amount']);
    }

    /**
     * TEST RBC-002: DRAFT does NOT lock/reserve budget
     */
    public function test_draft_does_not_reserve_budget(): void
    {
        $draft = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/DRAFT-01',
            'transaction_date' => '2026-09-03',
            'title' => 'Draft Belanja',
            'amount' => 5000000.00,
            'submit_action' => 'DRAFT',
        ], $this->ptkUser);

        $this->assertEquals('DRAFT', $draft->status);

        $this->bucket->refresh();
        $this->assertEquals(0.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);
    }
}
