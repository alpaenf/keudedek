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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtkTransactionRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected FiscalYear $fiscalYear;

    protected BudgetVersion $version;

    protected FundingSource $fundingSource;

    protected BudgetBucket $bucket;

    protected BudgetLine $budgetLine;

    protected User $ptkUser;

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
                'version_label' => 'Pagu Definitif Awal',
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
            'description' => 'Snack Rapat Pembahasan Praktikum',
            'volume' => 10.00,
            'unit' => 'Kotak',
            'unit_price' => 50000.00,
            'budget_amount' => 500000.00,
            'status' => 'ACTIVE',
        ]);

        $this->ptkUser = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $this->department->id,
        ]);
    }

    public function test_ptk_can_render_create_transaction_page_with_budget_lines(): void
    {
        $response = $this->actingAs($this->ptkUser)->get(route('submissions.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Submissions/Create')
            ->has('initialBudgetLines')
            ->where('activeFiscalYear', 2026)
            ->where('userDepartmentId', $this->department->id)
        );
    }

    public function test_ptk_can_submit_transaction_using_budget_line(): void
    {
        $payload = [
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/JTIF/2026/09/001',
            'transaction_date' => '2026-09-03',
            'title' => 'Pembelian Snack Rapat Evaluasi Kurikulum',
            'amount' => 500000.00,
            'notes' => 'Bukti kuitansi asli terlampir',
            'submit_action' => 'PROCESSING',
            // Tampered department_id should be safely ignored by backend
            'department_id' => 999,
        ];

        $response = $this->actingAs($this->ptkUser)->post(route('submissions.store'), $payload);

        $submission = Submission::where('evidence_number', 'FRA/JTIF/2026/09/001')->first();
        $this->assertNotNull($submission);

        $response->assertRedirect(route('submissions.show', $submission));

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'budget_line_id' => $this->budgetLine->id,
            'budget_bucket_id' => $this->bucket->id,
            'evidence_number' => 'FRA/JTIF/2026/09/001',
            'title' => 'Pembelian Snack Rapat Evaluasi Kurikulum',
            'amount' => 500000.00,
            'department_id' => $this->department->id, // Backend scope strictly enforced
            'status' => 'PROCESSING',
        ]);

        // Assert Control Bucket was reserved atomically
        $this->bucket->refresh();
        $this->assertEquals(500000.00, (float) $this->bucket->reserved_budget);
        $this->assertEquals(9500000.00, (float) $this->bucket->available_balance);
    }

    public function test_ptk_cannot_record_transaction_with_overbudget_amount(): void
    {
        $payload = [
            'budget_line_id' => $this->budgetLine->id,
            'evidence_number' => 'FRA/JTIF/2026/09/002',
            'transaction_date' => '2026-09-03',
            'title' => 'Pembelian Melebihi Saldo Pagu',
            'amount' => 15000000.00, // Bucket available is only 10,000,000
            'submit_action' => 'PROCESSING',
        ];

        $response = $this->actingAs($this->ptkUser)->post(route('submissions.store'), $payload);

        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseMissing('submissions', [
            'title' => 'Pembelian Melebihi Saldo Pagu',
        ]);
    }
}
