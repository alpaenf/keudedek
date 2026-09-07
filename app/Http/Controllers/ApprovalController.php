<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\BudgetCalculationService;
use App\Services\BudgetControlService;
use App\Services\BudgetService;
use App\Services\ScopeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! ScopeService::canApproveFinancial($user)) {
            abort(403, 'Akses Ditolak: Role Anda tidak memiliki kewenangan pemeriksaan / verifikasi finansial.');
        }

        $activeTab = strtoupper($request->input('tab', 'DIAJUKAN')); // DIAJUKAN, SELESAI, DIKEMBALIKAN, DITOLAK, ALL

        // Base Query with Full Relational Context for Examination Workbench
        $baseQuery = Submission::with([
            'department',
            'studyProgram',
            'budgetBucket.fundingSource',
            'budgetBucket.fiscalYear',
            'budgetBucket.budgetVersion',
            'budgetLine.subcomponent',
            'budgetLine.account',
            'budgetLine.program',
            'budgetLine.activity',
            'budgetLine.kro',
            'budgetLine.ro',
            'budgetLine.component',
            'creator',
            'items',
            'documents.documentType',
            'statusHistories.actor',
        ]);

        ScopeService::applyDepartmentScope($baseQuery, $user, $request->department_id);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $baseQuery->where(function ($q) use ($search) {
                $q->where('evidence_number', 'like', "%{$search}%")
                    ->orWhere('submission_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('budgetLine', function ($blq) use ($search) {
                        $blq->where('rba_sequence_no', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    })
                    ->orWhereHas('budgetBucket', function ($bq) use ($search) {
                        $bq->where('account_code', 'like', "%{$search}%")
                            ->orWhere('account_name', 'like', "%{$search}%");
                    });
            });
        }

        // Count for each Tab
        $countQuery = clone $baseQuery;
        $countDiajukan = (clone $countQuery)->whereIn('status', ['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'RESERVED'])->count();
        $countSelesai = (clone $countQuery)->whereIn('status', ['FINAL', 'COMPLETED'])->count();
        $countDikembalikan = (clone $countQuery)->whereIn('status', ['RETURNED', 'REVISION_REQUIRED'])->count();
        $countDitolak = (clone $countQuery)->whereIn('status', ['REJECTED', 'CANCELLED'])->count();
        $countAll = (clone $countQuery)->count();

        // Apply Tab Filter
        $tabFilteredQuery = clone $baseQuery;
        switch ($activeTab) {
            case 'DIAJUKAN':
                $tabFilteredQuery->whereIn('status', ['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'RESERVED']);
                break;
            case 'SELESAI':
                $tabFilteredQuery->whereIn('status', ['FINAL', 'COMPLETED']);
                break;
            case 'DIKEMBALIKAN':
                $tabFilteredQuery->whereIn('status', ['RETURNED', 'REVISION_REQUIRED']);
                break;
            case 'DITOLAK':
                $tabFilteredQuery->whereIn('status', ['REJECTED', 'CANCELLED']);
                break;
            case 'ALL':
                // All submissions
                break;
            default:
                $tabFilteredQuery->whereIn('status', ['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'RESERVED']);
                break;
        }

        $submissionsPaginated = $tabFilteredQuery->latest('transaction_date')->latest('id')->paginate(15)->withQueryString();

        // Transform collection to add computed helper attributes for Drawer & Examination
        $submissionsPaginated->getCollection()->transform(function ($sub) {
            $bucket = $sub->budgetBucket;
            $line = $sub->budgetLine;
            $dept = $sub->department;
            $deptName = $dept?->name ?? 'Fakultas Teknik';

            // Age formatting
            $createdAt = Carbon::parse($sub->transaction_date ?: $sub->created_at);
            $sub->age_human = $createdAt->diffForHumans();

            // Financial Context Snapshot using single source of truth service
            $snapshot = $line ? BudgetCalculationService::getLineFinancialSnapshot($line) : [
                'line_budget' => 0,
                'line_diajukan' => 0,
                'line_realisasi' => 0,
                'line_saldo' => 0,
                'bucket_id' => $bucket?->id,
                'bucket_allocated' => (float) ($bucket?->allocated_budget ?? 0),
                'bucket_reserved' => (float) ($bucket?->reserved_budget ?? 0),
                'bucket_realized' => (float) ($bucket?->realized_budget ?? 0),
                'bucket_available' => (float) ($bucket?->available_balance ?? 0),
            ];

            $subAmount = (float) $sub->amount;
            $bucketAvailable = $snapshot['bucket_available'];
            $isSolvent = in_array($sub->status, ['PROCESSING', 'SUBMITTED', 'FINAL', 'COMPLETED']) || $bucketAvailable >= $subAmount;

            $sub->financial_context = [
                'allocated_budget' => $snapshot['bucket_allocated'],
                'reserved_budget' => $snapshot['bucket_reserved'],
                'realized_budget' => $snapshot['bucket_realized'],
                'available_balance' => $bucketAvailable,
                'submission_amount' => $subAmount,
                'projected_balance' => $bucketAvailable - $subAmount,
                'is_solvent' => $isSolvent,
                'line_budget' => $snapshot['line_budget'],
                'line_saldo' => $snapshot['line_saldo'],
            ];

            // 7-Segment Budget Hierarchy (from Line or Bucket)
            $sub->budget_context = [
                'ta' => $bucket?->fiscalYear?->year ?? 2026,
                'sumber_dana' => $bucket?->fundingSource?->code ?? 'RM',
                'revision' => $bucket?->budgetVersion?->revision_no ?? 'Rev 00',
                'jurusan_code' => $dept?->code ?? 'FT',
                'jurusan_name' => $deptName,
                'prodi_name' => $sub->studyProgram?->name ?? 'Level Jurusan',
                'rba_sequence_no' => $line?->rba_sequence_no ?? '-',
                'rba_description' => $line?->description ?? $sub->title,
                'program_code' => $line?->program?->code ?? 'WA',
                'program_name' => $line?->program?->name ?? 'Program Pendidikan dan Pelayanan Masyarakat',
                'activity_code' => $line?->activity?->code ?? '2134',
                'activity_name' => $line?->activity?->name ?? 'Penyelenggaraan Pendidikan Tinggi',
                'kro_code' => $line?->kro?->code ?? 'BMA',
                'kro_name' => $line?->kro?->name ?? 'Gedung dan Prasarana Kampus',
                'ro_code' => $line?->ro?->code ?? '001',
                'ro_name' => $line?->ro?->name ?? 'Operasional Fakultas Teknik',
                'component_code' => $line?->component?->code ?? '051',
                'component_name' => $line?->component?->name ?? 'Layanan Perkantoran',
                'subcomponent_code' => $line?->subcomponent?->code ?? $bucket?->subcomponent_code ?? 'A',
                'subcomponent_name' => $line?->subcomponent?->name ?? $bucket?->subcomponent_name ?? "Operasional {$deptName}",
                'account_code' => $line?->account?->code ?? $bucket?->account_code ?? '-',
                'account_name' => $line?->account?->name ?? $bucket?->account_name ?? 'Belanja Bahan',
            ];

            // Rule Check Snapshot
            $duplicateWarning = BudgetControlService::checkDuplicateReference($sub->evidence_number, $sub->department_id, $sub->id);
            $sub->rule_check = [
                'rbc_001_solvency' => $isSolvent ? 'PASSED' : 'OVERBUDGET',
                'rbc_006_duplicate' => $duplicateWarning ? 'WARNING' : 'PASSED',
                'duplicate_message' => $duplicateWarning,
                'has_documents' => $sub->documents && $sub->documents->count() > 0,
                'document_count' => $sub->documents ? $sub->documents->count() : 0,
            ];

            return $sub;
        });

        $departments = ScopeService::getSelectableDepartments($user);

        return Inertia::render('Approvals/Index', [
            'submissions' => $submissionsPaginated,
            'departments' => $departments,
            'activeTab' => $activeTab,
            'tabCounts' => [
                'diajukan' => $countDiajukan,
                'selesai' => $countSelesai,
                'dikembalikan' => $countDikembalikan,
                'ditolak' => $countDitolak,
                'all' => $countAll,
            ],
            'canFinalize' => ScopeService::canFinalizeTransaction($user),
            'filters' => $request->only(['department_id', 'search', 'tab']),
            'userRole' => $user->role === 'WD' ? 'WAKIL_DEKAN' : $user->role,
        ]);
    }

    public function decide(Request $request, Submission $submission): RedirectResponse
    {
        $user = $request->user();

        if (! ScopeService::canApproveFinancial($user)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki otoritas verifikasi / persetujuan finansial.');
        }

        if (! ScopeService::canAccessDepartment($user, $submission->department_id)) {
            abort(403, 'Akses Ditolak: Anda tidak berwenang memeriksa pengajuan di luar lingkup unit Anda.');
        }

        $request->validate([
            'action' => 'required|string|in:KEMBALIKAN,RETURN,RETURNED,TOLAK,REJECT,REJECTED,SELESAI,FINALIZE,APPROVED',
            'comment' => 'nullable|string|max:1000',
        ]);

        $action = strtoupper($request->action);

        // ==================================================
        // ACTION 1: KEMBALIKAN (Wajib Alasan -> DIKEMBALIKAN, Commitment Dilepas)
        // ==================================================
        if (in_array($action, ['KEMBALIKAN', 'RETURN', 'RETURNED'])) {
            if (empty(trim((string) $request->comment))) {
                return redirect()->back()->withErrors([
                    'comment' => 'Wajib mengisi catatan / alasan pengembalian berkas transaksi kepada PTK.',
                ]);
            }

            try {
                BudgetControlService::transitionStatus(
                    submission: $submission,
                    targetStatus: 'RETURNED',
                    actor: $user,
                    notes: "Pengembalian berkas: {$request->comment}"
                );
            } catch (\InvalidArgumentException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            return redirect()->back()->with('success', "Transaksi {$submission->evidence_number} telah dikembalikan ke PTK untuk perbaikan.");
        }

        // ==================================================
        // ACTION 2: TOLAK (Wajib Alasan -> DITOLAK, Commitment Dilepas)
        // ==================================================
        if (in_array($action, ['TOLAK', 'REJECT', 'REJECTED'])) {
            if (empty(trim((string) $request->comment))) {
                return redirect()->back()->withErrors([
                    'comment' => 'Wajib mengisi catatan / alasan penolakan berkas transaksi.',
                ]);
            }

            try {
                BudgetControlService::transitionStatus(
                    submission: $submission,
                    targetStatus: 'REJECTED',
                    actor: $user,
                    notes: "Penolakan berkas: {$request->comment}"
                );
            } catch (\InvalidArgumentException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            return redirect()->back()->with('success', "Transaksi {$submission->evidence_number} telah ditolak.");
        }

        // ==================================================
        // ACTION 3: SELESAI (Hanya Permission Diizinkan -> SELESAI, Commitment -> Realisasi)
        // ==================================================
        if (in_array($action, ['SELESAI', 'FINALIZE', 'APPROVED'])) {
            if (! ScopeService::canFinalizeTransaction($user)) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyelesaikan transaksi & realisasi.');
            }

            try {
                BudgetControlService::transitionStatus(
                    submission: $submission,
                    targetStatus: 'FINAL',
                    actor: $user,
                    notes: $request->comment ?: 'Pemeriksaan selesai. Realisasi internal telah dibukukan oleh PTU / Bendahara.'
                );
            } catch (\InvalidArgumentException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }

            return redirect()->back()->with('success', "Transaksi {$submission->evidence_number} berhasil diselesaikan. Realisasi anggaran internal telah dibukukan.");
        }

        return redirect()->back()->with('error', 'Aksi pemeriksaan tidak dikenali.');
    }
}
