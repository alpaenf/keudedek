<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundingSource;
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
        $query = Submission::with(['department', 'studyProgram', 'budgetBucket', 'creator', 'transactionType']);

        ScopeService::applyDepartmentScope($query, $user, $request->department_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('submission_number', 'like', "%{$search}%")
                    ->orWhere('evidence_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();
        $departments = ScopeService::getSelectableDepartments($user);
        $studyPrograms = ScopeService::getSelectableStudyPrograms($user);

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'departments' => $departments,
            'studyPrograms' => $studyPrograms,
            'canCreate' => ScopeService::canCreateTransaction($user),
            'filters' => $request->only(['status', 'department_id', 'search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        // Enforce authorization: KAJUR & KAPRODI are read-only
        if (! ScopeService::canCreateTransaction($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mencatat transaksi baru (Monitoring Read-Only).');
        }

        $departments = ScopeService::getSelectableDepartments($user);
        $studyPrograms = ScopeService::getSelectableStudyPrograms($user, $user?->department_id);

        $queryBuckets = BudgetBucket::with(['department', 'fundingSource', 'budgetVersion']);
        ScopeService::applyDepartmentScope($queryBuckets, $user);
        $rawBuckets = $queryBuckets->get();

        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first()?->year ?? 2026;
        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();
        $activeFundingSource = FundingSource::where('code', 'RM')->first() ?? FundingSource::first();

        $buckets = $rawBuckets->map(function ($b) use ($activeFiscalYear, $activeVersion, $activeFundingSource) {
            $deptCode = $b->department?->code ?? 'FT';
            $deptName = $b->department?->name ?? 'Fakultas Teknik';

            return [
                'id' => $b->id,
                'account_code' => $b->account_code,
                'account_name' => $b->account_name,
                'department_id' => $b->department_id,
                'department_code' => $deptCode,
                'department_name' => $deptName,
                'allocated_budget' => (float) $b->allocated_budget,
                'reserved_budget' => (float) $b->reserved_budget,
                'realized_budget' => (float) $b->realized_budget,
                'available_balance' => (float) $b->available_balance,
                'serapan_rate' => $b->allocated_budget > 0 ? round(($b->realized_budget / $b->allocated_budget) * 100, 1) : 0,

                // 7-Segment Hierarchy Master Data (Read-Only)
                'fiscal_year' => $b->fiscalYear?->year ?? $activeFiscalYear,
                'funding_source_code' => $b->fundingSource?->code ?? $activeFundingSource->code,
                'budget_version' => $b->budgetVersion?->revision_no ?? $activeVersion?->revision_no ?? 'Rev 02',
                'program_code' => 'WA',
                'program_name' => 'Program Dukungan Manajemen',
                'activity_code' => '4257',
                'activity_name' => 'Dukungan Manajemen & Pelaksanaan Tugas Teknis Lainnya Ditjen Dikti',
                'kro_code' => '7734.EBA',
                'kro_name' => 'Layanan Dukungan Manajemen Internal',
                'ro_code' => '994',
                'ro_name' => 'Layanan Perkantoran',
                'component_code' => '001',
                'component_name' => 'Operasional & Pemeliharaan Kantor',
                'subcomponent_code' => $b->subcomponent_code ?? 'AA',
                'subcomponent_name' => $b->subcomponent_name ?? "Operasional & Praktikum {$deptName}",
                'subaccount_code' => $b->account_code.'.001',
                'subaccount_name' => 'Alokasi Operasional Standar Unit',
            ];
        });

        $transactionTypes = TransactionType::where('is_active', true)->get();
        $documentTypes = DocumentType::where('is_active', true)->get();
        $performanceIndicators = PerformanceIndicator::all();
        $subcomponents = BudgetSubcomponent::all();

        return Inertia::render('Submissions/Create', [
            'departments' => $departments,
            'studyPrograms' => $studyPrograms,
            'buckets' => $buckets,
            'transactionTypes' => $transactionTypes,
            'documentTypes' => $documentTypes,
            'activeFiscalYear' => $activeFiscalYear,
            'activeVersion' => $activeVersion,
            'activeFundingSource' => $activeFundingSource,
            'performanceIndicators' => $performanceIndicators,
            'subcomponents' => $subcomponents,
            'userDepartmentId' => $user?->department_id,
            'userStudyProgramId' => $user?->study_program_id,
            'userRole' => $user?->role === 'WD' ? 'WAKIL_DEKAN' : $user?->role,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! ScopeService::canCreateTransaction($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mencatat transaksi baru.');
        }

        // Enforce department scope
        $departmentId = $user && $user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])
            ? $user->department_id
            : ($request->department_id ?? $user?->department_id);

        $request->validate([
            'budget_bucket_id' => 'required|exists:budget_buckets,id',
            'evidence_number' => 'nullable|string|max:100',
            'transaction_date' => 'required|date',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1000',
            'study_program_id' => 'nullable|exists:study_programs,id',
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
            'reference_no' => 'nullable|string|max:100',
            'beneficiary_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'submit_action' => 'required|in:DRAFT,PROCESSING,SUBMITTED,FINAL',
        ]);

        $fiscalYear = FiscalYear::where('status', 'ACTIVE')->firstOrFail();
        $submissionNumber = 'TRX/'.date('Y/m').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $status = in_array($request->submit_action, ['PROCESSING', 'SUBMITTED']) ? 'PROCESSING' : $request->submit_action;

        // Atomic DB Transaction with Pessimistic Locking (RBC-001 / Concurrency Safe)
        try {
            $submission = DB::transaction(function () use ($request, $user, $departmentId, $fiscalYear, $submissionNumber, $status) {
                $bucket = BudgetBucket::where('id', $request->budget_bucket_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // RBC-001: Overbudget Protection Rule Check
                if ($status !== 'DRAFT' && $request->amount > $bucket->available_balance) {
                    $shortfall = $request->amount - $bucket->available_balance;
                    throw new \InvalidArgumentException(
                        'Nominal transaksi (Rp '.number_format($request->amount, 0, ',', '.').') melebihi sisa saldo tersedia (Rp '.number_format($bucket->available_balance, 0, ',', '.').'). Kekurangan: Rp '.number_format($shortfall, 0, ',', '.').'. Transaksi diblokir oleh aturan RBC-001 (Overbudget Protection).'
                    );
                }

                $sub = Submission::create([
                    'submission_number' => $submissionNumber,
                    'evidence_number' => $request->evidence_number,
                    'transaction_date' => $request->transaction_date,
                    'reference_no' => $request->reference_no,
                    'title' => $request->title,
                    'department_id' => $departmentId,
                    'study_program_id' => $request->study_program_id ?? $user?->study_program_id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'transaction_type_id' => $request->transaction_type_id ?? 1,
                    'budget_bucket_id' => $request->budget_bucket_id,
                    'amount' => $request->amount,
                    'beneficiary_name' => $request->beneficiary_name,
                    'status' => $status,
                    'created_by' => $user?->id ?? 1,
                    'notes' => $request->notes,
                    'subcomponent_full_code' => $bucket->subcomponent_full_code,
                ]);

                // Update Balances atomically based on Status
                if ($status === 'PROCESSING') {
                    $bucket->decrement('available_balance', $request->amount);
                    $bucket->increment('reserved_budget', $request->amount);
                } elseif ($status === 'FINAL') {
                    $bucket->decrement('available_balance', $request->amount);
                    $bucket->increment('realized_budget', $request->amount);
                }

                // If items provided, save them
                if ($request->has('items') && is_array($request->items)) {
                    foreach ($request->items as $item) {
                        if (! empty($item['item_name'])) {
                            SubmissionItem::create([
                                'submission_id' => $sub->id,
                                'item_name' => $item['item_name'],
                                'quantity' => $item['quantity'] ?? 1,
                                'unit_price' => $item['unit_price'] ?? $request->amount,
                                'total_price' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? $request->amount),
                            ]);
                        }
                    }
                } else {
                    // Create default 1-line item matching transaction title
                    SubmissionItem::create([
                        'submission_id' => $sub->id,
                        'item_name' => $request->title,
                        'quantity' => 1,
                        'unit_price' => $request->amount,
                        'total_price' => $request->amount,
                    ]);
                }

                // Handle multi-file attachments safely
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

                // Status history
                SubmissionStatusHistory::create([
                    'submission_id' => $sub->id,
                    'from_status' => null,
                    'to_status' => $status,
                    'actor_id' => $user?->id ?? 1,
                    'role' => $user?->role ?? 'PTK',
                    'notes' => $status === 'DRAFT' ? 'Draft transaksi dicatat.' : 'Transaksi dicatat dan masuk ke tahap Dalam Proses.',
                ]);

                AuditLogService::log(
                    'CREATE_TRANSACTION',
                    Submission::class,
                    $sub->id,
                    null,
                    ['submission_number' => $sub->submission_number, 'amount' => $sub->amount, 'status' => $status]
                );

                return $sub;
            });
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors([
                'amount' => $e->getMessage(),
            ]);
        }

        return redirect()->route('submissions.show', $submission)
            ->with('success', "Transaksi {$submission->submission_number} berhasil dicatat dengan status {$status}.");
    }

    public function show(Submission $submission): Response
    {
        $submission->load([
            'department',
            'studyProgram',
            'budgetBucket.fundingSource',
            'budgetBucket.budgetVersion',
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
            'studyProgram',
            'budgetBucket.fundingSource',
            'budgetBucket.budgetVersion',
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
