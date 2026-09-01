<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\BudgetRevision;

class BudgetService
{
    public function __construct(
        protected RuleEngineService $ruleEngine
    ) {}

    public function reserveBudget(BudgetBucket $bucket, float $amount): bool
    {
        if ($this->ruleEngine->checkOverbudget($bucket, $amount)) {
            return false;
        }

        $bucket->reserved_budget += $amount;
        $bucket->recalculateAvailableBalance();
        $this->ruleEngine->evaluateBucket($bucket);

        AuditLogService::log(
            'RESERVE_BUDGET',
            BudgetBucket::class,
            $bucket->id,
            null,
            ['reserved_budget' => $bucket->reserved_budget, 'amount' => $amount]
        );

        return true;
    }

    public function releaseReservation(BudgetBucket $bucket, float $amount): void
    {
        $bucket->reserved_budget = max(0, $bucket->reserved_budget - $amount);
        $bucket->recalculateAvailableBalance();
        $this->ruleEngine->evaluateBucket($bucket);

        AuditLogService::log(
            'RELEASE_RESERVATION',
            BudgetBucket::class,
            $bucket->id,
            null,
            ['reserved_budget' => $bucket->reserved_budget, 'amount' => $amount]
        );
    }

    public function finalizeRealization(BudgetBucket $bucket, float $amount): void
    {
        $bucket->reserved_budget = max(0, $bucket->reserved_budget - $amount);
        $bucket->realized_budget += $amount;
        $bucket->recalculateAvailableBalance();
        $this->ruleEngine->evaluateBucket($bucket);

        AuditLogService::log(
            'FINALIZE_REALIZATION',
            BudgetBucket::class,
            $bucket->id,
            null,
            ['realized_budget' => $bucket->realized_budget, 'amount' => $amount]
        );
    }

    public function applyRevision(BudgetBucket $bucket, float $newAllocatedAmount, string $reason, int $approvedById): BudgetRevision
    {
        $usedAmount = (float) ($bucket->reserved_budget + $bucket->realized_budget);
        if ($newAllocatedAmount < $usedAmount) {
            throw new \InvalidArgumentException('Gagal Revisi: Pagu baru (Rp '.number_format($newAllocatedAmount, 0, ',', '.').') tidak mencukupi untuk menutup total komitmen dan realisasi berjalan (Rp '.number_format($usedAmount, 0, ',', '.').').');
        }

        $oldAmount = $bucket->allocated_budget;
        $difference = $newAllocatedAmount - $oldAmount;

        $bucket->allocated_budget = $newAllocatedAmount;
        $bucket->recalculateAvailableBalance();
        $this->ruleEngine->evaluateBucket($bucket);

        $revision = BudgetRevision::create([
            'revision_number' => 'REV/'.date('Ym').'/'.rand(100, 999),
            'budget_bucket_id' => $bucket->id,
            'previous_amount' => $oldAmount,
            'revised_amount' => $newAllocatedAmount,
            'difference' => $difference,
            'reason' => $reason,
            'approved_by' => $approvedById,
        ]);

        AuditLogService::log(
            'APPLY_REVISION',
            BudgetRevision::class,
            $revision->id,
            ['allocated_budget' => $oldAmount],
            ['allocated_budget' => $newAllocatedAmount, 'reason' => $reason]
        );

        return $revision;
    }
}
