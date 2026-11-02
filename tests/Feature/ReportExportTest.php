<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_export_reports_to_pdf_xlsx_csv_docx(): void
    {
        $user = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($user);

        // PDF Export
        $responsePdf = $this->get('/reports/export-pdf');
        $responsePdf->assertStatus(200);
        $this->assertTrue(str_contains($responsePdf->headers->get('content-type'), 'application/pdf'));

        // XLSX Export
        $responseXlsx = $this->get('/reports/export-xlsx');
        $responseXlsx->assertStatus(200);
        $this->assertTrue(str_contains($responseXlsx->headers->get('content-type'), 'application/vnd.ms-excel'));

        // CSV Export
        $responseCsv = $this->get('/reports/export-csv');
        $responseCsv->assertStatus(200);
        $this->assertTrue(str_contains($responseCsv->headers->get('content-type'), 'text/csv'));

        // DOCX Export
        $responseDocx = $this->get('/reports/export-docx');
        $responseDocx->assertStatus(200);
        $this->assertTrue(str_contains($responseDocx->headers->get('content-type'), 'application/msword'));
    }

    public function test_can_export_submission_to_pdf_docx_print(): void
    {
        $user = User::factory()->create(['role' => 'ADMIN']);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );
        $fs = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true]
        );
        $dept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Teknik Informatika', 'is_active' => true]
        );
        $v = BudgetVersion::create([
            'fiscal_year_id' => $fy->id,
            'funding_source_id' => $fs->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);
        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $fy->id,
            'budget_version_id' => $v->id,
            'department_id' => $dept->id,
            'funding_source_id' => $fs->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
            'allocated_budget' => 10000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 10000000,
        ]);

        $sub = Submission::create([
            'submission_number' => 'TRX/2026/001',
            'evidence_number' => 'KW-001',
            'transaction_date' => '2026-03-01',
            'title' => 'Pembelian ATK',
            'amount' => 1500000,
            'status' => 'PROCESSING',
            'department_id' => $dept->id,
            'fiscal_year_id' => $fy->id,
            'budget_bucket_id' => $bucket->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        // Submission PDF
        $responsePdf = $this->get("/submissions/{$sub->id}/export-pdf");
        $responsePdf->assertStatus(200);
        $this->assertTrue(str_contains($responsePdf->headers->get('content-type'), 'application/pdf'));

        // Submission DOCX
        $responseDocx = $this->get("/submissions/{$sub->id}/export-docx");
        $responseDocx->assertStatus(200);
        $this->assertTrue(str_contains($responseDocx->headers->get('content-type'), 'application/msword'));

        // Submission Print Page
        $responsePrint = $this->get("/submissions/{$sub->id}/print");
        $responsePrint->assertStatus(200);
    }
}
