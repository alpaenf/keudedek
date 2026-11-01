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
use App\Services\BudgetImportPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbcVersionContinuityTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected BudgetSubcomponent $subcomponent;

    protected BudgetAccount $account;

    protected User $ptkUser;

    protected User $ptuUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Master data yang valid: Jurusan, Tahun Anggaran, Sumber Dana RM, Subkomponen, Akun
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

        $this->subcomponent = BudgetSubcomponent::firstOrCreate(
            ['full_code' => '023.17.WA.4257.EBA.994.001.AA'],
            ['code' => 'AA', 'name' => 'Operasional Jurusan']
        );

        $this->account = BudgetAccount::firstOrCreate(
            ['code' => '521211'],
            ['name' => 'Belanja Bahan']
        );

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
     * Skenario R-12 / RBC-01:
     * Kontinuitas saldo ketika versi anggaran berubah (Revisi DIPA Pagu Turun: 100M -> 85M).
     *
     * Posisi awal (Versi 00):
     * P = 100.000.000, C = 20.000.000 (DP), R = 70.000.000 (RI), S = 10.000.000.
     *
     * Versi 01 DRAFT dibuat dengan pagu 85.000.000, lalu diaktifkan via BudgetImportPipelineService::activateVersion().
     *
     * Expected Result pada Versi 01 yang aktif:
     * P = 85.000.000
     * C = 20.000.000
     * R = 70.000.000
     * S = -5.000.000
     * Seluruh kontribusi transaksi lama tetap diperhitungkan satu kali.
     */
    public function test_version_continuity_preserves_commitments_and_realizations_when_version_changes(): void
    {
        // 2. Buat Versi 00 berstatus ACTIVE dengan pagu Rp100.000.000
        $version00 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA Awal 2026',
            'status' => 'ACTIVE',
            'total_budget' => 100000000.00,
        ]);

        $bucket00 = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $version00->id,
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
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 100000000.00,
        ]);

        $line00 = BudgetLine::create([
            'budget_version_id' => $version00->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $bucket00->id,
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'rba_sequence_no' => '001',
            'description' => 'Belanja Bahan Praktikum',
            'volume' => 1.00,
            'unit' => 'Paket',
            'unit_price' => 100000000.00,
            'budget_amount' => 100000000.00,
            'status' => 'ACTIVE',
        ]);

        // 3. Buat transaksi DP Rp20.000.000 dan realisasi internal RI Rp70.000.000 yang benar-benar memiliki relasi Submission dengan pos tersebut
        $dpSubmission = BudgetControlService::recordTransaction([
            'budget_line_id' => $line00->id,
            'evidence_number' => 'FRA/2026/DP-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Uang Muka Kerja Praktikum (DP)',
            'amount' => 20000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        $riSubmission = BudgetControlService::recordTransaction([
            'budget_line_id' => $line00->id,
            'evidence_number' => 'FRA/2026/RI-001',
            'transaction_date' => '2026-03-05',
            'title' => 'Realisasi Internal Pembelian Bahan (RI)',
            'amount' => 70000000.00,
            'submit_action' => 'PROCESSING',
        ], $this->ptkUser);

        BudgetControlService::transitionStatus(
            submission: $riSubmission,
            targetStatus: 'FINAL',
            actor: $this->ptuUser,
            notes: 'Penyelesaian SPJ Realisasi Internal (RI)'
        );

        // 4. Pastikan posisi awal:
        // P = 100.000.000, C = 20.000.000, R = 70.000.000, S = 10.000.000
        $bucket00->refresh();
        $this->assertEquals(100000000.00, (float) $bucket00->allocated_budget, 'Pagu awal Versi 00 harus 100.000.000');
        $this->assertEquals(20000000.00, (float) $bucket00->reserved_budget, 'Komitmen awal Versi 00 harus 20.000.000 (DP)');
        $this->assertEquals(70000000.00, (float) $bucket00->realized_budget, 'Realisasi awal Versi 00 harus 70.000.000 (RI)');
        $this->assertEquals(10000000.00, (float) $bucket00->available_balance, 'Saldo awal Versi 00 harus 10.000.000 (S = P - C - R)');
        $this->assertEquals('PROCESSING', $dpSubmission->fresh()->status);
        $this->assertEquals('FINAL', $riSubmission->fresh()->status);

        // 5. Buat Versi 01 berstatus DRAFT untuk pos yang sama dengan pagu Rp85.000.000
        $version01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'Revisi DIPA 01 2026',
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

        BudgetLine::create([
            'budget_version_id' => $version01->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $bucket01->id,
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'rba_sequence_no' => '001',
            'description' => 'Belanja Bahan Praktikum (Revisi)',
            'volume' => 1.00,
            'unit' => 'Paket',
            'unit_price' => 85000000.00,
            'budget_amount' => 85000000.00,
            'status' => 'ACTIVE',
        ]);

        // 6. Aktifkan Versi 01 menggunakan method aktivasi yang benar-benar digunakan aplikasi, bukan simulasi penggantian status langsung
        BudgetImportPipelineService::activateVersion($version01, $this->adminUser);

        // 7. Periksa posisi anggaran pada versi aktif yang baru
        $activeVersion = BudgetVersion::where('fiscal_year_id', $this->fiscalYear->id)
            ->where('funding_source_id', $this->fundingSource->id)
            ->where('status', 'ACTIVE')
            ->first();

        $this->assertNotNull($activeVersion, 'Versi aktif baru harus ada.');
        $this->assertEquals($version01->id, $activeVersion->id, 'Versi aktif harus Versi 01.');

        $activeBucket = BudgetBucket::where('budget_version_id', $activeVersion->id)
            ->where('department_id', $this->department->id)
            ->where('account_code', $this->account->code)
            ->first();

        $this->assertNotNull($activeBucket, 'Pos anggaran (control bucket) pada versi aktif baru harus ditemukan.');

        // Expected Result sesuai Proposal RBC-01, Persamaan (4), Tabel 23 dan Skenario R-12:
        // P = 85.000.000
        // C = 20.000.000
        // R = 70.000.000
        // S = -5.000.000
        // Seluruh kontribusi transaksi lama tetap diperhitungkan satu kali
        $this->assertEquals(85000000.00, (float) $activeBucket->allocated_budget, 'Pagu (P) pada versi baru harus Rp85.000.000');
        $this->assertEquals(20000000.00, (float) $activeBucket->reserved_budget, 'Komitmen (C) dari DP lama harus tetap diperhitungkan sebesar Rp20.000.000');
        $this->assertEquals(70000000.00, (float) $activeBucket->realized_budget, 'Realisasi (R) dari RI lama harus tetap diperhitungkan sebesar Rp70.000.000');
        $this->assertEquals(-5000000.00, (float) $activeBucket->available_balance, 'Saldo tersedia (S) harus -Rp5.000.000 (S = P - C - R)');
    }
}
