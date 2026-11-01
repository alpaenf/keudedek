<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Submission;
use App\Models\User;
use App\Services\BudgetCalculationService;
use App\Services\BudgetControlService;
use App\Services\BudgetImportPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundingSourceMultiVersionSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected FiscalYear $fy;
    protected Department $dept;
    protected FundingSource $fundRM;
    protected FundingSource $fundBoptn;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );

        $this->dept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Jurusan Teknik Informatika', 'is_active' => true]
        );

        $this->fundRM = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->fundBoptn = FundingSource::firstOrCreate(
            ['code' => 'BOPTN'],
            ['name' => 'Bantuan Operasional PTN', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->user = User::factory()->create([
            'email' => 'operator.jtif@ft.unsoed.ac.id',
            'department_id' => $this->dept->id,
            'role' => 'PTK',
        ]);
    }

    /**
     * Test isolation between RM and BOPTN across multiple versions.
     */
    public function test_multi_funding_source_and_multi_version_continuity_simulation(): void
    {
        // 1. Skenario A: RM Rev 00 (P = 100.000.000)
        $vRm00 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA RM Awal 2026',
            'status' => 'DRAFT',
            'effective_at' => '2026-01-02',
        ]);

        $bucketRm00 = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm00->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan RM',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000,
            'initial_budget' => 100000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 100000000,
        ]);

        // 2. Skenario B: BOPTN Rev 00 (P = 60.000.000)
        $vBoptn00 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundBoptn->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'BOPTN Awal 2026',
            'status' => 'DRAFT',
            'effective_at' => '2026-01-02',
        ]);

        $bucketBoptn00 = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vBoptn00->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundBoptn->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan BOPTN',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 60000000,
            'initial_budget' => 60000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 60000000,
        ]);

        // 3. Skenario C: Aktifkan Keduanya
        BudgetImportPipelineService::activateVersion($vRm00, $this->user);
        BudgetImportPipelineService::activateVersion($vBoptn00, $this->user);

        $this->assertEquals('ACTIVE', $vRm00->fresh()->status);
        $this->assertEquals('ACTIVE', $vBoptn00->fresh()->status);

        // 4. Skenario D: Buat Transaksi pada masing-masing sumber dana
        // Transaksi RM: DP 20jt (PROCESSING), RI 30jt (FINAL)
        $subRmDp = Submission::create([
            'submission_number' => 'SUB-RM-DP-01',
            'title' => 'Uang Muka Praktikum RM',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketRm00->id,
            'amount' => 20000000,
            'status' => 'PROCESSING',
            'created_by' => $this->user->id,
        ]);

        $subRmRi = Submission::create([
            'submission_number' => 'SUB-RM-RI-01',
            'title' => 'Realisasi Bahan RM',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketRm00->id,
            'amount' => 30000000,
            'status' => 'FINAL',
            'created_by' => $this->user->id,
        ]);

        // Transaksi BOPTN: DP 10jt (PROCESSING), RI 15jt (FINAL)
        $subBoptnDp = Submission::create([
            'submission_number' => 'SUB-BOPTN-DP-01',
            'title' => 'Uang Muka Riset BOPTN',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketBoptn00->id,
            'amount' => 10000000,
            'status' => 'PROCESSING',
            'created_by' => $this->user->id,
        ]);

        $subBoptnRi = Submission::create([
            'submission_number' => 'SUB-BOPTN-RI-01',
            'title' => 'Realisasi Riset BOPTN',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketBoptn00->id,
            'amount' => 15000000,
            'status' => 'FINAL',
            'created_by' => $this->user->id,
        ]);

        // Sinkronisasi bucket awal
        BudgetCalculationService::synchronizeBucketFromTransactions($bucketRm00);
        BudgetCalculationService::synchronizeBucketFromTransactions($bucketBoptn00);

        // Verifikasi Saldo Awal RM: P=100jt, C=20jt, R=30jt, S=50jt
        $bRmFresh = $bucketRm00->fresh();
        $this->assertEquals(100000000, (float) $bRmFresh->allocated_budget);
        $this->assertEquals(20000000, (float) $bRmFresh->reserved_budget);
        $this->assertEquals(30000000, (float) $bRmFresh->realized_budget);
        $this->assertEquals(50000000, (float) $bRmFresh->available_balance);

        // Verifikasi Saldo Awal BOPTN: P=60jt, C=10jt, R=15jt, S=35jt
        $bBoptnFresh = $bucketBoptn00->fresh();
        $this->assertEquals(60000000, (float) $bBoptnFresh->allocated_budget);
        $this->assertEquals(10000000, (float) $bBoptnFresh->reserved_budget);
        $this->assertEquals(15000000, (float) $bBoptnFresh->realized_budget);
        $this->assertEquals(35000000, (float) $bBoptnFresh->available_balance);

        // 5. Skenario E: Upload/Buat RM Rev 01 dengan pagu baru (misal P = 120.000.000)
        $vRm01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'DIPA RM Revisi 01 2026',
            'status' => 'DRAFT',
            'effective_at' => '2026-06-01',
        ]);

        $bucketRm01 = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm01->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan RM',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 120000000,
            'initial_budget' => 120000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 120000000,
        ]);

        // Pastikan DRAFT tidak memengaruhi versi aktif RM
        $this->assertEquals('ACTIVE', $vRm00->fresh()->status);
        $this->assertEquals('DRAFT', $vRm01->fresh()->status);

        // 6. Skenario F: Aktifkan RM Rev 01
        BudgetImportPipelineService::activateVersion($vRm01, $this->user);

        // 7. Skenario G: Pastikan transaksi RM tetap berlanjut pada RM Rev 01
        $this->assertEquals('ARCHIVED', $vRm00->fresh()->status);
        $this->assertEquals('ACTIVE', $vRm01->fresh()->status);

        // Cek bucket RM Rev 01: P=120jt, C=20jt, R=30jt, S=70jt (Persamaan 4: S = 120 - 20 - 30 = 70jt)
        $bRm01Fresh = $bucketRm01->fresh();
        $this->assertEquals(120000000, (float) $bRm01Fresh->allocated_budget);
        $this->assertEquals(20000000, (float) $bRm01Fresh->reserved_budget, 'Komitmen RM tidak boleh tercampur dengan BOPTN');
        $this->assertEquals(30000000, (float) $bRm01Fresh->realized_budget, 'Realisasi RM tidak boleh tercampur dengan BOPTN');
        $this->assertEquals(70000000, (float) $bRm01Fresh->available_balance);

        // 8. Skenario H: Pastikan BOPTN Rev 00 TIDAK BERUBAH dan TETAP ACTIVE!
        $this->assertEquals('ACTIVE', $vBoptn00->fresh()->status, 'BOPTN Rev 00 harus tetap ACTIVE saat RM Rev 01 diaktifkan');
        $bBoptnAfterRmRev = $bucketBoptn00->fresh();
        $this->assertEquals(60000000, (float) $bBoptnAfterRmRev->allocated_budget);
        $this->assertEquals(10000000, (float) $bBoptnAfterRmRev->reserved_budget, 'Komitmen BOPTN tidak boleh hilang');
        $this->assertEquals(15000000, (float) $bBoptnAfterRmRev->realized_budget, 'Realisasi BOPTN tidak boleh hilang');
        $this->assertEquals(35000000, (float) $bBoptnAfterRmRev->available_balance);

        // Pastikan transaksi BOPTN tetap mengarah ke bucket BOPTN, bukan tertelan ke bucket RM Rev 01
        $this->assertEquals($bucketBoptn00->id, $subBoptnDp->fresh()->budget_bucket_id, 'Transaksi BOPTN tidak boleh pindah ke bucket RM');
        $this->assertEquals($bucketBoptn00->id, $subBoptnRi->fresh()->budget_bucket_id, 'Transaksi BOPTN tidak boleh pindah ke bucket RM');
    }
}
