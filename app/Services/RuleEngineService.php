<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\BudgetVersion;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\Submission;
use App\Models\SubmissionImportStaging;

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
        $activeYear = FiscalYear::where('status', 'ACTIVE')->first() ?? FiscalYear::first();
        if (! $activeYear) {
            return 0;
        }

        $buckets = BudgetBucket::with('department')
            ->where('fiscal_year_id', $activeYear->id)
            ->get();

        $generatedCount = 0;

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

            // ==================================================
            // EWS-001: Saldo Kritis (< 10% atau Saldo <= 0)
            // ==================================================
            if ($available <= 0) {
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-001',
                    'CRITICAL',
                    "Saldo Kritis: Pos anggaran [{$bucket->account_code}] {$bucket->account_name} telah habis/defisit (Rp ".number_format($available, 0, ',', '.').'). Seluruh pengajuan baru akan diblokir.'
                );
                $generatedCount++;
            } elseif ($availRatio < 0.10) {
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-001',
                    'HIGH',
                    "Saldo Kritis: Sisa saldo bebas pada pos [{$bucket->account_code}] tersisa Rp ".number_format($available, 0, ',', '.').' ('.round($availRatio * 100, 1).'% < 10%). Ketersediaan dana sangat terbatas.'
                );
                $generatedCount++;
            }

            // ==================================================
            // EWS-002: High Utilization (>= 85%)
            // ==================================================
            if ($utilRatio >= 0.85) {
                $severity = $utilRatio >= 0.95 ? 'HIGH' : 'WARNING';
                $this->createOrUpdateWarning(
                    $bucket->department_id,
                    $bucket->id,
                    'EWS-002',
                    $severity,
                    "High Utilization: Tingkat penyerapan & komitmen anggaran pos [{$bucket->account_code}] telah mencapai ".round($utilRatio * 100, 1).'% (>= 85%). Alokasi pagu hampir terserap penuh.'
                );
                $generatedCount++;
            }
        }

        // ==================================================
        // EWS-003: Transaksi Terlalu Lama Dalam Proses (> 3 hari)
        // ==================================================
        $overdueSubmissions = Submission::with(['department', 'budgetBucket'])
            ->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'PROCESSING'])
            ->where('created_at', '<', now()->subDays(3))
            ->get();

        foreach ($overdueSubmissions as $sub) {
            $days = (int) now()->diffInDays($sub->created_at);
            $this->createOrUpdateWarning(
                $sub->department_id,
                $sub->budget_bucket_id,
                'EWS-003',
                $days >= 7 ? 'HIGH' : 'WARNING',
                "Transaksi Terlalu Lama: Transaksi {$sub->evidence_number} ({$sub->title}) telah berada di antrean pemeriksaan selama {$days} hari tanpa tindak lanjut."
            );
            $generatedCount++;
        }

        // ==================================================
        // EWS-004: Revision Conflict (NewBudget < Processing + Final)
        // ==================================================
        $draftVersions = BudgetVersion::where('status', 'DRAFT')->get();
        foreach ($draftVersions as $draft) {
            // Check for buckets where current commitments exceed draft budget
            foreach ($buckets as $b) {
                $totalCommitted = $b->reserved_budget + $b->realized_budget;
                // If a revised budget drops below total committed
                if ($b->initial_budget > 0 && $b->allocated_budget < $totalCommitted) {
                    $this->createOrUpdateWarning(
                        $b->department_id,
                        $b->id,
                        'EWS-004',
                        'CRITICAL',
                        "Revision Conflict: Alokasi pagu revisi pada pos [{$b->account_code}] lebih kecil dari total belanja berjalan (Rp ".number_format($totalCommitted, 0, ',', '.').').'
                    );
                    $generatedCount++;
                }
            }
        }

        // ==================================================
        // EWS-005: Unmapped Data (Unmapped hierarchy / staging items)
        // ==================================================
        $unmappedStagings = SubmissionImportStaging::whereNull('matched_bucket_id')
            ->orWhere('validation_status', 'INVALID')
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($unmappedStagings->count() > 0) {
            $unmappedCount = $unmappedStagings->count();
            $firstStaging = $unmappedStagings->first();
            $dept = $buckets->first()?->department_id ?? 1;

            $this->createOrUpdateWarning(
                $dept,
                null,
                'EWS-005',
                'INFO',
                "Unmapped Data: Ditemukan {$unmappedCount} baris data transaksi pada import staging yang belum terpetakan ke pos pagu master."
            );
            $generatedCount++;
        }

        return $generatedCount;
    }

    public function checkOverbudget(BudgetBucket $bucket, float $amount): bool
    {
        return $bucket->available_balance < $amount;
    }

    public function evaluateBucket(BudgetBucket $bucket): void
    {
        $allocated = (float) $bucket->allocated_budget;
        $available = (float) $bucket->available_balance;
        $realized = (float) $bucket->realized_budget;
        $reserved = (float) $bucket->reserved_budget;

        if ($allocated <= 0) {
            return;
        }

        $availRatio = $available / $allocated;
        $utilRatio = ($realized + $reserved) / $allocated;

        if ($available <= 0) {
            $this->createOrUpdateWarning(
                $bucket->department_id,
                $bucket->id,
                'EWS-001',
                'CRITICAL',
                "Saldo Kritis: Pos anggaran [{$bucket->account_code}] {$bucket->account_name} telah habis/defisit (Rp ".number_format($available, 0, ',', '.').').'
            );
        } elseif ($availRatio < 0.10) {
            $this->createOrUpdateWarning(
                $bucket->department_id,
                $bucket->id,
                'EWS-001',
                'HIGH',
                "Saldo Kritis: Sisa saldo bebas pada pos [{$bucket->account_code}] tersisa ".round($availRatio * 100, 1).'% (< 10%).'
            );
        }

        if ($utilRatio >= 0.85) {
            $this->createOrUpdateWarning(
                $bucket->department_id,
                $bucket->id,
                'EWS-002',
                $utilRatio >= 0.95 ? 'HIGH' : 'WARNING',
                "High Utilization: Tingkat penyerapan & komitmen anggaran pos [{$bucket->account_code}] telah mencapai ".round($utilRatio * 100, 1).'%.'
            );
        }
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
