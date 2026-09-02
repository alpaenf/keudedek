<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\BudgetBucket;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\Models\WorkflowDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * LEGACY / DEFERRED MODULE — NOT USED IN MVP.
 *
 * Sistem SIKARA MVP tidak menggunakan rantai approval panjang berbelit
 * (WorkflowDefinition / WorkflowStep). Alur verifikasi langsung ditangani
 * oleh PTU (Penguji Tagihan Unit BLU) via ApprovalController.
 * File ini dipertahankan sebagai dead/deferred module agar tidak merusak relasi historis.
 */
class WorkflowService
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Process workflow transition with approval signature and atomic budget mutations.
     */
    public function processDecision(
        Submission $submission,
        User $actor,
        string $decision, // APPROVED, RETURNED, REJECTED
        ?string $comment = null,
        ?string $password = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        // Verify user password if provided (Electronic Sign-off re-authentication)
        if ($password && ! Hash::check($password, $actor->password)) {
            return [
                'success' => false,
                'message' => 'Kata sandi konfirmasi tidak valid. Otorisasi tanda tangan elektronik dibatalkan.',
            ];
        }

        return DB::transaction(function () use ($submission, $actor, $decision, $comment, $ipAddress, $userAgent) {
            // Lock submission and budget bucket for update (Pessimistic locking RBC-008)
            $submission = Submission::where('id', $submission->id)->lockForUpdate()->first();
            $bucket = BudgetBucket::where('id', $submission->budget_bucket_id)->lockForUpdate()->first();

            $oldStatus = $submission->status;
            $currentStep = $submission->currentWorkflowStep;
            $workflow = $submission->transactionType?->workflowDefinitions()->first()
                ?? WorkflowDefinition::where('is_active', true)->first();

            $allSteps = $workflow ? $workflow->steps : collect();

            if ($decision === 'APPROVED') {
                // Find next step in sequence
                $nextStep = null;
                if ($currentStep) {
                    $nextStep = $allSteps->where('sequence', '>', $currentStep->sequence)->sortBy('sequence')->first();
                } else {
                    $nextStep = $allSteps->sortBy('sequence')->first();
                }

                // Determine target status
                $targetStatus = 'APPROVED';
                if ($currentStep && $currentStep->reserve_trigger) {
                    $targetStatus = 'RESERVED';
                } elseif ($currentStep && $currentStep->final_trigger) {
                    $targetStatus = 'FINAL';
                } elseif (! $nextStep) {
                    $targetStatus = 'FINAL';
                } elseif ($nextStep->role === 'PTU') {
                    $targetStatus = 'UNDER_REVIEW';
                } elseif ($nextStep->role === 'KABAG') {
                    $targetStatus = 'APPROVED';
                }

                // Handle budget reservations / finalizations
                if ($targetStatus === 'RESERVED' || ($currentStep && $currentStep->reserve_trigger)) {
                    if ($oldStatus !== 'RESERVED' && $oldStatus !== 'FINAL') {
                        $reservedSuccess = $this->budgetService->reserveBudget($bucket, (float) $submission->amount);
                        if (! $reservedSuccess) {
                            return [
                                'success' => false,
                                'message' => 'Gagal Menyetujui: Saldo ketersediaan anggaran tidak mencukupi (Overbudget Blocked).',
                            ];
                        }
                    }
                } elseif ($targetStatus === 'FINAL' || ($currentStep && $currentStep->final_trigger)) {
                    $this->budgetService->finalizeRealization($bucket, (float) $submission->amount);
                }

                $submission->status = $targetStatus;
                $submission->current_workflow_step_id = $nextStep?->id;

            } elseif ($decision === 'RETURNED') {
                // Release reservation if it was reserved
                if ($oldStatus === 'RESERVED') {
                    $this->budgetService->releaseReservation($bucket, (float) $submission->amount);
                }

                $submission->status = 'RETURNED';
                // Reset step back to first step (PTK)
                $firstStep = $allSteps->sortBy('sequence')->first();
                $submission->current_workflow_step_id = $firstStep?->id;

            } elseif ($decision === 'REJECTED') {
                if ($oldStatus === 'RESERVED') {
                    $this->budgetService->releaseReservation($bucket, (float) $submission->amount);
                }

                $submission->status = 'REJECTED';
            }

            // Generate Electronic Sign-off Document Hash
            $signoffHash = hash('sha256', $submission->id.'-'.$actor->id.'-'.$decision.'-'.now()->toIso8601String());
            $submission->electronic_signoff_hash = $signoffHash;
            $submission->notes = $comment ?? $submission->notes;
            $submission->save();

            // Record Approval Log
            Approval::create([
                'submission_id' => $submission->id,
                'workflow_step_id' => $currentStep?->id,
                'user_id' => $actor->id,
                'role' => $actor->role,
                'decision' => $decision,
                'comment' => $comment,
                'document_hash' => $signoffHash,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            // Record Timeline History
            SubmissionStatusHistory::create([
                'submission_id' => $submission->id,
                'from_status' => $oldStatus,
                'to_status' => $submission->status,
                'actor_id' => $actor->id,
                'role' => $actor->role,
                'notes' => $comment ? "Keputusan [{$decision}]: {$comment}" : "Keputusan status dialihkan ke {$submission->status}.",
            ]);

            // Record Audit Trail
            AuditLogService::log(
                'WORKFLOW_'.$decision,
                Submission::class,
                $submission->id,
                ['status' => $oldStatus],
                [
                    'status' => $submission->status,
                    'decision' => $decision,
                    'actor_role' => $actor->role,
                    'comment' => $comment,
                ]
            );

            return [
                'success' => true,
                'message' => "Keputusan {$decision} berhasil diproses. Status pengajuan kini: {$submission->status}.",
            ];
        });
    }
}
