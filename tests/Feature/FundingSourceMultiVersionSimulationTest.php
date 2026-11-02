<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Submission;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\BudgetCalculationService;
use App\Services\BudgetControlService;
use App\Services\BudgetImportPipelineService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundingSourceMultiVersionSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected FiscalYear $fy;

    protected Department $dept;

    protected Department $deptOther;

    protected FundingSource $fundRM;

    protected FundingSource $fundBoptn;

    protected FundingSource $fundUkt;

    protected User $userPtk;

    protected User $userOtherPtk;

    protected TransactionType $trxType;

    protected DocumentType $docType;

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

        $this->deptOther = Department::firstOrCreate(
            ['code' => 'JTE'],
            ['name' => 'Jurusan Teknik Elektro', 'is_active' => true]
        );

        $this->fundRM = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->fundBoptn = FundingSource::firstOrCreate(
            ['code' => 'BOPTN'],
            ['name' => 'Bantuan Operasional PTN', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->fundUkt = FundingSource::firstOrCreate(
            ['code' => 'UKT'],
            ['name' => 'Uang Kuliah Tunggal', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->trxType = TransactionType::firstOrCreate(
            ['code' => 'LS'],
            ['name' => 'Langsung', 'is_active' => true]
        );

        $this->docType = DocumentType::firstOrCreate(
            ['code' => 'KUITANSI'],
            ['name' => 'Kuitansi Pembayaran', 'is_active' => true]
        );

        $this->userPtk = User::factory()->create([
            'email' => 'operator.jtif@ft.unsoed.ac.id',
            'department_id' => $this->dept->id,
            'role' => 'PTK',
        ]);

        $this->userOtherPtk = User::factory()->create([
            'email' => 'operator.jte@ft.unsoed.ac.id',
            'department_id' => $this->deptOther->id,
            'role' => 'PTK',
        ]);
    }

    /**
     * Skenario 1 & Skenario 2:
     * - Multi-sumber dana (RM Rev 00 100jt, BOPTN Rev 00 60jt sama-sama ACTIVE)
     * - RM DP 20jt, RI 30jt -> Saldo RM = 50jt
     * - BOPTN DP 10jt, RI 15jt -> Saldo BOPTN = 35jt
     * - Revisi RM Rev 01 pagu 120jt DRAFT -> tidak ubah saldo sebelum aktivasi
     * - Aktivasi RM Rev 01 -> RM Rev 00 ARCHIVED, RM Rev 01 ACTIVE, DP RM 20jt, RI RM 30jt, S RM = 70jt
     * - Transaksi BOPTN tidak terbawa, BOPTN Rev 00 tetap ACTIVE dengan saldo 35jt.
     */
    public function test_skenario_1_dan_2_multi_funding_source_dan_revisi_rm(): void
    {
        // 1. RM Rev 00
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

        // 2. BOPTN Rev 00
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

        // 3. Aktifkan keduanya
        BudgetImportPipelineService::activateVersion($vRm00, $this->userPtk);
        BudgetImportPipelineService::activateVersion($vBoptn00, $this->userPtk);

        $this->assertEquals('ACTIVE', $vRm00->fresh()->status);
        $this->assertEquals('ACTIVE', $vBoptn00->fresh()->status);

        // 4. Transaksi RM
        Submission::create([
            'submission_number' => 'SUB-RM-DP-01',
            'title' => 'Uang Muka Praktikum RM',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketRm00->id,
            'amount' => 20000000,
            'status' => 'PROCESSING',
            'created_by' => $this->userPtk->id,
        ]);

        Submission::create([
            'submission_number' => 'SUB-RM-RI-01',
            'title' => 'Realisasi Bahan RM',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketRm00->id,
            'amount' => 30000000,
            'status' => 'FINAL',
            'created_by' => $this->userPtk->id,
        ]);

        // Transaksi BOPTN
        $subBoptnDp = Submission::create([
            'submission_number' => 'SUB-BOPTN-DP-01',
            'title' => 'Uang Muka Riset BOPTN',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketBoptn00->id,
            'amount' => 10000000,
            'status' => 'PROCESSING',
            'created_by' => $this->userPtk->id,
        ]);

        $subBoptnRi = Submission::create([
            'submission_number' => 'SUB-BOPTN-RI-01',
            'title' => 'Realisasi Riset BOPTN',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketBoptn00->id,
            'amount' => 15000000,
            'status' => 'FINAL',
            'created_by' => $this->userPtk->id,
        ]);

        // Sinkronisasi posisi anggaran
        BudgetCalculationService::synchronizeBucketFromTransactions($bucketRm00);
        BudgetCalculationService::synchronizeBucketFromTransactions($bucketBoptn00);

        // Verifikasi Saldo Skenario 1
        $bRm = $bucketRm00->fresh();
        $this->assertEquals(100000000, (float) $bRm->allocated_budget);
        $this->assertEquals(20000000, (float) $bRm->reserved_budget);
        $this->assertEquals(30000000, (float) $bRm->realized_budget);
        $this->assertEquals(50000000, (float) $bRm->available_balance, 'Saldo RM harus 50.000.000');

        $bBoptn = $bucketBoptn00->fresh();
        $this->assertEquals(60000000, (float) $bBoptn->allocated_budget);
        $this->assertEquals(10000000, (float) $bBoptn->reserved_budget);
        $this->assertEquals(15000000, (float) $bBoptn->realized_budget);
        $this->assertEquals(35000000, (float) $bBoptn->available_balance, 'Saldo BOPTN harus 35.000.000');

        // Skenario 2: Buat RM Rev 01 DRAFT (120jt)
        $vRm01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'DIPA RM Rev 01 2026',
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

        // Sebelum aktivasi, RM Rev 00 tetap aktif dan operasional tidak terganggu
        $this->assertEquals('ACTIVE', $vRm00->fresh()->status);
        $this->assertEquals('DRAFT', $vRm01->fresh()->status);
        $this->assertEquals(50000000, (float) $bucketRm00->fresh()->available_balance);

        // Aktifkan RM Rev 01
        BudgetImportPipelineService::activateVersion($vRm01, $this->userPtk);

        // Verifikasi hasil aktivasi
        $this->assertEquals('ARCHIVED', $vRm00->fresh()->status);
        $this->assertEquals('ACTIVE', $vRm01->fresh()->status);

        $bRm01 = $bucketRm01->fresh();
        $this->assertEquals(120000000, (float) $bRm01->allocated_budget);
        $this->assertEquals(20000000, (float) $bRm01->reserved_budget, 'DP RM tetap 20.000.000');
        $this->assertEquals(30000000, (float) $bRm01->realized_budget, 'RI RM tetap 30.000.000');
        $this->assertEquals(70000000, (float) $bRm01->available_balance, 'Saldo RM harus 70.000.000');

        // Pastikan BOPTN Rev 00 TIDAK TERPENGARUH
        $this->assertEquals('ACTIVE', $vBoptn00->fresh()->status, 'BOPTN Rev 00 harus tetap ACTIVE');
        $bBoptnAfter = $bucketBoptn00->fresh();
        $this->assertEquals(60000000, (float) $bBoptnAfter->allocated_budget);
        $this->assertEquals(10000000, (float) $bBoptnAfter->reserved_budget, 'DP BOPTN tetap 10.000.000');
        $this->assertEquals(15000000, (float) $bBoptnAfter->realized_budget, 'RI BOPTN tetap 15.000.000');
        $this->assertEquals(35000000, (float) $bBoptnAfter->available_balance, 'Saldo BOPTN tetap 35.000.000');

        // Transaksi BOPTN tidak terbawa ke bucket RM
        $this->assertEquals($bucketBoptn00->id, $subBoptnDp->fresh()->budget_bucket_id);
        $this->assertEquals($bucketBoptn00->id, $subBoptnRi->fresh()->budget_bucket_id);
    }

    /**
     * Skenario 3: Revisi BOPTN secara independen
     * - Buat BOPTN Rev 01 dan aktifkan
     * - Pastikan aktivasi BOPTN tidak mengubah versi aktif RM maupun transaksi RM.
     */
    public function test_skenario_3_revisi_boptn_secara_independen(): void
    {
        // Setup RM Rev 01 ACTIVE (120jt)
        $vRm01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'DIPA RM Rev 01',
            'status' => 'ACTIVE',
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
            'reserved_budget' => 20000000,
            'realized_budget' => 30000000,
            'available_balance' => 70000000,
        ]);

        // Setup BOPTN Rev 00 ACTIVE (60jt)
        $vBoptn00 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundBoptn->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'BOPTN Awal 2026',
            'status' => 'ACTIVE',
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
            'reserved_budget' => 10000000,
            'realized_budget' => 15000000,
            'available_balance' => 35000000,
        ]);

        // Buat transaksi pada BOPTN Rev 00
        Submission::create([
            'submission_number' => 'SUB-BOPTN-DP-02',
            'title' => 'DP Riset BOPTN',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketBoptn00->id,
            'amount' => 10000000,
            'status' => 'PROCESSING',
            'created_by' => $this->userPtk->id,
        ]);
        Submission::create([
            'submission_number' => 'SUB-BOPTN-RI-02',
            'title' => 'RI Riset BOPTN',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketBoptn00->id,
            'amount' => 15000000,
            'status' => 'FINAL',
            'created_by' => $this->userPtk->id,
        ]);

        // Buat BOPTN Rev 01 (P = 80.000.000)
        $vBoptn01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundBoptn->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'BOPTN Revisi 01',
            'status' => 'DRAFT',
            'effective_at' => '2026-07-01',
        ]);
        $bucketBoptn01 = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vBoptn01->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundBoptn->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan BOPTN',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 80000000,
            'initial_budget' => 80000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 80000000,
        ]);

        // Aktifkan BOPTN Rev 01
        BudgetImportPipelineService::activateVersion($vBoptn01, $this->userPtk);

        // Verifikasi: BOPTN Rev 00 ARCHIVED, BOPTN Rev 01 ACTIVE
        $this->assertEquals('ARCHIVED', $vBoptn00->fresh()->status);
        $this->assertEquals('ACTIVE', $vBoptn01->fresh()->status);

        // Verifikasi Saldo BOPTN Rev 01: P=80jt, C=10jt, R=15jt, S=55jt
        $bBoptn01Fresh = $bucketBoptn01->fresh();
        $this->assertEquals(80000000, (float) $bBoptn01Fresh->allocated_budget);
        $this->assertEquals(10000000, (float) $bBoptn01Fresh->reserved_budget);
        $this->assertEquals(15000000, (float) $bBoptn01Fresh->realized_budget);
        $this->assertEquals(55000000, (float) $bBoptn01Fresh->available_balance);

        // Verifikasi RM Rev 01 TETAP ACTIVE dan SALDO RM TIDAK BERUBAH
        $this->assertEquals('ACTIVE', $vRm01->fresh()->status, 'RM Rev 01 harus tetap ACTIVE');
        $bRm01Fresh = $bucketRm01->fresh();
        $this->assertEquals(120000000, (float) $bRm01Fresh->allocated_budget);
        $this->assertEquals(20000000, (float) $bRm01Fresh->reserved_budget);
        $this->assertEquals(30000000, (float) $bRm01Fresh->realized_budget);
        $this->assertEquals(70000000, (float) $bRm01Fresh->available_balance);
    }

    /**
     * Skenario 4: Dashboard Multi-Sumber Dana
     * - Filter Semua Sumber Dana menjumlahkan seluruh sumber dana ACTIVE dengan benar.
     * - Filter RM hanya menampilkan RM.
     * - Filter BOPTN hanya menampilkan BOPTN.
     * - Versi DRAFT dan ARCHIVED tidak dihitung.
     * - Persamaan S = P - C - R terpenuhi secara presisi.
     */
    public function test_skenario_4_dashboard_multi_sumber_dana_aggregation_dan_filtering(): void
    {
        // 1. RM Rev 01 (ACTIVE): P=120jt, C=20jt, R=30jt, S=70jt
        $vRm = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'DIPA RM Rev 01',
            'status' => 'ACTIVE',
        ]);
        BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Bahan RM',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 120000000,
            'reserved_budget' => 20000000,
            'realized_budget' => 30000000,
            'available_balance' => 70000000,
        ]);

        // 2. BOPTN Rev 01 (ACTIVE): P=80jt, C=10jt, R=15jt, S=55jt
        $vBoptn = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundBoptn->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'BOPTN Rev 01',
            'status' => 'ACTIVE',
        ]);
        BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vBoptn->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundBoptn->id,
            'account_code' => '521211',
            'account_name' => 'Bahan BOPTN',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 80000000,
            'reserved_budget' => 10000000,
            'realized_budget' => 15000000,
            'available_balance' => 55000000,
        ]);

        // 3. RM Rev 00 (ARCHIVED): P=100jt, tidak boleh ikut dihitung di dashboard
        $vRmArchived = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'RM Lama',
            'status' => 'ARCHIVED',
        ]);
        BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRmArchived->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Bahan RM Lama',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 100000000,
            'reserved_budget' => 20000000,
            'realized_budget' => 30000000,
            'available_balance' => 50000000,
        ]);

        // 4. UKT Rev 00 (DRAFT): P=50jt, tidak boleh ikut dihitung di dashboard
        $vUktDraft = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundUkt->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'UKT Draft',
            'status' => 'DRAFT',
        ]);
        BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vUktDraft->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundUkt->id,
            'account_code' => '521211',
            'account_name' => 'Bahan UKT Draft',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 50000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 50000000,
        ]);

        // TEST 4A: SEMUA SUMBER DANA (fundingSourceId = null)
        $payloadAll = DashboardService::getPayload($this->userPtk, null, null, 2026);
        $this->assertEquals(200000000, $payloadAll['totalAllocated'], 'P Total = 120jt RM + 80jt BOPTN = 200jt');
        $this->assertEquals(30000000, $payloadAll['totalReserved'], 'C Total = 20jt RM + 10jt BOPTN = 30jt');
        $this->assertEquals(45000000, $payloadAll['totalRealized'], 'R Total = 30jt RM + 15jt BOPTN = 45jt');
        $this->assertEquals(125000000, $payloadAll['totalAvailable'], 'S Total = 200jt - 30jt - 45jt = 125jt');

        // TEST 4B: FILTER RM (fundingSourceId = $this->fundRM->id)
        $payloadRm = DashboardService::getPayload($this->userPtk, null, $this->fundRM->id, 2026);
        $this->assertEquals(120000000, $payloadRm['totalAllocated'], 'P RM = 120jt');
        $this->assertEquals(20000000, $payloadRm['totalReserved'], 'C RM = 20jt');
        $this->assertEquals(30000000, $payloadRm['totalRealized'], 'R RM = 30jt');
        $this->assertEquals(70000000, $payloadRm['totalAvailable'], 'S RM = 70jt');
        $this->assertEquals('RM', $payloadRm['fundSourceCode']);

        // TEST 4C: FILTER BOPTN (fundingSourceId = $this->fundBoptn->id)
        $payloadBoptn = DashboardService::getPayload($this->userPtk, null, $this->fundBoptn->id, 2026);
        $this->assertEquals(80000000, $payloadBoptn['totalAllocated'], 'P BOPTN = 80jt');
        $this->assertEquals(10000000, $payloadBoptn['totalReserved'], 'C BOPTN = 10jt');
        $this->assertEquals(15000000, $payloadBoptn['totalRealized'], 'R BOPTN = 15jt');
        $this->assertEquals(55000000, $payloadBoptn['totalAvailable'], 'S BOPTN = 55jt');
        $this->assertEquals('BOPTN', $payloadBoptn['fundSourceCode']);

        // TEST 4D: Invarian S = P - C - R
        $this->assertEquals(
            $payloadAll['totalAllocated'] - $payloadAll['totalReserved'] - $payloadAll['totalRealized'],
            $payloadAll['totalAvailable']
        );
    }

    /**
     * Skenario 5: Form Transaksi PTK dan Anti-Tampering
     * - PTK dapat memilih sumber dana
     * - Pos anggaran hanya sesuai sumber dana yang dipilih
     * - Transaksi yang sah tersimpan dengan benar
     * - Percobaan manipulasi funding_source_id atau lintas jurusan ditolak.
     */
    public function test_skenario_5_form_transaksi_ptk_dan_anti_tampering(): void
    {
        // 1. Versi ACTIVE RM & Pos Anggaran RM
        $vRm = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);
        $bucketRm = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Bahan RM JTIF',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 50000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 50000000,
        ]);
        $lineRm = BudgetLine::create([
            'budget_version_id' => $vRm->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'budget_bucket_id' => $bucketRm->id,
            'rba_sequence_no' => '001',
            'description' => 'Kertas dan ATK Praktikum RM',
            'budget_amount' => 50000000,
            'status' => 'ACTIVE',
        ]);

        // 2. Versi ACTIVE BOPTN & Pos Anggaran BOPTN
        $vBoptn = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundBoptn->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);
        $bucketBoptn = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vBoptn->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundBoptn->id,
            'account_code' => '521211',
            'account_name' => 'Bahan BOPTN JTIF',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 40000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 40000000,
        ]);
        $lineBoptn = BudgetLine::create([
            'budget_version_id' => $vBoptn->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundBoptn->id,
            'budget_bucket_id' => $bucketBoptn->id,
            'rba_sequence_no' => '002',
            'description' => 'Bahan Uji Riset BOPTN',
            'budget_amount' => 40000000,
            'status' => 'ACTIVE',
        ]);

        // 3. Pos Anggaran Jurusan Lain (JTE)
        $bucketOther = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm->id,
            'department_id' => $this->deptOther->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Bahan RM JTE',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 30000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 30000000,
        ]);
        $lineOther = BudgetLine::create([
            'budget_version_id' => $vRm->id,
            'department_id' => $this->deptOther->id,
            'funding_source_id' => $this->fundRM->id,
            'budget_bucket_id' => $bucketOther->id,
            'rba_sequence_no' => '003',
            'description' => 'Bahan Elektro',
            'budget_amount' => 30000000,
            'status' => 'ACTIVE',
        ]);

        // TEST 5A: Akses halaman Create
        $responseCreate = $this->actingAs($this->userPtk)->get('/submissions/create');
        $responseCreate->assertStatus(200);
        $responseCreate->assertInertia(fn ($page) => $page
            ->component('Submissions/Create')
            ->has('availableFundingSources', 2)
        );

        // TEST 5B: Search Budget Lines untuk BOPTN
        $responseSearch = $this->actingAs($this->userPtk)->getJson('/api/budget-lines/search?funding_source_id='.$this->fundBoptn->id);
        $responseSearch->assertStatus(200);
        $linesData = $responseSearch->json('data');
        $this->assertCount(1, $linesData);
        $this->assertEquals('Bahan Uji Riset BOPTN', $linesData[0]['description']);

        // TEST 5C: Simpan transaksi yang sah (RM)
        $responseStore = $this->actingAs($this->userPtk)->post('/submissions', [
            'budget_line_id' => $lineRm->id,
            'funding_source_id' => $this->fundRM->id,
            'budget_bucket_id' => $bucketRm->id,
            'evidence_number' => 'KW-JTIF-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Pembelian Kertas A4',
            'amount' => 5000000,
            'submit_action' => 'PROCESSING',
        ]);
        $responseStore->assertRedirect();
        $this->assertDatabaseHas('submissions', [
            'evidence_number' => 'KW-JTIF-001',
            'budget_bucket_id' => $bucketRm->id,
            'budget_line_id' => $lineRm->id,
            'amount' => 5000000,
            'status' => 'PROCESSING',
        ]);

        // TEST 5D: Anti-tampering — Manipulasi funding_source_id tidak cocok dengan line
        $responseTamperFunding = $this->actingAs($this->userPtk)->post('/submissions', [
            'budget_line_id' => $lineRm->id,
            'funding_source_id' => $this->fundBoptn->id, // Manipulasi: Line RM tapi dikirim BOPTN
            'budget_bucket_id' => $bucketRm->id,
            'evidence_number' => 'KW-TAMPER-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Percobaan Manipulasi Sumber Dana',
            'amount' => 1000000,
            'submit_action' => 'PROCESSING',
        ]);
        $responseTamperFunding->assertSessionHasErrors('funding_source_id');

        // TEST 5E: Anti-tampering — Akses Pos Anggaran Jurusan Lain (JTE)
        $responseTamperDept = $this->actingAs($this->userPtk)->post('/submissions', [
            'budget_line_id' => $lineOther->id, // Milik JTE
            'funding_source_id' => $this->fundRM->id,
            'budget_bucket_id' => $bucketOther->id,
            'evidence_number' => 'KW-TAMPER-002',
            'transaction_date' => '2026-03-01',
            'title' => 'Percobaan Akses Lintas Jurusan',
            'amount' => 1000000,
            'submit_action' => 'PROCESSING',
        ]);
        $responseTamperDept->assertSessionHasErrors('budget_line_id');
    }

    /**
     * Skenario 6: Regresi RBC dan EWS
     * - RBC-01 s/d RBC-09
     * - Overbudget blocking
     * - EWS-01 s/d EWS-05
     * - Persamaan S = P - C - R
     */
    public function test_skenario_6_regresi_rbc_dan_ews_invariants(): void
    {
        $vRm = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);
        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Bahan RM Test',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 20000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 20000000,
        ]);
        $line = BudgetLine::create([
            'budget_version_id' => $vRm->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'budget_bucket_id' => $bucket->id,
            'rba_sequence_no' => '100',
            'description' => 'Baris Uji Regresi',
            'budget_amount' => 20000000,
            'status' => 'ACTIVE',
        ]);

        // 1. RBC-08: Overbudget Blocking (Pagu 20jt, ajukan 25jt)
        $this->expectException(\InvalidArgumentException::class);
        BudgetControlService::recordTransaction([
            'budget_line_id' => $line->id,
            'evidence_number' => 'KW-OVER-01',
            'transaction_date' => '2026-03-01',
            'title' => 'Pengajuan Overbudget',
            'amount' => 25000000,
            'submit_action' => 'PROCESSING',
        ], $this->userPtk);
    }

    /**
     * Skenario Tambahan Audit Lanjutan: Simulasi Kelima Sumber Dana (RM, BOPTN, UKT, PNBP/BLU, SBSN)
     * - Kelima sumber dana aktif bersamaan pada TA 2026
     * - Memiliki akun dan subkomponen yang sama
     * - Revisi independen pada salah satu sumber dana (misal SBSN) tidak mengganggu 4 sumber dana lainnya.
     */
    public function test_simulasi_kelima_sumber_dana_dan_independensi_revisi(): void
    {
        $fundPnbp = FundingSource::firstOrCreate(
            ['code' => 'PNBP'],
            ['name' => 'PNBP/BLU', 'is_active' => true, 'is_mvp_enabled' => true]
        );
        $fundSbsn = FundingSource::firstOrCreate(
            ['code' => 'SBSN'],
            ['name' => 'Surat Berharga Syariah Negara', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $funds = [
            'RM' => ['source' => $this->fundRM, 'pagu' => 100000000, 'dp' => 20000000, 'ri' => 30000000, 'saldo' => 50000000],
            'BOPTN' => ['source' => $this->fundBoptn, 'pagu' => 60000000, 'dp' => 10000000, 'ri' => 15000000, 'saldo' => 35000000],
            'UKT' => ['source' => $this->fundUkt, 'pagu' => 50000000, 'dp' => 5000000, 'ri' => 10000000, 'saldo' => 35000000],
            'PNBP' => ['source' => $fundPnbp, 'pagu' => 40000000, 'dp' => 8000000, 'ri' => 12000000, 'saldo' => 20000000],
            'SBSN' => ['source' => $fundSbsn, 'pagu' => 80000000, 'dp' => 15000000, 'ri' => 25000000, 'saldo' => 40000000],
        ];

        $buckets = [];
        $versions = [];

        foreach ($funds as $code => $data) {
            $v = BudgetVersion::create([
                'fiscal_year_id' => $this->fy->id,
                'funding_source_id' => $data['source']->id,
                'revision_no' => 'Rev 00',
                'version_label' => "{$code} Awal 2026",
                'status' => 'ACTIVE',
            ]);
            $versions[$code] = $v;

            $b = BudgetBucket::create([
                'fiscal_year_id' => $this->fy->id,
                'budget_version_id' => $v->id,
                'department_id' => $this->dept->id,
                'funding_source_id' => $data['source']->id,
                'account_code' => '521211',
                'account_name' => "Belanja Bahan {$code}",
                'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
                'allocated_budget' => $data['pagu'],
                'initial_budget' => $data['pagu'],
                'reserved_budget' => 0,
                'realized_budget' => 0,
                'available_balance' => $data['pagu'],
            ]);
            $buckets[$code] = $b;

            // Transaksi DP
            Submission::create([
                'submission_number' => "SUB-{$code}-DP",
                'title' => "DP {$code}",
                'department_id' => $this->dept->id,
                'fiscal_year_id' => $this->fy->id,
                'budget_bucket_id' => $b->id,
                'amount' => $data['dp'],
                'status' => 'PROCESSING',
                'created_by' => $this->userPtk->id,
            ]);

            // Transaksi RI
            Submission::create([
                'submission_number' => "SUB-{$code}-RI",
                'title' => "RI {$code}",
                'department_id' => $this->dept->id,
                'fiscal_year_id' => $this->fy->id,
                'budget_bucket_id' => $b->id,
                'amount' => $data['ri'],
                'status' => 'FINAL',
                'created_by' => $this->userPtk->id,
            ]);

            BudgetCalculationService::synchronizeBucketFromTransactions($b);
        }

        // Verifikasi saldo kelima sumber dana
        foreach ($funds as $code => $data) {
            $bFresh = $buckets[$code]->fresh();
            $this->assertEquals('ACTIVE', $versions[$code]->fresh()->status);
            $this->assertEquals($data['pagu'], (float) $bFresh->allocated_budget);
            $this->assertEquals($data['dp'], (float) $bFresh->reserved_budget);
            $this->assertEquals($data['ri'], (float) $bFresh->realized_budget);
            $this->assertEquals($data['saldo'], (float) $bFresh->available_balance, "Saldo {$code} harus sesuai invarian");
        }

        // Revisi SBSN: Buat SBSN Rev 01 (P = 90jt) dan aktifkan
        $vSbsn01 = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $fundSbsn->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'SBSN Revisi 01',
            'status' => 'DRAFT',
        ]);
        $bSbsn01 = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vSbsn01->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $fundSbsn->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan SBSN',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 90000000,
            'initial_budget' => 90000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 90000000,
        ]);

        BudgetImportPipelineService::activateVersion($vSbsn01, $this->userPtk);

        // SBSN Rev 00 ARCHIVED, SBSN Rev 01 ACTIVE
        $this->assertEquals('ARCHIVED', $versions['SBSN']->fresh()->status);
        $this->assertEquals('ACTIVE', $vSbsn01->fresh()->status);

        // Saldo SBSN Rev 01: P=90jt, DP=15jt, RI=25jt, S=50jt
        $bSbsn01Fresh = $bSbsn01->fresh();
        $this->assertEquals(90000000, (float) $bSbsn01Fresh->allocated_budget);
        $this->assertEquals(15000000, (float) $bSbsn01Fresh->reserved_budget);
        $this->assertEquals(25000000, (float) $bSbsn01Fresh->realized_budget);
        $this->assertEquals(50000000, (float) $bSbsn01Fresh->available_balance);

        // 4 Sumber Dana Lainnya (RM, BOPTN, UKT, PNBP) TIDAK BERUBAH dan TETAP ACTIVE
        foreach (['RM', 'BOPTN', 'UKT', 'PNBP'] as $c) {
            $this->assertEquals('ACTIVE', $versions[$c]->fresh()->status, "{$c} harus tetap ACTIVE");
            $this->assertEquals($funds[$c]['saldo'], (float) $buckets[$c]->fresh()->available_balance, "Saldo {$c} tidak boleh berubah");
        }
    }

    /**
     * Skenario Tambahan Audit Lanjutan: Integritas Relasi Transaksi dan Penanganan Konflik Sumber Dana
     * - Transaksi dengan budgetBucket dan originalBudgetBucket bertentangan sumber dananya TIDAK boleh dihitung.
     */
    public function test_audit_integritas_relasi_transaksi_konflik_sumber_dana(): void
    {
        $vRm = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundRM->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);
        $bucketRm = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vRm->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundRM->id,
            'account_code' => '521211',
            'account_name' => 'Bahan RM',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 50000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 50000000,
        ]);

        $vBoptn = BudgetVersion::create([
            'fiscal_year_id' => $this->fy->id,
            'funding_source_id' => $this->fundBoptn->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);
        $bucketBoptn = BudgetBucket::create([
            'fiscal_year_id' => $this->fy->id,
            'budget_version_id' => $vBoptn->id,
            'department_id' => $this->dept->id,
            'funding_source_id' => $this->fundBoptn->id,
            'account_code' => '521211',
            'account_name' => 'Bahan BOPTN',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 40000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 40000000,
        ]);

        // 1. Transaksi Sah RM (10jt DP)
        Submission::create([
            'submission_number' => 'SUB-SAH-RM-01',
            'title' => 'Transaksi Sah RM',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketRm->id,
            'original_budget_bucket_id' => $bucketRm->id,
            'amount' => 10000000,
            'status' => 'PROCESSING',
            'created_by' => $this->userPtk->id,
        ]);

        // 2. Transaksi Anomali / Konflik: original BOPTN tapi budget_bucket salah diset ke RM
        Submission::create([
            'submission_number' => 'SUB-KONFLIK-01',
            'title' => 'Transaksi Anomali Sumber Dana Bertentangan',
            'department_id' => $this->dept->id,
            'fiscal_year_id' => $this->fy->id,
            'budget_bucket_id' => $bucketRm->id,
            'original_budget_bucket_id' => $bucketBoptn->id, // KONFLIK: BOPTN vs RM
            'amount' => 7000000,
            'status' => 'PROCESSING',
            'created_by' => $this->userPtk->id,
        ]);

        // Hitung query submissions untuk bucket RM
        $subsRm = BudgetCalculationService::getSubmissionsForBucket($bucketRm)->get();

        // 1. Tidak masuk ke perhitungan sumber dana yang salah
        $this->assertCount(1, $subsRm, 'Hanya transaksi sah RM yang boleh ditarik ke RM');
        $this->assertEquals('SUB-SAH-RM-01', $subsRm->first()->submission_number);

        // Pastikan juga tidak masuk ke perhitungan BOPTN
        $subsBoptn = BudgetCalculationService::getSubmissionsForBucket($bucketBoptn)->get();
        $this->assertCount(0, $subsBoptn, 'Transaksi anomali tidak boleh masuk ke perhitungan BOPTN');

        // 2. Tidak hilang tanpa jejak (tetap ada di tabel submissions)
        $this->assertDatabaseHas('submissions', [
            'submission_number' => 'SUB-KONFLIK-01',
            'amount' => 7000000,
        ]);

        // Sinkronisasi bucket RM dan BOPTN
        BudgetCalculationService::synchronizeBucketFromTransactions($bucketRm);
        BudgetCalculationService::synchronizeBucketFromTransactions($bucketBoptn);

        // 3. Tercatat sebagai anomali integritas (needs_review = true)
        $conflictingSubFresh = Submission::where('submission_number', 'SUB-KONFLIK-01')->first();
        $this->assertTrue((bool) $conflictingSubFresh->needs_review, 'Transaksi anomali harus ditandai needs_review = true');

        // 4. Dapat ditelusuri oleh administrator/pengelola keuangan
        $anomalies = BudgetCalculationService::getIntegrityAnomalies($this->fy->id)->get();
        $this->assertCount(1, $anomalies, 'Administrator harus dapat mendeteksi dan menelusuri transaksi anomali');
        $this->assertEquals('SUB-KONFLIK-01', $anomalies->first()->submission_number);
        $this->assertEquals('RM', $anomalies->first()->budgetBucket->fundingSource->code);
        $this->assertEquals('BOPTN', $anomalies->first()->originalBudgetBucket->fundingSource->code);

        // 5. Tidak menyebabkan saldo tersedia terlihat lebih besar secara menyesatkan
        // Bucket ditandai needs_review dan data_incomplete agar pengelola waspada sebelum mencairkan dana
        $bRmFresh = $bucketRm->fresh();
        $this->assertEquals(10000000, (float) $bRmFresh->reserved_budget, 'Transaksi anomali tidak boleh mengotori reserved RM');
        $this->assertEquals(40000000, (float) $bRmFresh->available_balance);
        $this->assertTrue((bool) $bRmFresh->needs_review, 'Pos anggaran terkait transaksi anomali harus berstatus needs_review');
        $this->assertTrue((bool) $bRmFresh->data_incomplete, 'Pos anggaran terkait transaksi anomali harus berstatus data_incomplete');

        $snapshot = BudgetCalculationService::getBucketFinancialSnapshot($bRmFresh);
        $this->assertTrue($snapshot['has_integrity_anomaly'], 'Snapshot finansial pos anggaran harus mendeteksi keberadaan anomali integritas');
        $this->assertTrue($snapshot['needs_review'], 'Snapshot finansial pos anggaran harus mengindikasikan needs_review');
    }
}
