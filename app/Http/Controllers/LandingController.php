<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FiscalYear;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        $activeYear = (string) (FiscalYear::where('status', 'ACTIVE')->first()?->year ?? '2026');
        $departmentsCount = Department::whereNotNull('parent_id')->count();

        return Inertia::render('Landing', [
            'fiscalYear' => $activeYear,
            'departmentsCount' => $departmentsCount,
            'user' => auth()->user() ? auth()->user()->load('department') : null,
        ]);
    }
}
