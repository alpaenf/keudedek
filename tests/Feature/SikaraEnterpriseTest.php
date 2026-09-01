<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\Submission;
use App\Models\User;
use Tests\TestCase;

class SikaraEnterpriseTest extends TestCase
{
    public function test_all_official_roles_can_render_their_respective_dashboard(): void
    {
        $roles = ['PTK', 'KAJUR', 'KAPRODI', 'PTU', 'BENDAHARA', 'KABAG', 'WAKIL_DEKAN', 'DEKAN', 'ADMIN'];

        foreach ($roles as $role) {
            $user = User::where('role', $role)->first();
            if (! $user) {
                continue;
            }

            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertStatus(200);
        }
    }

    public function test_ptk_operator_is_restricted_to_their_own_department_scope(): void
    {
        $ptkUser = User::where('role', 'PTK')->first();
        if (! $ptkUser) {
            $this->markTestSkipped('No PTK user found in database');
        }

        $otherDept = Department::where('id', '!=', $ptkUser->department_id)
            ->whereNotNull('parent_id')
            ->first();

        if (! $otherDept) {
            $this->markTestSkipped('No other department found');
        }

        // Test dashboard enforces user's department
        $response = $this->actingAs($ptkUser)->get("/dashboard?department_id={$otherDept->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('selectedDepartmentId', $ptkUser->department_id));
    }

    public function test_quick_entry_transaction_creation_and_rbc001_protection(): void
    {
        $ptkUser = User::where('role', 'PTK')->first();
        if (! $ptkUser) {
            $this->markTestSkipped('PTK user missing');
        }

        $bucket = BudgetBucket::where('department_id', $ptkUser->department_id)->first();
        if (! $bucket) {
            $this->markTestSkipped('Budget bucket missing for department');
        }

        // 1. RBC-001 Overbudget Protection Test
        $excessiveAmount = $bucket->available_balance + 999999999;
        $overbudgetResponse = $this->actingAs($ptkUser)->post('/submissions', [
            'department_id' => $ptkUser->department_id,
            'budget_bucket_id' => $bucket->id,
            'evidence_number' => 'BKT-OVER-001',
            'transaction_date' => '2026-08-25',
            'title' => 'Pengadaan Melebihi Saldo Tersedia',
            'amount' => $excessiveAmount,
            'submit_action' => 'PROCESSING',
        ]);
        $overbudgetResponse->assertSessionHasErrors(['amount']);

        // 2. Valid Transaction Creation Test (Minimal Input)
        $validAmount = 2500000;
        $postData = [
            'department_id' => $ptkUser->department_id,
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
