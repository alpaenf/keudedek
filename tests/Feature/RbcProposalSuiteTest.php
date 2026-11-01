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
use App\Services\BudgetImportPipelineService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class RbcProposalSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetVersion $version;

    protected BudgetSubcomponent $subcomponent;

    protected BudgetAccount $account;

    protected BudgetBucket $bucket;

    protected BudgetLine $line;

    protected User $ptkUser;

    protected User $ptuUser;

    protected User $adminUser;

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
                'version_label' => 'DIPA Induk 2026',
                'status' => 'ACTIVE',
                'total_budget' => 100000000.00,
            ]
        );

        $this->subcomponent = BudgetSubcomponent::firstOrCreate(
            ['full_code' => '023.17.WA.4257.EBA.994.001.AA'],
            ['code' => 'AA', 'name' => 'Operasional Jurusan']
        );

        $this->account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan']
        );

        // Standard Baseline (Tabel 30 Proposal):
        // P = 100.000.000, C = 30.000.000, R = 60.000.000, S = 10.000.000
        $this->bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'subcomponent_code' => $this->subcomponent->code,
            'subcomponent_name' => $this->subcomponent->name,
            'budget_bucket_name' => "{$this->account->name} - {$this->department->name}",
            'initial_budget' => 100000000.00,
            'allocated_budget' => 100000000.00,
            'reserved_budget' => 30000000.00,
            'realized_budget' => 60000000.00,
            'available_balance' => 10000000.00,
        ]);

        $this->line = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'rba_sequence_no' => '001',
            'description' => 'Bahan Praktikum Komputasi',
            'volume' => 1.00,
            'unit' => 'Paket',
            'unit_price' => 100000000.00,
            'budget_amount' => 100000000.00,
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

        $this->adminUser = User::factory()->create([
            'role' => 'ADMIN',
            'department_id' => $this->department->id,
        ]);
    }

    /**
     * R-01: Transaksi DP baru Rp9.999.999 -> Saldo Rp1
     */
    public function test_r01_new_dp_transaction_under_available(): void
    {
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-01',
            'transaction_date' => '2026-04-01',
            'title' => 'DP Bahan R-01',
            'amount' => 9999999.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(39999999.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(1.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-02: Transaksi DP baru Rp10.000.000 -> Saldo Rp0
     */
    public function test_r02_new_dp_transaction_exact_available(): void
    {
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-02',
            'transaction_date' => '2026-04-02',
            'title' => 'DP Bahan R-02',
            'amount' => 10000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(40000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(0.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-03: Transaksi terdokumentasi baru Rp10.000.001 -> Saldo -Rp1 dan perlu ditinjau
     */
    public function test_r03_documented_transaction_exceeding_pagu(): void
    {
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-03',
            'transaction_date' => '2026-04-03',
            'title' => 'DP Bahan Terdokumentasi R-03',
            'amount' => 10000001.00,
            'submit_action' => 'PROCESSING',
            'is_documented_historical' => true,
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(40000001.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(-1.00, (float) $this->bucket->available_balance);
        $this->assertTrue((bool) $this->bucket->needs_review);
        $this->assertTrue((bool) $sub->fresh()->needs_review);
    }

    /**
     * R-04: Nominal nol atau negatif -> Ditolak; posisi tetap
     */
    public function test_r04_zero_or_negative_amount_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RBC-09: Nominal transaksi harus lebih besar dari nol.');

        BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-04',
            'transaction_date' => '2026-04-04',
            'title' => 'Nominal Negatif R-04',
            'amount' => -50000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-05: Transaksi Rp10.000.000 dalam (C) menjadi RI -> C=20M, R=70M, S=10M (RBC-04)
     */
    public function test_r05_dp_to_ri_transition_preserves_available_balance(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-05',
            'evidence_number' => 'FRA/R-05',
            'transaction_date' => '2026-04-05',
            'title' => 'DP Eksisting R-05',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Penyelesaian SPJ R-05'
        );

        $this->bucket->refresh();
        $this->assertEquals(20000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-06: Permintaan R-05 dikirim ulang (Idempotensi RBC-07) -> Tidak terjadi pengurangan atau pemindahan kedua
     */
    public function test_r06_duplicate_transition_request_is_idempotent(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-06',
            'evidence_number' => 'FRA/R-06',
            'transaction_date' => '2026-04-06',
            'title' => 'DP Eksisting R-06',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        // First execution
        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Penyelesaian Pertama',
            eventId: 'EVT-R06-001'
        );

        $this->bucket->refresh();
        $this->assertEquals(20000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);

        // Replay identical event
        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Penyelesaian Dikirim Ulang',
            eventId: 'EVT-R06-001'
        );

        $this->bucket->refresh();
        // Values must remain exactly identical
        $this->assertEquals(20000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(10000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-07: Transaksi Rp5.000.000 dalam (C) dikoreksi menjadi Rp8.000.000 -> C=33M, S=7M (RBC-05)
     */
    public function test_r07_correction_of_dp_amount_increases_commitment(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-07',
            'evidence_number' => 'FRA/R-07',
            'transaction_date' => '2026-04-07',
            'title' => 'DP 5M R-07',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetControlService::correctTransaction(
            submission: $sub,
            newAmount: 8000000.00,
            actor: $this->ptuUser,
            notes: 'Koreksi DP dari 5M ke 8M'
        );

        $this->bucket->refresh();
        $this->assertEquals(33000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(70000000.00 - 63000000.00, (float) $this->bucket->available_balance); // 7.000.000
    }

    /**
     * R-08: Transaksi Rp5.000.000 dalam (C) dikoreksi menjadi Rp14.000.000:
     * Kapasitas setelah kontribusi lama dikeluarkan = 15.000.000; saldo akhir = 1.000.000 (RBC-05)
     */
    public function test_r08_correction_evaluates_capacity_after_releasing_old_contribution(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-08',
            'evidence_number' => 'FRA/R-08',
            'transaction_date' => '2026-04-08',
            'title' => 'DP 5M R-08',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 5000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetControlService::correctTransaction(
            submission: $sub,
            newAmount: 14000000.00,
            actor: $this->ptuUser,
            notes: 'Koreksi DP dari 5M ke 14M'
        );

        $this->bucket->refresh();
        $this->assertEquals(39000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(1000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-09: Transaksi Rp10.000.000 dalam (C) dibatalkan secara sah -> C=20M, saldo 20M (RBC-06)
     */
    public function test_r09_cancellation_releases_commitment_once(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-09',
            'evidence_number' => 'FRA/R-09',
            'transaction_date' => '2026-04-09',
            'title' => 'DP 10M R-09',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'CANCELLED',
            actor: $this->ptuUser,
            notes: 'Pembatalan pengajuan sah'
        );

        $this->bucket->refresh();
        $this->assertEquals(20000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(20000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-10: Realisasi Rp10.000.000 dalam (R) dikoreksi menjadi Rp8.000.000 -> R=58M, saldo 12M (RBC-05)
     */
    public function test_r10_correction_of_realization_amount(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-10',
            'evidence_number' => 'FRA/R-10',
            'transaction_date' => '2026-04-10',
            'title' => 'RI 10M R-10',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 10000000.00,
            'status' => 'FINAL',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetControlService::correctTransaction(
            submission: $sub,
            newAmount: 8000000.00,
            actor: $this->ptuUser,
            notes: 'Koreksi SPJ Realisasi dari 10M ke 8M'
        );

        $this->bucket->refresh();
        $this->assertEquals(30000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(58000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(12000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-11: Status sumber tidak dikenal / belum dapat dipetakan -> Klarifikasi diperlukan; saldo tidak disimpulkan final (RBC-09)
     */
    public function test_r11_unmapped_status_marks_data_incomplete(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-11',
            'evidence_number' => 'FRA/R-11',
            'transaction_date' => '2026-04-11',
            'title' => 'Transaksi Status Aneh R-11',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 2000000.00,
            'status' => 'UNKNOWN_PENDING',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetCalculationService::synchronizeBucketFromTransactions($this->bucket);
        $this->bucket->refresh();

        $this->assertTrue((bool) $this->bucket->data_incomplete);
    }

    /**
     * R-12: Pagu aktif direvisi menjadi Rp85.000.000 -> Saldo -Rp5.000.000 dan kondisi perlu ditinjau (RBC-01)
     */
    public function test_r12_pagu_revision_downward_preserves_continuity_and_flags_review(): void
    {
        $version01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'Revisi 01 Pagu Turun',
            'status' => 'DRAFT',
            'total_budget' => 85000000.00,
        ]);

        $bucket01 = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $version01->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => $this->account->code,
            'account_name' => $this->account->name,
            'subcomponent_full_code' => $this->subcomponent->full_code,
            'subcomponent_code' => $this->subcomponent->code,
            'subcomponent_name' => $this->subcomponent->name,
            'budget_bucket_name' => "{$this->account->name} - {$this->department->name}",
            'initial_budget' => 85000000.00,
            'allocated_budget' => 85000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 85000000.00,
        ]);

        // Existing DP 30M and RI 60M submissions
        $subDP = Submission::create([
            'submission_number' => 'TRX/R-12-DP',
            'evidence_number' => 'FRA/R-12-DP',
            'transaction_date' => '2026-03-01',
            'title' => 'DP 30M',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 30000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        $subRI = Submission::create([
            'submission_number' => 'TRX/R-12-RI',
            'evidence_number' => 'FRA/R-12-RI',
            'transaction_date' => '2026-03-05',
            'title' => 'RI 60M',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 60000000.00,
            'status' => 'FINAL',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetImportPipelineService::activateVersion($version01, $this->adminUser);

        $bucket01->refresh();
        $this->assertEquals(85000000.00, (float) $bucket01->allocated_budget);
        $this->assertEquals(30000000.00, (float) $bucket01->reserved_budget);
        $this->assertEquals(60000000.00, (float) $bucket01->realized_budget);
        $this->assertEquals(-5000000.00, (float) $bucket01->available_balance);
        $this->assertTrue((bool) $bucket01->needs_review);
    }

    /**
     * R-13: Kejadian identik dikirim dua kali -> Kontribusi dicatat satu kali (RBC-07)
     */
    public function test_r13_identical_event_submission_records_contribution_once(): void
    {
        $payload = [
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-13',
            'transaction_date' => '2026-04-13',
            'title' => 'Transaksi Idempotent R-13',
            'amount' => 4000000.00,
            'submit_action' => 'PROCESSING',
            'event_id' => 'EVT-R13-UNIQUE',
        ];

        // Submission 1
        $sub1 = BudgetControlService::recordTransaction($payload, $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(34000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(6000000.00, (float) $this->bucket->available_balance);

        // Submission 2 with same event_id
        $sub2 = BudgetControlService::recordTransaction($payload, $this->ptkUser);

        $this->bucket->refresh();
        // Contribution must not be added again
        $this->assertEquals(34000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(6000000.00, (float) $this->bucket->available_balance);
        $this->assertEquals($sub1->id, $sub2->id);
    }

    /**
     * R-14: Dua transaksi terdokumentasi masing-masing Rp6.000.000 diproses bersamaan:
     * Keduanya tercatat sekali; saldo -Rp2.000.000 dengan penanda (RBC-08)
     */
    public function test_r14_two_documented_transactions_processed_concurrently(): void
    {
        $sub1 = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-14-A',
            'transaction_date' => '2026-04-14',
            'title' => 'Dokumen Sah A',
            'amount' => 6000000.00,
            'submit_action' => 'PROCESSING',
            'is_documented_historical' => true,
        ], $this->ptkUser);

        $sub2 = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-14-B',
            'transaction_date' => '2026-04-14',
            'title' => 'Dokumen Sah B',
            'amount' => 6000000.00,
            'submit_action' => 'PROCESSING',
            'is_documented_historical' => true,
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(42000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(60000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(-2000000.00, (float) $this->bucket->available_balance);
        $this->assertTrue((bool) $this->bucket->needs_review);
    }

    /**
     * R-15: Penyimpanan gagal sebelum seluruh perubahan selesai -> Perubahan satu kesatuan dibatalkan
     */
    public function test_r15_atomic_transaction_rollback_on_failure(): void
    {
        $initialAvailable = (float) $this->bucket->available_balance;

        try {
            DB::transaction(function () {
                $sub = BudgetControlService::recordTransaction([
                    'budget_line_id' => $this->line->id,
                    'evidence_number' => 'FRA/R-15-FAIL',
                    'transaction_date' => '2026-04-15',
                    'title' => 'Transaksi Batal Rollback',
                    'amount' => 5000000.00,
                    'submit_action' => 'PROCESSING',
                ], $this->ptkUser);

                // Simulate unexpected system exception midway
                throw new Exception('Database Disk Failure / Simulating Error');
            });
        } catch (Exception $e) {
            // Expected simulation
        }

        $this->bucket->refresh();
        $this->assertEquals($initialAvailable, (float) $this->bucket->available_balance);
        $this->assertNull(Submission::where('evidence_number', 'FRA/R-15-FAIL')->first());
    }

    /**
     * R-16: Transaksi selesai Rp4.000.000 dicatat langsung berdasarkan dokumen sah:
     * R sebesar Rp64.000.000, C tetap Rp30.000.000, saldo Rp6.000.000 (RBC-03)
     */
    public function test_r16_direct_realization_from_valid_document(): void
    {
        $sub = BudgetControlService::recordTransaction([
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-16-DIRECT',
            'transaction_date' => '2026-04-16',
            'title' => 'SPJ Langsung Pembelian Komponen',
            'amount' => 4000000.00,
            'submit_action' => 'FINAL',
            'is_documented_historical' => true,
        ], $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(30000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(64000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(6000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-17: Pada kondisi P=85M, C=30M, R=60M (S=-5M), transaksi Rp10.000.000 dalam C menjadi RI:
     * C=20M, R=70M, saldo tetap -5M (RBC-04)
     */
    public function test_r17_dp_to_ri_with_negative_balance_preserves_deficit(): void
    {
        // Setup initial negative balance state
        $this->bucket->update([
            'allocated_budget' => 85000000.00,
            'reserved_budget' => 30000000.00,
            'realized_budget' => 60000000.00,
            'available_balance' => -5000000.00,
            'needs_review' => true,
        ]);

        $sub = Submission::create([
            'submission_number' => 'TRX/R-17',
            'evidence_number' => 'FRA/R-17',
            'transaction_date' => '2026-04-17',
            'title' => 'DP 10M dalam defisit',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 10000000.00,
            'status' => 'PROCESSING',
            'created_by' => $this->ptkUser->id,
        ]);

        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Penyelesaian SPJ dalam defisit'
        );

        $this->bucket->refresh();
        $this->assertEquals(20000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(70000000.00, (float) $this->bucket->realized_budget);
        $this->assertEquals(-5000000.00, (float) $this->bucket->available_balance);
    }

    /**
     * R-18: Perubahan keadaan tidak sesuai aturan final -> Ditolak; posisi sebelumnya tetap
     */
    public function test_r18_illegal_status_transition_rejected(): void
    {
        $sub = Submission::create([
            'submission_number' => 'TRX/R-18',
            'evidence_number' => 'FRA/R-18',
            'transaction_date' => '2026-04-18',
            'title' => 'Transaksi Dibatalkan',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $this->line->id,
            'amount' => 5000000.00,
            'status' => 'CANCELLED',
            'created_by' => $this->ptkUser->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RBC-06: Perubahan keadaan tidak sah: Transisi status dari \'CANCELLED\' ke \'FINAL\' tidak diizinkan.');

        BudgetControlService::transitionStatus(
            submission: $sub,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Ilegal loncat dari batal ke selesai'
        );

        $this->assertEquals('CANCELLED', $sub->fresh()->status);
    }

    /**
     * R-19: Identitas kejadian yang telah diproses digunakan kembali dengan isi perubahan yang berbeda:
     * Permintaan ditolak sebagai konflik identitas kejadian; hasil terdahulu dan posisi anggaran dipertahankan (RBC-07)
     */
    public function test_r19_conflicting_event_identity_payload_is_rejected(): void
    {
        $initialPayload = [
            'budget_line_id' => $this->line->id,
            'evidence_number' => 'FRA/R-19',
            'transaction_date' => '2026-04-19',
            'title' => 'Transaksi Asli R-19',
            'amount' => 3000000.00,
            'submit_action' => 'PROCESSING',
            'event_id' => 'EVT-R19-CONFLICT-CHECK',
        ];

        // 1. First execution succeeds
        $sub1 = BudgetControlService::recordTransaction($initialPayload, $this->ptkUser);

        $this->bucket->refresh();
        $this->assertEquals(33000000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(7000000.00, (float) $this->bucket->available_balance);

        // 2. Second execution with SAME event_id but DIFFERENT amount (5.000.000)
        $conflictingPayload = $initialPayload;
        $conflictingPayload['amount'] = 5000000.00;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('RBC-07: Konflik Identitas Kejadian: Identitas kejadian telah digunakan untuk data perubahan yang berbeda.');

        try {
            BudgetControlService::recordTransaction($conflictingPayload, $this->ptkUser);
        } finally {
            // Position must be strictly retained
            $this->bucket->refresh();
            $this->assertEquals(33000000.00, (float) $this->bucket->reserved_budget);
            $this->assertEquals(7000000.00, (float) $this->bucket->available_balance);
        }
    }
}
