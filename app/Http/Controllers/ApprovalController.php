<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\ScopeService;
use App\Services\WorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! ScopeService::canApproveFinancial($user)) {
            abort(403, 'Akses Ditolak: Role Anda tidak memiliki kewenangan approval finansial.');
        }

        $query = Submission::with(['department', 'budgetBucket.fundingSource', 'creator', 'items', 'documents']);

        // Filter based on role in workflow queue
        if ($user->role === 'KAJUR') {
            $query->where('department_id', $user->department_id)
                ->whereIn('status', ['SUBMITTED']);
        } elseif ($user->role === 'PTU') {
            $query->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW', 'REVIEW']);
        } elseif ($user->role === 'KABAG') {
            $query->whereIn('status', ['APPROVED', 'UNDER_REVIEW']);
        } elseif (in_array($user->role, ['WAKIL_DEKAN', 'WD', 'DEKAN'])) {
            $query->whereIn('status', ['APPROVED', 'RESERVED', 'PROCESSING']);
        }

        if ($request->filled('department_id') && $user->hasFacultyScope()) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('submission_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(10)->withQueryString();
        $departments = ScopeService::getSelectableDepartments($user);

        return Inertia::render('Approvals/Index', [
            'submissions' => $submissions,
            'departments' => $departments,
            'filters' => $request->only(['department_id', 'search']),
        ]);
    }

    public function decide(Request $request, Submission $submission): RedirectResponse
    {
        $user = $request->user();

        if (! ScopeService::canApproveFinancial($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki otoritas persetujuan.');
        }

        if (! ScopeService::canAccessDepartment($user, $submission->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang menyetujui pengajuan di luar jurusan Anda.');
        }

        $request->validate([
            'decision' => 'required|string|in:APPROVED,RETURNED,REJECTED',
            'comment' => 'nullable|string|max:1000',
            'password' => 'nullable|string',
        ]);

        if (in_array($request->decision, ['RETURNED', 'REJECTED']) && empty($request->comment)) {
            return redirect()->back()->withErrors([
                'comment' => 'Wajib menyertakan catatan/alasan untuk keputusan pengembalian atau penolakan berkas.',
            ]);
        }

        $result = $this->workflowService->processDecision(
            $submission,
            $user,
            $request->decision,
            $request->comment,
            $request->password,
            $request->ip(),
            $request->userAgent()
        );

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }
}
