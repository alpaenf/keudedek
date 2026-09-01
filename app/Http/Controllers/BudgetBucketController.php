<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Services\AuditLogService;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetBucketController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    public function index(Request $request): Response
    {
        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $buckets = $query->orderBy('account_code')->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->get();

        return Inertia::render('Budgets/Index', [
            'buckets' => $buckets,
            'departments' => $departments,
            'filters' => $request->only(['search', 'department_id']),
        ]);
    }

    public function create(): Response
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $fundingSources = FundingSource::orderBy('name')->get();
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first() ?? $fiscalYears->first();

        return Inertia::render('Budgets/Create', [
            'departments' => $departments,
            'fundingSources' => $fundingSources,
            'fiscalYears' => $fiscalYears,
            'activeFiscalYear' => $activeFiscalYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'department_id' => 'required|exists:departments,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'account_code' => 'required|string|max:30',
            'account_name' => 'required|string|max:255',
            'initial_budget' => 'required|numeric|min:0',
        ]);

        $initialBudget = (float) $validated['initial_budget'];

        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $validated['fiscal_year_id'],
            'department_id' => $validated['department_id'],
            'funding_source_id' => $validated['funding_source_id'],
            'account_code' => $validated['account_code'],
            'account_name' => $validated['account_name'],
            'initial_budget' => $initialBudget,
            'allocated_budget' => $initialBudget,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => $initialBudget,
        ]);

        AuditLogService::log(
            'CREATE_BUDGET_BUCKET',
            BudgetBucket::class,
            $bucket->id,
            null,
            $bucket->toArray()
        );

        return redirect()->route('budgets.show', $bucket)
            ->with('success', "Pos Pagu Anggaran {$bucket->account_code} - {$bucket->account_name} berhasil dibuat.");
    }

    public function show(BudgetBucket $budgetBucket): Response
    {
        $budgetBucket->load(['department', 'fundingSource', 'fiscalYear', 'submissions.creator', 'revisions.approver', 'earlyWarnings']);

        return Inertia::render('Budgets/Show', [
            'budgetBucket' => $budgetBucket,
        ]);
    }

    public function revise(Request $request, BudgetBucket $budgetBucket): RedirectResponse
    {
        $request->validate([
            'revised_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->budgetService->applyRevision(
                $budgetBucket,
                (float) $request->revised_amount,
                $request->reason,
                auth()->id() ?? 1
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('budgets.show', $budgetBucket)
            ->with('success', 'Revisi pagu anggaran berhasil diterapkan dan dicatat di audit log.');
    }

    public function destroy(BudgetBucket $budgetBucket): RedirectResponse
    {
        if ($budgetBucket->submissions()->exists()) {
            return redirect()->route('budgets.index')
                ->with('error', "Pagu {$budgetBucket->account_code} tidak dapat dihapus karena sudah memiliki data pengajuan belanja.");
        }

        $oldValues = $budgetBucket->toArray();
        $code = $budgetBucket->account_code;
        $budgetBucket->revisions()->delete();
        $budgetBucket->earlyWarnings()->delete();
        $budgetBucket->delete();

        AuditLogService::log(
            'DELETE_BUDGET_BUCKET',
            BudgetBucket::class,
            $budgetBucket->id,
            $oldValues,
            null
        );

        return redirect()->route('budgets.index')
            ->with('success', "Pos Pagu Anggaran {$code} berhasil dihapus.");
    }
}
