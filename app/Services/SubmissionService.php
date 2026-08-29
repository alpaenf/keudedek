<?php

namespace App\Services;

use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class SubmissionService
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    public function transitionStatus(Submission $submission, string $targetStatus, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($submission, $targetStatus, $notes) {
            $oldStatus = $submission->status;
            $bucket = $submission->budgetBucket;

            if ($targetStatus === 'APPROVED' || $targetStatus === 'RESERVED') {
                if ($oldStatus !== 'RESERVED' && $oldStatus !== 'APPROVED') {
                    $reservedSuccess = $this->budgetService->reserveBudget($bucket, (float) $submission->amount);
                    if (! $reservedSuccess) {
                        return false; // Overbudget blocked
                    }
                }
            } elseif ($targetStatus === 'COMPLETED') {
                if ($oldStatus === 'RESERVED' || $oldStatus === 'APPROVED' || $oldStatus === 'PROCESSING') {
                    $this->budgetService->finalizeRealization($bucket, (float) $submission->amount);
                }
            } elseif ($targetStatus === 'REJECTED' || $targetStatus === 'RETURNED') {
                if ($oldStatus === 'RESERVED' || $oldStatus === 'APPROVED') {
                    $this->budgetService->releaseReservation($bucket, (float) $submission->amount);
                }
            }

            $submission->status = $targetStatus;
            if ($notes) {
                $submission->notes = $notes;
            }
            $submission->save();

            AuditLogService::log(
                'TRANSITION_SUBMISSION_STATUS',
                Submission::class,
                $submission->id,
                ['status' => $oldStatus],
                ['status' => $targetStatus, 'notes' => $notes]
            );

            return true;
        });
    }
}
