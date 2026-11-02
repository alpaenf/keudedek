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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class EarlyWarningService
{
    /**
     * Canonical Rule Code Aliases for Backward Compatibility
     */
    protected const RULE_ALIASES = [
        'EWS-001' => 'EWS-01',
        'EWS-002' => 'EWS-02',
    ];

    /**
     * Normalize rule code to canonical proposal standard
     */
    public static function normalizeRuleCode(string $ruleCode): string
    {
        return self::RULE_ALIASES[$ruleCode] ?? $ruleCode;
    }

    /**
     * Get parameter for a rule from database or fallback to config
     */
    public static function getParameter(string $ruleCode, string $paramKey, mixed $default = null): mixed
    {
        $canonicalCode = self::normalizeRuleCode($ruleCode);

        // Check exact rule code first, then canonical alias
        $rule = RuleConfig::where('rule_code', $ruleCode)->first()
            ?? RuleConfig::where('rule_code', $canonicalCode)->first();

        $altKeys = [
            'threshold_days' => 'stale_days',
            'stale_days' => 'threshold_days',
            'threshold_ratio' => 'warning_ratio',
            'warning_ratio' => 'threshold_ratio',
        ];

        if ($rule) {
            if (isset($rule->parameters[$paramKey])) {
                return $rule->parameters[$paramKey];
            }
            if (isset($altKeys[$paramKey]) && isset($rule->parameters[$altKeys[$paramKey]])) {
                return $rule->parameters[$altKeys[$paramKey]];
            }
        }

        return config("ews.rules.{$ruleCode}.default_parameters.{$paramKey}",
            config("ews.rules.{$canonicalCode}.default_parameters.{$paramKey}", $default)
        );
    }

    /**
     * Get parameter version for tracking and explainability (E-14)
     */
    public static function getParameterVersion(string $ruleCode): int
    {
        $canonicalCode = self::normalizeRuleCode($ruleCode);
        $rule = RuleConfig::where('rule_code', $canonicalCode)->first()
            ?? RuleConfig::where('rule_code', $ruleCode)->first();

        return (int) ($rule?->parameter_version ?? 1);
    }

    /**
     * Check if a rule is active
     */
    public static function isRuleActive(string $ruleCode): bool
    {
        $canonicalCode = self::normalizeRuleCode($ruleCode);
        $rule = RuleConfig::where('rule_code', $canonicalCode)->first()
            ?? RuleConfig::where('rule_code', $ruleCode)->first();

        if ($rule !== null) {
            return (bool) $rule->is_active;
        }

        return (bool) config("ews.rules.{$canonicalCode}.is_active",
            config("ews.rules.{$ruleCode}.is_active", true)
        );
    }

    /**
     * Update rule parameter with automatic version incrementing (E-14)
     */
    public static function updateParameter(string $ruleCode, array $newParameters, ?User $actor = null): RuleConfig
    {
        $canonicalCode = self::normalizeRuleCode($ruleCode);

        $rule = RuleConfig::firstOrCreate(
            ['rule_code' => $canonicalCode],
            [
                'rule_name' => config("ews.rules.{$canonicalCode}.name", $canonicalCode),
                'category' => config("ews.rules.{$canonicalCode}.category", 'EWS'),
                'indicator_type' => config("ews.rules.{$canonicalCode}.indicator_type", 'CORE'),
                'parameters' => config("ews.rules.{$canonicalCode}.default_parameters", []),
                'parameter_version' => 1,
                'is_active' => true,
            ]
        );

        $merged = array_merge($rule->parameters ?? [], $newParameters);
        $newVersion = ((int) $rule->parameter_version) + 1;

        $rule->update([
            'parameters' => $merged,
            'parameter_version' => $newVersion,
        ]);

        AuditLogService::log(
            'UPDATE_EWS_PARAMETER',
            RuleConfig::class,
            $rule->id,
            null,
            [
                'rule_code' => $canonicalCode,
                'parameters' => $merged,
                'version' => $newVersion,
                'updated_by' => $actor?->name ?? 'System',
            ]
        );

        return $rule;
    }

    /**
     * Evaluate all active EWS rules
     */
    public function evaluateAll(?Carbon $evaluationTime = null): array
    {
        $now = $evaluationTime ?? now();
        $counts = [
            'ews_01' => 0,
            'ews_02' => 0,
            'ews_03' => 0,
            'ex_01' => 0,
            'operational' => 0,
            'total' => 0,
        ];

        // 1. Core Proposal Indicators
        if (self::isRuleActive('EWS-01')) {
            $counts['ews_01'] = $this->evaluateEws01SaldoMenipis(null, $now);
        }

        if (self::isRuleActive('EWS-02')) {
            $counts['ews_02'] = $this->evaluateEws02StaleDP(null, $now);
        }

        if (self::isRuleActive('EWS-03')) {
            $counts['ews_03'] = $this->evaluateEws03UnderTarget(null, $now);
        }

        if (self::isRuleActive('EX-01')) {
            $counts['ex_01'] = $this->evaluateEx01Overbudget(null, $now);
        }

        // 2. Operational Rules
        if (self::isRuleActive('EWS-003')) {
            $counts['operational'] += $this->evaluateEws003RevisionConflict();
        }

        if (self::isRuleActive('EWS-004')) {
            $counts['operational'] += $this->evaluateEws004UnmappedData();
        }

        if (self::isRuleActive('EWS-005')) {
            $counts['operational'] += $this->evaluateEws005RepeatedReturn();
        }

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * Legacy Method Wrapper for EWS-001 (Backward Compatibility)
     */
    public function evaluateEws001SaldoKritis(?BudgetBucket $bucket = null): int
    {
        return $this->evaluateEws01SaldoMenipis($bucket, null, 'EWS-001');
    }

    /**
     * Legacy Method Wrapper for EWS-002 (Backward Compatibility)
     */
    public function evaluateEws002StaleSubmission(?Submission $submission = null): int
    {
        return $this->evaluateEws02StaleDP($submission, null, 'EWS-002');
    }

    /**
     * EWS-01: Saldo Tersedia Menipis (Proposal Tabel 24 & Persamaan 7-8)
     * Kondisi: 0 <= q_k(t) <= theta_s, dengan q_k(t) = (S_k(t) / P_k(t)) * 100%.
     * - Saldo tepat nol (S = 0): memicu EWS-01 dengan keterangan saldo habis.
     * - Saldo negatif (S < 0): ditangani oleh EX-01 (tidak memicu EWS-01).
     * - Pagu nol (P = 0): tidak dihitung / bebas division by zero (E-08, E-09).
     */
    public function evaluateEws01SaldoMenipis(?BudgetBucket $specificBucket = null, ?Carbon $evaluationTime = null, ?string $ruleCodeOverride = null): int
    {
        $evalTime = $evaluationTime ?? now();
        $ruleCode = $ruleCodeOverride ?? 'EWS-01';
        $thresholdRatio = (float) self::getParameter($ruleCode, 'threshold_ratio', self::getParameter($ruleCode, 'warning_ratio', 0.20));
        $criticalRatio = (float) self::getParameter($ruleCode, 'critical_ratio', 0.05);
        $paramVersion = self::getParameterVersion($ruleCode);

        $query = BudgetBucket::with(['department', 'budgetVersion']);
        if ($specificBucket) {
            $query->where('id', $specificBucket->id);
        } else {
            $activeYear = FiscalYear::where('status', 'ACTIVE')->first();
            if ($activeYear) {
                $query->where('fiscal_year_id', $activeYear->id);
            }
            $query->whereHas('budgetVersion', fn ($q) => $q->where('status', 'ACTIVE'));
        }

        $buckets = $query->get();
        $generated = 0;

        foreach ($buckets as $bucket) {
            $p = (float) $bucket->allocated_budget;
            $s = (float) $bucket->available_balance;

            // E-08 & E-09: Handling Pagu Nol (Avoid division by zero)
            if ($p <= 0) {
                // If S < 0, EX-01 handles it. If S == 0, rasio tidak dihitung (E-08).
                // Auto-resolve any legacy EWS-01 if previously existed
                $this->resolveWarningIfExists('EWS-01', $bucket->id, 'budget_bucket_id', 'Pagu pos anggaran bernilai nol; rasio saldo tidak dapat dihitung.');

                continue;
            }

            // E-10: Saldo negatif ditangani oleh EX-01, bukan EWS-01
            if ($s < 0) {
                $this->resolveWarningIfExists('EWS-01', $bucket->id, 'budget_bucket_id', 'Saldo bernilai negatif; dialihkan ke penanganan pengecualian EX-01.');

                continue;
            }

            // Persamaan (7): q = S / P * 100%
            $qRatio = $s / $p; // e.g. 0.20 for 20%
            $qPct = ($s / $p) * 100.0;

            // Persamaan (8): 0 <= q <= theta_s
            if ($qRatio <= $thresholdRatio) {
                $isCritical = ($s == 0 || $qRatio <= $criticalRatio);
                $severity = $isCritical ? 'CRITICAL' : 'WARNING';
                $thresholdPct = round($thresholdRatio * 100, 2);
                $formattedPct = number_format($qPct, 2, ',', '.');
                $formattedS = number_format($s, 0, ',', '.');
                $formattedP = number_format($p, 0, ',', '.');

                $reason = $s == 0
                    ? "Saldo alokasi anggaran telah habis (0,00%). Sisa dana Rp 0 dari pagu alokasi Rp {$formattedP}."
                    : "Rasio saldo tersedia ({$formattedPct}%) mencapai atau berada di bawah ambang batas perhatian (<= {$thresholdPct}%). Sisa dana Rp {$formattedS} dari pagu alokasi Rp {$formattedP}.";

                $message = $s == 0
                    ? "Saldo Habis: Saldo pos [{$bucket->account_code}] telah habis (Rp 0)."
                    : "Saldo Menipis: Saldo pos [{$bucket->account_code}] tersisa {$formattedPct}% (Rp {$formattedS}).";

                $this->createOrUpdateWarning([
                    'rule_code' => $ruleCode,
                    'target_object' => "Pos {$bucket->account_code} - {$bucket->department?->code}",
                    'severity' => $severity,
                    'department_id' => $bucket->department_id,
                    'budget_bucket_id' => $bucket->id,
                    'current_value' => $qPct,
                    'threshold_value' => $thresholdPct,
                    'operator' => '<=',
                    'parameter_version' => $paramVersion,
                    'eval_status' => 'TRIGGERED',
                    'message' => $message,
                    'reason' => $reason,
                    'context_metadata' => [
                        'pagu' => $p,
                        'available' => $s,
                        'ratio_pct' => $qPct,
                        'threshold_ratio_pct' => $thresholdPct,
                        'evaluation_time' => $evalTime->toIso8601String(),
                    ],
                ]);
                $generated++;
            } else {
                // Condition not met (E-03): q > theta_s -> Auto-resolve if open
                $this->resolveWarningIfExists('EWS-01', $bucket->id, 'budget_bucket_id', 'Auto-resolved: Rasio saldo tersedia telah melampaui ambang batas perhatian.');
            }
        }

        return $generated;
    }

    /**
     * EWS-02: Transaksi Terlalu Lama Dalam Proses (Proposal Tabel 24 & Persamaan 9-10)
     * Kondisi: s_i(t) = DP AND a_i(t) > tau.
     * a_i(t) = selisihHari(d_i, t) dalam hari kalender.
     * - Jika d_i (tanggal pengajuan) tidak tersedia atau setelah t: status belum dapat dievaluasi (E-11).
     * - Jika transaksi telah menjadi RI atau BATAL: peringatan diselesaikan (E-07).
     */
    public function evaluateEws02StaleDP(?Submission $specificSubmission = null, ?Carbon $evaluationTime = null, ?string $ruleCodeOverride = null): int
    {
        $evalTime = $evaluationTime ?? now();
        $ruleCode = $ruleCodeOverride ?? 'EWS-02';
        $tau = (int) self::getParameter($ruleCode, 'threshold_days', self::getParameter($ruleCode, 'stale_days', 30));
        $criticalDays = (int) self::getParameter($ruleCode, 'critical_days', (int) ceil($tau * 1.5));
        $paramVersion = self::getParameterVersion($ruleCode);

        $query = Submission::with(['department', 'budgetBucket']);
        if ($specificSubmission) {
            $query->where('id', $specificSubmission->id);
        } else {
            $query->whereIn('status', BudgetControlService::COMMITMENT_STATUSES);
        }

        $submissions = $query->get();
        $generated = 0;

        foreach ($submissions as $sub) {
            $ref = $sub->evidence_number ?: ($sub->reference_no ?: "TRX-{$sub->id}");

            // E-07: If no longer in DP (e.g. converted to RI or CANCELLED) -> Resolve
            if (! in_array($sub->status, BudgetControlService::COMMITMENT_STATUSES)) {
                $this->resolveWarningIfExists('EWS-02', $sub->id, 'submission_id', "Auto-resolved: Transaksi {$ref} telah ditindaklanjuti ke status {$sub->status}.");

                continue;
            }

            // Transaction date checking
            $trxDate = $sub->transaction_date;
            if (! $trxDate && $ruleCode === 'EWS-002') {
                $trxDate = $sub->updated_at ?? $sub->created_at;
            }

            // E-11: Tanggal pengajuan tidak tersedia atau tidak valid (future date)
            if (! $trxDate || $trxDate->gt($evalTime)) {
                // E-20: Status belum dapat dievaluasi (CANNOT_BE_EVALUATED), pertahankan riwayat jika ada
                $this->recordUnresolvableEvaluation([
                    'rule_code' => $ruleCode,
                    'target_object' => "Transaksi {$ref}",
                    'department_id' => $sub->department_id,
                    'budget_bucket_id' => $sub->budget_bucket_id,
                    'submission_id' => $sub->id,
                    'operator' => '>',
                    'threshold_value' => $tau,
                    'parameter_version' => $paramVersion,
                    'reason' => "Tanggal pengajuan tidak tersedia atau tidak valid pada waktu evaluasi {$evalTime->toDateString()}; indikator umur belum dapat dievaluasi.",
                ]);

                continue;
            }

            // Persamaan (9): Umur transaksi dalam hari kalender
            $ageDays = abs((int) $trxDate->diffInDays($evalTime, false));

            // Persamaan (10): a_i(t) > tau
            if ($ageDays > $tau) {
                $reason = "Transaksi berstatus {$sub->status} telah berada dalam antrean proses selama {$ageDays} hari kalender (Ambang batas perhatian: > {$tau} hari).";
                $message = "Antrean DP Terlalu Lama: Transaksi {$ref} telah berjalan selama {$ageDays} hari.";

                $this->createOrUpdateWarning([
                    'rule_code' => $ruleCode,
                    'target_object' => "Transaksi {$ref}",
                    'severity' => $ageDays >= $criticalDays ? 'CRITICAL' : 'WARNING',
                    'department_id' => $sub->department_id,
                    'budget_bucket_id' => $sub->budget_bucket_id,
                    'submission_id' => $sub->id,
                    'current_value' => $ageDays,
                    'threshold_value' => $tau,
                    'operator' => '>',
                    'parameter_version' => $paramVersion,
                    'eval_status' => 'TRIGGERED',
                    'message' => $message,
                    'reason' => $reason,
                    'context_metadata' => [
                        'transaction_date' => $trxDate?->toDateString(),
                        'evaluation_date' => $evalTime->toDateString(),
                        'age_days' => $ageDays,
                        'threshold_days' => $tau,
                    ],
                ]);
                $generated++;
            } else {
                // Condition not met (E-04, E-05): age <= tau -> Auto-resolve if open
                $this->resolveWarningIfExists($ruleCode, $sub->id, 'submission_id', "Auto-resolved: Umur transaksi ({$ageDays} hari) berada dalam batas normal (<= {$tau} hari).");
            }
        }

        return $generated;
    }

    /**
     * EWS-03: Realisasi Internal di Bawah Target Periode (Proposal Tabel 24 & Persamaan 11-12)
     * Kondisi: u_k(t) < T_k(t), dengan u_k(t) = (R_k(t) / P_k(t)) * 100%.
     * - Hanya aktif bila target kumulatif sah T_k(t) tersedia.
     * - Jika target tidak tersedia: status belum dapat dievaluasi (CANNOT_BE_EVALUATED) (E-17).
     */
    public function evaluateEws03UnderTarget(?BudgetBucket $specificBucket = null, ?Carbon $evaluationTime = null): int
    {
        $evalTime = $evaluationTime ?? now();
        $targetRatio = self::getParameter('EWS-03', 'target_ratio', null);
        $paramVersion = self::getParameterVersion('EWS-03');

        $query = BudgetBucket::with('department');
        if ($specificBucket) {
            $query->where('id', $specificBucket->id);
        } else {
            $activeYear = FiscalYear::where('status', 'ACTIVE')->first();
            if ($activeYear) {
                $query->where('fiscal_year_id', $activeYear->id);
            }
            $query->whereHas('budgetVersion', fn ($q) => $q->where('status', 'ACTIVE'));
        }

        $buckets = $query->get();
        $generated = 0;

        foreach ($buckets as $bucket) {
            $target = $targetRatio !== null ? (float) $targetRatio : null;

            // E-17: Target kumulatif sah tidak tersedia -> Status belum dapat dievaluasi
            if ($target === null) {
                $this->recordUnresolvableEvaluation([
                    'rule_code' => 'EWS-03',
                    'target_object' => "Pos {$bucket->account_code} - {$bucket->department?->code}",
                    'department_id' => $bucket->department_id,
                    'budget_bucket_id' => $bucket->id,
                    'operator' => '<',
                    'threshold_value' => 0,
                    'parameter_version' => $paramVersion,
                    'reason' => 'Target kumulatif realisasi yang sah belum tersedia atau belum ditetapkan untuk waktu evaluasi ini. Status: Belum dapat dievaluasi.',
                ]);

                continue;
            }

            $p = (float) $bucket->allocated_budget;
            $r = (float) $bucket->realized_budget;

            if ($p <= 0) {
                continue;
            }

            // Persamaan (11): u_k(t) = R / P * 100%
            $uPct = ($r / $p) * 100.0;
            $targetPct = $target > 1 ? $target : ($target * 100.0);

            // Persamaan (12): u_k(t) < T_k(t)
            if ($uPct < $targetPct) {
                $reason = 'Rasio realisasi internal ('.number_format($uPct, 2, ',', '.').'%) berada di bawah target kumulatif periode ('.number_format($targetPct, 2, ',', '.').'%).';
                $message = "Realisasi di Bawah Target: Pos [{$bucket->account_code}] baru terealisasi ".number_format($uPct, 2, ',', '.').'% dari target '.number_format($targetPct, 2, ',', '.').'%.';

                $this->createOrUpdateWarning([
                    'rule_code' => 'EWS-03',
                    'target_object' => "Pos {$bucket->account_code} - {$bucket->department?->code}",
                    'severity' => 'WARNING',
                    'department_id' => $bucket->department_id,
                    'budget_bucket_id' => $bucket->id,
                    'current_value' => $uPct,
                    'threshold_value' => $targetPct,
                    'operator' => '<',
                    'parameter_version' => $paramVersion,
                    'eval_status' => 'TRIGGERED',
                    'message' => $message,
                    'reason' => $reason,
                    'context_metadata' => [
                        'realized' => $r,
                        'pagu' => $p,
                        'ratio_pct' => $uPct,
                        'target_pct' => $targetPct,
                    ],
                ]);
                $generated++;
            } else {
                // Condition not met (E-16): u >= T -> Auto-resolve if open
                $this->resolveWarningIfExists('EWS-03', $bucket->id, 'budget_bucket_id', 'Auto-resolved: Realisasi internal telah mencapai atau melampaui target kumulatif.');
            }
        }

        return $generated;
    }

    /**
     * EX-01: Informasi Pengecualian Saldo Negatif (Proposal Tabel 24 & E-09, E-10)
     * Kondisi: S_k(t) < 0
     */
    public function evaluateEx01Overbudget(?BudgetBucket $specificBucket = null, ?Carbon $evaluationTime = null): int
    {
        $evalTime = $evaluationTime ?? now();
        $paramVersion = self::getParameterVersion('EX-01');

        $query = BudgetBucket::with('department');
        if ($specificBucket) {
            $query->where('id', $specificBucket->id);
        } else {
            $activeYear = FiscalYear::where('status', 'ACTIVE')->first();
            if ($activeYear) {
                $query->where('fiscal_year_id', $activeYear->id);
            }
            $query->whereHas('budgetVersion', fn ($q) => $q->where('status', 'ACTIVE'));
        }

        $buckets = $query->get();
        $generated = 0;

        foreach ($buckets as $bucket) {
            $s = (float) $bucket->available_balance;

            if ($s < 0) {
                $p = (float) $bucket->allocated_budget;
                $c = (float) $bucket->reserved_budget;
                $r = (float) $bucket->realized_budget;
                $defisitAbs = abs($s);

                $reason = 'Penggunaan anggaran melampaui alokasi pagu (Pagu: Rp '.number_format($p, 0, ',', '.').', Komitmen DP: Rp '.number_format($c, 0, ',', '.').', Realisasi RI: Rp '.number_format($r, 0, ',', '.').'). Terjadi defisit sebesar Rp '.number_format($defisitAbs, 0, ',', '.').' yang memerlukan peninjauan pimpinan.';
                $message = "Pengecualian Pelampauan Pagu: Pos [{$bucket->account_code}] mengalami saldo negatif -Rp ".number_format($defisitAbs, 0, ',', '.').'.';

                $this->createOrUpdateWarning([
                    'rule_code' => 'EX-01',
                    'target_object' => "Pos {$bucket->account_code} - {$bucket->department?->code}",
                    'severity' => 'CRITICAL',
                    'department_id' => $bucket->department_id,
                    'budget_bucket_id' => $bucket->id,
                    'current_value' => $s,
                    'threshold_value' => 0.0,
                    'operator' => '<',
                    'parameter_version' => $paramVersion,
                    'eval_status' => 'TRIGGERED',
                    'message' => $message,
                    'reason' => $reason,
                    'context_metadata' => [
                        'pagu' => $p,
                        'reserved' => $c,
                        'realized' => $r,
                        'available' => $s,
                        'deficit_amount' => $defisitAbs,
                        'is_exception' => true,
                    ],
                ]);
                $generated++;
            } else {
                $this->resolveWarningIfExists('EX-01', $bucket->id, 'budget_bucket_id', 'Auto-resolved: Saldo anggaran telah kembali seimbang (non-negatif).');
            }
        }

        return $generated;
    }

    /**
     * Operational Rule 1: EWS-003 Revision Conflict
     */
    public function evaluateEws003RevisionConflict(): int
    {
        $draftVersions = BudgetVersion::where('status', 'DRAFT')->get();
        if ($draftVersions->isEmpty()) {
            return 0;
        }

        $generated = 0;

        foreach ($draftVersions as $draft) {
            $activeVersion = BudgetVersion::where('fiscal_year_id', $draft->fiscal_year_id)
                ->where('funding_source_id', $draft->funding_source_id)
                ->where('status', 'ACTIVE')
                ->first();

            if (! $activeVersion) {
                continue;
            }

            $activeBuckets = BudgetBucket::where('budget_version_id', $activeVersion->id)
                ->get()
                ->keyBy(fn ($b) => "{$b->department_id}_{$b->account_code}_{$b->subcomponent_full_code}");
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
                        'operator' => '<',
                        'eval_status' => 'TRIGGERED',
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
     * Operational Rule 2: EWS-004 Unmapped Data
     */
    public function evaluateEws004UnmappedData(): int
    {
        $windowDays = (int) self::getParameter('EWS-004', 'days_window', 14);
        $since = now()->subDays($windowDays);

        $unmappedBudgetStagings = BudgetImportStaging::where('status', 'INVALID')
            ->where('created_at', '>=', $since)
            ->get();

        $unmappedSubStagings = SubmissionImportStaging::where(function ($q) {
            $q->whereNull('matched_bucket_id')
                ->orWhere('validation_status', 'INVALID');
        })->where('created_at', '>=', $since)->get();

        $totalUnmapped = $unmappedBudgetStagings->count() + $unmappedSubStagings->count();

        if ($totalUnmapped > 0) {
            $first = $unmappedBudgetStagings->first();
            $deptId = $first?->department_id ?? null;

            $reason = "Ditemukan {$totalUnmapped} baris data staging import dalam rentang {$windowDays} hari terakhir yang belum terpetakan ke master jurusan atau master akun.";

            $this->createOrUpdateWarning([
                'rule_code' => 'EWS-004',
                'target_object' => "Staging Import ({$totalUnmapped} baris)",
                'severity' => 'WARNING',
                'department_id' => $deptId,
                'budget_bucket_id' => null,
                'current_value' => $totalUnmapped,
                'threshold_value' => 0,
                'operator' => '>',
                'eval_status' => 'TRIGGERED',
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
     * Operational Rule 3: EWS-005 Repeated Return
     */
    public function evaluateEws005RepeatedReturn(): int
    {
        $returnThreshold = (int) self::getParameter('EWS-005', 'threshold_return_count', 2);

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
                'operator' => '>=',
                'eval_status' => 'TRIGGERED',
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
     * Create or update early warning record with explainability, versioning, and anti-duplication
     * Skenario E-12, E-13, E-14
     */
    public function createOrUpdateWarning(array $data): EarlyWarning
    {
        $ruleCode = $data['rule_code'];
        $canonicalCode = self::normalizeRuleCode($ruleCode);
        $bucketId = $data['budget_bucket_id'] ?? null;
        $deptId = $data['department_id'] ?? null;
        $submissionId = $data['submission_id'] ?? null;
        $versionId = $data['budget_version_id'] ?? null;
        $paramVersion = $data['parameter_version'] ?? self::getParameterVersion($canonicalCode);

        // Anti-duplication query: find existing non-resolved warning for this rule and object
        $query = EarlyWarning::where(function ($q) use ($ruleCode, $canonicalCode) {
            $q->where('rule_code', $ruleCode)->orWhere('rule_code', $canonicalCode);
        })->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED']);

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

        // If active warning already exists: Update in place without duplicate record (E-12)
        // If it was already acknowledged, keep it ACKNOWLEDGED (is_read), but update current values (E-13)
        if ($existing) {
            $existing->update([
                'severity' => $data['severity'] ?? $existing->severity,
                'current_value' => $data['current_value'] ?? $existing->current_value,
                'threshold_value' => $data['threshold_value'] ?? $existing->threshold_value,
                'operator' => $data['operator'] ?? $existing->operator,
                'parameter_version' => $paramVersion,
                'eval_status' => 'TRIGGERED',
                'status' => 'ACTIVE',
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
            'severity' => $data['severity'] ?? 'WARNING',
            'department_id' => $deptId,
            'budget_bucket_id' => $bucketId,
            'submission_id' => $submissionId,
            'budget_version_id' => $versionId,
            'current_value' => $data['current_value'] ?? 0,
            'threshold_value' => $data['threshold_value'] ?? 0,
            'operator' => $data['operator'] ?? '<=',
            'parameter_version' => $paramVersion,
            'eval_status' => 'TRIGGERED',
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
     * Handle unresolvable evaluation status (E-11, E-17, E-20)
     * Does NOT auto-resolve. Preserves history with status CANNOT_BE_EVALUATED.
     */
    public function recordUnresolvableEvaluation(array $data): EarlyWarning
    {
        $ruleCode = self::normalizeRuleCode($data['rule_code']);
        $bucketId = $data['budget_bucket_id'] ?? null;
        $submissionId = $data['submission_id'] ?? null;
        $deptId = $data['department_id'] ?? null;

        $query = EarlyWarning::where(function ($q) use ($ruleCode, $data) {
            $q->where('rule_code', $ruleCode)->orWhere('rule_code', $data['rule_code']);
        })->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED']);

        if ($submissionId) {
            $query->where('submission_id', $submissionId);
        } elseif ($bucketId) {
            $query->where('budget_bucket_id', $bucketId);
        }

        $existing = $query->first();
        if ($existing) {
            // E-20: Do NOT resolve. Mark eval_status as CANNOT_BE_EVALUATED and update reason.
            $existing->update([
                'eval_status' => 'CANNOT_BE_EVALUATED',
                'reason' => $data['reason'] ?? 'Data pendukung tidak memadai untuk evaluasi terkini. Keadaan belum dapat dipastikan.',
            ]);

            return $existing;
        }

        $ruleConfig = RuleConfig::where('rule_code', $ruleCode)->first();

        return EarlyWarning::create([
            'rule_code' => $data['rule_code'] ?? $ruleCode,
            'target_object' => $data['target_object'] ?? "Objek {$ruleCode}",
            'severity' => $data['severity'] ?? 'INFO',
            'department_id' => $deptId,
            'budget_bucket_id' => $bucketId,
            'submission_id' => $submissionId,
            'current_value' => $data['current_value'] ?? 0,
            'threshold_value' => $data['threshold_value'] ?? 0,
            'operator' => $data['operator'] ?? '=',
            'parameter_version' => $data['parameter_version'] ?? self::getParameterVersion($ruleCode),
            'eval_status' => 'CANNOT_BE_EVALUATED',
            'message' => $data['message'] ?? "Indikator {$ruleCode}: Belum dapat dievaluasi.",
            'reason' => $data['reason'] ?? 'Data pendukung tidak memadai untuk evaluasi terkini. Keadaan belum dapat dipastikan.',
            'status' => 'ACTIVE',
            'lifecycle_state' => 'OPEN',
            'first_triggered_at' => now(),
            'rule_config_id' => $ruleConfig?->id,
        ]);
    }

    /**
     * Auto-resolve warning when trigger condition is affirmatively no longer met (E-07, I-01)
     */
    protected function resolveWarningIfExists(string $ruleCode, int $objectId, string $objectColumn, string $resolutionReason): void
    {
        $canonicalCode = self::normalizeRuleCode($ruleCode);

        $warnings = EarlyWarning::where(function ($q) use ($ruleCode, $canonicalCode) {
            $q->where('rule_code', $ruleCode)->orWhere('rule_code', $canonicalCode);
        })
            ->where($objectColumn, $objectId)
            ->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])
            ->get();

        foreach ($warnings as $w) {
            $w->update([
                'status' => 'RESOLVED',
                'lifecycle_state' => 'RESOLVED',
                'eval_status' => 'NOT_TRIGGERED',
                'reason' => $resolutionReason,
            ]);
        }
    }

    /**
     * Decoupled integration with RBC: safely evaluates EWS after an RBC event
     * Skenario I-01, I-02, I-03, I-06
     */
    public static function evaluateAfterRbcChange(?BudgetBucket $bucket = null, ?Submission $submission = null): void
    {
        try {
            $service = app(self::class);

            if ($bucket) {
                $service->evaluateEws01SaldoMenipis($bucket);
                $service->evaluateEx01Overbudget($bucket);
                if (self::isRuleActive('EWS-03')) {
                    $service->evaluateEws03UnderTarget($bucket);
                }
            }

            if ($submission) {
                $service->evaluateEws02StaleDP($submission);
            }
        } catch (Throwable $e) {
            // I-06: Failure of EWS must NOT break RBC transaction consistency
            Log::warning("EWS background evaluation after RBC failed safely: {$e->getMessage()}", [
                'bucket_id' => $bucket?->id,
                'submission_id' => $submission?->id,
            ]);
        }
    }
}
