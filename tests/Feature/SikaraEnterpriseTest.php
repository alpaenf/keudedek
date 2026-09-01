<?php

namespace Tests\Feature;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\Submission;
use App\Models\User;
use Tests\TestCase;

class SikaraEnterpriseTest extends TestCase
{
    public function test_7_roles_can_render_their_respective_dashboard(): void
    {
        $roles = ['PTK', 'KAJUR', 'PTU', 'KABAG', 'WAKIL_DEKAN', 'DEKAN', 'ADMIN'];

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

    public function test_submission_creation_and_workflow_electronic_signoff(): void
    {
        $ptkUser = User::where('role', 'PTK')->first();
        $kajurUser = User::where('role', 'KAJUR')
            ->where('department_id', $ptkUser?->department_id)
            ->first();

        if (! $ptkUser || ! $kajurUser) {
            $this->markTestSkipped('PTK or KAJUR user missing');
        }

        $bucket = BudgetBucket::where('department_id', $ptkUser->department_id)->first();
        if (! $bucket) {
            $this->markTestSkipped('Budget bucket missing for department');
        }

        // 1. Create Submission via wizard payload
        $postData = [
            'department_id' => $ptkUser->department_id,
            'budget_bucket_id' => $bucket->id,
            'title' => 'Pengadaan Lisensi Software CAD untuk Laboratorium Teknik',
            'amount' => 5000000,
            'reference_no' => 'UN23.FT.IF/KU/TEST/'.rand(100, 999),
            'beneficiary_name' => 'PT CAD Indonesia',
            'notes' => 'Keperluan praktikum mahasiswa',
            'submit_action' => 'SUBMITTED',
            'items' => [
                [
                    'item_name' => 'Lisensi 1 Tahun CAD Edukasi',
                    'quantity' => 1,
                    'unit_price' => 5000000,
                    'total_price' => 5000000,
                ],
            ],
        ];

        $createResponse = $this->actingAs($ptkUser)->post('/submissions', $postData);
        $createResponse->assertRedirect();

        $submission = Submission::where('title', 'Pengadaan Lisensi Software CAD untuk Laboratorium Teknik')->latest()->first();
        $this->assertNotNull($submission);
        $this->assertEquals('SUBMITTED', $submission->status);

        // 2. KAJUR Approval Decision & Sign-off
        $approvalResponse = $this->actingAs($kajurUser)->post("/approvals/{$submission->id}/decide", [
            'decision' => 'APPROVED',
            'comment' => 'Disetujui untuk diteruskan ke verifikasi fakultas.',
        ]);

        $approvalResponse->assertRedirect();

        $submission->refresh();
        $this->assertNotNull($submission->electronic_signoff_hash);
        $this->assertTrue(in_array($submission->status, ['APPROVED', 'UNDER_REVIEW', 'RESERVED']));
    }
}
