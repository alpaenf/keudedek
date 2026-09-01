<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\Submission;

class RuleEngineService
{
    /**
     * Evaluate all active Early Warning System (EWS) rules across all active budget buckets.
     */
    public function evaluateAllEws(): int
    {
        $activeYear = FiscalYear::where('status', 'ACTIVE')->first();
        if (! $activeYear) {
            return 0;
        }

        $buckets = BudgetBucket::with('department')
            ->where('fiscal_year_id', $activeYear->id)
            ->get();

        $generatedCount = 0;
        $currentMonth = (int) date('n');

        foreach ($buckets as $bucket) {
            $allocated = (float) $bucket->allocated_budget;
            $available = (float) $bucket->available_balance;
            $realized = (float) $bucket->realized_budget;
            $reserved = (float) $bucket->reserved_budget;

            if ($allocated <= 0) {
                continue;
            }

            $availRatio = $available / $allocated;
            $utilRatio = ($realized + $reserved) / $allocated;

            // EWS-002: Saldo Kritis (< 5%)
            if ($availRatio < 0.05) {
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-002',
                    'CRITICAL',
                    "Sisa saldo bebas pada pos [{$bucket->account_code}] tersisa {$available} (".round($availRatio * 100, 1).'% < 5%). Ketersediaan dana dalam kondisi sangat kritis.'
                );
                $generatedCount++;
            }
            // EWS-001: Saldo Rendah (< 15%)
            elseif ($availRatio < 0.15) {
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-001',
                    'HIGH',
                    "Sisa saldo bebas pada pos [{$bucket->account_code}] tersisa ".round($availRatio * 100, 1).'% (< 15%). Perlu pengendalian belanja ketat.'
                );
                $generatedCount++;
            }

            // EWS-003: High Utilization (>= 85%)
            if ($utilRatio >= 0.85 && $availRatio >= 0.15) {
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-003',
                    'WARNING',
                    "Tingkat utilisasi anggaran pada pos [{$bucket->account_code}] telah mencapai ".round($utilRatio * 100, 1).'% (>= 85%).'
                );
                $generatedCount++;
            }

            // EWS-004: Zero Spending at Q2+
            if ($currentMonth >= 6 && $realized == 0 && $allocated > 10000000) {
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-004',
                    'WARNING',
                    "Pos anggaran [{$bucket->account_code}] belum memiliki realisasi belanja (0%) hingga pertengahan tahun anggaran."
                );
                $generatedCount++;
            }
        }

        // EWS-006: Overdue SLA Verification (> 3 days in queue)
        $overdueSubmissions = Submission::with(['department', 'budgetBucket'])
            ->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW'])
            ->where('created_at', '<', now()->subDays(3))
            ->get();

        foreach ($overdueSubmissions as $sub) {
            $days = (int) now()->diffInDays($sub->created_at);
            $this->createOrUpdateWarning(
                $sub->department_id,
                $sub->budget_bucket_id,
                'EWS-006',
                'WARNING',
                "Pengajuan {$sub->submission_number} telah berada di antrean verifikasi selama {$days} hari (melebihi target SLA fakultas)."
            );
            $generatedCount++;
        }

        return $generatedCount;
    }

    protected function createOrUpdateWarning(
        int $departmentId,
        ?int $bucketId,
        string $ruleCode,
        string $severity,
        string $message
    ): void {
        $existing = EarlyWarning::where('department_id', $departmentId)
            ->where('rule_code', $ruleCode)
            ->where('budget_bucket_id', $bucketId)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->first();

        if ($existing) {
            $existing->update([
                'severity' => $severity,
                'message' => $message,
                'status' => 'ACTIVE',
            ]);
        } else {
            EarlyWarning::create([
                'department_id' => $departmentId,
                'budget_bucket_id' => $bucketId,
                'rule_code' => $ruleCode,
                'severity' => $severity,
                'message' => $message,
                'status' => 'ACTIVE',
                'lifecycle_state' => 'OPEN',
            ]);
        }
    }
}
