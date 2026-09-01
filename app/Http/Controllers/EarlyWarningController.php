<?php

namespace App\Http\Controllers;

use App\Models\EarlyWarning;
use App\Services\AuditLogService;
use App\Services\RuleEngineService;
use App\Services\ScopeService;
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
        $query = EarlyWarning::with(['department', 'budgetBucket', 'acknowledger']);

        ScopeService::applyDepartmentScope($query, $user, $request->department_id);

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('lifecycle_state')) {
            $query->where('lifecycle_state', $request->lifecycle_state);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rule_code', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $warnings = $query->orderByRaw("FIELD(severity, 'CRITICAL', 'HIGH', 'WARNING', 'INFO')")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $departments = ScopeService::getSelectableDepartments($user);

        // Warning statistics
        $stats = [
            'total_open' => EarlyWarning::whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'critical_count' => EarlyWarning::where('severity', 'CRITICAL')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'high_count' => EarlyWarning::where('severity', 'HIGH')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
            'warning_count' => EarlyWarning::where('severity', 'WARNING')->whereIn('lifecycle_state', ['OPEN', 'ACKNOWLEDGED'])->count(),
        ];

        return Inertia::render('Warnings/Index', [
            'warnings' => $warnings,
            'departments' => $departments,
            'stats' => $stats,
            'filters' => $request->only(['severity', 'lifecycle_state', 'department_id', 'search']),
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
