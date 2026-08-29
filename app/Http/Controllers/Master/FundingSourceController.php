<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\FundingSource;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FundingSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FundingSource::withCount('budgetBuckets');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $fundingSources = $query->orderBy('code')->paginate(15)->withQueryString();

        return Inertia::render('Master/FundingSources/Index', [
            'fundingSources' => $fundingSources,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:funding_sources,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $fundingSource = FundingSource::create($validated);

        AuditLogService::log(
            'CREATE_FUNDING_SOURCE',
            FundingSource::class,
            $fundingSource->id,
            null,
            $fundingSource->toArray()
        );

        return redirect()->route('master.funding-sources.index')
            ->with('success', "Sumber dana {$fundingSource->name} ({$fundingSource->code}) berhasil ditambahkan.");
    }

    public function update(Request $request, FundingSource $fundingSource): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:funding_sources,code,'.$fundingSource->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $oldValues = $fundingSource->toArray();
        $fundingSource->update($validated);

        AuditLogService::log(
            'UPDATE_FUNDING_SOURCE',
            FundingSource::class,
            $fundingSource->id,
            $oldValues,
            $fundingSource->toArray()
        );

        return redirect()->route('master.funding-sources.index')
            ->with('success', "Sumber dana {$fundingSource->name} berhasil diperbarui.");
    }

    public function destroy(FundingSource $fundingSource): RedirectResponse
    {
        if ($fundingSource->budgetBuckets()->exists()) {
            return redirect()->route('master.funding-sources.index')
                ->with('error', "Sumber dana {$fundingSource->name} tidak dapat dihapus karena masih digunakan pada pos pagu anggaran.");
        }

        $oldValues = $fundingSource->toArray();
        $name = $fundingSource->name;
        $fundingSource->delete();

        AuditLogService::log(
            'DELETE_FUNDING_SOURCE',
            FundingSource::class,
            $fundingSource->id,
            $oldValues,
            null
        );

        return redirect()->route('master.funding-sources.index')
            ->with('success', "Sumber dana {$name} berhasil dihapus.");
    }
}
