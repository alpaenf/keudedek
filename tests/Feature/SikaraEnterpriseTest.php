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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SikaraEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_official_roles_can_render_their_respective_dashboard(): void
    {
        $dept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Teknik Informatika', 'is_active' => true]
        );

        $roles = ['PTK', 'KAJUR', 'KAPRODI', 'PTU', 'BENDAHARA', 'KABAG', 'WAKIL_DEKAN', 'DEKAN', 'ADMIN'];

        foreach ($roles as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'department_id' => $dept->id,
            ]);

            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertStatus(200);
        }
    }

    public function test_ptk_operator_is_restricted_to_their_own_department_scope(): void
    {
        $dept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Teknik Informatika', 'is_active' => true]
        );

        $otherDept = Department::firstOrCreate(
            ['code' => 'JTE'],
            ['name' => 'Teknik Elektro', 'is_active' => true]
        );

        $ptkUser = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $dept->id,
        ]);

        // Test dashboard enforces user's department
        $response = $this->actingAs($ptkUser)->get("/dashboard?department_id={$otherDept->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('selectedDepartmentId', $ptkUser->department_id));
    }

    public function test_quick_entry_transaction_creation_and_rbc001_protection(): void
    {
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
            'allocated_budget' => 50000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 50000000,
        ]);
        $line = BudgetLine::create([
            'budget_version_id' => $v->id,
            'department_id' => $dept->id,
            'funding_source_id' => $fs->id,
            'budget_bucket_id' => $bucket->id,
            'rba_sequence_no' => '001',
            'description' => 'Bahan Praktikum',
            'budget_amount' => 50000000,
            'status' => 'ACTIVE',
        ]);

        $ptkUser = User::factory()->create([
            'role' => 'PTK',
            'department_id' => $dept->id,
        ]);

        // 1. RBC-001 Overbudget Protection Test
        $excessiveAmount = $bucket->available_balance + 999999999;
        $overbudgetResponse = $this->actingAs($ptkUser)->post('/submissions', [
            'department_id' => $ptkUser->department_id,
            'budget_line_id' => $line->id,
            'budget_bucket_id' => $bucket->id,
            'evidence_number' => 'BKT-OVER-001',
            'transaction_date' => '2026-08-25',
            'title' => 'Pengadaan Melebihi Saldo Tersedia',
            'amount' => $excessiveAmount,
            'submit_action' => 'PROCESSING',
        ]);
        $overbudgetResponse->assertSessionHasErrors();

        // 2. Valid Transaction Creation Test
        $validAmount = 2500000;
        $postData = [
            'department_id' => $ptkUser->department_id,
            'budget_line_id' => $line->id,
            'budget_bucket_id' => $bucket->id,
            'evidence_number' => 'BKT-TEST-001',
            'transaction_date' => '2026-08-25',
            'title' => 'Pengadaan Lisensi Software CAD untuk Laboratorium Teknik',
            'amount' => $validAmount,
            'reference_no' => 'UN23.FT.IF/KU/TEST/'.rand(100, 999),
            'beneficiary_name' => 'PT CAD Indonesia',
            'notes' => 'Keperluan praktikum mahasiswa',
            'submit_action' => 'PROCESSING',
        ];

        $createResponse = $this->actingAs($ptkUser)->post('/submissions', $postData);
        $createResponse->assertRedirect();

        $submission = Submission::where('evidence_number', 'BKT-TEST-001')->latest()->first();
        $this->assertNotNull($submission);
        $this->assertEquals('PROCESSING', $submission->status);
        $this->assertEquals($validAmount, (float) $submission->amount);
    }
}
