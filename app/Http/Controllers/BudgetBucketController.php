<?php

namespace App\Http\Controllers;

use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetBucket;
use App\Models\BudgetComponent;
use App\Models\BudgetKro;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Services\AuditLogService;
use App\Services\BudgetCalculationService;
use App\Services\BudgetService;
use App\Services\ScopeService;
use Illuminate\Http\JsonResponse;
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
        $user = $request->user();
        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear', 'budgetVersion']);

        ScopeService::applyDepartmentScope($query, $user, $request->department_id);

        if ($request->filled('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->fiscal_year_id);
        }

        if ($request->filled('funding_source_id')) {
            $query->where('funding_source_id', $request->funding_source_id);
        }

        if ($request->filled('budget_version_id')) {
            $query->where('budget_version_id', $request->budget_version_id);
        }

        if ($request->filled('account_code')) {
            $query->where('account_code', $request->account_code);
        }

        if ($request->filled('program_code')) {
            $query->where('subcomponent_full_code', 'like', "%{$request->program_code}%");
        }

        if ($request->filled('activity_code')) {
            $query->where('subcomponent_full_code', 'like', "%{$request->activity_code}%");
        }

        if ($request->filled('kro_code')) {
            $query->where('subcomponent_full_code', 'like', "%{$request->kro_code}%");
        }

        if ($request->filled('ro_code')) {
            $query->where('subcomponent_full_code', 'like', "%{$request->ro_code}%");
        }

        if ($request->filled('component_code')) {
            $query->where('subcomponent_full_code', 'like', "%{$request->component_code}%");
        }

        if ($request->filled('subcomponent_code')) {
            $query->where('subcomponent_full_code', 'like', "%{$request->subcomponent_code}%");
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhere('budget_bucket_name', 'like', "%{$search}%")
                    ->orWhere('subcomponent_name', 'like', "%{$search}%")
                    ->orWhere('subcomponent_full_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $buckets = $query->orderBy('department_id')
            ->orderBy('account_code')
            ->paginate(15)
            ->withQueryString();

        // Transform buckets with relational hierarchy details and warnings
        $buckets->getCollection()->transform(function ($b) {
            $fullCode = $b->subcomponent_full_code ?? '';
            $parts = $fullCode ? explode('.', $fullCode) : [];

            // Example pattern: 023.17.WA.4257.EBA.994.001.AA or 139.03.DK.7730.DBA.001.051.AA
            $progCode = $parts[2] ?? 'WA';
            $actCode = $parts[3] ?? '4257';
            $kroCode = $parts[4] ?? 'EBA';
            $roCode = $parts[5] ?? '994';
            $compCode = $parts[6] ?? '001';
            $subcompCode = $parts[7] ?? 'AA';

            $progName = match ($progCode) {
                'WA', '139.03.WA', '023.17.WA' => 'Program Dukungan Manajemen',
                'DK', '139.03.DK', '023.17.DK' => 'Program Pendidikan Tinggi',
                default => 'Program Pendidikan Tinggi'
            };

            $actName = match ($actCode) {
                '4257' => 'Dukungan Manajemen & Pelaksanaan Tugas Teknis Lainnya',
                '7734' => 'Dukungan Manajemen Ditjen Pendidikan Tinggi',
                '7730' => 'Peningkatan Kualitas & Kapasitas PT Akademik',
                '7729' => 'Penyediaan Dana BOPTN Akademik',
                '4470' => 'Penyediaan Dana BOPTN',
                '4471' => 'Peningkatan Kualitas dan Kapasitas PT',
                default => 'Dukungan Operasional & Akademik'
            };

            $kroFullCode = in_array($kroCode, ['DBA', '7730.DBA']) ? '7730.DBA' : (in_array($kroCode, ['EBA', '7734.EBA']) ? '7734.EBA' : (in_array($kroCode, ['BEI', '7729.BEI']) ? '7729.BEI' : $kroCode));
            $kroName = match ($kroCode) {
                'DBA', '7730.DBA' => 'Pendidikan Tinggi',
                'EBA', '7734.EBA' => 'Layanan Dukungan Manajemen Internal',
                'BEI', '7729.BEI' => 'Bantuan Lembaga',
                'CAA' => 'Sarana Bidang Pendidikan',
                default => 'Layanan Operasional Perguruan Tinggi'
            };

            $allocated = (float) $b->allocated_budget;
            $realized = (float) $b->realized_budget;
            $reserved = (float) $b->reserved_budget;
            $available = (float) $b->available_balance;
            $serapan = $allocated > 0 ? round(($realized / $allocated) * 100, 1) : 0;
            $utilization = $allocated > 0 ? round((($realized + $reserved) / $allocated) * 100, 1) : 0;

            // Warning state calculation
            $warningState = 'AMAN';
            $warningLabel = 'Normal';
            $warningBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200';

            if ($available <= 15000000 || $utilization >= 90) {
                $warningState = 'KRITIS';
                $warningLabel = 'Saldo Menipis';
                $warningBadge = 'bg-rose-50 text-rose-700 border-rose-200';
            } elseif ($available < 50000000 || $utilization >= 80) {
                $warningState = 'PERHATIAN';
                $warningLabel = 'Perlu Pantauan';
                $warningBadge = 'bg-amber-50 text-amber-700 border-amber-200';
            }

            $b->hierarchy = [
                'program_code' => $progCode,
                'program_label' => "{$progCode} — {$progName}",
                'activity_code' => $actCode,
                'activity_label' => "{$actCode} — {$actName}",
                'kro_code' => $kroFullCode,
                'kro_label' => "{$kroFullCode} — {$kroName}",
                'ro_code' => $roCode,
                'ro_label' => "{$roCode} — Layanan Perkantoran",
                'component_code' => $compCode,
                'component_label' => "{$compCode} — Operasional Kantor",
                'subcomponent_code' => $subcompCode,
                'subcomponent_label' => "{$subcompCode} — ".($b->subcomponent_name ?? 'Operasional Jurusan'),
                'account_label' => "{$b->account_code} — {$b->account_name}",
            ];

            $b->serapan_rate = $serapan;
            $b->utilization_rate = $utilization;
            $b->warning_state = $warningState;
            $b->warning_label = $warningLabel;
            $b->warning_badge = $warningBadge;

            return $b;
        });

        $departments = ScopeService::getSelectableDepartments($user);
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $fundingSources = FundingSource::all();
        $budgetVersions = BudgetVersion::orderBy('revision_no')->get();
        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first() ?? $fiscalYears->first();

        // Master lists for cascading/quick filters
        $programs = BudgetProgram::select('code', 'name')->distinct()->get();
        $activities = BudgetActivity::select('code', 'name')->distinct()->get();
        $kros = BudgetKro::select('code', 'name')->distinct()->get();
        $ros = BudgetRo::select('code', 'name')->distinct()->get();
        $components = BudgetComponent::select('code', 'name')->distinct()->get();
        $subcomponents = BudgetSubcomponent::select('code', 'name')->distinct()->get();
        $accounts = BudgetAccount::select('code', 'name')->distinct()->get();

        return Inertia::render('Budgets/Index', [
            'buckets' => $buckets,
            'departments' => $departments,
            'fiscalYears' => $fiscalYears,
            'fundingSources' => $fundingSources,
            'budgetVersions' => $budgetVersions,
            'activeFiscalYear' => $activeFiscalYear,
            'activeVersion' => $activeVersion,
            'programs' => $programs,
            'activities' => $activities,
            'kros' => $kros,
            'ros' => $ros,
            'components' => $components,
            'subcomponents' => $subcomponents,
            'accounts' => $accounts,
            'filters' => $request->only([
                'search',
                'department_id',
                'fiscal_year_id',
                'funding_source_id',
                'budget_version_id',
                'program_code',
                'activity_code',
                'kro_code',
                'ro_code',
                'component_code',
                'subcomponent_code',
                'account_code',
            ]),
        ]);
    }

    /**
     * API Quick Search Pos Anggaran for PTK & Transaction Input.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear', 'budgetVersion']);

        ScopeService::applyDepartmentScope($query, $user, $request->department_id);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('account_code', 'like', "%{$q}%")
                    ->orWhere('account_name', 'like', "%{$q}%")
                    ->orWhere('budget_bucket_name', 'like', "%{$q}%")
                    ->orWhere('subcomponent_name', 'like', "%{$q}%")
                    ->orWhere('subcomponent_full_code', 'like', "%{$q}%");
            });
        }

        $buckets = $query->orderBy('account_code')->limit(20)->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'account_code' => $b->account_code,
                'account_name' => $b->account_name,
                'budget_bucket_name' => $b->budget_bucket_name,
                'subcomponent_full_code' => $b->subcomponent_full_code,
                'subcomponent_name' => $b->subcomponent_name,
                'allocated_budget' => (float) $b->allocated_budget,
                'reserved_budget' => (float) $b->reserved_budget,
                'realized_budget' => (float) $b->realized_budget,
                'available_balance' => (float) $b->available_balance,
                'department' => [
                    'id' => $b->department_id,
                    'code' => $b->department?->code,
                    'name' => $b->department?->name,
                ],
                'funding_source' => [
                    'id' => $b->funding_source_id,
                    'code' => $b->fundingSource?->code,
                    'name' => $b->fundingSource?->name,
                ],
                'fiscal_year' => $b->fiscalYear?->year ?? 2026,
                'budget_version' => $b->budgetVersion?->revision_no ?? 'Rev 02',
                'context_badge' => sprintf(
                    'TA %s • %s • %s • %s',
                    $b->fiscalYear?->year ?? 2026,
                    $b->fundingSource?->code ?? 'RM',
                    $b->budgetVersion?->revision_no ?? 'Rev 02',
                    $b->department?->code ?? 'FT'
                ),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $buckets,
        ]);
    }

    /**
     * Dedicated API endpoint to search Budget Lines with hierarchy, scope, and financial snapshot.
     */
    public function searchBudgetLines(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = $request->query('q', $request->query('search'));
        $departmentId = $request->filled('department_id') ? (int) $request->department_id : null;
        $budgetVersionId = $request->filled('budget_version_id') ? (int) $request->budget_version_id : null;
        $limit = min(50, max(1, (int) $request->query('limit', 25)));

        $lines = BudgetCalculationService::searchBudgetLines(
            user: $user,
            search: $search,
            departmentId: $departmentId,
            budgetVersionId: $budgetVersionId,
            limit: $limit
        );

        return response()->json([
            'status' => 'success',
            'count' => count($lines),
            'data' => $lines,
        ]);
    }

    public function create(): Response
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $fundingSources = FundingSource::orderBy('name')->get();
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first() ?? $fiscalYears->first();
        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();

        return Inertia::render('Budgets/Create', [
            'departments' => $departments,
            'fundingSources' => $fundingSources,
            'fiscalYears' => $fiscalYears,
            'activeFiscalYear' => $activeFiscalYear,
            'activeVersion' => $activeVersion,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'budget_version_id' => 'nullable|exists:budget_versions,id',
            'department_id' => 'required|exists:departments,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'account_code' => 'required|string|max:30',
            'account_name' => 'required|string|max:255',
            'budget_bucket_name' => 'nullable|string|max:255',
            'subcomponent_full_code' => 'nullable|string|max:255',
            'subcomponent_name' => 'nullable|string|max:255',
            'initial_budget' => 'required|numeric|min:0',
        ]);

        $initialBudget = (float) $validated['initial_budget'];
        $activeVersionId = $validated['budget_version_id'] ?? BudgetVersion::where('status', 'ACTIVE')->first()?->id;

        $bucket = BudgetBucket::create([
            'fiscal_year_id' => $validated['fiscal_year_id'],
            'budget_version_id' => $activeVersionId,
            'department_id' => $validated['department_id'],
            'funding_source_id' => $validated['funding_source_id'],
            'account_code' => $validated['account_code'],
            'account_name' => $validated['account_name'],
            'budget_bucket_name' => $validated['budget_bucket_name'] ?? $validated['account_name'],
            'subcomponent_full_code' => $validated['subcomponent_full_code'] ?? null,
            'subcomponent_name' => $validated['subcomponent_name'] ?? null,
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
        $budgetBucket->load([
            'department',
            'fundingSource',
            'fiscalYear',
            'budgetVersion',
            'submissions.creator',
            'submissions.studyProgram',
            'revisions.approver',
            'earlyWarnings',
        ]);

        $fullCode = $budgetBucket->subcomponent_full_code ?? '';
        $parts = $fullCode ? explode('.', $fullCode) : [];

        $progCode = $parts[2] ?? 'WA';
        $actCode = $parts[3] ?? '4257';
        $kroCode = $parts[4] ?? 'EBA';
        $roCode = $parts[5] ?? '994';
        $compCode = $parts[6] ?? '001';
        $subcompCode = $parts[7] ?? 'AA';

        $progName = match ($progCode) {
            'WA', '139.03.WA', '023.17.WA' => 'Program Dukungan Manajemen',
            'DK', '139.03.DK', '023.17.DK' => 'Program Pendidikan Tinggi',
            default => 'Program Pendidikan Tinggi'
        };

        $actName = match ($actCode) {
            '4257' => 'Dukungan Manajemen & Pelaksanaan Tugas Teknis Lainnya Ditjen Dikti',
            '7734' => 'Dukungan Manajemen dan Pelaksanaan Tugas Teknis Lainnya Ditjen Pendidikan Tinggi',
            '7730' => 'Peningkatan Kualitas dan Kapasitas Perguruan Tinggi Akademik',
            '7729' => 'Penyediaan Dana Bantuan Operasional Perguruan Tinggi Negeri Akademik',
            '4470' => 'Penyediaan Dana Bantuan Operasional Perguruan Tinggi Negeri',
            '4471' => 'Peningkatan Kualitas dan Kapasitas Perguruan Tinggi',
            default => 'Dukungan Operasional dan Akademik'
        };

        $kroFullCode = in_array($kroCode, ['DBA', '7730.DBA']) ? '7730.DBA' : (in_array($kroCode, ['EBA', '7734.EBA']) ? '7734.EBA' : (in_array($kroCode, ['BEI', '7729.BEI']) ? '7729.BEI' : $kroCode));
        $kroName = match ($kroCode) {
            'DBA', '7730.DBA' => 'Pendidikan Tinggi',
            'EBA', '7734.EBA' => 'Layanan Dukungan Manajemen Internal',
            'BEI', '7729.BEI' => 'Bantuan Lembaga',
            'CAA' => 'Sarana Bidang Pendidikan',
            default => 'Layanan Operasional Perguruan Tinggi'
        };

        $hierarchy = [
            'program_code' => $progCode,
            'program_name' => $progName,
            'activity_code' => $actCode,
            'activity_name' => $actName,
            'kro_code' => $kroFullCode,
            'kro_name' => $kroName,
            'ro_code' => $roCode,
            'ro_name' => 'Layanan Perkantoran',
            'component_code' => $compCode,
            'component_name' => 'Operasional & Pemeliharaan Kantor',
            'subcomponent_code' => $subcompCode,
            'subcomponent_name' => $budgetBucket->subcomponent_name ?? 'Operasional Jurusan & Laboratorium',
            'account_code' => $budgetBucket->account_code,
            'account_name' => $budgetBucket->account_name,
            'subaccount_code' => $budgetBucket->account_code.'.001',
            'subaccount_name' => $budgetBucket->account_name.' Operasional Standar',
        ];

        $allocated = (float) $budgetBucket->allocated_budget;
        $initial = (float) $budgetBucket->initial_budget;
        $reserved = (float) $budgetBucket->reserved_budget;
        $realized = (float) $budgetBucket->realized_budget;
        $available = (float) $budgetBucket->available_balance;

        $financialSummary = [
            'initial_budget' => $initial,
            'revision_delta' => $allocated - $initial,
            'allocated_budget' => $allocated,
            'reserved_budget' => $reserved,
            'realized_budget' => $realized,
            'available_balance' => $available,
            'serapan_rate' => $allocated > 0 ? round(($realized / $allocated) * 100, 1) : 0,
            'utilization_rate' => $allocated > 0 ? round((($realized + $reserved) / $allocated) * 100, 1) : 0,
        ];

        // Sample Source Lines (Kertas Kerja DIPA breakdown)
        $sourceLines = [
            [
                'header' => 'Rincian Kebutuhan Operasional Utama',
                'description' => $budgetBucket->budget_bucket_name ?? $budgetBucket->account_name,
                'volume' => 1,
                'unit' => 'Tahun',
                'unit_price' => $initial > 0 ? $initial * 0.6 : 30000000,
                'total_price' => $initial > 0 ? $initial * 0.6 : 30000000,
            ],
            [
                'header' => 'Dukungan Pelaksanaan Praktikum & Kegiatan',
                'description' => 'Bahan habis pakai & perlengkapan pendukung',
                'volume' => 2,
                'unit' => 'Paket',
                'unit_price' => $initial > 0 ? $initial * 0.2 : 10000000,
                'total_price' => $initial > 0 ? $initial * 0.4 : 20000000,
            ],
        ];

        return Inertia::render('Budgets/Show', [
            'budgetBucket' => $budgetBucket,
            'hierarchy' => $hierarchy,
            'financialSummary' => $financialSummary,
            'sourceLines' => $sourceLines,
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
