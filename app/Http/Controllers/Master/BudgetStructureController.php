<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetBucket;
use App\Models\BudgetComponent;
use App\Models\BudgetKro;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubaccount;
use App\Models\BudgetSubcomponent;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BudgetStructureController extends Controller
{
    /**
     * Maps tab name → model class + table name + parent column + year column.
     *
     * @var array<string, array<string, string>>
     */
    private array $tabMap = [
        'program' => [
            'model' => BudgetProgram::class,
            'table' => 'budget_programs',
            'parent_col' => null,
            'year_col' => 'fiscal_year',
        ],
        'activity' => [
            'model' => BudgetActivity::class,
            'table' => 'budget_activities',
            'parent_col' => 'parent_program_code',
            'year_col' => 'fiscal_year',
        ],
        'kro' => [
            'model' => BudgetKro::class,
            'table' => 'budget_kros',
            'parent_col' => 'parent_activity_code',
            'year_col' => 'fiscal_year',
        ],
        'ro' => [
            'model' => BudgetRo::class,
            'table' => 'budget_ros',
            'parent_col' => 'parent_kro_code',
            'year_col' => 'fiscal_year',
        ],
        'component' => [
            'model' => BudgetComponent::class,
            'table' => 'budget_components',
            'parent_col' => 'parent_ro_code',
            'year_col' => 'fiscal_year',
        ],
        'subcomponent' => [
            'model' => BudgetSubcomponent::class,
            'table' => 'budget_subcomponents',
            'parent_col' => 'parent_component_code',
            'year_col' => 'fiscal_year',
        ],
        'account' => [
            'model' => BudgetAccount::class,
            'table' => 'budget_accounts',
            'parent_col' => null,
            'year_col' => 'effective_year',
        ],
        'subaccount' => [
            'model' => BudgetSubaccount::class,
            'table' => 'budget_subaccounts',
            'parent_col' => 'parent_account_code',
            'year_col' => 'effective_year',
        ],
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasRole(['ADMIN', 'KABAG'])) {
            abort(403, 'Akses Ditolak: Master Struktur Anggaran hanya dapat diakses oleh Administrator Sistem dan Kepala Bagian Tata Usaha.');
        }

        $canManage = $user && $user->hasRole(['ADMIN']);
        $activeTab = $request->query('tab', 'program');

        // -----------------------------------------------
        // Filters
        // -----------------------------------------------
        $search = $request->query('search', '');
        $filterYear = $request->query('year', '');
        $filterSource = $request->query('source', '');
        $filterStatus = $request->query('status', '');

        // -----------------------------------------------
        // Build data for active tab (paginate 100)
        // -----------------------------------------------
        $tabConfig = $this->tabMap[$activeTab] ?? $this->tabMap['program'];
        $model = $tabConfig['model'];
        $table = $tabConfig['table'];
        $parentCol = $tabConfig['parent_col'];
        $yearCol = $tabConfig['year_col'];

        $query = $model::query()->orderBy($yearCol ?? 'id', 'desc')->orderBy('code');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($filterYear) {
            $query->where($yearCol, $filterYear);
        }

        if ($filterSource) {
            $query->where('source_type', $filterSource);
        }

        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        $rows = $query->paginate(100)->withQueryString();

        // Attach "used_by_budget_count" for each row
        $usedCounts = $this->resolveUsedCounts($activeTab, $rows->pluck('code')->toArray(), $filterYear);

        $mappedRows = $rows->through(function ($row) use ($usedCounts) {
            $count = $usedCounts[$row->code] ?? 0;
            $row->used_by_budget_count = $count;
            $row->can_delete = $count === 0;

            return $row;
        });

        // -----------------------------------------------
        // Summary counts per tab (for badge numbers)
        // -----------------------------------------------
        $tabCounts = [];
        foreach ($this->tabMap as $tab => $cfg) {
            $tabCounts[$tab] = DB::table($cfg['table'])->count();
        }

        // Available year options for filter
        $availableYears = $this->resolveAvailableYears($activeTab, $table, $yearCol);

        return Inertia::render('Master/BudgetStructure/Index', [
            'rows' => $mappedRows,
            'activeTab' => $activeTab,
            'canManage' => $canManage,
            'tabCounts' => $tabCounts,
            'availableYears' => $availableYears,
            'filters' => [
                'search' => $search,
                'year' => $filterYear,
                'source' => $filterSource,
                'status' => $filterStatus,
            ],
            'parentCol' => $parentCol,
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        if (! $request->user()->hasRole(['ADMIN'])) {
            abort(403);
        }

        if (! isset($this->tabMap[$type])) {
            abort(404, "Unknown budget structure type: {$type}");
        }

        $model = $this->tabMap[$type]['model'];
        $record = $model::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'source_type' => 'required|in:OFFICIAL_IMPORT,OFFICIAL_DOCUMENT,INTERNAL,NEEDS_VALIDATION',
            'status' => 'required|in:ACTIVE,INACTIVE,ARCHIVED',
        ]);

        $oldValues = $record->toArray();
        $record->update($validated);

        AuditLogService::log(
            'MASTER_UPDATE',
            $model,
            $record->id,
            $oldValues,
            $record->toArray()
        );

        return redirect()->route('master.budget-structure.index', [
            'tab' => $type,
        ])->with('success', "Master {$type} [{$record->code}] berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, string $type, int $id): RedirectResponse
    {
        if (! $request->user()->hasRole(['ADMIN'])) {
            abort(403);
        }

        if (! isset($this->tabMap[$type])) {
            abort(404);
        }

        $model = $this->tabMap[$type]['model'];
        $record = $model::findOrFail($id);

        $usedCount = $this->resolveUsedCounts($type, [$record->code], null)[$record->code] ?? 0;

        if ($usedCount > 0 && $record->status === 'ACTIVE') {
            return redirect()->route('master.budget-structure.index', ['tab' => $type])
                ->with('error', "Kode [{$record->code}] tidak dapat dinonaktifkan karena digunakan oleh {$usedCount} pos pagu.");
        }

        $oldStatus = $record->status;
        $record->status = match ($record->status) {
            'ACTIVE' => 'INACTIVE',
            'INACTIVE' => 'ACTIVE',
            'ARCHIVED' => 'INACTIVE',
            default => 'ACTIVE',
        };
        $record->save();

        AuditLogService::log(
            'MASTER_STATUS_CHANGE',
            $model,
            $record->id,
            ['status' => $oldStatus, 'code' => $record->code],
            ['status' => $record->status, 'code' => $record->code]
        );

        return redirect()->route('master.budget-structure.index', ['tab' => $type])
            ->with('success', "Status [{$record->code}] berhasil diubah menjadi {$record->status}.");
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    /**
     * Resolve used_by_budget_count for a list of codes.
     *
     * @param  array<string>  $codes
     * @return array<string, int>
     */
    private function resolveUsedCounts(string $type, array $codes, ?string $year): array
    {
        if (empty($codes)) {
            return [];
        }

        $result = [];
        foreach ($codes as $code) {
            $result[$code] = 0;
        }

        switch ($type) {
            case 'subcomponent':
                $counts = BudgetBucket::whereIn('subcomponent_full_code', $codes)
                    ->selectRaw('subcomponent_full_code as code, COUNT(*) as cnt')
                    ->groupBy('subcomponent_full_code')
                    ->pluck('cnt', 'code');
                foreach ($counts as $code => $cnt) {
                    $result[$code] = (int) $cnt;
                }
                break;

            case 'account':
                $counts = BudgetBucket::whereIn('account_code', $codes)
                    ->selectRaw('account_code as code, COUNT(*) as cnt')
                    ->groupBy('account_code')
                    ->pluck('cnt', 'code');
                foreach ($counts as $code => $cnt) {
                    $result[$code] = (int) $cnt;
                }
                break;

            case 'program':
                // Count distinct subcomponent rows whose full_code starts with program prefix
                foreach ($codes as $code) {
                    $result[$code] = (int) BudgetBucket::where('subcomponent_full_code', 'like', "{$code}%")->count();
                }
                break;

            case 'activity':
                foreach ($codes as $code) {
                    // Activity code is typically segment 3-4 of full_code
                    $result[$code] = (int) BudgetBucket::where('subcomponent_full_code', 'like', "%.{$code}.%")->count();
                }
                break;

            default:
                // kro, ro, component — not directly referenced, return 0
                break;
        }

        return $result;
    }

    /**
     * Get distinct available years from the table.
     *
     * @return array<string>
     */
    private function resolveAvailableYears(string $activeTab, string $table, ?string $yearCol): array
    {
        if (! $yearCol || in_array($activeTab, ['subaccount'])) {
            return [];
        }

        return DB::table($table)
            ->select($yearCol)
            ->distinct()
            ->orderBy($yearCol, 'desc')
            ->pluck($yearCol)
            ->filter()
            ->values()
            ->toArray();
    }
}
