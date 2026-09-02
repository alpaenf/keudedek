<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Services\AuditLogService;
use App\Services\RuleEngineService;
use App\Services\ScopeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EarlyWarningController extends Controller
{
    public function __construct(
        protected RuleEngineService $ruleEngineService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Run EWS sweep on demand
        $this->ruleEngineService->evaluateAllEws();

        $query = EarlyWarning::with([
            'department',
            'budgetBucket.fiscalYear',
            'budgetBucket.fundingSource',
            'acknowledger',
        ]);

        ScopeService::applyDepartmentScope($query, $user, $request->department_id);

        // 1. TA Filter (Fiscal Year)
        if ($request->filled('fiscal_year_id')) {
            $fyId = $request->fiscal_year_id;
            $query->whereHas('budgetBucket', function ($bq) use ($fyId) {
                $bq->where('fiscal_year_id', $fyId);
            });
        }

        // 2. Akun Filter (Account Code)
        if ($request->filled('account_code')) {
            $accCode = $request->account_code;
            $query->whereHas('budgetBucket', function ($bq) use ($accCode) {
                $bq->where('account_code', $accCode);
            });
        }

        // 3. Rule Filter (EWS-001, EWS-002, EWS-003, EWS-004, EWS-005)
        if ($request->filled('rule_code')) {
            $query->where('rule_code', $request->rule_code);
        }

        // 4. Severity Filter (INFO, WARNING, HIGH, CRITICAL)
        if ($request->filled('severity')) {
            $query->where('severity', strtoupper($request->severity));
        }

        // 5. State Filter (OPEN, ACKNOWLEDGED, RESOLVED)
        if ($request->filled('lifecycle_state')) {
            $query->where('lifecycle_state', strtoupper($request->lifecycle_state));
        }

        // 6. Date Range Filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 7. Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('rule_code', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('budgetBucket', function ($bq) use ($search) {
                        $bq->where('account_code', 'like', "%{$search}%")
                            ->orWhere('account_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $warnings = $query->orderByRaw("FIELD(lifecycle_state, 'OPEN', 'ACKNOWLEDGED', 'RESOLVED')")
            ->orderByRaw("FIELD(severity, 'CRITICAL', 'HIGH', 'WARNING', 'INFO')")
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $departments = ScopeService::getSelectableDepartments($user);
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $accounts = BudgetBucket::select('account_code', 'account_name')->distinct()->orderBy('account_code')->get();

        // Metric Statistics
        $statQuery = EarlyWarning::query();
        ScopeService::applyDepartmentScope($statQuery, $user, $request->department_id);

        $stats = [
            'total_open' => (clone $statQuery)->where('lifecycle_state', 'OPEN')->count(),
            'total_acknowledged' => (clone $statQuery)->where('lifecycle_state', 'ACKNOWLEDGED')->count(),
            'total_resolved' => (clone $statQuery)->where('lifecycle_state', 'RESOLVED')->count(),
            'critical_count' => (clone $statQuery)->where('severity', 'CRITICAL')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'high_count' => (clone $statQuery)->where('severity', 'HIGH')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'warning_count' => (clone $statQuery)->where('severity', 'WARNING')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'info_count' => (clone $statQuery)->where('severity', 'INFO')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
        ];

        return Inertia::render('Warnings/Index', [
            'warnings' => $warnings,
            'departments' => $departments,
            'fiscalYears' => $fiscalYears,
            'accounts' => $accounts,
            'stats' => $stats,
            'filters' => $request->only([
                'fiscal_year_id',
                'department_id',
                'account_code',
                'rule_code',
                'severity',
                'lifecycle_state',
                'start_date',
                'end_date',
                'search',
            ]),
            'userRole' => $user?->role === 'WD' ? 'WAKIL_DEKAN' : ($user?->role ?? 'GUEST'),
        ]);
    }

    public function show(EarlyWarning $earlyWarning): Response
    {
        $earlyWarning->load([
            'department',
            'budgetBucket.fiscalYear',
            'budgetBucket.fundingSource',
            'budgetBucket.budgetVersion',
            'acknowledger',
        ]);

        $bucket = $earlyWarning->budgetBucket;
        $dept = $earlyWarning->department;
        $deptName = $dept?->name ?? 'Fakultas Teknik';

        $ruleNames = [
            'EWS-001' => 'Saldo Kritis',
            'EWS-002' => 'High Utilization',
            'EWS-003' => 'Transaksi Terlalu Lama Dalam Proses',
            'EWS-004' => 'Revision Conflict',
            'EWS-005' => 'Unmapped Data Staging',
        ];

        $ruleName = $ruleNames[$earlyWarning->rule_code] ?? 'Early Warning Indicator';

        // 7-Segment Budget Context
        $budgetContext = [
            'ta' => $bucket?->fiscalYear?->year ?? 2026,
            'sumber_dana' => $bucket?->fundingSource?->code ?? 'RM',
            'revision' => $bucket?->budgetVersion?->revision_no ?? 'Rev 02',
            'jurusan_code' => $dept?->code ?? 'FT',
            'jurusan_name' => $deptName,
            'program_code' => 'WA',
            'program_name' => 'Program Dukungan Manajemen',
            'activity_code' => '4257',
            'activity_name' => 'Dukungan Manajemen & Pelaksanaan Tugas Teknis Ditjen Dikti',
            'kro_code' => '7734.EBA',
            'kro_name' => 'Layanan Dukungan Manajemen Internal',
            'ro_code' => '994',
            'ro_name' => 'Layanan Perkantoran',
            'component_code' => '001',
            'component_name' => 'Operasional & Pemeliharaan Kantor',
            'subcomponent_code' => $bucket?->subcomponent_code ?? 'AA',
            'subcomponent_name' => $bucket?->subcomponent_name ?? "Operasional & Praktikum {$deptName}",
            'account_code' => $bucket?->account_code ?? '-',
            'account_name' => $bucket?->account_name ?? 'Belanja Operasional',
            'subaccount_code' => ($bucket?->account_code ?? '521211').'.001',
            'subaccount_name' => 'Alokasi Operasional Standar Unit',
        ];

        // Calculation Metrics & Ratios
        $allocated = (float) ($bucket?->allocated_budget ?? 0);
        $available = (float) ($bucket?->available_balance ?? 0);
        $reserved = (float) ($bucket?->reserved_budget ?? 0);
        $realized = (float) ($bucket?->realized_budget ?? 0);

        $availableRatio = $allocated > 0 ? round(($available / $allocated) * 100, 2) : 0;
        $utilizationRatio = $allocated > 0 ? round((($realized + $reserved) / $allocated) * 100, 2) : 0;

        $thresholdMap = [
            'EWS-001' => 'Available Balance Ratio <= 10.00% atau Saldo <= Rp 0',
            'EWS-002' => 'Utilization Ratio (Realized + Reserved) >= 85.00%',
            'EWS-003' => 'Pending Examination Duration > 3 Hari Kerja',
            'EWS-004' => 'Pagu Revisi Baru < Total Belanja Berjalan',
            'EWS-005' => 'Unmapped Field Count > 0 pada Import Staging',
        ];

        $calculation = [
            'allocated_budget' => $allocated,
            'available_balance' => $available,
            'reserved_budget' => $reserved,
            'realized_budget' => $realized,
            'available_ratio' => $availableRatio,
            'utilization_ratio' => $utilizationRatio,
            'threshold' => $thresholdMap[$earlyWarning->rule_code] ?? 'Standard Threshold',
            'reason' => "{$earlyWarning->rule_code} triggered: {$earlyWarning->message}",
        ];

        // History Timeline
        $history = [
            'opened' => [
                'timestamp' => $earlyWarning->created_at,
                'human' => $earlyWarning->created_at->diffForHumans(),
                'actor' => 'System Evaluator Engine (RBC/EWS)',
                'notes' => 'Peringatan terdeteksi secara otomatis oleh pemindaian sistem.',
            ],
            'acknowledged' => $earlyWarning->acknowledged_at ? [
                'timestamp' => $earlyWarning->acknowledged_at,
                'human' => Carbon::parse($earlyWarning->acknowledged_at)->diffForHumans(),
                'actor' => $earlyWarning->acknowledger?->name ?? 'Verifikator',
                'notes' => 'Peringatan telah dipelajari dan sedang dalam penanganan unit terkait.',
            ] : null,
            'resolved' => $earlyWarning->lifecycle_state === 'RESOLVED' ? [
                'timestamp' => $earlyWarning->updated_at,
                'human' => $earlyWarning->updated_at->diffForHumans(),
                'actor' => 'Pejabat Otorisator',
                'notes' => 'Kondisi risiko telah diselesaikan atau pagu telah disesuaikan.',
            ] : null,
        ];

        return Inertia::render('Warnings/Show', [
            'warning' => $earlyWarning,
            'ruleName' => $ruleName,
            'budgetContext' => $budgetContext,
            'calculation' => $calculation,
            'history' => $history,
            'relatedBudgetUrl' => $bucket ? "/budgets/{$bucket->id}" : '/budgets',
            'relatedTransactionUrl' => $bucket ? "/submissions?account_code={$bucket->account_code}" : '/submissions',
        ]);
    }

    public function acknowledge(Request $request, EarlyWarning $earlyWarning): RedirectResponse
    {
        $user = $request->user();

        if (! ScopeService::canAccessDepartment($user, $earlyWarning->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang merespon peringatan unit lain.');
        }

        $earlyWarning->update([
            'lifecycle_state' => 'ACKNOWLEDGED',
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);

        AuditLogService::log('ACKNOWLEDGE_EWS', EarlyWarning::class, $earlyWarning->id, null, ['rule_code' => $earlyWarning->rule_code]);

        return redirect()->back()->with('success', "Peringatan {$earlyWarning->rule_code} telah ditandai sebagai direspon (Acknowledged).");
    }

    public function resolve(Request $request, EarlyWarning $earlyWarning): RedirectResponse
    {
        $user = $request->user();

        if (! ScopeService::canAccessDepartment($user, $earlyWarning->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang menyelesaikan peringatan unit lain.');
        }

        $earlyWarning->update([
            'lifecycle_state' => 'RESOLVED',
            'status' => 'RESOLVED',
            'resolved_at' => now(),
        ]);

        AuditLogService::log('RESOLVE_EWS', EarlyWarning::class, $earlyWarning->id, null, ['rule_code' => $earlyWarning->rule_code]);

        return redirect()->back()->with('success', "Peringatan {$earlyWarning->rule_code} berhasil diselesaikan (Resolved).");
    }

    public function reevaluate(): RedirectResponse
    {
        $count = $this->ruleEngineService->evaluateAllEws();

        return redirect()->back()->with('success', "Pemindaian EWS selesai. {$count} aturan terdeteksi dan diperbarui.");
    }
}
