<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FiscalYearController extends Controller
{
    public function index(Request $request): Response
    {
        $fiscalYears = FiscalYear::withCount(['budgetBuckets', 'submissions'])
            ->orderBy('year', 'desc')
            ->paginate(15);

        return Inertia::render('Master/FiscalYears/Index', [
            'fiscalYears' => $fiscalYears,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|string|max:4|unique:fiscal_years,year',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:ACTIVE,CLOSED,PLANNING',
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

        return redirect()->route('master.fiscal-years.index')
            ->with('success', "Tahun anggaran {$fiscalYear->year} berhasil ditambahkan.");
    }

    public function update(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|string|max:4|unique:fiscal_years,year,'.$fiscalYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:ACTIVE,CLOSED,PLANNING',
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

        return redirect()->route('master.fiscal-years.index')
            ->with('success', "Tahun anggaran {$fiscalYear->year} berhasil diperbarui.");
    }

    public function setActive(FiscalYear $fiscalYear): RedirectResponse
    {
        FiscalYear::where('status', 'ACTIVE')->update(['status' => 'CLOSED']);
        $fiscalYear->status = 'ACTIVE';
        $fiscalYear->save();

        AuditLogService::log(
            'SET_ACTIVE_FISCAL_YEAR',
            FiscalYear::class,
            $fiscalYear->id,
            null,
            ['status' => 'ACTIVE']
        );

        return redirect()->route('master.fiscal-years.index')
            ->with('success', "Tahun anggaran {$fiscalYear->year} berhasil diaktifkan.");
    }
}
