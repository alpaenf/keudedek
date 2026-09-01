<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Services\ScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $departments = ScopeService::getSelectableDepartments($user);

        $totalAllocated = (float) $buckets->sum('allocated_budget');
        $totalReserved = (float) $buckets->sum('reserved_budget');
        $totalRealized = (float) $buckets->sum('realized_budget');
        $totalAvailable = (float) $buckets->sum('available_balance');

        $serapanRate = $totalAllocated > 0 ? round(($totalRealized / $totalAllocated) * 100, 1) : 0;
        $utilizationRate = $totalAllocated > 0 ? round((($totalRealized + $totalReserved) / $totalAllocated) * 100, 1) : 0;
        $availableRate = $totalAllocated > 0 ? round(($totalAvailable / $totalAllocated) * 100, 1) : 0;

        return Inertia::render('Reports/Index', [
            'buckets' => $buckets,
            'departments' => $departments,
            'selectedDepartmentId' => $selectedDepartmentId,
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
            'serapanRate' => $serapanRate,
            'utilizationRate' => $utilizationRate,
            'availableRate' => $availableRate,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $selectedDepartment = $selectedDepartmentId ? Department::find($selectedDepartmentId) : null;

        $totalAllocated = (float) $buckets->sum('allocated_budget');
        $totalReserved = (float) $buckets->sum('reserved_budget');
        $totalRealized = (float) $buckets->sum('realized_budget');
        $totalAvailable = (float) $buckets->sum('available_balance');

        $serapanRate = $totalAllocated > 0 ? round(($totalRealized / $totalAllocated) * 100, 1) : 0;
        $utilizationRate = $totalAllocated > 0 ? round((($totalRealized + $totalReserved) / $totalAllocated) * 100, 1) : 0;

        $pdf = Pdf::loadView('exports.report-pdf', [
            'buckets' => $buckets,
            'selectedDepartmentName' => $selectedDepartment ? $selectedDepartment->name : null,
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
            'serapanRate' => $serapanRate,
            'utilizationRate' => $utilizationRate,
        ])->setPaper('a4', 'landscape');

        $filename = 'SIKARA_Laporan_Realisasi_Anggaran_'.date('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $filename = 'SIKARA_Laporan_Realisasi_Anggaran_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($buckets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['No', 'Kode Akun', 'Nama Pos Anggaran', 'Unit / Jurusan', 'Pagu Alokasi', 'Komitmen (Reserved)', 'Realisasi (Final)', 'Saldo Bebas (Available)', 'Serapan (%)', 'Utilization (%)']);

            foreach ($buckets as $index => $b) {
                $alloc = (float) $b->allocated_budget;
                $res = (float) $b->reserved_budget;
                $real = (float) $b->realized_budget;
                $avail = (float) $b->available_balance;
                $serapan = $alloc > 0 ? round(($real / $alloc) * 100, 1) : 0;
                $util = $alloc > 0 ? round((($real + $res) / $alloc) * 100, 1) : 0;

                fputcsv($file, [
                    $index + 1,
                    $b->account_code,
                    $b->budget_bucket_name ?: $b->account_name,
                    $b->department->name ?? '-',
                    $alloc,
                    $res,
                    $real,
                    $avail,
                    $serapan.'%',
                    $util.'%',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
