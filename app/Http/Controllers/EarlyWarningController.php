<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\EarlyWarning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EarlyWarningController extends Controller
{
    public function index(Request $request): Response
    {
        $query = EarlyWarning::with(['department', 'budgetBucket', 'acknowledger']);

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $warnings = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->get();

        return Inertia::render('Warnings/Index', [
            'warnings' => $warnings,
            'departments' => $departments,
            'filters' => $request->only(['severity', 'status', 'department_id']),
        ]);
    }

    public function acknowledge(EarlyWarning $earlyWarning): RedirectResponse
    {
        $earlyWarning->update([
            'status' => 'ACKNOWLEDGED',
            'acknowledged_by' => auth()->id() ?? 1,
        ]);

        return redirect()->back()->with('success', 'Warning EWS telah dikonfirmasi (Acknowledged).');
    }
}
