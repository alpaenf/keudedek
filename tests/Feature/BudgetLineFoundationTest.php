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
}
