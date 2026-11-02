<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $selectedDepartmentId = $request->query('department_id');
        $selectedFundingSourceId = $request->query('funding_source_id');
        $selectedFiscalYear = $request->query('fiscal_year');

        $payload = DashboardService::getPayload(
            $user,
            $selectedDepartmentId,
            $selectedFundingSourceId,
            $selectedFiscalYear
        );

        return Inertia::render('Dashboard', $payload);
    }
}
