<?php

namespace App\Services;

use App\Models\BudgetBucket;

class RuleEngineService
{
    /**
     * Evaluate all active MVP Early Warning System (EWS) rules:
     * - EWS-001: Saldo Kritis (< 10% atau <= 0)
     * - EWS-002: High Utilization (>= 85%)
     * - EWS-003: Transaksi Terlalu Lama Dalam Proses (> 3 hari)
     * - EWS-004: Revision Conflict (NewBudget < Processing + Final)
     * - EWS-005: Unmapped Data (Unmapped hierarchy/account)
     */
    public function evaluateAllEws(): int
    {
        $ewsService = app(EarlyWarningService::class);

        return $ewsService->evaluateAll();
    }

    public function checkOverbudget(BudgetBucket $bucket, float $amount): bool
    {
        return $bucket->available_balance < $amount;
    }

    public function evaluateBucket(BudgetBucket $bucket): void
    {
        $ewsService = app(EarlyWarningService::class);
        $ewsService->evaluateEws001SaldoKritis($bucket);
    }
}
