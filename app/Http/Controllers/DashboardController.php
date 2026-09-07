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

        $payload = DashboardService::getPayload($user, $selectedDepartmentId);

        return Inertia::render('Dashboard', $payload);
    }
}
