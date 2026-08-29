<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\EarlyWarning;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedDepartmentId = $request->query('department_id');

        $queryBuckets = BudgetBucket::with(['department', 'fundingSource']);
        $querySubmissions = Submission::with(['department', 'budgetBucket', 'creator']);
        $queryWarnings = EarlyWarning::with(['department', 'budgetBucket']);

        if ($selectedDepartmentId) {
            $queryBuckets->where('department_id', $selectedDepartmentId);
            $querySubmissions->where('department_id', $selectedDepartmentId);
            $queryWarnings->where('department_id', $selectedDepartmentId);
        }

        $totalAllocated = (float) $queryBuckets->clone()->sum('allocated_budget');
        $totalReserved = (float) $queryBuckets->clone()->sum('reserved_budget');
        $totalRealized = (float) $queryBuckets->clone()->sum('realized_budget');
        $totalAvailable = (float) $queryBuckets->clone()->sum('available_balance');
        $absorptionRate = $totalAllocated > 0 ? (($totalRealized + $totalReserved) / $totalAllocated) * 100 : 0;

        $activeWarningsCount = $queryWarnings->clone()->where('status', 'ACTIVE')->count();

        $recentSubmissions = $querySubmissions->clone()->latest()->take(5)->get();
        $activeWarnings = $queryWarnings->clone()->where('status', 'ACTIVE')->latest()->take(5)->get();
        $departments = Department::where('is_active', true)->get();
        $departmentSummaries = Department::with('budgetBuckets')->whereNotNull('parent_id')->get();

        return Inertia::render('Dashboard', [
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
            'absorptionRate' => $absorptionRate,
            'activeWarningsCount' => $activeWarningsCount,
            'recentSubmissions' => $recentSubmissions,
            'activeWarnings' => $activeWarnings,
            'departments' => $departments,
            'selectedDepartmentId' => $selectedDepartmentId,
            'departmentSummaries' => $departmentSummaries,
        ]);
    }
}
