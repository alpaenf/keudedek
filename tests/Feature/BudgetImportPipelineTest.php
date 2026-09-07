<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\ImportHistory;
use App\Models\User;
use App\Services\BudgetImportPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetImportPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected Department $deptA;

    protected Department $deptB;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptA = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Jurusan Teknik Informatika', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->deptB = Department::firstOrCreate(
            ['code' => 'JTE'],
            ['name' => 'Jurusan Teknik Elektro', 'type' => 'DEPARTMENT', 'is_active' => true]
        );

        $this->fiscalYear = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );

        $this->fundingSource = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );

        $this->adminUser = User::factory()->create([
            'role' => 'ADMIN',
        ]);
    }

    /**
     * Test 1: Upload and staging generates validation report and creates Draft version (does not activate)
     */
    public function test_upload_creates_draft_version_and_validation_report(): void
    {
        // Sample valid CSV content
        $csvContent = "No,Tahun,Sumber,Unit,Program,NamaProg,Kegiatan,NamaKeg,KRO,NamaKRO,RO,NamaRO,Komponen,NamaKomp,Subkomp,NamaSubkomp,Akun,NamaAkun,Uraian,Volume,Satuan,Harga,Pagu\n".
            "001,2026,RM,Jurusan JTIF,WA,Dukman,4257,Dukman FT,EBA,Layanan,994,Perkantoran,001,Operasional,023.17.WA.4257.EBA.994.001.AA,Operasional JTIF,521111,Belanja Keperluan Kantor,Kertas HVS,10,Rim,50000,500000\n".
            "002,2026,RM,Jurusan JTIF,WA,Dukman,4257,Dukman FT,EBA,Layanan,994,Perkantoran,001,Operasional,023.17.WA.4257.EBA.994.001.AA,Operasional JTIF,521211,Belanja Bahan,Toner Printer,2,Unit,750000,1500000\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'bgt_').'.csv';
        file_put_contents($tempFile, $csvContent);

        $history = BudgetImportPipelineService::processUpload(
            $tempFile,
            'rba_jtif_2026.csv',
            2026,
            'RM',
            'Rev 01',
            'Revisi 01 DIPA',
            '2026-03-01',
            $this->adminUser
        );

        @unlink($tempFile);

        $this->assertInstanceOf(ImportHistory::class, $history);
        $this->assertEquals(2, $history->valid_rows);
        $this->assertEquals(0, $history->invalid_rows);
        $this->assertEquals('PENDING', $history->status);

        // Version check: MUST BE DRAFT
        $version = $history->budgetVersion;
        $this->assertNotNull($version);
        $this->assertEquals('DRAFT', $version->status);
        $this->assertEquals('Rev 01', $version->revision_no);
    }

    /**
     * Test 2: Validation detects duplicates, unmapped departments, invalid accounts, and zero amounts
     */
    public function test_validation_detects_schema_and_master_anomalies(): void
    {
        // 1 valid, 1 duplicate RBA, 1 unmapped dept, 1 invalid account, 1 zero pagu
        $csvContent = "No,Tahun,Sumber,Unit,Program,NamaProg,Kegiatan,NamaKeg,KRO,NamaKRO,RO,NamaRO,Komponen,NamaKomp,Subkomp,NamaSubkomp,Akun,NamaAkun,Uraian,Volume,Satuan,Harga,Pagu\n".
            "001,2026,RM,JTIF,WA,Dukman,4257,Dukman,EBA,Layanan,994,Perkantoran,001,Ops,023.17.WA.4257.EBA.994.001.AA,Ops,521111,ATK,ATK,1,Paket,1000000,1000000\n".
            "001,2026,RM,JTIF,WA,Dukman,4257,Dukman,EBA,Layanan,994,Perkantoran,001,Ops,023.17.WA.4257.EBA.994.001.AA,Ops,521111,ATK Duplicate,ATK 2,1,Paket,1000000,1000000\n".
            "002,2026,RM,FAKULTAS_KEDOKTERAN,WA,Dukman,4257,Dukman,EBA,Layanan,994,Perkantoran,001,Ops,023.17.WA.4257.EBA.994.001.AA,Ops,521111,ATK,ATK,1,Paket,1000000,1000000\n".
            "003,2026,RM,JTIF,WA,Dukman,4257,Dukman,EBA,Layanan,994,Perkantoran,001,Ops,023.17.WA.4257.EBA.994.001.AA,Ops,99,Akun Rusak,ATK,1,Paket,1000000,1000000\n".
            "004,2026,RM,JTIF,WA,Dukman,4257,Dukman,EBA,Layanan,994,Perkantoran,001,Ops,023.17.WA.4257.EBA.994.001.AA,Ops,521111,ATK,ATK,1,Paket,0,0\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'bgt_err_').'.csv';
        file_put_contents($tempFile, $csvContent);

        $history = BudgetImportPipelineService::processUpload(
            $tempFile,
            'rba_anomalies.csv',
            2026,
            'RM',
            'Rev 01',
            'Revisi 01 DIPA',
            '2026-03-01',
            $this->adminUser
        );

        @unlink($tempFile);

        $this->assertEquals(5, $history->total_rows);
        $this->assertEquals(1, $history->valid_rows);
        $this->assertEquals(4, $history->invalid_rows);

        $report = $history->validation_report;
        $this->assertFalse($report['is_ready_for_commit']);
        $this->assertGreaterThan(0, $report['errors_summary']['duplicate_rba']);
        $this->assertGreaterThan(0, $report['errors_summary']['unmapped_department']);
        $this->assertGreaterThan(0, $report['errors_summary']['unmapped_master']);
        $this->assertGreaterThan(0, $report['errors_summary']['invalid_amount']);
    }

    /**
     * Test 3: Commit creates Budget Lines and Control Buckets, maintaining Draft status
     */
    public function test_commit_creates_budget_lines_and_control_buckets(): void
    {
        $csvContent = "No,Tahun,Sumber,Unit,Program,NamaProg,Kegiatan,NamaKeg,KRO,NamaKRO,RO,NamaRO,Komponen,NamaKomp,Subkomp,NamaSubkomp,Akun,NamaAkun,Uraian,Volume,Satuan,Harga,Pagu\n".
            "001,2026,RM,JTIF,WA,Dukman,4257,Dukman FT,EBA,Layanan,994,Perkantoran,001,Operasional,023.17.WA.4257.EBA.994.001.AA,Operasional JTIF,521211,Belanja Bahan,Bahan Praktikum A,2,Paket,5000000,10000000\n".
            "002,2026,RM,JTIF,WA,Dukman,4257,Dukman FT,EBA,Layanan,994,Perkantoran,001,Operasional,023.17.WA.4257.EBA.994.001.AA,Operasional JTIF,521211,Belanja Bahan,Bahan Praktikum B,1,Paket,5000000,5000000\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'bgt_commit_').'.csv';
        file_put_contents($tempFile, $csvContent);

        $history = BudgetImportPipelineService::processUpload(
            $tempFile,
            'commit_test.csv',
            2026,
            'RM',
            'Rev 01',
            'Revisi 01 DIPA',
            '2026-03-01',
            $this->adminUser
        );

        @unlink($tempFile);

        $result = BudgetImportPipelineService::commitBatch($history, $this->adminUser);

        $this->assertEquals(2, $result['lines_count']);
        $this->assertEquals(1, $result['buckets_count']); // 2 lines aggregated into 1 bucket (same subcomp + account)
        $this->assertEquals('COMMITTED', $history->fresh()->status);
        $this->assertEquals('DRAFT', $result['budget_version']->status); // NOT ACTIVATED YET

        // Verify Bucket Allocated = 10M + 5M = 15M
        $bucket = BudgetBucket::where('budget_version_id', $result['budget_version']->id)->first();
        $this->assertNotNull($bucket);
        $this->assertEquals(15000000.00, (float) $bucket->allocated_budget);

        // Verify 2 Budget Lines linked to bucket
        $this->assertEquals(2, BudgetLine::where('budget_bucket_id', $bucket->id)->count());
    }

    /**
     * Test 4: Version comparison detects new lines, removed lines, pagu changes, and revision conflict
     */
    public function test_compare_versions_and_detect_conflict_when_new_pagu_below_commitment(): void
    {
        // Version 00 (Active)
        $v0 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA Awal',
            'status' => 'ACTIVE',
        ]);

        $bucket0 = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $v0->id,
            'department_id' => $this->deptA->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'budget_bucket_name' => 'Belanja Bahan JTIF',
            'allocated_budget' => 20000000.00,
            'reserved_budget' => 12000000.00, // 12M Active Commitment
            'realized_budget' => 5000000.00,  // 5M Realization
            'available_balance' => 3000000.00,
        ]);

        $line0_1 = BudgetLine::create([
            'budget_version_id' => $v0->id,
            'department_id' => $this->deptA->id,
            'budget_bucket_id' => $bucket0->id,
            'rba_sequence_no' => '001',
            'description' => 'Pos Awal 1',
            'budget_amount' => 10000000.00,
            'status' => 'ACTIVE',
        ]);

        $line0_2 = BudgetLine::create([
            'budget_version_id' => $v0->id,
            'department_id' => $this->deptA->id,
            'budget_bucket_id' => $bucket0->id,
            'rba_sequence_no' => '002',
            'description' => 'Pos Awal 2 (Akan Dihapus)',
            'budget_amount' => 10000000.00,
            'status' => 'ACTIVE',
        ]);

        // Version 01 (Draft Target)
        // Conflict scenario: Total New Pagu for this bucket reduced to 10M, while commitment (12M) + realization (5M) = 17M!
        $v1 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'Revisi 01 DIPA',
            'status' => 'DRAFT',
        ]);

        $bucket1 = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $v1->id,
            'department_id' => $this->deptA->id,
            'funding_source_id' => $this->fundingSource->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'budget_bucket_name' => 'Belanja Bahan JTIF',
            'allocated_budget' => 10000000.00, // Deficit! 10M < 17M
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 10000000.00,
        ]);

        // Line 001 increased to 10M, Line 002 removed, Line 003 new
        BudgetLine::create([
            'budget_version_id' => $v1->id,
            'department_id' => $this->deptA->id,
            'budget_bucket_id' => $bucket1->id,
            'rba_sequence_no' => '001',
            'description' => 'Pos Awal 1',
            'budget_amount' => 10000000.00,
            'status' => 'ACTIVE',
        ]);

        BudgetLine::create([
            'budget_version_id' => $v1->id,
            'department_id' => $this->deptA->id,
            'budget_bucket_id' => $bucket1->id,
            'rba_sequence_no' => '003',
            'description' => 'Pos Baru 3',
            'budget_amount' => 5000000.00,
            'status' => 'ACTIVE',
        ]);

        $comparison = BudgetImportPipelineService::compareVersions($v0, $v1);

        $this->assertEquals(1, $comparison['summary']['new_lines_count']); // Pos 003
        $this->assertEquals(1, $comparison['summary']['removed_lines_count']); // Pos 002
        $this->assertTrue($comparison['summary']['has_conflict']);
        $this->assertCount(1, $comparison['conflicts']);
        $this->assertEquals(7000000.00, $comparison['conflicts'][0]['deficit_amount']); // 17M - 10M = 7M deficit!
    }

    /**
     * Test 5: Activate version sets status to ACTIVE, archives previous version without overwriting history
     */
    public function test_activate_version_archives_previous_without_overwriting(): void
    {
        $v0 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA Awal',
            'status' => 'ACTIVE',
        ]);

        $v1 = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 01',
            'version_label' => 'Revisi 01 DIPA',
            'status' => 'DRAFT',
        ]);

        BudgetImportPipelineService::activateVersion($v1, $this->adminUser);

        $this->assertEquals('ARCHIVED', $v0->fresh()->status);
        $this->assertEquals('ACTIVE', $v1->fresh()->status);
        $this->assertNotNull($v1->fresh()->effective_at);
    }
}
