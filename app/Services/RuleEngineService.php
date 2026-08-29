<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\EarlyWarning;

class RuleEngineService
{
    public function evaluateBucket(BudgetBucket $bucket): void
    {
        $bucket->recalculateAvailableBalance();

        if ($bucket->allocated_budget > 0) {
            $percentage = ($bucket->available_balance / $bucket->allocated_budget) * 100;

            // EWS-001 Critical Available Balance (< 15%)
            if ($percentage < 15) {
                $severity = $percentage < 5 ? 'CRITICAL' : 'HIGH';

                EarlyWarning::updateOrCreate(
                    [
                        'rule_code' => 'EWS-001',
                        'budget_bucket_id' => $bucket->id,
                        'status' => 'ACTIVE',
                    ],
                    [
                        'severity' => $severity,
                        'department_id' => $bucket->department_id,
                        'current_value' => $bucket->available_balance,
                        'threshold_value' => $bucket->allocated_budget * 0.15,
                        'message' => "Sisa saldo ketersediaan anggaran pada pos {$bucket->account_code} - {$bucket->account_name} berada di bawah threshold ".number_format($percentage, 1).'% (Rp '.number_format($bucket->available_balance, 0, ',', '.').').',
                    ]
                );
            } else {
                EarlyWarning::where('rule_code', 'EWS-001')
                    ->where('budget_bucket_id', $bucket->id)
                    ->where('status', 'ACTIVE')
                    ->update(['status' => 'RESOLVED']);
            }
        }
    }

    public function checkOverbudget(BudgetBucket $bucket, float $requestedAmount): bool
    {
        return $requestedAmount > $bucket->available_balance;
    }
}
