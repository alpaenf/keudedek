<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $payload = DashboardService::getPayload($user, $selectedDepartmentId);

        return Inertia::render('Dashboard', $payload);
    }
}
