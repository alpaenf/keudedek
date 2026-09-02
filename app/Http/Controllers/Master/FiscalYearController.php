<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\BudgetBucket;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FiscalYearController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Authorization: ADMIN (full access) & KABAG (view only)
        if ($user && ! $user->hasRole(['ADMIN', 'KABAG'])) {
            abort(403, 'Akses Ditolak: Modul Administrasi Tahun & Versi Pagu hanya dapat diakses oleh Administrator Sistem dan Kepala Bagian Tata Usaha.');
        }

        $canManage = $user && $user->hasRole(['ADMIN']);
        $activeTab = $request->query('tab', 'fiscal-years'); // fiscal-years | funding-sources | budget-versions

        // ==========================================
        // 1. SECTION 1: TAHUN ANGGARAN
        // ==========================================
        $fiscalYears = FiscalYear::with(['budgetBuckets'])
            ->withCount(['budgetBuckets', 'submissions'])
            ->orderBy('year', 'desc')
            ->get()
            ->map(function ($fy) {
                $activeRev = BudgetVersion::where('fiscal_year_id', $fy->id)
                    ->where('status', 'ACTIVE')
                    ->with('fundingSource')
                    ->first();

                $fy->active_version_label = $activeRev
                    ? "{$activeRev->fundingSource?->code} {$activeRev->revision_no}"
                    : 'Belum Ada Versi Aktif';

                $fy->can_delete = ($fy->budget_buckets_count === 0) && ($fy->submissions_count === 0);

                return $fy;
            });

        // ==========================================
        // 2. SECTION 2: SUMBER DANA
        // ==========================================
        $fundingSources = FundingSource::withCount(['budgetBuckets', 'budgetVersions'])
            ->orderBy('code')
            ->get()
            ->map(function ($fs) {
                $fs->can_delete = ($fs->budget_buckets_count === 0) && ($fs->budget_versions_count === 0) && ($fs->code !== 'RM');

                return $fs;
            });

        // ==========================================
        // 3. SECTION 3: BUDGET VERSIONS
        // ==========================================
        $selectedFyId = $request->query('fiscal_year_id', FiscalYear::where('status', 'ACTIVE')->first()?->id ?? FiscalYear::first()?->id);
        $selectedFundId = $request->query('funding_source_id', FundingSource::where('code', 'RM')->first()?->id ?? FundingSource::first()?->id);

        $versionQuery = BudgetVersion::with(['fiscalYear', 'fundingSource', 'creator', 'importHistory'])
            ->withCount('budgetBuckets');

        if ($selectedFyId) {
            $versionQuery->where('fiscal_year_id', $selectedFyId);
        }
        if ($selectedFundId) {
            $versionQuery->where('funding_source_id', $selectedFundId);
        }

        $budgetVersions = $versionQuery->orderBy('revision_no', 'desc')->get()->map(function ($bv) {
            // Revision Conflict Safety Check
            $totalAlloc = (float) $bv->budgetBuckets()->sum('allocated_budget');
            $currentCommitment = (float) BudgetBucket::where('fiscal_year_id', $bv->fiscal_year_id)
                ->where('funding_source_id', $bv->funding_source_id)
                ->selectRaw('SUM(reserved_budget + realized_budget) as total_used')
                ->value('total_used') ?? 0;

            $isConflict = ($bv->status !== 'ACTIVE') && ($totalAlloc > 0) && ($totalAlloc < $currentCommitment);

            $bv->total_allocated = $totalAlloc;
            $bv->current_commitments = $currentCommitment;
            $bv->is_conflict = $isConflict;
            $bv->conflict_message = $isConflict
                ? 'REVISION CONFLICT: Total alokasi pagu versi ini (Rp '.number_format($totalAlloc, 0, ',', '.').') lebih kecil dari belanja berjalan (Rp '.number_format($currentCommitment, 0, ',', '.').').'
                : null;

            $bv->can_delete = ($bv->status === 'DRAFT') && ($bv->budget_buckets_count === 0);

            return $bv;
        });

        // Smart Active Context
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first();
        $activeFundingSource = FundingSource::where('code', 'RM')->first();
        $activeBudgetVersion = BudgetVersion::where('status', 'ACTIVE')->with(['fiscalYear', 'fundingSource'])->first();

        return Inertia::render('Master/FiscalYears/Index', [
            'fiscalYears' => $fiscalYears,
            'fundingSources' => $fundingSources,
            'budgetVersions' => $budgetVersions,
            'activeFiscalYear' => $activeFiscalYear,
            'activeFundingSource' => $activeFundingSource,
            'activeBudgetVersion' => $activeBudgetVersion,
            'canManage' => $canManage,
            'activeTab' => $activeTab,
            'selectedFyId' => $selectedFyId ? (int) $selectedFyId : null,
            'selectedFundId' => $selectedFundId ? (int) $selectedFundId : null,
        ]);
    }

    // ==========================================
    // FISCAL YEAR ACTIONS
    // ==========================================

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|string|max:4|unique:fiscal_years,year',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:ACTIVE,CLOSED,PLANNING,INACTIVE',
        ]);

        if ($validated['status'] === 'ACTIVE') {
            FiscalYear::where('status', 'ACTIVE')->update(['status' => 'CLOSED']);
        }

        $fiscalYear = FiscalYear::create($validated);

        AuditLogService::log(
            'CREATE_FISCAL_YEAR',
            FiscalYear::class,
            $fiscalYear->id,
            null,
            $fiscalYear->toArray()
        );

        return redirect()->route('master.fiscal-years.index', ['tab' => 'fiscal-years'])
            ->with('success', "Tahun anggaran {$fiscalYear->year} berhasil ditambahkan.");
    }

    public function update(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|string|max:4|unique:fiscal_years,year,'.$fiscalYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:ACTIVE,CLOSED,PLANNING,INACTIVE',
        ]);

        if ($validated['status'] === 'ACTIVE') {
            FiscalYear::where('id', '!=', $fiscalYear->id)->where('status', 'ACTIVE')->update(['status' => 'CLOSED']);
        }

        $oldValues = $fiscalYear->toArray();
        $fiscalYear->update($validated);

        AuditLogService::log(
            'UPDATE_FISCAL_YEAR',
            FiscalYear::class,
            $fiscalYear->id,
            $oldValues,
            $fiscalYear->toArray()
        );

        return redirect()->route('master.fiscal-years.index', ['tab' => 'fiscal-years'])
            ->with('success', "Tahun anggaran {$fiscalYear->year} berhasil diperbarui.");
    }

    public function setActive(FiscalYear $fiscalYear): RedirectResponse
    {
        DB::transaction(function () use ($fiscalYear) {
            FiscalYear::where('status', 'ACTIVE')->update(['status' => 'CLOSED']);
            $fiscalYear->status = 'ACTIVE';
            $fiscalYear->save();

            AuditLogService::log(
                'SET_ACTIVE_FISCAL_YEAR',
                FiscalYear::class,
                $fiscalYear->id,
                null,
                ['status' => 'ACTIVE', 'year' => $fiscalYear->year]
            );
        });

        return redirect()->route('master.fiscal-years.index', ['tab' => 'fiscal-years'])
            ->with('success', "Tahun anggaran {$fiscalYear->year} berhasil ditetapkan sebagai Tahun Aktif Sistem.");
    }

    public function destroy(FiscalYear $fiscalYear): RedirectResponse
    {
        if ($fiscalYear->budgetBuckets()->exists() || $fiscalYear->submissions()->exists()) {
            return redirect()->route('master.fiscal-years.index', ['tab' => 'fiscal-years'])
                ->with('error', "Tahun Anggaran {$fiscalYear->year} tidak dapat dihapus karena memiliki relasi data pagu atau transaksi.");
        }

        $old = $fiscalYear->toArray();
        $fiscalYear->delete();

        AuditLogService::log('DELETE_FISCAL_YEAR', FiscalYear::class, null, $old, null);

        return redirect()->route('master.fiscal-years.index', ['tab' => 'fiscal-years'])
            ->with('success', "Tahun Anggaran {$fiscalYear->year} berhasil dihapus.");
    }

    // ==========================================
    // FUNDING SOURCES ACTIONS
    // ==========================================

    public function toggleFundingSourceActive(FundingSource $fundingSource): RedirectResponse
    {
        $fundingSource->is_active = ! $fundingSource->is_active;
        $fundingSource->status = $fundingSource->is_active ? 'ACTIVE' : 'INACTIVE';
        $fundingSource->save();

        AuditLogService::log(
            'TOGGLE_ACTIVE_FUNDING_SOURCE',
            FundingSource::class,
            $fundingSource->id,
            null,
            ['code' => $fundingSource->code, 'status' => $fundingSource->status]
        );

        return redirect()->route('master.fiscal-years.index', ['tab' => 'funding-sources'])
            ->with('success', "Status sumber dana {$fundingSource->code} diubah menjadi {$fundingSource->status}.");
    }

    // ==========================================
    // BUDGET VERSIONS ACTIONS
    // ==========================================

    public function storeBudgetVersion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'revision_no' => 'required|string|max:50',
            'version_label' => 'nullable|string|max:150',
            'effective_at' => 'nullable|date',
            'source_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:DRAFT,ACTIVE,ARCHIVED',
        ]);

        $validated['created_by'] = $request->user()?->id;
        $validated['status'] = $validated['status'] ?? 'DRAFT';

        $version = BudgetVersion::create($validated);

        AuditLogService::log(
            'CREATE_BUDGET_VERSION',
            BudgetVersion::class,
            $version->id,
            null,
            $version->toArray()
        );

        return redirect()->route('master.fiscal-years.index', [
            'tab' => 'budget-versions',
            'fiscal_year_id' => $version->fiscal_year_id,
            'funding_source_id' => $version->funding_source_id,
        ])->with('success', "Versi Pagu {$version->revision_no} berhasil ditambahkan.");
    }

    public function updateBudgetVersion(Request $request, BudgetVersion $budgetVersion): RedirectResponse
    {
        $validated = $request->validate([
            'version_label' => 'nullable|string|max:150',
            'effective_at' => 'nullable|date',
            'source_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $budgetVersion->toArray();
        $budgetVersion->update($validated);

        AuditLogService::log(
            'UPDATE_BUDGET_VERSION',
            BudgetVersion::class,
            $budgetVersion->id,
            $oldValues,
            $budgetVersion->toArray()
        );

        return redirect()->route('master.fiscal-years.index', [
            'tab' => 'budget-versions',
            'fiscal_year_id' => $budgetVersion->fiscal_year_id,
            'funding_source_id' => $budgetVersion->funding_source_id,
        ])->with('success', "Metadata Versi Pagu {$budgetVersion->revision_no} berhasil diperbarui.");
    }

    public function setActiveBudgetVersion(BudgetVersion $budgetVersion): RedirectResponse
    {
        DB::transaction(function () use ($budgetVersion) {
            // Set any currently ACTIVE version for this (fiscal_year_id, funding_source_id) to ARCHIVED
            BudgetVersion::where('fiscal_year_id', $budgetVersion->fiscal_year_id)
                ->where('funding_source_id', $budgetVersion->funding_source_id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'ARCHIVED']);

            // Set this version to ACTIVE
            $budgetVersion->status = 'ACTIVE';
            $budgetVersion->effective_at = $budgetVersion->effective_at ?: now();
            $budgetVersion->save();

            AuditLogService::log(
                'ACTIVATE_BUDGET_VERSION',
                BudgetVersion::class,
                $budgetVersion->id,
                null,
                [
                    'revision_no' => $budgetVersion->revision_no,
                    'fiscal_year_id' => $budgetVersion->fiscal_year_id,
                    'funding_source_id' => $budgetVersion->funding_source_id,
                    'status' => 'ACTIVE',
                ]
            );
        });

        return redirect()->route('master.fiscal-years.index', [
            'tab' => 'budget-versions',
            'fiscal_year_id' => $budgetVersion->fiscal_year_id,
            'funding_source_id' => $budgetVersion->funding_source_id,
        ])->with('success', "Versi Pagu {$budgetVersion->revision_no} ({$budgetVersion->fundingSource?->code}) berhasil diaktifkan secara transaksional.");
    }

    public function destroyBudgetVersion(BudgetVersion $budgetVersion): RedirectResponse
    {
        if ($budgetVersion->status === 'ACTIVE' || $budgetVersion->budgetBuckets()->exists()) {
            return redirect()->route('master.fiscal-years.index', ['tab' => 'budget-versions'])
                ->with('error', "Versi Pagu {$budgetVersion->revision_no} tidak dapat dihapus karena berstatus AKTIF atau memiliki alokasi pos pagu.");
        }

        $old = $budgetVersion->toArray();
        $fyId = $budgetVersion->fiscal_year_id;
        $fundId = $budgetVersion->funding_source_id;
        $budgetVersion->delete();

        AuditLogService::log('DELETE_BUDGET_VERSION', BudgetVersion::class, null, $old, null);

        return redirect()->route('master.fiscal-years.index', [
            'tab' => 'budget-versions',
            'fiscal_year_id' => $fyId,
            'funding_source_id' => $fundId,
        ])->with('success', "Versi Pagu {$budgetVersion->revision_no} berhasil dihapus.");
    }
}
