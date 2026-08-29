<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        if ($selectedDepartmentId) {
            $query->where('department_id', $selectedDepartmentId);
        }

        $buckets = $query->orderBy('account_code')->get();
        $departments = Department::where('is_active', true)->get();

        $totalAllocated = (float) $buckets->sum('allocated_budget');
        $totalReserved = (float) $buckets->sum('reserved_budget');
        $totalRealized = (float) $buckets->sum('realized_budget');
        $totalAvailable = (float) $buckets->sum('available_balance');

        return Inertia::render('Reports/Index', [
            'buckets' => $buckets,
            'departments' => $departments,
            'selectedDepartmentId' => $selectedDepartmentId,
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
        ]);
    }
}
