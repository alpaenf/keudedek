<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\BudgetImportStaging;
use App\Models\BudgetVersion;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\RuleConfig;
use App\Models\Submission;
use App\Models\SubmissionImportStaging;
use App\Models\SubmissionStatusHistory;

class EarlyWarningService
{
    /**
     * Get parameter for a rule from database or fallback to config
     */
    public static function getParameter(string $ruleCode, string $paramKey, mixed $default = null): mixed
    {
        $rule = RuleConfig::where('rule_code', $ruleCode)->first();
        if ($rule && isset($rule->parameters[$paramKey])) {
            return $rule->parameters[$paramKey];
        }

        return config("ews.rules.{$ruleCode}.default_parameters.{$paramKey}", $default);
    }

    /**
     * Check if a rule is active
     */
    public static function isRuleActive(string $ruleCode): bool
    {
        $rule = RuleConfig::where('rule_code', $ruleCode)->first();
        if ($rule !== null) {
            return (bool) $rule->is_active;
        }

        return (bool) config("ews.rules.{$ruleCode}.is_active", true);
    }

    /**
     * Evaluate all baseline EWS rules
     */
    public function evaluateAll(): int
    {
        $count = 0;

        if (self::isRuleActive('EWS-001')) {
            $count += $this->evaluateEws001SaldoKritis();
        }

        if (self::isRuleActive('EWS-002')) {
            $count += $this->evaluateEws002StaleSubmission();
        }

        if (self::isRuleActive('EWS-003')) {
            $count += $this->evaluateEws003RevisionConflict();
        }

        if (self::isRuleActive('EWS-004')) {
            $count += $this->evaluateEws004UnmappedData();
        }

        if (self::isRuleActive('EWS-005')) {
            $count += $this->evaluateEws005RepeatedReturn();
        }

        // Auto-resolve stale warnings that are no longer triggering
        $this->autoResolveClearedWarnings();

        return $count;
    }

    /**
     * EWS-001: Saldo Kritis
     * Formula: available / current_budget <= X (X configurable)
     */
    public function evaluateEws001SaldoKritis(?BudgetBucket $specificBucket = null): int
    {
        $warningRatio = (float) self::getParameter('EWS-001', 'warning_ratio', 0.15);
        $criticalRatio = (float) self::getParameter('EWS-001', 'critical_ratio', 0.05);

        $query = BudgetBucket::with('department');
        if ($specificBucket) {
            $query->where('id', $specificBucket->id);
        } else {
            $activeYear = FiscalYear::where('status', 'ACTIVE')->first();
            if ($activeYear) {
                $query->where('fiscal_year_id', $activeYear->id);
            }
        }

        $buckets = $query->get();
        $generated = 0;

        foreach ($buckets as $bucket) {
            $allocated = (float) $bucket->allocated_budget;
            $available = (float) $bucket->available_balance;

            if ($allocated <= 0) {
                continue;
            }

            $ratio = $available / $allocated;

            if ($available <= 0 || $ratio <= $criticalRatio) {
                $severity = 'CRITICAL';
                $threshold = $criticalRatio * 100;
                $currentPct = round($ratio * 100, 2);
                $reason = "Rasio saldo tersedia ({$currentPct}%) berada pada atau di bawah ambang batas kritis (<= {$threshold}%). Sisa saldo Rp ".number_format($available, 0, ',', '.').' dari pagu alokasi Rp '.number_format($allocated, 0, ',', '.').'.';

                $this->createOrUpdateWarning([
                    'rule_code' => 'EWS-001',
                    'target_object' => "Pos {$bucket->account_code} - {$bucket->department?->code}",
                    'severity' => $severity,
                    'department_id' => $bucket->department_id,
                    'budget_bucket_id' => $bucket->id,
                    'current_value' => $ratio,
                    'threshold_value' => $criticalRatio,
                    'message' => "Saldo Kritis: Saldo pos [{$bucket->account_code}] {$bucket->account_name} sisa {$currentPct}% (Rp ".number_format($available, 0, ',', '.').').',
                    'reason' => $reason,
                    'context_metadata' => [
                        'available_balance' => $available,
                        'allocated_budget' => $allocated,
                        'ratio' => $ratio,
                        'threshold_type' => 'CRITICAL',
                    ],
                ]);
                $generated++;
            } elseif ($ratio <= $warningRatio) {
                $severity = 'WARNING';
                $threshold = $warningRatio * 100;
                $currentPct = round($ratio * 100, 2);
                $reason = "Rasio saldo tersedia ({$currentPct}%) mendekati batas peringatan (<= {$threshold}%). Sisa dana bebas menipis.";

                $this->createOrUpdateWarning([
                    'rule_code' => 'EWS-001',
                    'target_object' => "Pos {$bucket->account_code} - {$bucket->department?->code}",
                    'severity' => $severity,
                    'department_id' => $bucket->department_id,
                    'budget_bucket_id' => $bucket->id,
                    'current_value' => $ratio,
                    'threshold_value' => $warningRatio,
                    'message' => "Saldo Menipis: Saldo pos [{$bucket->account_code}] sisa {$currentPct}% (Rp ".number_format($available, 0, ',', '.').').',
                    'reason' => $reason,
                    'context_metadata' => [
                        'available_balance' => $available,
                        'allocated_budget' => $allocated,
                        'ratio' => $ratio,
                        'threshold_type' => 'WARNING',
                    ],
                ]);
                $generated++;
            }
        }

        return $generated;
    }

