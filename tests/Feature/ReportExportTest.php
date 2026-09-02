<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    public function test_can_export_reports_to_pdf_xlsx_csv_docx(): void
    {
        $user = User::where('role', 'ADMIN')->first() ?? User::first();
        if (! $user) {
            $this->markTestSkipped('No user found in database');
        }

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
        $user = User::where('role', 'ADMIN')->first() ?? User::first();
        if (! $user) {
            $this->markTestSkipped('No user found in database');
        }

        $sub = Submission::first();
        if (! $sub) {
            $this->markTestSkipped('No submission found in database');
        }

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
