<?php

namespace Tests\Feature;

use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetBucket;
use App\Models\BudgetComponent;
use App\Models\BudgetKro;
use App\Models\BudgetLine;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubaccount;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Submission;
use App\Models\User;
use App\Services\BudgetCalculationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetLineFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected FiscalYear $fiscalYear;

    protected FundingSource $fundingSource;

    protected Department $department;

    protected BudgetVersion $version;

    protected BudgetBucket $bucket;

    protected BudgetProgram $program;

    protected BudgetActivity $activity;

    protected BudgetKro $kro;

    protected BudgetRo $ro;

    protected BudgetComponent $component;

    protected BudgetSubcomponent $subcomponent;

    protected BudgetAccount $account;

    protected BudgetSubaccount $subaccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fiscalYear = FiscalYear::create([
            'year' => 2026,
            'status' => 'ACTIVE',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->fundingSource = FundingSource::create([
            'code' => 'RM',
            'name' => 'Rupiah Murni',
            'is_mvp_enabled' => true,
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'code' => 'JTIF',
            'name' => 'Jurusan Teknik Informatika',
            'type' => 'DEPARTMENT',
            'is_active' => true,
        ]);

        $this->version = BudgetVersion::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'funding_source_id' => $this->fundingSource->id,
            'revision_no' => 'Rev 00',
            'version_label' => 'DIPA Induk 2026',
            'status' => 'ACTIVE',
        ]);

        // Master Nomenklatur Hierarchy
        $this->program = BudgetProgram::create([
            'fiscal_year' => 2026,
            'code' => 'WA',
            'name' => 'Program Pendidikan dan Pelayanan Masyarakat',
        ]);

        $this->activity = BudgetActivity::create([
            'fiscal_year' => 2026,
            'code' => '2134',
            'parent_program_code' => 'WA',
            'name' => 'Penyelenggaraan Pendidikan Tinggi',
        ]);

        $this->kro = BudgetKro::create([
            'fiscal_year' => 2026,
            'code' => 'BMA',
            'parent_activity_code' => '2134',
            'name' => 'Gedung dan Prasarana Kampus',
        ]);

        $this->ro = BudgetRo::create([
            'fiscal_year' => 2026,
            'code' => '001',
            'parent_kro_code' => 'BMA',
            'name' => 'Operasional Fakultas Teknik',
        ]);

        $this->component = BudgetComponent::create([
            'fiscal_year' => 2026,
            'code' => '051',
            'parent_ro_code' => '001',
            'name' => 'Layanan Perkantoran',
        ]);

        $this->subcomponent = BudgetSubcomponent::create([
            'fiscal_year' => 2026,
            'code' => 'A',
            'full_code' => 'WA.2134.BMA.001.051.A',
            'parent_component_code' => '051',
            'name' => 'Operasional Kantor Jurusan',
        ]);

        $this->account = BudgetAccount::create([
            'code' => '521211',
            'name' => 'Belanja Bahan',
            'type' => 'EXPENSE',
        ]);

        $this->subaccount = BudgetSubaccount::create([
            'code' => '521211.01',
            'parent_account_code' => '521211',
            'name' => 'Konsumsi Rapat Internal',
        ]);

        // Control Bucket (Working baseline grain: version + dept + subcomponent + account)
        $this->bucket = BudgetBucket::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'subcomponent_full_code' => 'WA.2134.BMA.001.051.A',
            'subcomponent_name' => 'Operasional Kantor Jurusan',
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'initial_budget' => 50000000.00,
            'allocated_budget' => 50000000.00,
            'reserved_budget' => 0.00,
            'realized_budget' => 0.00,
            'available_balance' => 50000000.00,
        ]);
    }

    public function test_can_create_budget_line_with_hierarchy_relations(): void
    {
        $line = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucket->id,
            'rba_sequence_no' => '001',
            'budget_program_id' => $this->program->id,
            'budget_activity_id' => $this->activity->id,
            'budget_kro_id' => $this->kro->id,
            'budget_ro_id' => $this->ro->id,
            'budget_component_id' => $this->component->id,
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'budget_subaccount_id' => $this->subaccount->id,
            'description' => 'Snack Rapat Koordinasi Kurikulum',
            'volume' => 50.00,
            'unit' => 'Kotak',
            'unit_price' => 25000.00,
            'budget_amount' => 1250000.00,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('budget_lines', [
            'id' => $line->id,
            'rba_sequence_no' => '001',
            'budget_amount' => 1250000.00,
        ]);

        // Test Relations
        $this->assertEquals($this->version->id, $line->budgetVersion->id);
        $this->assertEquals($this->department->id, $line->department->id);
        $this->assertEquals($this->bucket->id, $line->budgetBucket->id);
        $this->assertEquals($this->program->id, $line->program->id);
        $this->assertEquals($this->subcomponent->id, $line->subcomponent->id);
        $this->assertEquals($this->account->id, $line->account->id);
        $this->assertEquals($this->subaccount->id, $line->subaccount->id);
    }

    public function test_contextual_uniqueness_of_rba_sequence_no(): void
    {
        BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'rba_sequence_no' => '001',
            'description' => 'First item',
            'volume' => 1.00,
            'unit_price' => 100000.00,
            'budget_amount' => 100000.00,
        ]);

        // Second item with same sequence in DIFFERENT department should SUCCEED
        $dept2 = Department::create([
            'code' => 'JTE',
            'name' => 'Jurusan Teknik Elektro',
            'type' => 'DEPARTMENT',
            'is_active' => true,
        ]);

        $lineOtherDept = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $dept2->id,
            'rba_sequence_no' => '001',
            'description' => 'First item of Elektro',
            'volume' => 1.00,
            'unit_price' => 200000.00,
            'budget_amount' => 200000.00,
        ]);

        $this->assertNotNull($lineOtherDept->id);

        // Same sequence in SAME version & SAME department must FAIL unique constraint
        $this->expectException(QueryException::class);

        BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'rba_sequence_no' => '001',
            'description' => 'Duplicate item',
            'volume' => 1.00,
            'unit_price' => 100000.00,
            'budget_amount' => 100000.00,
        ]);
    }

    public function test_transaction_can_reference_budget_line(): void
    {
        $user = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->department->id,
        ]);

        $line = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'budget_bucket_id' => $this->bucket->id,
            'rba_sequence_no' => '001',
            'description' => 'Konsumsi Kegiatan',
            'volume' => 10.00,
            'unit_price' => 25000.00,
            'budget_amount' => 250000.00,
        ]);

        $submission = Submission::create([
            'submission_number' => 'SUB-2026-001',
            'evidence_number' => 'KW-001',
            'title' => 'Pembelian Snack Rapat',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $line->id,
            'amount' => 250000.00,
            'status' => 'DRAFT',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'budget_line_id' => $line->id,
        ]);

        $this->assertEquals($line->id, $submission->budgetLine->id);
        $this->assertEquals(1, $line->submissions()->count());
    }

    public function test_mapping_many_budget_lines_to_one_control_bucket(): void
    {
        // Line 1: Snack Rapat
        $line1 = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'rba_sequence_no' => '001',
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'description' => 'Snack Rapat Jurusan',
            'volume' => 20.00,
            'unit_price' => 25000.00,
            'budget_amount' => 500000.00,
        ]);

        // Line 2: Makan Siang Rapat
        $line2 = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'rba_sequence_no' => '002',
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'description' => 'Makan Siang Rapat Kerja',
            'volume' => 20.00,
            'unit_price' => 50000.00,
            'budget_amount' => 1000000.00,
        ]);

        // Bulk mapping via BudgetCalculationService
        $linked = BudgetCalculationService::mapLinesToBuckets($this->version->id);
        $this->assertEquals(2, $linked);

        $line1->refresh();
        $line2->refresh();

        // Both lines should resolve to the same control bucket
        $this->assertEquals($this->bucket->id, $line1->budget_bucket_id);
        $this->assertEquals($this->bucket->id, $line2->budget_bucket_id);
        $this->assertEquals(2, $this->bucket->budgetLines()->count());
    }

    public function test_financial_snapshot_and_aggregation(): void
    {
        $user = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->department->id,
        ]);

        $line = BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'funding_source_id' => $this->fundingSource->id,
            'budget_bucket_id' => $this->bucket->id,
            'rba_sequence_no' => '005',
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'description' => 'Pembelian ATK Kantor Jurusan',
            'volume' => 1.00,
            'unit_price' => 5000000.00,
            'budget_amount' => 5000000.00,
        ]);

        // 1 Transaction DIAJUKAN (amount 1,000,000)
        Submission::create([
            'submission_number' => 'SUB-DIAJUKAN-01',
            'evidence_number' => 'KW-D01',
            'title' => 'Pengajuan Kuitansi Kertas HVS',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $line->id,
            'amount' => 1000000.00,
            'status' => 'SUBMITTED',
            'created_by' => $user->id,
        ]);

        // 1 Transaction SELESAI (amount 1,500,000)
        Submission::create([
            'submission_number' => 'SUB-SELESAI-01',
            'evidence_number' => 'KW-S01',
            'title' => 'Pembelian Tinta Printer Lunas',
            'department_id' => $this->department->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'budget_bucket_id' => $this->bucket->id,
            'budget_line_id' => $line->id,
            'amount' => 1500000.00,
            'status' => 'FINAL',
            'created_by' => $user->id,
        ]);

        // Update bucket reserved and realized to simulate bucket state
        $this->bucket->reserved_budget = 1000000.00;
        $this->bucket->realized_budget = 1500000.00;
        $this->bucket->recalculateAvailableBalance();

        $snapshot = BudgetCalculationService::getLineFinancialSnapshot($line);

        // Assert Line metrics
        $this->assertEquals(5000000.00, $snapshot['line_budget']);
        $this->assertEquals(1000000.00, $snapshot['line_diajukan']);
        $this->assertEquals(1500000.00, $snapshot['line_realisasi']);
        $this->assertEquals(2500000.00, $snapshot['line_saldo']); // 5M - 1M - 1.5M = 2.5M

        // Assert Bucket control metrics
        $this->assertEquals($this->bucket->id, $snapshot['bucket_id']);
        $this->assertEquals(50000000.00, $snapshot['bucket_allocated']);
        $this->assertEquals(1000000.00, $snapshot['bucket_reserved']);
        $this->assertEquals(1500000.00, $snapshot['bucket_realized']);
        $this->assertEquals(47500000.00, $snapshot['bucket_available']); // 50M - 1M - 1.5M = 47.5M
    }

    public function test_api_search_budget_lines_enforces_user_scope(): void
    {
        // Dept 1 Line
        BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $this->department->id,
            'rba_sequence_no' => '001',
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'description' => 'Konsumsi Jurusan Informatika',
            'volume' => 1.00,
            'unit_price' => 500000.00,
            'budget_amount' => 500000.00,
            'status' => 'ACTIVE',
        ]);

        // Dept 2 (Elektro) Line
        $deptElektro = Department::create([
            'code' => 'JTE',
            'name' => 'Jurusan Teknik Elektro',
            'type' => 'DEPARTMENT',
            'is_active' => true,
        ]);

        BudgetLine::create([
            'budget_version_id' => $this->version->id,
            'department_id' => $deptElektro->id,
            'rba_sequence_no' => '001',
            'budget_subcomponent_id' => $this->subcomponent->id,
            'budget_account_id' => $this->account->id,
            'description' => 'Konsumsi Jurusan Elektro',
            'volume' => 1.00,
            'unit_price' => 600000.00,
            'budget_amount' => 600000.00,
            'status' => 'ACTIVE',
        ]);

        // User PTK Informatika
        $userPtkInformatika = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->department->id,
        ]);

        $response = $this->actingAs($userPtkInformatika)->getJson('/api/budget-lines/search?q=Konsumsi');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.department.code', 'JTIF');
        $response->assertJsonPath('data.0.description', 'Konsumsi Jurusan Informatika');

        // Admin can search across departments or filter specific
        $adminUser = User::factory()->create(['role' => 'ADMIN']);
        $responseAdmin = $this->actingAs($adminUser)->getJson('/api/budget-lines/search?q=Konsumsi');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJsonCount(2, 'data');
    }
}