    /**
     * EWS-002: Stale Submission
     * DIAJUKAN > N hari tanpa perubahan (N configurable)
     */
    public function evaluateEws002StaleSubmission(): int
    {
        $staleDays = (int) self::getParameter('EWS-002', 'stale_days', 3);
        $criticalDays = (int) self::getParameter('EWS-002', 'critical_days', 7);

        $thresholdDate = now()->subDays($staleDays);

        $submissions = Submission::with(['department', 'budgetBucket'])
            ->whereIn('status', BudgetControlService::COMMITMENT_STATUSES)
            ->where('updated_at', '<=', $thresholdDate)
            ->get();

        $generated = 0;

        foreach ($submissions as $sub) {
            $daysWaiting = abs((int) now()->diffInDays($sub->updated_at, false));
            $severity = $daysWaiting >= $criticalDays ? 'HIGH' : 'WARNING';
            $ref = $sub->evidence_number ?: ($sub->reference_no ?: "TRX-{$sub->id}");

            $reason = "Pengajuan berstatus {$sub->status} telah berada di antrean pemeriksaan selama {$daysWaiting} hari tanpa perubahan status (Ambang batas: > {$staleDays} hari).";

            $this->createOrUpdateWarning([
                'rule_code' => 'EWS-002',
                'target_object' => "Transaksi {$ref}",
                'severity' => $severity,
                'department_id' => $sub->department_id,
                'budget_bucket_id' => $sub->budget_bucket_id,
                'submission_id' => $sub->id,
                'current_value' => $daysWaiting,
                'threshold_value' => $staleDays,
                'message' => "Stale Submission: Transaksi {$ref} ({$sub->title}) belum diproses selama {$daysWaiting} hari.",
                'reason' => $reason,
                'context_metadata' => [
                    'submission_id' => $sub->id,
                    'evidence_number' => $sub->evidence_number,
                    'submission_status' => $sub->status,
                    'days_waiting' => $daysWaiting,
                    'last_updated' => $sub->updated_at->toIso8601String(),
                ],
            ]);
            $generated++;
        }

        return $generated;
    }

    /**
     * EWS-003: Revision Conflict
     * budget baru < commitment aktif + internal realization
     */
    public function evaluateEws003RevisionConflict(): int
    {
        $draftVersions = BudgetVersion::where('status', 'DRAFT')->get();
        if ($draftVersions->isEmpty()) {
            return 0;
        }

        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();
        if (! $activeVersion) {
            return 0;
        }

        $activeBuckets = BudgetBucket::where('budget_version_id', $activeVersion->id)
            ->get()
            ->keyBy(fn ($b) => "{$b->department_id}_{$b->account_code}_{$b->subcomponent_full_code}");

        $generated = 0;

        foreach ($draftVersions as $draft) {
            $draftBuckets = BudgetBucket::where('budget_version_id', $draft->id)->get();

            foreach ($draftBuckets as $dBucket) {
                $key = "{$dBucket->department_id}_{$dBucket->account_code}_{$dBucket->subcomponent_full_code}";
                $active = $activeBuckets->get($key);

                if (! $active) {
                    continue;
                }

                $activeCommitted = (float) $active->reserved_budget;
                $activeRealized = (float) $active->realized_budget;
                $totalActiveLoad = $activeCommitted + $activeRealized;
                $newAllocated = (float) $dBucket->allocated_budget;

                if ($newAllocated < $totalActiveLoad) {
                    $deficit = $totalActiveLoad - $newAllocated;
                    $dept = $dBucket->department;
                    $ref = "Pos {$dBucket->account_code} - {$dept?->code}";

                    $reason = "Usulan pagu revisi pada versi [{$draft->revision_no}] sebesar Rp ".number_format($newAllocated, 0, ',', '.').' lebih kecil daripada total belanja aktif berjalan (Komitmen Diajukan: Rp '.number_format($activeCommitted, 0, ',', '.').' + Realisasi Selesai: Rp '.number_format($activeRealized, 0, ',', '.').' = Rp '.number_format($totalActiveLoad, 0, ',', '.').'). Terjadi potensi defisit sebesar Rp '.number_format($deficit, 0, ',', '.').'.';

                    $this->createOrUpdateWarning([
                        'rule_code' => 'EWS-003',
                        'target_object' => $ref,
                        'severity' => 'CRITICAL',
                        'department_id' => $dBucket->department_id,
                        'budget_bucket_id' => $active->id,
                        'budget_version_id' => $draft->id,
                        'current_value' => $newAllocated,
                        'threshold_value' => $totalActiveLoad,
                        'message' => "Revision Conflict: Usulan pagu versi [{$draft->revision_no}] pada pos [{$dBucket->account_code}] mengalami defisit Rp ".number_format($deficit, 0, ',', '.').'.',
                        'reason' => $reason,
                        'context_metadata' => [
                            'draft_version_id' => $draft->id,
                            'draft_revision_no' => $draft->revision_no,
                            'new_allocated' => $newAllocated,
                            'active_load' => $totalActiveLoad,
                            'deficit_amount' => $deficit,
                        ],
                    ]);
                    $generated++;
                }
            }
        }

        return $generated;
    }

