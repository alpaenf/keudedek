<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\BudgetRevision;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\EarlyWarning;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\StudyProgram;
use App\Models\Submission;
use App\Services\AuditLogService;
use App\Services\ScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 13 Filter Dimensions
        $ta = $request->input('fiscal_year_id');
        $revision = $request->input('budget_version_id');
        $fund = $request->input('funding_source_id');
        $jurusan = $request->input('department_id');
        $prodi = $request->input('study_program_id');
        $program = $request->input('program_code');
        $kegiatan = $request->input('activity_code');
        $kro = $request->input('kro_code');
        $ro = $request->input('ro_code');
        $subkomponen = $request->input('subcomponent_code');
        $akun = $request->input('account_code');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        $activeReport = $request->input('report', 'REALISASI_JURUSAN');

        // Master Reference Data
        $departments = ScopeService::getSelectableDepartments($user);
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $fundingSources = FundingSource::all();
        $budgetVersions = BudgetVersion::orderBy('revision_no', 'desc')->get();
        $studyPrograms = StudyProgram::orderBy('name')->get();
        $accounts = BudgetBucket::select('account_code', 'account_name')->distinct()->orderBy('account_code')->get();

        // Base Budget Bucket Query with relational filters
        $bucketQuery = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear', 'budgetVersion']);
        ScopeService::applyDepartmentScope($bucketQuery, $user, $jurusan);

        if ($ta) {
            $bucketQuery->where('fiscal_year_id', $ta);
        }
        if ($revision) {
            $bucketQuery->where('budget_version_id', $revision);
        }
        if ($fund) {
            $bucketQuery->where('funding_source_id', $fund);
        }
        if ($akun) {
            $bucketQuery->where('account_code', $akun);
        }
        if ($subkomponen) {
            $bucketQuery->where('subcomponent_code', $subkomponen);
        }

        $buckets = $bucketQuery->get();

        // Financial Totals KPI
        $totalAllocated = (float) $buckets->sum('allocated_budget');
        $totalReserved = (float) $buckets->sum('reserved_budget');
        $totalRealized = (float) $buckets->sum('realized_budget');
        $totalAvailable = (float) $buckets->sum('available_balance');
        $serapanRate = $totalAllocated > 0 ? round(($totalRealized / $totalAllocated) * 100, 1) : 0;
        $utilizationRate = $totalAllocated > 0 ? round((($totalRealized + $totalReserved) / $totalAllocated) * 100, 1) : 0;

        // Base Submissions Query with filters
        $submissionQuery = Submission::with(['department', 'studyProgram', 'budgetBucket.fundingSource', 'creator']);
        ScopeService::applyDepartmentScope($submissionQuery, $user, $jurusan);

        if ($prodi) {
            $submissionQuery->where('study_program_id', $prodi);
        }
        if ($akun) {
            $submissionQuery->whereHas('budgetBucket', fn ($q) => $q->where('account_code', $akun));
        }
        if ($status) {
            $submissionQuery->where('status', $status);
        }
        if ($startDate) {
            $submissionQuery->whereDate('transaction_date', '>=', $startDate);
        }
        if ($endDate) {
            $submissionQuery->whereDate('transaction_date', '<=', $endDate);
        }

        $submissions = $submissionQuery->latest('transaction_date')->limit(200)->get();

        // ==================================================
        // 1. REPORT: REALISASI PER JURUSAN (Department Breakdown)
        // ==================================================
        $reportByDept = Department::all()->map(function ($d) use ($buckets) {
            $deptBuckets = $buckets->where('department_id', $d->id);
            $alloc = (float) $deptBuckets->sum('allocated_budget');
            $res = (float) $deptBuckets->sum('reserved_budget');
            $real = (float) $deptBuckets->sum('realized_budget');
            $avail = (float) $deptBuckets->sum('available_balance');
            $rate = $alloc > 0 ? round(($real / $alloc) * 100, 1) : 0;

            return [
                'department_id' => $d->id,
                'code_name' => "{$d->code} &mdash; {$d->name}",
                'department_code' => $d->code,
                'department_name' => $d->name,
                'allocated_budget' => $alloc,
                'reserved_budget' => $res,
                'realized_budget' => $real,
                'available_balance' => $avail,
                'serapan_rate' => $rate,
            ];
        });

        // ==================================================
        // 2. REPORT: PAGU VS DALAM PROSES VS REALISASI
        // ==================================================
        $reportPaguVsReal = [
            'total_allocated' => $totalAllocated,
            'total_reserved' => $totalReserved,
            'total_realized' => $totalRealized,
            'total_available' => $totalAvailable,
            'serapan_rate' => $serapanRate,
            'utilization_rate' => $utilizationRate,
            'sisa_rate' => $totalAllocated > 0 ? round(($totalAvailable / $totalAllocated) * 100, 1) : 0,
        ];

        // ==================================================
        // 3. REPORT: REALISASI PER AKUN (MASTER CODE + NAME)
        // ==================================================
        $reportByAccount = $buckets->groupBy('account_code')->map(function ($group, $accCode) {
            $firstName = $group->first()?->account_name ?? 'Belanja Operasional';
            $alloc = (float) $group->sum('allocated_budget');
            $res = (float) $group->sum('reserved_budget');
            $real = (float) $group->sum('realized_budget');
            $avail = (float) $group->sum('available_balance');
            $rate = $alloc > 0 ? round(($real / $alloc) * 100, 1) : 0;

            return [
                'account_code' => $accCode,
                'account_name' => $firstName,
                'code_name' => "[{$accCode}] {$firstName}",
                'allocated_budget' => $alloc,
                'reserved_budget' => $res,
                'realized_budget' => $real,
                'available_balance' => $avail,
                'serapan_rate' => $rate,
            ];
        })->values();

        // ==================================================
        // 4. REPORT: REALISASI PER KEGIATAN (MASTER CODE + NAME)
        // ==================================================
        $reportByActivity = collect([
            [
                'code_name' => '4257 &mdash; Dukungan Manajemen & Pelaksanaan Tugas Teknis Ditjen Dikti',
                'program' => 'WA &mdash; Program Dukungan Manajemen',
                'kro' => '7734.EBA &mdash; Layanan Dukungan Manajemen Internal',
                'ro' => '994 &mdash; Layanan Perkantoran',
                'allocated_budget' => $totalAllocated,
                'reserved_budget' => $totalReserved,
                'realized_budget' => $totalRealized,
                'available_balance' => $totalAvailable,
                'serapan_rate' => $serapanRate,
            ],
        ]);

        // ==================================================
        // 5. REPORT: REALISASI PER PRODI (jika mapping tersedia)
        // ==================================================
        $reportByProdi = StudyProgram::with('department')->get()->map(function ($sp) use ($submissions) {
            $spSubs = $submissions->where('study_program_id', $sp->id);
            $real = (float) $spSubs->where('status', 'FINAL')->sum('amount');
            $proc = (float) $spSubs->whereIn('status', ['PROCESSING', 'UNDER_REVIEW', 'SUBMITTED', 'APPROVED'])->sum('amount');
            $count = $spSubs->count();

            return [
                'prodi_id' => $sp->id,
                'code_name' => "{$sp->code} &mdash; {$sp->name}",
                'prodi_name' => $sp->name,
                'department_code' => $sp->department?->code ?? '-',
                'degree' => $sp->degree ?? 'S1',
                'transaction_count' => $count,
                'processing_amount' => $proc,
                'realized_amount' => $real,
                'total_activity_amount' => $real + $proc,
            ];
        });

        // ==================================================
        // 6. REPORT: TRANSAKSI PER PERIODE
        // ==================================================
        $reportTransactions = $submissions->map(function ($s) {
            return [
                'id' => $s->id,
                'evidence_number' => $s->evidence_number ?: $s->submission_number,
                'transaction_date' => $s->transaction_date ?: $s->created_at->format('Y-m-d'),
                'department_code' => $s->department?->code ?? 'FT',
                'prodi_name' => $s->studyProgram?->name ?? 'Level Jurusan',
                'account_code_name' => "[{$s->budgetBucket?->account_code}] {$s->budgetBucket?->account_name}",
                'title' => $s->title,
                'amount' => (float) $s->amount,
                'status' => $s->status,
                'creator_name' => $s->creator?->name ?? 'Operator',
            ];
        });

        // ==================================================
        // 7. REPORT: SALDO ANGGARAN (Detailed Audit Balance Table)
        // ==================================================
        $reportBudgetBalances = $buckets->map(function ($b) {
            $alloc = (float) $b->allocated_budget;
            $res = (float) $b->reserved_budget;
            $real = (float) $b->realized_budget;
            $avail = (float) $b->available_balance;
            $rate = $alloc > 0 ? round(($real / $alloc) * 100, 1) : 0;

            return [
                'id' => $b->id,
                'jurusan_code_name' => "{$b->department?->code} &mdash; {$b->department?->name}",
                'account_code_name' => "[{$b->account_code}] {$b->account_name}",
                'subcomponent_code_name' => "{$b->subcomponent_code} &mdash; {$b->subcomponent_name}",
                'funding_source' => $b->fundingSource?->code ?? 'RM',
                'allocated_budget' => $alloc,
                'reserved_budget' => $res,
                'realized_budget' => $real,
                'available_balance' => $avail,
                'serapan_rate' => $rate,
            ];
        });

        // ==================================================
        // 8. REPORT: EARLY WARNING SUMMARY
        // ==================================================
        $warningQuery = EarlyWarning::with(['department', 'budgetBucket']);
        ScopeService::applyDepartmentScope($warningQuery, $user, $jurusan);
        $warnings = $warningQuery->latest()->get();

        $reportEwsSummary = [
            'total_warnings' => $warnings->count(),
            'critical' => $warnings->where('severity', 'CRITICAL')->count(),
            'high' => $warnings->where('severity', 'HIGH')->count(),
            'warning' => $warnings->where('severity', 'WARNING')->count(),
            'info' => $warnings->where('severity', 'INFO')->count(),
            'open_count' => $warnings->where('lifecycle_state', 'OPEN')->count(),
            'acknowledged_count' => $warnings->where('lifecycle_state', 'ACKNOWLEDGED')->count(),
            'resolved_count' => $warnings->where('lifecycle_state', 'RESOLVED')->count(),
            'items' => $warnings->map(fn ($w) => [
                'id' => $w->id,
                'rule_code' => $w->rule_code,
                'severity' => $w->severity,
                'lifecycle_state' => $w->lifecycle_state,
                'department_code_name' => "{$w->department?->code} &mdash; {$w->department?->name}",
                'account_code_name' => $w->budgetBucket ? "[{$w->budgetBucket->account_code}] {$w->budgetBucket->account_name}" : '-',
                'message' => $w->message,
                'created_at' => $w->created_at->format('Y-m-d H:i'),
            ]),
        ];

        // ==================================================
        // 9. REPORT: REVISION COMPARISON
        // ==================================================
        $revisions = BudgetRevision::with(['budgetBucket.department', 'approver'])->latest()->limit(50)->get();
        $reportRevisionComp = $revisions->map(function ($r) {
            $bucket = $r->budgetBucket;
            $dept = $bucket?->department;
            $old = (float) $r->previous_amount;
            $new = (float) $r->revised_amount;
            $delta = $new - $old;
            $proc = (float) ($bucket?->reserved_budget ?? 0);
            $real = (float) ($bucket?->realized_budget ?? 0);
            $isConflict = $new < ($proc + $real);

            return [
                'revision_number' => $r->revision_number,
                'department_code_name' => "{$dept?->code} &mdash; {$dept?->name}",
                'account_code_name' => "[{$bucket?->account_code}] {$bucket?->account_name}",
                'old_pagu' => $old,
                'new_pagu' => $new,
                'delta' => $delta,
                'dalam_proses' => $proc,
                'realisasi' => $real,
                'is_conflict' => $isConflict,
                'impact_note' => $isConflict ? 'REVISION CONFLICT (Pagu baru < Belanja berjalan)' : ($delta >= 0 ? '+ Penambahan alokasi' : '- Pengurangan alokasi'),
                'reason' => $r->reason,
                'date' => $r->created_at->format('Y-m-d'),
            ];
        });

        return Inertia::render('Reports/Index', [
            'activeReport' => $activeReport,
            // 9 Report Datasets
            'reportByDept' => $reportByDept,
            'reportPaguVsReal' => $reportPaguVsReal,
            'reportByAccount' => $reportByAccount,
            'reportByActivity' => $reportByActivity,
            'reportByProdi' => $reportByProdi,
            'reportTransactions' => $reportTransactions,
            'reportBudgetBalances' => $reportBudgetBalances,
            'reportEwsSummary' => $reportEwsSummary,
            'reportRevisionComp' => $reportRevisionComp,
            // Summary Totals
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
            'serapanRate' => $serapanRate,
            'utilizationRate' => $utilizationRate,
            // 13 Filters Select Options
            'departments' => $departments,
            'fiscalYears' => $fiscalYears,
            'fundingSources' => $fundingSources,
            'budgetVersions' => $budgetVersions,
            'studyPrograms' => $studyPrograms,
            'accounts' => $accounts,
            'filters' => $request->only([
                'report',
                'fiscal_year_id',
                'budget_version_id',
                'funding_source_id',
                'department_id',
                'study_program_id',
                'program_code',
                'activity_code',
                'kro_code',
                'ro_code',
                'subcomponent_code',
                'account_code',
                'start_date',
                'end_date',
                'status',
            ]),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $selectedDepartment = $selectedDepartmentId ? Department::find($selectedDepartmentId) : null;

        $totalAllocated = (float) $buckets->sum('allocated_budget');
        $totalReserved = (float) $buckets->sum('reserved_budget');
        $totalRealized = (float) $buckets->sum('realized_budget');
        $totalAvailable = (float) $buckets->sum('available_balance');

        $serapanRate = $totalAllocated > 0 ? round(($totalRealized / $totalAllocated) * 100, 1) : 0;
        $utilizationRate = $totalAllocated > 0 ? round((($totalRealized + $totalReserved) / $totalAllocated) * 100, 1) : 0;

        // Audit Log Recording
        AuditLogService::log(
            'EXPORT_PDF_REPORT',
            ReportController::class,
            null,
            null,
            [
                'format' => 'PDF',
                'department' => $selectedDepartment?->code ?? 'FACULTY',
                'actor' => $user?->name,
                'role' => $user?->role,
            ]
        );

        $pdf = Pdf::loadView('exports.report-pdf', [
            'buckets' => $buckets,
            'selectedDepartmentName' => $selectedDepartment ? "{$selectedDepartment->code} &mdash; {$selectedDepartment->name}" : 'Semua Jurusan Fakultas Teknik',
            'totalAllocated' => $totalAllocated,
            'totalReserved' => $totalReserved,
            'totalRealized' => $totalRealized,
            'totalAvailable' => $totalAvailable,
            'serapanRate' => $serapanRate,
            'utilizationRate' => $utilizationRate,
        ])->setPaper('a4', 'landscape');

        $filename = 'SIKARA_Laporan_Realisasi_Anggaran_'.date('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    public function exportXlsx(Request $request)
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $selectedDepartment = $selectedDepartmentId ? Department::find($selectedDepartmentId) : null;
        $deptName = $selectedDepartment ? "{$selectedDepartment->code} - {$selectedDepartment->name}" : 'Fakultas Teknik UNSOED';

        // Audit Log Recording
        AuditLogService::log(
            'EXPORT_XLSX_REPORT',
            ReportController::class,
            null,
            null,
            [
                'format' => 'XLSX',
                'department' => $selectedDepartment?->code ?? 'FACULTY',
                'actor' => $user?->name,
            ]
        );

        $filename = 'SIKARA_Laporan_Realisasi_Tabular_'.date('Ymd_His').'.xls';

        $rowsHtml = '';
        $totAlloc = 0;
        $totRes = 0;
        $totReal = 0;
        $totAvail = 0;

        foreach ($buckets as $idx => $b) {
            $no = $idx + 1;
            $alloc = (float) $b->allocated_budget;
            $res = (float) $b->reserved_budget;
            $real = (float) $b->realized_budget;
            $avail = (float) $b->available_balance;
            $rate = $alloc > 0 ? round(($real / $alloc) * 100, 1) : 0;

            $totAlloc += $alloc;
            $totRes += $res;
            $totReal += $real;
            $totAvail += $avail;

            $rowsHtml .= "<tr>
                <td align='center'>{$no}</td>
                <td>[{$b->account_code}] {$b->account_name}</td>
                <td>{$b->department?->code} - {$b->department?->name}</td>
                <td align='right'>".number_format($alloc, 0, ',', '.')."</td>
                <td align='right'>".number_format($res, 0, ',', '.')."</td>
                <td align='right'>".number_format($real, 0, ',', '.')."</td>
                <td align='right'>".number_format($avail, 0, ',', '.')."</td>
                <td align='center'>{$rate}%</td>
            </tr>";
        }

        $totSerapan = $totAlloc > 0 ? round(($totReal / $totAlloc) * 100, 1) : 0;

        $xlsContent = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
<style>
table { border-collapse: collapse; width: 100%; font-family: Calibri, sans-serif; font-size: 11pt; }
th { background-color: #0284c7; color: #ffffff; border: 1px solid #000000; padding: 6px; font-weight: bold; }
td { border: 1px solid #000000; padding: 5px; }
.total { background-color: #f1f5f9; font-weight: bold; }
</style>
</head>
<body>
<h3>LAPORAN REALISASI ANGGARAN & PENGENDALIAN BELANJA (LRA)</h3>
<p>Unit: {$deptName} | Tanggal Ekspor: ".date('d F Y H:i')."</p>
<table>
<thead>
<tr>
<th>No</th>
<th>Kode & Nama Akun</th>
<th>Kode & Nama Jurusan</th>
<th>Pagu Alokasi (Rp)</th>
<th>Komitmen Reserved (Rp)</th>
<th>Realisasi Definitif (Rp)</th>
<th>Saldo Bebas Available (Rp)</th>
<th>Serapan (%)</th>
</tr>
</thead>
<tbody>
{$rowsHtml}
<tr class='total'>
<td colspan='3' align='right'>TOTAL KESELURUHAN:</td>
<td align='right'>".number_format($totAlloc, 0, ',', '.')."</td>
<td align='right'>".number_format($totRes, 0, ',', '.')."</td>
<td align='right'>".number_format($totReal, 0, ',', '.')."</td>
<td align='right'>".number_format($totAvail, 0, ',', '.')."</td>
<td align='center'>{$totSerapan}%</td>
</tr>
</tbody>
</table>
</body>
</html>";

        return response($xlsContent, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $selectedDepartment = $selectedDepartmentId ? Department::find($selectedDepartmentId) : null;

        // Audit Log Recording
        AuditLogService::log(
            'EXPORT_CSV_REPORT',
            ReportController::class,
            null,
            null,
            [
                'format' => 'CSV',
                'department' => $selectedDepartment?->code ?? 'FACULTY',
                'actor' => $user?->name,
            ]
        );

        $filename = 'SIKARA_Laporan_Realisasi_Anggaran_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($buckets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['No', 'Kode & Nama Akun', 'Kode & Nama Jurusan', 'Pagu Alokasi (Rp)', 'Komitmen Reserved (Rp)', 'Realisasi Definitif (Rp)', 'Saldo Bebas Available (Rp)', 'Serapan (%)', 'Utilization (%)']);

            foreach ($buckets as $index => $b) {
                $alloc = (float) $b->allocated_budget;
                $res = (float) $b->reserved_budget;
                $real = (float) $b->realized_budget;
                $avail = (float) $b->available_balance;
                $serapan = $alloc > 0 ? round(($real / $alloc) * 100, 1) : 0;
                $util = $alloc > 0 ? round((($real + $res) / $alloc) * 100, 1) : 0;

                fputcsv($file, [
                    $index + 1,
                    "[{$b->account_code}] {$b->account_name}",
                    "{$b->department?->code} - {$b->department?->name}",
                    $alloc,
                    $res,
                    $real,
                    $avail,
                    $serapan.'%',
                    $util.'%',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportDocx(Request $request)
    {
        $user = $request->user();
        $selectedDepartmentId = $request->query('department_id');

        $query = BudgetBucket::with(['department', 'fundingSource', 'fiscalYear']);
        ScopeService::applyDepartmentScope($query, $user, $selectedDepartmentId);

        $buckets = $query->orderBy('account_code')->get();
        $selectedDepartment = $selectedDepartmentId ? Department::find($selectedDepartmentId) : null;
        $deptName = $selectedDepartment ? "{$selectedDepartment->code} - {$selectedDepartment->name}" : 'Fakultas Teknik UNSOED';

        // Audit Log Recording
        AuditLogService::log(
            'EXPORT_DOCX_REPORT',
            ReportController::class,
            null,
            null,
            [
                'format' => 'DOCX',
                'department' => $selectedDepartment?->code ?? 'FACULTY',
                'actor' => $user?->name,
            ]
        );

        $filename = 'SIKARA_Laporan_Realisasi_Naratif_'.date('Ymd_His').'.doc';

        $totalAlloc = (float) $buckets->sum('allocated_budget');
        $totalReal = (float) $buckets->sum('realized_budget');
        $totalRes = (float) $buckets->sum('reserved_budget');
        $totalAvail = (float) $buckets->sum('available_balance');
        $serapan = $totalAlloc > 0 ? round(($totalReal / $totalAlloc) * 100, 1) : 0;

        $rowsHtml = '';
        foreach ($buckets as $idx => $b) {
            $no = $idx + 1;
            $alloc = 'Rp '.number_format($b->allocated_budget, 0, ',', '.');
            $real = 'Rp '.number_format($b->realized_budget, 0, ',', '.');
            $avail = 'Rp '.number_format($b->available_balance, 0, ',', '.');
            $rate = $b->allocated_budget > 0 ? round(($b->realized_budget / $b->allocated_budget) * 100, 1) : 0;

            $rowsHtml .= "<tr><td align='center'>{$no}</td><td>[{$b->account_code}] {$b->account_name}</td><td>{$b->department?->code}</td><td align='right'>{$alloc}</td><td align='right'>{$real}</td><td align='right'>{$avail}</td><td align='center'>{$rate}%</td></tr>";
        }

        $docContent = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head><title>Laporan Realisasi Anggaran</title>
<style>
body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.3; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid black; padding: 5px; font-size: 10pt; }
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

<h3 align='center'><u>LAPORAN REALISASI & PENGENDALIAN ANGGARAN (LRA)</u><br/><small>Unit: {$deptName} | Tahun Anggaran 2026</small></h3>

<p><b>Ringkasan Eksekutif:</b><br/>
Total Alokasi Pagu: <b>Rp ".number_format($totalAlloc, 0, ',', '.').'</b><br/>
Total Realisasi Belanja Definitif: <b>Rp '.number_format($totalReal, 0, ',', '.')." ({$serapan}%)</b><br/>
Total Komitmen Dalam Proses: <b>Rp ".number_format($totalRes, 0, ',', '.').'</b><br/>
Sisa Saldo Anggaran Bebas: <b>Rp '.number_format($totalAvail, 0, ',', '.')."</b>
</p>

<h4>Tabel Rincian Realisasi per Mata Anggaran:</h4>
<table>
<thead>
<tr bgcolor='#f2f2f2'>
<th>No</th><th>Mata Anggaran (Akun)</th><th>Jurusan</th><th>Pagu Alokasi</th><th>Realisasi Definitif</th><th>Saldo Bebas</th><th>% Serapan</th>
</tr>
</thead>
<tbody>
{$rowsHtml}
</tbody>
</table>

<br/><br/>
<table style='border: none;'>
<tr style='border: none;'>
<td style='border: none;' width='50%' align='center'>
Mengetahui,<br/>
Wakil Dekan Bidang Perencanaan & Keuangan<br/><br/><br/><br/>
( ____________________ )<br/>
NIP. .........................
</td>
<td style='border: none;' width='50%' align='center'>
Purwokerto, ".date('d F Y').'<br/>
Kepala Bagian Tata Usaha / PPK<br/><br/><br/><br/>
( ____________________ )<br/>
NIP. .........................
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
