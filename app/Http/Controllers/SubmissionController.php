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
use Barryvdh\DomPDF\Facade\Pdf;
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
        $query = Submission::with([
            'department',
            'studyProgram',
            'budgetBucket.fundingSource',
            'budgetBucket.fiscalYear',
            'creator',
            'transactionType',
        ]);

        // Role-based scoping:
        // - PTK: Scope to own department & own submissions
        // - Kajur: Scope to own department (monitoring read-only)
        // - Kaprodi: Scope strictly to own study_program_id
        // - PTU/Bendahara/Kabag/WD/Dekan/Admin: Faculty-wide with selectable filters
        if ($user && $user->hasRole('KAPRODI')) {
            $query->where('study_program_id', $user->study_program_id);
        } elseif ($user && $user->hasRole(['PTK', 'KAJUR'])) {
            ScopeService::applyDepartmentScope($query, $user, $user->department_id);
        } else {
            ScopeService::applyDepartmentScope($query, $user, $request->department_id);
        }

        // Multi-dimensional Filtering
        // 1. TA (Fiscal Year)
        if ($request->filled('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->fiscal_year_id);
        }

        // 2. Sumber Dana
        if ($request->filled('funding_source_id')) {
            $fsId = $request->funding_source_id;
            $query->whereHas('budgetBucket', function ($q) use ($fsId) {
                $q->where('funding_source_id', $fsId);
            });
        }

        // 3. Prodi
        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->study_program_id);
        }

        // 4. Status
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'PROCESSING') {
                $query->whereIn('status', ['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'RESERVED']);
            } elseif ($status === 'RETURNED') {
                $query->whereIn('status', ['RETURNED', 'REVISION_REQUIRED']);
            } elseif ($status === 'FINAL') {
                $query->whereIn('status', ['FINAL', 'COMPLETED']);
            } elseif ($status === 'CANCELLED') {
                $query->whereIn('status', ['CANCELLED', 'REJECTED']);
            } else {
                $query->where('status', $status);
            }
        }

        // 5. Akun (Kode Akun)
        if ($request->filled('account_code')) {
            $accCode = $request->account_code;
            $query->whereHas('budgetBucket', function ($q) use ($accCode) {
                $q->where('account_code', $accCode);
            });
        }

        // 6. Periode (Date Range)
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Search across: Nomor Bukti, Uraian, Kode Akun
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('evidence_number', 'like', "%{$search}%")
                    ->orWhere('submission_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('budgetBucket', function ($bq) use ($search) {
                        $bq->where('account_code', 'like', "%{$search}%")
                            ->orWhere('account_name', 'like', "%{$search}%");
                    });
            });
        }

        $submissions = $query->latest('transaction_date')->latest('id')->paginate(15)->withQueryString();
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $fundingSources = FundingSource::all();
        $departments = ScopeService::getSelectableDepartments($user);
        $studyPrograms = ScopeService::getSelectableStudyPrograms($user, $request->department_id ?: $user?->department_id);
        $accounts = BudgetBucket::select('account_code', 'account_name')->distinct()->orderBy('account_code')->get();

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'fiscalYears' => $fiscalYears,
            'fundingSources' => $fundingSources,
            'departments' => $departments,
            'studyPrograms' => $studyPrograms,
            'accounts' => $accounts,
            'canCreate' => ScopeService::canCreateTransaction($user),
            'userRole' => $user?->role === 'WD' ? 'WAKIL_DEKAN' : ($user?->role ?? 'GUEST'),
            'userDepartmentId' => $user?->department_id,
            'userStudyProgramId' => $user?->study_program_id,
            'filters' => $request->only([
                'fiscal_year_id',
                'funding_source_id',
                'department_id',
                'study_program_id',
                'status',
                'account_code',
                'start_date',
                'end_date',
                'search',
            ]),
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

                $defaultTransactionTypeId = TransactionType::where('is_active', true)->first()?->id ?? TransactionType::first()?->id;

                $sub = Submission::create([
                    'submission_number' => $submissionNumber,
                    'evidence_number' => $request->evidence_number,
                    'transaction_date' => $request->transaction_date,
                    'reference_no' => $request->reference_no,
                    'title' => $request->title,
                    'department_id' => $departmentId,
                    'study_program_id' => $request->study_program_id ?? $user?->study_program_id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'transaction_type_id' => $request->transaction_type_id ?? $defaultTransactionTypeId,
                    'budget_bucket_id' => $request->budget_bucket_id,
                    'amount' => $request->amount,
                    'beneficiary_name' => $request->beneficiary_name,
                    'status' => $status,
                    'created_by' => $user?->id,
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

        // Audit Log Recording
        AuditLogService::log(
            'PRINT_TRANSACTION',
            Submission::class,
            $submission->id,
            null,
            [
                'evidence_number' => $submission->evidence_number ?: $submission->submission_number,
                'amount' => $submission->amount,
                'actor' => $user?->name,
                'role' => $user?->role,
            ]
        );

        return Inertia::render('Submissions/Print', [
            'submission' => $submission,
            'signoffUser' => $signoffApproval?->user,
            'signoffDate' => $signoffApproval ? date('d F Y', strtotime($signoffApproval->created_at)) : null,
        ]);
    }

    public function exportPdf(Submission $submission)
    {
        $user = auth()->user();
        if (! ScopeService::canAccessDepartment($user, $submission->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang mengunduh dokumen unit lain.');
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

        // Audit Log Recording
        AuditLogService::log(
            'EXPORT_SUBMISSION_PDF',
            Submission::class,
            $submission->id,
            null,
            [
                'evidence_number' => $submission->evidence_number ?: $submission->submission_number,
                'amount' => $submission->amount,
                'actor' => $user?->name,
            ]
        );

        $pdf = Pdf::loadView('exports.submission-pdf', [
            'submission' => $submission,
            'signoffApproval' => $submission->approvals()->latest()->first(),
        ])->setPaper('a4', 'portrait');

        $evidenceNo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $submission->evidence_number ?: $submission->submission_number);
        $filename = "SPJ_{$evidenceNo}_".date('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    public function exportDocx(Submission $submission)
    {
        $user = auth()->user();
        if (! ScopeService::canAccessDepartment($user, $submission->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang mengunduh dokumen unit lain.');
        }

        $submission->load([
            'department',
            'studyProgram',
            'budgetBucket.fundingSource',
            'budgetBucket.budgetVersion',
            'creator',
            'items',
        ]);

        // Audit Log Recording
        AuditLogService::log(
            'EXPORT_SUBMISSION_DOCX',
            Submission::class,
            $submission->id,
            null,
            [
                'evidence_number' => $submission->evidence_number ?: $submission->submission_number,
                'amount' => $submission->amount,
                'actor' => $user?->name,
            ]
        );

        $evidenceNo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $submission->evidence_number ?: $submission->submission_number);
        $filename = "SPJ_{$evidenceNo}_Editable_".date('Ymd_His').'.doc';

        $itemsHtml = '';
        foreach ($submission->items as $idx => $item) {
            $no = $idx + 1;
            $qty = $item->quantity;
            $unitPrice = 'Rp '.number_format($item->unit_price, 0, ',', '.');
            $totalPrice = 'Rp '.number_format($item->total_price, 0, ',', '.');
            $itemsHtml .= "<tr><td align='center'>{$no}</td><td>{$item->item_name}</td><td align='center'>{$qty}</td><td align='right'>{$unitPrice}</td><td align='right'>{$totalPrice}</td></tr>";
        }

        $totalFormatted = 'Rp '.number_format($submission->amount, 0, ',', '.');
        $deptName = $submission->department?->name ?? 'Fakultas Teknik';
        $accountStr = "[{$submission->budgetBucket?->account_code}] {$submission->budgetBucket?->account_name}";

        $docContent = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head><title>Surat Usulan SPJ Belanja</title>
<style>
body { font-family: 'Times New Roman', serif; font-size: 12pt; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid black; padding: 6px; }
.no-border th, .no-border td { border: none; }
.header-title { text-align: center; font-weight: bold; }
</style>
</head>
<body>
<div class='header-title'>
<h3>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
<h2>UNIVERSITAS JENDERAL SOEDIRMAN - FAKULTAS TEKNIK</h2>
<p>Jl. Mayjen HR. Boenyamin No. 708 Purwokerto 53122 | Web: ft.unsoed.ac.id</p>
<hr size='3' color='black' />
</div>

<h3 align='center'><u>SURAT USULAN & BUKTI BELANJA ANGGARAN</u><br/><small>Nomor: {$submission->evidence_number}</small></h3>

<table class='no-border' style='margin-bottom: 20px;'>
<tr><td width='25%'><b>Unit / Jurusan</b></td><td>: {$deptName}</td></tr>
<tr><td><b>Mata Anggaran (Pos)</b></td><td>: {$accountStr}</td></tr>
<tr><td><b>Nama Kegiatan</b></td><td>: {$submission->title}</td></tr>
<tr><td><b>Penerima Pembayaran</b></td><td>: {$submission->beneficiary_name}</td></tr>
<tr><td><b>Total Nilai Belanja</b></td><td>: <b>{$totalFormatted}</b></td></tr>
</table>

<h4>Rincian Komponen Belanja:</h4>
<table>
<thead>
<tr bgcolor='#f2f2f2'>
<th>No</th><th>Uraian Spesifikasi Item</th><th>Qty</th><th>Harga Satuan</th><th>Total Harga</th>
</tr>
</thead>
<tbody>
{$itemsHtml}
</tbody>
<tfoot>
<tr bgcolor='#f2f2f2'><th colspan='4' align='right'>Total Usulan:</th><th align='right'>{$totalFormatted}</th></tr>
</tfoot>
</table>

<br/><br/>
<table class='no-border'>
<tr>
<td width='50%' align='center'>
Mengetahui / Mengajukan,<br/>
Pengelola Transaksi (PTK)<br/><br/><br/><br/>
( <u>{$submission->creator?->name}</u> )
</td>
<td width='50%' align='center'>
Purwokerto, ".date('d F Y').'<br/>
Verifikator PTU / Bendahara<br/><br/><br/><br/>
( ____________________ )
</td>
</tr>
</table>

</body>
</html>';

        return response($docContent, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