    /**
     * EWS-004: Unmapped Data
     * import/master belum termapping
     */
    public function evaluateEws004UnmappedData(): int
    {
        $windowDays = (int) self::getParameter('EWS-004', 'days_window', 14);
        $since = now()->subDays($windowDays);

        // Check 1: Budget import stagings with unmapped status or errors
        $unmappedBudgetStagings = BudgetImportStaging::where('status', 'INVALID')
            ->where('created_at', '>=', $since)
            ->get();

        // Check 2: Submission import stagings with unmapped status
        $unmappedSubStagings = SubmissionImportStaging::where(function ($q) {
            $q->whereNull('matched_bucket_id')
                ->orWhere('validation_status', 'INVALID');
        })->where('created_at', '>=', $since)->get();

        $totalUnmapped = $unmappedBudgetStagings->count() + $unmappedSubStagings->count();

        if ($totalUnmapped > 0) {
            $first = $unmappedBudgetStagings->first();
            $deptId = $first?->department_id ?? null;

            $reason = "Ditemukan {$totalUnmapped} baris data staging import (Pagu: {$unmappedBudgetStagings->count()} baris, Transaksi: {$unmappedSubStagings->count()} baris) dalam rentang {$windowDays} hari terakhir yang belum terpetakan ke master jurusan atau master akun.";

            $this->createOrUpdateWarning([
                'rule_code' => 'EWS-004',
                'target_object' => "Staging Import ({$totalUnmapped} baris)",
                'severity' => 'WARNING',
                'department_id' => $deptId,
                'budget_bucket_id' => null,
                'current_value' => $totalUnmapped,
                'threshold_value' => 0,
                'message' => "Unmapped Data: Terdeteksi {$totalUnmapped} baris import yang belum terpetakan ke master data.",
                'reason' => $reason,
                'context_metadata' => [
                    'budget_staging_invalid_count' => $unmappedBudgetStagings->count(),
                    'submission_staging_invalid_count' => $unmappedSubStagings->count(),
                    'window_days' => $windowDays,
                ],
            ]);

            return 1;
        }

        return 0;
    }

    /**
     * EWS-005: Repeated Return
     * Transaksi dikembalikan >= N kali (N configurable)
     */
    public function evaluateEws005RepeatedReturn(): int
    {
        $returnThreshold = (int) self::getParameter('EWS-005', 'threshold_return_count', 2);

        // Count returns per submission from status histories
        $returnedSubmissions = SubmissionStatusHistory::where('to_status', 'RETURNED')
            ->selectRaw('submission_id, count(*) as return_count')
            ->groupBy('submission_id')
            ->having('return_count', '>=', $returnThreshold)
            ->get();

        $generated = 0;

        foreach ($returnedSubmissions as $row) {
            $sub = Submission::with(['department', 'budgetBucket'])->find($row->submission_id);
            if (! $sub) {
                continue;
            }

            $ref = $sub->evidence_number ?: ($sub->reference_no ?: "TRX-{$sub->id}");
            $count = (int) $row->return_count;

            $reason = "Transaksi {$ref} telah mengalami penolakan/pengembalian pemeriksaan sebanyak {$count} kali (Ambang batas: >= {$returnThreshold} kali). Diperlukan asistensi kelengkapan dokumen kepada PTK pemohon.";

            $this->createOrUpdateWarning([
                'rule_code' => 'EWS-005',
                'target_object' => "Transaksi {$ref}",
                'severity' => 'HIGH',
                'department_id' => $sub->department_id,
                'budget_bucket_id' => $sub->budget_bucket_id,
                'submission_id' => $sub->id,
                'current_value' => $count,
                'threshold_value' => $returnThreshold,
                'message' => "Repeated Return: Transaksi {$ref} telah dikembalikan sebanyak {$count} kali.",
                'reason' => $reason,
                'context_metadata' => [
                    'submission_id' => $sub->id,
                    'return_count' => $count,
                    'threshold_return_count' => $returnThreshold,
                ],
            ]);
            $generated++;
        }

        return $generated;
    }

