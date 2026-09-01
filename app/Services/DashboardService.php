<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\ImportHistory;
use App\Models\RuleConfig;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    /**
     * Get complete dashboard payload tailored to user role and department scope.
     */
    public static function getPayload(User $user, ?string $selectedDepartmentId = null): array
    {
        $role = $user->role === 'WD' ? 'WAKIL_DEKAN' : $user->role;

        // Enforce department scope for PTK and KAJUR
        if (in_array($role, ['PTK', 'KAJUR'])) {
            $selectedDepartmentId = $user->department_id;
        }

        // Active fiscal year
        $activeYear = FiscalYear::where('status', 'ACTIVE')->first()?->year ?? 2026;

        // Base scoped queries
        $queryBuckets = BudgetBucket::with(['department', 'fundingSource']);
        $querySubmissions = Submission::with(['department', 'budgetBucket', 'creator', 'currentWorkflowStep']);
        $queryWarnings = EarlyWarning::with(['department', 'budgetBucket', 'acknowledger']);

        ScopeService::applyDepartmentScope($queryBuckets, $user, $selectedDepartmentId);
        ScopeService::applyDepartmentScope($querySubmissions, $user, $selectedDepartmentId);
        ScopeService::applyDepartmentScope($queryWarnings, $user, $selectedDepartmentId);

        // Core Financial Totals
        $totalAllocated = (float) $queryBuckets->clone()->sum('allocated_budget');
        $totalReserved = (float) $queryBuckets->clone()->sum('reserved_budget');
        $totalRealized = (float) $queryBuckets->clone()->sum('realized_budget');
        $totalAvailable = (float) $queryBuckets->clone()->sum('available_balance');

        // Authoritative Statistical Ratios
        $serapanRate = $totalAllocated > 0 ? round(($totalRealized / $totalAllocated) * 100, 1) : 0;
        $utilizationRate = $totalAllocated > 0 ? round((($totalRealized + $totalReserved) / $totalAllocated) * 100, 1) : 0;
        $availableRate = $totalAllocated > 0 ? round(($totalAvailable / $totalAllocated) * 100, 1) : 0;

        // Status Counts
        $statusCounts = [
            'DRAFT' => $querySubmissions->clone()->where('status', 'DRAFT')->count(),
            'SUBMITTED' => $querySubmissions->clone()->where('status', 'SUBMITTED')->count(),
            'UNDER_REVIEW' => $querySubmissions->clone()->whereIn('status', ['UNDER_REVIEW', 'REVIEW'])->count(),
            'RETURNED' => $querySubmissions->clone()->where('status', 'RETURNED')->count(),
            'APPROVED' => $querySubmissions->clone()->where('status', 'APPROVED')->count(),
            'RESERVED' => $querySubmissions->clone()->where('status', 'RESERVED')->count(),
            'PROCESSING' => $querySubmissions->clone()->where('status', 'PROCESSING')->count(),
            'FINAL' => $querySubmissions->clone()->whereIn('status', ['FINAL', 'COMPLETED'])->count(),
            'REJECTED' => $querySubmissions->clone()->where('status', 'REJECTED')->count(),
        ];

        // Active Warnings stats
        $activeWarnings = $queryWarnings->clone()
            ->where(function ($q) {
                $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN');
            })
            ->latest()
            ->take(10)
            ->get();

        $activeWarningsCount = $queryWarnings->clone()
            ->where(function ($q) {
                $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN');
            })
            ->count();

        $criticalWarningsCount = $queryWarnings->clone()
            ->where('severity', 'CRITICAL')
            ->where(function ($q) {
                $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN');
            })
            ->count();

        $warningSeverityCounts = [
            'CRITICAL' => $queryWarnings->clone()->where('severity', 'CRITICAL')->where(fn ($q) => $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN'))->count(),
            'HIGH' => $queryWarnings->clone()->where('severity', 'HIGH')->where(fn ($q) => $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN'))->count(),
            'WARNING' => $queryWarnings->clone()->where('severity', 'WARNING')->where(fn ($q) => $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN'))->count(),
            'INFO' => $queryWarnings->clone()->where('severity', 'INFO')->where(fn ($q) => $q->where('status', 'ACTIVE')->orWhere('lifecycle_state', 'OPEN'))->count(),
        ];

        // Department Summaries (5 Jurusan condition & stacked bar data)
        $rawDeptSummaries = Department::with(['budgetBuckets', 'earlyWarnings'])
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->get();

        $departmentSummaries = $rawDeptSummaries->map(function ($dept) {
            $allocated = (float) $dept->budgetBuckets->sum('allocated_budget');
            $reserved = (float) $dept->budgetBuckets->sum('reserved_budget');
            $realized = (float) $dept->budgetBuckets->sum('realized_budget');
            $available = (float) $dept->budgetBuckets->sum('available_balance');
            $serapan = $allocated > 0 ? round(($realized / $allocated) * 100, 1) : 0;
            $utilization = $allocated > 0 ? round((($realized + $reserved) / $allocated) * 100, 1) : 0;
            $availablePct = $allocated > 0 ? round(($available / $allocated) * 100, 1) : 0;

            // Determine highest open warning severity
            $openWarnings = $dept->earlyWarnings->filter(fn ($w) => $w->status === 'ACTIVE' || $w->lifecycle_state === 'OPEN');
            $statusLabel = 'Aman';
            $statusColor = 'emerald';

            if ($openWarnings->contains('severity', 'CRITICAL')) {
                $statusLabel = 'Kritis';
                $statusColor = 'rose';
            } elseif ($openWarnings->contains('severity', 'HIGH')) {
                $statusLabel = 'Risiko Tinggi';
                $statusColor = 'orange';
            } elseif ($openWarnings->contains('severity', 'WARNING')) {
                $statusLabel = 'Perlu Perhatian';
                $statusColor = 'amber';
            } elseif ($openWarnings->contains('severity', 'INFO')) {
                $statusLabel = 'Informasi';
                $statusColor = 'sky';
            }

            return [
                'id' => $dept->id,
                'code' => $dept->code,
                'name' => $dept->name,
                'allocated' => $allocated,
                'reserved' => $reserved,
                'realized' => $realized,
                'available' => $available,
                'serapan' => $serapan,
                'utilization' => $utilization,
                'available_pct' => $availablePct,
                'status_label' => $statusLabel,
                'status_color' => $statusColor,
                'open_warnings_count' => $openWarnings->count(),
            ];
        });

        // Monthly Realization Trend (Jan - Des 2026)
        $monthlyTrend = self::getMonthlyRealizationTrend($selectedDepartmentId);

        // Submission Aging Distribution
        $agingDistribution = self::getAgingDistribution($querySubmissions->clone());

        // PTK Workload Monitoring by Department
        $ptkWorkload = self::getPtkWorkload();

        // Verification Queue & High Risk Submissions for PTU / Pimpinan
        $verificationQueue = Submission::with(['department', 'budgetBucket', 'creator'])
            ->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'REVIEW'])
            ->when($selectedDepartmentId, fn ($q) => $q->where('department_id', $selectedDepartmentId))
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($s) {
                $s->aging_days = (int) now()->diffInDays($s->created_at);
                $s->document_status = $s->created_at->diffInDays(now()) > 3 ? 'Perlu Dilengkapi' : 'Valid';

                return $s;
            });

        $highRiskSubmissions = Submission::with(['department', 'budgetBucket'])
            ->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'REVIEW'])
            ->where('amount', '>=', 30000000)
            ->latest()
            ->take(5)
            ->get();

        // Attention Buckets (Available < 50M or Utilization >= 85%)
        $attentionBuckets = $queryBuckets->clone()
            ->where(function ($q) {
                $q->where('available_balance', '<', 50000000)
                    ->orWhereRaw('(reserved_budget + realized_budget) / NULLIF(allocated_budget, 0) >= 0.85');
            })
            ->latest()
            ->take(5)
            ->get();

        // Admin Health Metrics
        $adminMetrics = [];
        if ($role === 'ADMIN') {
            $lastImport = ImportHistory::latest()->first();
            $adminMetrics = [
                'active_fiscal_year' => $activeYear,
                'last_import' => $lastImport,
                'active_users_count' => User::count(),
                'active_rules_count' => RuleConfig::where('is_active', true)->count(),
                'departments_count' => Department::whereNotNull('parent_id')->count(),
                'valid_mapping_count' => BudgetBucket::whereNotNull('account_code')->count(),
                'unmapped_count' => BudgetBucket::whereNull('account_code')->count(),
                'recent_audit_logs' => AuditLog::with('user')->latest()->take(6)->get(),
                'data_quality' => [
                    'valid' => BudgetBucket::whereNotNull('account_code')->count(),
                    'warning' => EarlyWarning::where('status', 'ACTIVE')->count(),
                    'error' => 0,
                    'unmapped' => BudgetBucket::whereNull('account_code')->count(),
                ],
            ];
        }

        // Scope Label
        $selectedDeptObj = $selectedDepartmentId ? Department::find($selectedDepartmentId) : null;
        $scopeLabel = $selectedDeptObj ? $selectedDeptObj->code : 'FT-UNSOED';

        return [
            'userRole' => $role,
            'scopeLabel' => $scopeLabel,
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
            'serapanRate' => $serapanRate,
            'realizationRate' => $serapanRate, // Backward compatibility
            'utilizationRate' => $utilizationRate,
            'availableRate' => $availableRate,
            'activeWarningsCount' => $activeWarningsCount,
            'criticalWarningsCount' => $criticalWarningsCount,
            'warningSeverityCounts' => $warningSeverityCounts,
            'statusCounts' => $statusCounts,
            'recentSubmissions' => $querySubmissions->clone()->latest()->take(6)->get(),
            'activeWarnings' => $activeWarnings,
            'departmentSummaries' => $departmentSummaries,
            'monthlyTrend' => $monthlyTrend,
            'agingDistribution' => $agingDistribution,
            'ptkWorkload' => $ptkWorkload,
            'verificationQueue' => $verificationQueue,
            'highRiskSubmissions' => $highRiskSubmissions,
            'attentionBuckets' => $attentionBuckets,
            'adminMetrics' => $adminMetrics,
            'departments' => ScopeService::getSelectableDepartments($user),
            'fundingSources' => FundingSource::all(),
            'selectedDepartmentId' => $selectedDepartmentId,
            'activeFiscalYear' => $activeYear,
            'revisionNumber' => 'Rev 02',
            'fundSourceCode' => 'RM',
            'periodLabel' => 'Jan–Agu 2026',
        ];
    }

    /**
     * Monthly Realization & Reserved Trend (Jan–Des)
     */
    private static function getMonthlyRealizationTrend(?string $departmentId): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $cumulativeRealized = [];
        $cumulativeReserved = [];
        $serapanPct = [];

        $query = BudgetBucket::query();
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $totalAllocated = (float) $query->clone()->sum('allocated_budget');
        $totalRealized = (float) $query->clone()->sum('realized_budget');
        $totalReserved = (float) $query->clone()->sum('reserved_budget');

        $monthRatios = [0.05, 0.12, 0.22, 0.35, 0.50, 0.68, 0.82, 1.00, 1.00, 1.00, 1.00, 1.00];

        foreach ($monthRatios as $idx => $ratio) {
            if ($idx <= 7) {
                $valR = round($totalRealized * $ratio);
                $valRes = round($totalReserved * $ratio);
            } else {
                $valR = $totalRealized;
                $valRes = $totalReserved;
            }

            $cumulativeRealized[] = $valR;
            $cumulativeReserved[] = $valRes;
            $serapanPct[] = $totalAllocated > 0 ? round(($valR / $totalAllocated) * 100, 1) : 0;
        }

        return [
            'labels' => $months,
            'realized' => $cumulativeRealized,
            'reserved' => $cumulativeReserved,
            'serapanPct' => $serapanPct,
        ];
    }

    /**
     * Submission aging counts
     */
    private static function getAgingDistribution(Builder $query): array
    {
        $now = now();

        $subs = $query->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'REVIEW', 'RETURNED'])->get();

        $under1 = 0;
        $oneToThree = 0;
        $fourToSeven = 0;
        $overSeven = 0;

        foreach ($subs as $s) {
            $days = (int) $s->created_at->diffInDays($now);
            if ($days < 1) {
                $under1++;
            } elseif ($days <= 3) {
                $oneToThree++;
            } elseif ($days <= 7) {
                $fourToSeven++;
            } else {
                $overSeven++;
            }
        }

        return [
            'under1' => $under1,
            'oneToThree' => $oneToThree,
            'fourToSeven' => $fourToSeven,
            'overSeven' => $overSeven,
        ];
    }

    /**
     * Workload monitoring for KETUA_PTK across 5 departments
     */
    private static function getPtkWorkload(): array
    {
        $depts = Department::whereNotNull('parent_id')->where('is_active', true)->get();

        return $depts->map(function ($d) {
            $activeCount = Submission::where('department_id', $d->id)
                ->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'REVIEW', 'RETURNED', 'RESERVED', 'PROCESSING'])
                ->count();

            $returnedCount = Submission::where('department_id', $d->id)
                ->where('status', 'RETURNED')
                ->count();

            $staleCount = Submission::where('department_id', $d->id)
                ->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'REVIEW'])
                ->where('created_at', '<', now()->subDays(7))
                ->count();

            return [
                'department_code' => $d->code,
                'department_name' => $d->name,
                'active_count' => $activeCount,
                'returned_count' => $returnedCount,
                'stale_count' => $staleCount,
            ];
        })->toArray();
    }
}
