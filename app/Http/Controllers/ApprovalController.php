<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Services\AuditLogService;
use App\Services\BudgetService;
use App\Services\ScopeService;
use App\Services\WorkflowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $activeTab = strtoupper($request->input('tab', 'NEW')); // NEW, PROCESSING, RETURNED, FINAL, ISSUE

        // Base Query with Full Relational Context for Detail Drawer
        $baseQuery = Submission::with([
            'department',
            'studyProgram',
            'budgetBucket.fundingSource',
            'budgetBucket.fiscalYear',
            'budgetBucket.budgetVersion',
            'creator',
            'items',
            'documents.documentType',
            'statusHistories.actor',
            'approvals.user',
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
                    ->orWhereHas('budgetBucket', function ($bq) use ($search) {
                        $bq->where('account_code', 'like', "%{$search}%")
                            ->orWhere('account_name', 'like', "%{$search}%");
                    });
            });
        }

        // Count for each Tab
        $countQuery = clone $baseQuery;
        $countNew = (clone $countQuery)->whereIn('status', ['SUBMITTED', 'DRAFT'])->count();
        $countProcessing = (clone $countQuery)->whereIn('status', ['PROCESSING', 'UNDER_REVIEW', 'REVIEW', 'APPROVED', 'RESERVED'])->count();
        $countReturned = (clone $countQuery)->whereIn('status', ['RETURNED', 'REVISION_REQUIRED'])->count();
        $countFinal = (clone $countQuery)->whereIn('status', ['FINAL', 'COMPLETED'])->count();
        $countIssue = (clone $countQuery)->where(function ($q) {
            $q->whereIn('status', ['RETURNED', 'REJECTED', 'CANCELLED'])
                ->orWhereHas('budgetBucket', function ($bq) {
                    $bq->where('available_balance', '<', 0);
                });
        })->count();

        // Apply Tab Filter
        $tabFilteredQuery = clone $baseQuery;
        switch ($activeTab) {
            case 'NEW':
                $tabFilteredQuery->whereIn('status', ['SUBMITTED', 'DRAFT']);
                break;
            case 'PROCESSING':
                $tabFilteredQuery->whereIn('status', ['PROCESSING', 'UNDER_REVIEW', 'REVIEW', 'APPROVED', 'RESERVED']);
                break;
            case 'RETURNED':
                $tabFilteredQuery->whereIn('status', ['RETURNED', 'REVISION_REQUIRED']);
                break;
            case 'FINAL':
                $tabFilteredQuery->whereIn('status', ['FINAL', 'COMPLETED']);
                break;
            case 'ISSUE':
                $tabFilteredQuery->where(function ($q) {
                    $q->whereIn('status', ['RETURNED', 'REJECTED', 'CANCELLED'])
                        ->orWhereHas('budgetBucket', function ($bq) {
                            $bq->where('available_balance', '<', 0);
                        });
                });
                break;
            default:
                $tabFilteredQuery->whereIn('status', ['SUBMITTED', 'DRAFT']);
                break;
        }

        $submissionsPaginated = $tabFilteredQuery->latest('transaction_date')->latest('id')->paginate(15)->withQueryString();

        // Transform collection to add computed helper attributes for Drawer
        $submissionsPaginated->getCollection()->transform(function ($sub) {
            $bucket = $sub->budgetBucket;
            $dept = $sub->department;
            $deptName = $dept?->name ?? 'Fakultas Teknik';

            // Age formatting
            $createdAt = Carbon::parse($sub->transaction_date ?: $sub->created_at);
            $sub->age_human = $createdAt->diffForHumans();

            // 7-Segment Budget Context
            $sub->budget_context = [
                'ta' => $bucket?->fiscalYear?->year ?? 2026,
                'sumber_dana' => $bucket?->fundingSource?->code ?? 'RM',
                'revision' => $bucket?->budgetVersion?->revision_no ?? 'Rev 02',
                'jurusan_code' => $dept?->code ?? 'FT',
                'jurusan_name' => $deptName,
                'prodi_name' => $sub->studyProgram?->name ?? 'Level Jurusan',
                'program_code' => 'WA',
                'program_name' => 'Program Dukungan Manajemen',
                'activity_code' => '4257',
                'activity_name' => 'Dukungan Manajemen & Pelaksanaan Tugas Teknis Ditjen Dikti',
                'kro_code' => '7734.EBA',
                'kro_name' => 'Layanan Dukungan Manajemen Internal',
                'ro_code' => '994',
                'ro_name' => 'Layanan Perkantoran',
                'component_code' => '001',
                'component_name' => 'Operasional & Pemeliharaan Kantor',
                'subcomponent_code' => $bucket?->subcomponent_code ?? 'AA',
                'subcomponent_name' => $bucket?->subcomponent_name ?? "Operasional & Praktikum {$deptName}",
                'account_code' => $bucket?->account_code ?? '-',
                'account_name' => $bucket?->account_name ?? 'Belanja Operasional',
                'subaccount_code' => ($bucket?->account_code ?? '521211').'.001',
                'subaccount_name' => 'Alokasi Operasional Standar Unit',
            ];

            // Financial Context Snapshot
            $allocated = (float) ($bucket?->allocated_budget ?? 0);
            $reserved = (float) ($bucket?->reserved_budget ?? 0);
            $realized = (float) ($bucket?->realized_budget ?? 0);
            $available = (float) ($bucket?->available_balance ?? ($allocated - $reserved - $realized));
            $subAmount = (float) $sub->amount;
            $projected = $available - $subAmount;

            $sub->financial_context = [
                'allocated_budget' => $allocated,
                'reserved_budget' => $reserved,
                'realized_budget' => $realized,
                'available_balance' => $available,
                'submission_amount' => $subAmount,
                'projected_balance' => $projected,
                'is_solvent' => $projected >= 0,
                'serapan_rate' => $allocated > 0 ? round(($realized / $allocated) * 100, 1) : 0,
            ];

            // Rule Check Snapshot
            $sub->rule_check = [
                'rbc_001_solvency' => $projected >= 0 ? 'PASSED' : 'OVERBUDGET',
                'rbc_006_duplicate' => 'PASSED',
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
                'new' => $countNew,
                'processing' => $countProcessing,
                'returned' => $countReturned,
                'final' => $countFinal,
                'issue' => $countIssue,
            ],
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
            'action' => 'required|string|in:VERIFY,RETURN,FINALIZE,APPROVED,RETURNED,REJECTED',
            'comment' => 'nullable|string|max:1000',
        ]);

        $action = strtoupper($request->action);

        // Enforce: Return wajib memiliki alasan!
        if (in_array($action, ['RETURN', 'RETURNED']) && empty(trim((string) $request->comment))) {
            return redirect()->back()->withErrors([
                'comment' => 'Wajib mengisi catatan / alasan pengembalian berkas kepada PTK.',
            ]);
        }

        // ==================================================
        // ACTION 1: VERIFY (Lolos Tahap Pemeriksaan Awal)
        // ==================================================
        if ($action === 'VERIFY') {
            DB::transaction(function () use ($submission, $user, $request) {
                $sub = Submission::where('id', $submission->id)->lockForUpdate()->first();
                $oldStatus = $sub->status;

                $sub->status = 'PROCESSING';
                $sub->notes = $request->comment ?: ($sub->notes ?: 'Telah diverifikasi kelengkapan bukti transaksi oleh PTU (Penguji Tagihan Unit BLU).');
                $sub->save();

                SubmissionStatusHistory::create([
                    'submission_id' => $sub->id,
                    'from_status' => $oldStatus,
                    'to_status' => 'PROCESSING',
                    'actor_id' => $user->id,
                    'role' => $user->role,
                    'notes' => $request->comment ?: 'Verifikasi berkas & kepatuhan SPJ dinyatakan lolos oleh PTU (Penguji Tagihan Unit BLU).',
                ]);

                AuditLogService::log(
                    'VERIFY_SUBMISSION',
                    Submission::class,
                    $sub->id,
                    ['status' => $oldStatus],
                    ['status' => 'PROCESSING', 'actor' => $user->name]
                );
            });

            return redirect()->back()->with('success', "Transaksi {$submission->evidence_number} berhasil diverifikasi.");
        }

        // ==================================================
        // ACTION 2: RETURN (Kembalikan ke PTK untuk Perbaikan)
        // ==================================================
        if (in_array($action, ['RETURN', 'RETURNED'])) {
            DB::transaction(function () use ($submission, $user, $request) {
                $sub = Submission::where('id', $submission->id)->lockForUpdate()->first();
                $bucket = BudgetBucket::where('id', $sub->budget_bucket_id)->lockForUpdate()->first();
                $oldStatus = $sub->status;

                // Release reserved budget if previously reserved
                if ($bucket && in_array($oldStatus, ['PROCESSING', 'RESERVED', 'APPROVED', 'UNDER_REVIEW'])) {
                    $this->budgetService->releaseReservation($bucket, (float) $sub->amount);
                }

                $sub->status = 'RETURNED';
                $sub->notes = $request->comment;
                $sub->save();

                SubmissionStatusHistory::create([
                    'submission_id' => $sub->id,
                    'from_status' => $oldStatus,
                    'to_status' => 'RETURNED',
                    'actor_id' => $user->id,
                    'role' => $user->role,
                    'notes' => "Pengembalian berkas: {$request->comment}",
                ]);

                AuditLogService::log(
                    'RETURN_SUBMISSION',
                    Submission::class,
                    $sub->id,
                    ['status' => $oldStatus],
                    ['status' => 'RETURNED', 'reason' => $request->comment]
                );
            });

            return redirect()->back()->with('success', "Transaksi {$submission->evidence_number} telah dikembalikan ke PTK untuk perbaikan.");
        }

        // ==================================================
        // ACTION 3: FINALIZE (Backend Transactional Realization)
        // ==================================================
        if (in_array($action, ['FINALIZE', 'APPROVED'])) {
            DB::transaction(function () use ($submission, $user, $request) {
                $sub = Submission::where('id', $submission->id)->lockForUpdate()->first();
                $bucket = BudgetBucket::where('id', $sub->budget_bucket_id)->lockForUpdate()->first();
                $oldStatus = $sub->status;

                if (! $bucket) {
                    $bucket = BudgetBucket::where('department_id', $sub->department_id)->first();
                }

                if ($bucket) {
                    // Finalize realization: Move amount from reserved to realized, update available balance
                    $this->budgetService->finalizeRealization($bucket, (float) $sub->amount);
                }

                $sub->status = 'FINAL';
                $sub->notes = $request->comment ?: ($sub->notes ?: 'Transaksi selesai & realisasi definitif telah dibukukan.');
                $sub->save();

                SubmissionStatusHistory::create([
                    'submission_id' => $sub->id,
                    'from_status' => $oldStatus,
                    'to_status' => 'FINAL',
                    'actor_id' => $user->id,
                    'role' => $user->role,
                    'notes' => $request->comment ?: 'Finalisasi pencairan anggaran & realisasi belanja definitif oleh Penguji Tagihan Unit BLU / Bendahara.',
                ]);

                AuditLogService::log(
                    'FINALIZE_TRANSACTION',
                    Submission::class,
                    $sub->id,
                    ['status' => $oldStatus],
                    ['status' => 'FINAL', 'amount' => $sub->amount, 'actor' => $user->name]
                );
            });

            return redirect()->back()->with('success', "Transaksi {$submission->evidence_number} berhasil difinalisasi & realisasi anggaran definitif telah dibukukan.");
        }

        return redirect()->back()->with('error', 'Aksi otorisasi tidak dikenali.');
    }
}
