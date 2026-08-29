<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Submission;
use App\Models\SubmissionItem;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function __construct(
        protected SubmissionService $submissionService
    ) {}

    public function index(Request $request): Response
    {
        $query = Submission::with(['department', 'budgetBucket', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('submission_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->get();

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'departments' => $departments,
            'filters' => $request->only(['status', 'department_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $departments = Department::whereNotNull('parent_id')->get();
        $buckets = BudgetBucket::with(['department', 'fundingSource'])->get();

        return Inertia::render('Submissions/Create', [
            'departments' => $departments,
            'buckets' => $buckets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'budget_bucket_id' => 'required|exists:budget_buckets,id',
            'amount' => 'required|numeric|min:10000',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:1000',
        ]);

        $fiscalYear = FiscalYear::where('status', 'ACTIVE')->firstOrFail();
        $submissionNumber = 'SUB/'.date('Y/m').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        $submission = Submission::create([
            'submission_number' => $submissionNumber,
            'title' => $request->title,
            'department_id' => $request->department_id,
            'fiscal_year_id' => $fiscalYear->id,
            'budget_bucket_id' => $request->budget_bucket_id,
            'amount' => $request->amount,
            'status' => 'DRAFT',
            'created_by' => auth()->id() ?? 1,
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $item) {
            SubmissionItem::create([
                'submission_id' => $submission->id,
                'item_name' => $item['item_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Draft pengajuan berhasil dibuat.');
    }

    public function show(Submission $submission): Response
    {
        $submission->load(['department', 'budgetBucket.fundingSource', 'creator', 'items']);

        return Inertia::render('Submissions/Show', [
            'submission' => $submission,
        ]);
    }

    public function updateStatus(Request $request, Submission $submission): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string|in:DRAFT,SUBMITTED,REVIEW,RETURNED,APPROVED,RESERVED,PROCESSING,COMPLETED,REJECTED',
            'notes' => 'nullable|string',
        ]);

        $success = $this->submissionService->transitionStatus($submission, $request->status, $request->notes);

        if (! $success) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui status: Nominal pengajuan melebihi saldo ketersediaan anggaran (Overbudget Block).');
        }

        return redirect()->route('submissions.show', $submission)
            ->with('success', "Status pengajuan berhasil diubah menjadi {$request->status}.");
    }
}
