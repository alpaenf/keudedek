<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\BudgetSubcomponent;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\PerformanceIndicator;
use App\Models\Submission;
use App\Models\SubmissionDocument;
use App\Models\SubmissionItem;
use App\Models\SubmissionStatusHistory;
use App\Models\TransactionType;
use App\Services\AuditLogService;
use App\Services\ScopeService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function __construct(
        protected SubmissionService $submissionService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Submission::with(['department', 'budgetBucket', 'creator', 'transactionType']);

        ScopeService::applyDepartmentScope($query, $user, $request->department_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('submission_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();
        $departments = ScopeService::getSelectableDepartments($user);

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'departments' => $departments,
            'filters' => $request->only(['status', 'department_id', 'search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $departments = ScopeService::getSelectableDepartments($user);

        $queryBuckets = BudgetBucket::with(['department', 'fundingSource']);
        ScopeService::applyDepartmentScope($queryBuckets, $user);
        $buckets = $queryBuckets->get();

        $transactionTypes = TransactionType::where('is_active', true)->get();
        $documentTypes = DocumentType::where('is_active', true)->get();
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first()?->year ?? 2026;
        $performanceIndicators = PerformanceIndicator::all();
        $subcomponents = BudgetSubcomponent::all();

        return Inertia::render('Submissions/Create', [
            'departments' => $departments,
            'buckets' => $buckets,
            'transactionTypes' => $transactionTypes,
            'documentTypes' => $documentTypes,
            'activeFiscalYear' => $activeFiscalYear,
            'performanceIndicators' => $performanceIndicators,
            'subcomponents' => $subcomponents,
            'userDepartmentId' => $user?->department_id,
            'userRole' => $user?->role === 'WD' ? 'WAKIL_DEKAN' : $user?->role,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Enforce department scope
        $departmentId = $user && in_array($user->role, ['PTK', 'KAJUR'])
            ? $user->department_id
            : $request->department_id;

        $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'budget_bucket_id' => 'required|exists:budget_buckets,id',
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
            'amount' => 'required|numeric|min:1000',
            'reference_no' => 'nullable|string|max:100',
            'beneficiary_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'background_narrative' => 'nullable|string',
            'objective_narrative' => 'nullable|string',
            'target_output' => 'nullable|string',
            'performance_indicator_code' => 'nullable|string',
            'performance_indicator_name' => 'nullable|string',
            'subcomponent_full_code' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Fase 5: Preventative Real-Time Rupiah Murni (RM) Commitment Lock
        $bucket = BudgetBucket::findOrFail($request->budget_bucket_id);
        if ($request->submit_action !== 'DRAFT' && $request->amount > $bucket->available_balance) {
            return redirect()->back()->withErrors([
                'amount' => 'Jumlah usulan (Rp '.number_format($request->amount, 0, ',', '.').') melebihi sisa saldo komitmen (Rp '.number_format($bucket->available_balance, 0, ',', '.').'). Usulan ditolak oleh sistem penguncian komitmen saldo murni.',
            ]);
        }

        $fiscalYear = FiscalYear::where('status', 'ACTIVE')->firstOrFail();
        $submissionNumber = 'SUB/'.date('Y/m').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $status = $request->submit_action === 'DRAFT' ? 'DRAFT' : 'SUBMITTED';

        $submission = DB::transaction(function () use ($request, $user, $departmentId, $fiscalYear, $submissionNumber, $status, $bucket) {
            $sub = Submission::create([
                'submission_number' => $submissionNumber,
                'reference_no' => $request->reference_no,
                'title' => $request->title,
                'department_id' => $departmentId,
                'fiscal_year_id' => $fiscalYear->id,
                'transaction_type_id' => $request->transaction_type_id,
                'budget_bucket_id' => $request->budget_bucket_id,
                'amount' => $request->amount,
                'beneficiary_name' => $request->beneficiary_name,
                'status' => $status,
                'created_by' => $user?->id ?? 1,
                'notes' => $request->notes,
                'background_narrative' => $request->background_narrative,
                'objective_narrative' => $request->objective_narrative,
                'target_output' => $request->target_output,
                'performance_indicator_code' => $request->performance_indicator_code,
                'performance_indicator_name' => $request->performance_indicator_name,
                'subcomponent_full_code' => $request->subcomponent_full_code,
            ]);

            // If submitted directly, reserve commitment balance immediately
            if ($status === 'SUBMITTED') {
                $bucket->decrement('available_balance', $request->amount);
                $bucket->increment('reserved_budget', $request->amount);
            }

            // Save items
            foreach ($request->items as $item) {
                SubmissionItem::create([
                    'submission_id' => $sub->id,
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            // Save multi-file attachments
            if ($request->hasFile('documents')) {
                $blockedExtensions = ['php', 'exe', 'bat', 'cmd', 'sh', 'bin', 'js', 'vbs'];
                foreach ($request->file('documents') as $docTypeId => $file) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (in_array($ext, $blockedExtensions)) {
                        continue;
                    }

                    $storedPath = $file->store('submission_docs', 'local');

                    SubmissionDocument::create([
                        'submission_id' => $sub->id,
                        'document_type_id' => is_numeric($docTypeId) ? (int) $docTypeId : null,
                        'original_filename' => $file->getClientOriginalName(),
                        'stored_filename' => $storedPath,
                        'mime_type' => $file->getClientMimeType() ?? 'application/octet-stream',
                        'extension' => $ext,
                        'file_size' => $file->getSize(),
                        'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
                        'uploaded_by' => $user?->id ?? 1,
                    ]);
                }
            }

            // Initial status history
            SubmissionStatusHistory::create([
                'submission_id' => $sub->id,
                'from_status' => null,
                'to_status' => $status,
                'actor_id' => $user?->id ?? 1,
                'role' => $user?->role ?? 'PTK',
                'notes' => $status === 'DRAFT' ? 'Draft pengajuan berhasil dibuat.' : 'Pengajuan diajukan ke tahap verifikasi dan komitmen saldo dikunci.',
            ]);

            AuditLogService::log(
                'CREATE_SUBMISSION',
                Submission::class,
                $sub->id,
                null,
                ['submission_number' => $sub->submission_number, 'amount' => $sub->amount, 'status' => $status]
            );

            return $sub;
        });

        return redirect()->route('submissions.show', $submission)
            ->with('success', "Pengajuan {$submission->submission_number} berhasil disimpan dengan status {$status}.");
    }

    public function show(Submission $submission): Response
    {
        $submission->load([
            'department',
            'budgetBucket.fundingSource',
            'creator',
            'items',
            'documents.documentType',
            'approvals.user',
            'statusHistories.actor',
            'transactionType',
        ]);

        return Inertia::render('Submissions/Show', [
            'submission' => $submission,
        ]);
    }

    public function downloadDocument(SubmissionDocument $document)
    {
        $user = auth()->user();
        if (! ScopeService::canAccessDepartment($user, $document->submission->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang mengunduh dokumen unit lain.');
        }

        if (! Storage::disk('local')->exists($document->stored_filename)) {
            abort(404, 'Berkas dokumen tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('local')->download($document->stored_filename, $document->original_filename);
    }

    public function printDocument(Submission $submission): Response
    {
        $user = auth()->user();
        if (! ScopeService::canAccessDepartment($user, $submission->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang mencetak dokumen unit lain.');
        }

        $submission->load([
            'department',
            'budgetBucket.fundingSource',
            'creator',
            'items',
            'documents.documentType',
            'approvals.user',
            'transactionType',
        ]);

        $signoffApproval = $submission->approvals()->latest()->first();

        return Inertia::render('Submissions/Print', [
            'submission' => $submission,
            'signoffUser' => $signoffApproval?->user,
            'signoffDate' => $signoffApproval ? date('d F Y', strtotime($signoffApproval->created_at)) : null,
        ]);
    }
}