    /**
     * Create or update early warning record with explainability fields
     */
    public function createOrUpdateWarning(array $data): EarlyWarning
    {
        $ruleCode = $data['rule_code'];
        $bucketId = $data['budget_bucket_id'] ?? null;
        $deptId = $data['department_id'] ?? null;
        $submissionId = $data['submission_id'] ?? null;
        $versionId = $data['budget_version_id'] ?? null;

        $query = EarlyWarning::where('rule_code', $ruleCode)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED']);

        if ($submissionId) {
            $query->where('submission_id', $submissionId);
        } elseif ($bucketId) {
            $query->where('budget_bucket_id', $bucketId);
            if ($versionId) {
                $query->where('budget_version_id', $versionId);
            }
        } elseif ($deptId) {
            $query->where('department_id', $deptId);
        }

        $existing = $query->first();

        $ruleConfig = RuleConfig::where('rule_code', $ruleCode)->first();

        if ($existing) {
            $existing->update([
                'severity' => $data['severity'],
                'current_value' => $data['current_value'] ?? $existing->current_value,
                'threshold_value' => $data['threshold_value'] ?? $existing->threshold_value,
                'message' => $data['message'],
                'reason' => $data['reason'] ?? $existing->reason,
                'target_object' => $data['target_object'] ?? $existing->target_object,
                'context_metadata' => $data['context_metadata'] ?? $existing->context_metadata,
                'rule_config_id' => $ruleConfig?->id ?? $existing->rule_config_id,
            ]);

            return $existing;
        }

        return EarlyWarning::create([
            'rule_code' => $ruleCode,
            'target_object' => $data['target_object'] ?? null,
            'severity' => $data['severity'],
            'department_id' => $deptId,
            'budget_bucket_id' => $bucketId,
            'submission_id' => $submissionId,
            'budget_version_id' => $versionId,
            'current_value' => $data['current_value'] ?? 0,
            'threshold_value' => $data['threshold_value'] ?? 0,
            'message' => $data['message'],
            'reason' => $data['reason'] ?? $data['message'],
            'status' => 'ACTIVE',
            'lifecycle_state' => 'OPEN',
            'first_triggered_at' => now(),
            'rule_config_id' => $ruleConfig?->id,
            'context_metadata' => $data['context_metadata'] ?? null,
        ]);
    }

    /**
     * Auto-resolve warnings whose condition is no longer met
     */
    protected function autoResolveClearedWarnings(): void
    {
        // 1. EWS-001: Resolve if bucket available balance is now healthy
        $warningRatio = (float) self::getParameter('EWS-001', 'warning_ratio', 0.15);
        $active001 = EarlyWarning::where('rule_code', 'EWS-001')
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->whereNotNull('budget_bucket_id')
            ->get();

        foreach ($active001 as $w) {
            $bucket = $w->budgetBucket;
            if (! $bucket) {
                continue;
            }
            $allocated = (float) $bucket->allocated_budget;
            $available = (float) $bucket->available_balance;
            if ($allocated > 0 && ($available / $allocated) > $warningRatio) {
                $w->update([
                    'lifecycle_state' => 'RESOLVED',
                    'status' => 'RESOLVED',
                    'reason' => 'Auto-resolved: Saldo tersedia telah bertambah melebihi ambang batas peringatan.',
                ]);
            }
        }

        // 2. EWS-002: Resolve if submission is no longer in commitment queue (e.g. Selesai, Ditolak, Dikembalikan)
        $active002 = EarlyWarning::where('rule_code', 'EWS-002')
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->whereNotNull('submission_id')
            ->get();

        foreach ($active002 as $w) {
            $sub = $w->submission;
            if (! $sub || ! in_array($sub->status, BudgetControlService::COMMITMENT_STATUSES)) {
                $w->update([
                    'lifecycle_state' => 'RESOLVED',
                    'status' => 'RESOLVED',
                    'reason' => 'Auto-resolved: Transaksi telah ditindaklanjuti (Status: '.($sub?->status ?? 'REMOVED').').',
                ]);
            }
        }
    }
}
