<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Submission;
use App\Models\SubmissionImportBatch;
use App\Models\SubmissionImportStaging;
use App\Models\SubmissionItem;
use App\Models\SubmissionStatusHistory;
use App\Models\TransactionType;
use App\Services\AuditLogService;
use App\Services\ScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionImportController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $query = SubmissionImportBatch::with('user');

        if ($user && in_array($user->role, ['PTK', 'KAJUR'])) {
            $query->where('user_id', $user->id);
        }

        $batches = $query->latest()->paginate(10);

        return Inertia::render('Submissions/Import', [
            'batches' => $batches,
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,pdf,doc,docx,xls,xlsx|max:15360',
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->getRealPath();

        $rows = array_map('str_getcsv', file($path));
        if (empty($rows) || count($rows) < 2) {
            return redirect()->back()->with('error', 'File CSV kosong atau tidak memiliki baris data.');
        }

        $header = array_map('trim', array_shift($rows));

        // Master lookups
        $departments = Department::all()->keyBy('code');
        $fiscalYears = FiscalYear::all()->keyBy('year');
        $transactionTypes = TransactionType::all()->keyBy('code');

        $batchNumber = 'SIB-'.date('Ymd-His').'-'.rand(10, 99);
        $batch = SubmissionImportBatch::create([
            'batch_number' => $batchNumber,
            'user_id' => $user->id,
            'total_rows' => count($rows),
            'status' => 'PENDING',
        ]);

        $validCount = 0;
        $invalidCount = 0;

        foreach ($rows as $index => $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $rowNumber = $index + 2;
            $refNo = trim($row[0] ?? '');
            $year = trim($row[1] ?? '2026');
            $deptCode = trim($row[2] ?? '');
            $txCode = strtoupper(trim($row[3] ?? 'LS'));
            $title = trim($row[4] ?? '');
            $accCode = trim($row[5] ?? '');
            $amount = (float) str_replace(['.', ','], ['', ''], trim($row[6] ?? '0'));
            $beneficiary = trim($row[7] ?? '');
            $notes = trim($row[8] ?? '');
            $itemName = trim($row[9] ?? $title);
            $qty = (int) (trim($row[10] ?? '1') ?: 1);

            $errors = [];

            // Validation rules
            if (! isset($departments[$deptCode])) {
                $errors[] = "Kode Jurusan '{$deptCode}' tidak terdaftar di master data.";
            } elseif (! ScopeService::canAccessDepartment($user, $departments[$deptCode]->id)) {
                $errors[] = "Anda tidak memiliki izin mengimport usulan untuk jurusan {$deptCode}.";
            }

            if (! isset($fiscalYears[$year])) {
                $errors[] = "Tahun Anggaran '{$year}' tidak valid.";
            }

            if (empty($title)) {
                $errors[] = 'Judul / nama usulan kegiatan wajib diisi.';
            }

            if ($amount <= 0) {
                $errors[] = 'Nominal pengajuan harus lebih besar dari Rp 0.';
            }

            // Check Budget Bucket
            $dept = $departments[$deptCode] ?? null;
            $fy = $fiscalYears[$year] ?? null;
            $bucket = null;

            if ($dept && $fy && ! empty($accCode)) {
                $bucket = BudgetBucket::where('department_id', $dept->id)
                    ->where('fiscal_year_id', $fy->id)
                    ->where('account_code', $accCode)
                    ->first();

                if (! $bucket) {
                    $errors[] = "Pos anggaran dengan kode akun '{$accCode}' pada jurusan {$deptCode} tidak ditemukan.";
                } elseif ($bucket->available_balance < $amount) {
                    $errors[] = 'Peringatan RBC-001: Nominal (Rp '.number_format($amount, 0, ',', '.').') melebihi sisa saldo bebas (Rp '.number_format($bucket->available_balance, 0, ',', '.').').';
                }
            }

            // Duplicate ref check
            if (! empty($refNo)) {
                $duplicate = Submission::where('reference_no', $refNo)->exists();
                if ($duplicate) {
                    $errors[] = "Peringatan RBC-006: Nomor referensi '{$refNo}' sudah terdaftar sebelumnya.";
                }
            }

            $isValid = empty($errors);
            if ($isValid) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            SubmissionImportStaging::create([
                'batch_id' => $batch->id,
                'row_number' => $rowNumber,
                'reference_no' => $refNo ?: null,
                'fiscal_year' => $year,
                'department_code' => $deptCode,
                'transaction_type_code' => $txCode,
                'title' => $title,
                'account_code' => $accCode,
                'amount' => $amount,
                'beneficiary' => $beneficiary ?: null,
                'notes' => $notes ?: null,
                'validation_status' => $isValid ? 'VALID' : 'INVALID',
                'error_messages' => $errors,
                'parsed_items' => [
                    [
                        'item_name' => $itemName ?: $title,
                        'quantity' => $qty,
                        'unit_price' => $qty > 0 ? ($amount / $qty) : $amount,
                        'total_price' => $amount,
                    ],
                ],
            ]);
        }

        $batch->update([
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
        ]);

        return redirect()->route('submissions.import.show', $batch)
            ->with('success', "Upload batch berhasil. Ditemukan {$validCount} baris valid dan {$invalidCount} baris invalid.");
    }

    public function show(SubmissionImportBatch $batch): Response
    {
        $batch->load(['stagings', 'user']);

        return Inertia::render('Submissions/ImportShow', [
            'batch' => $batch,
        ]);
    }

    public function commit(Request $request, SubmissionImportBatch $batch): RedirectResponse
    {
        $user = $request->user();

        if ($batch->status === 'COMMITTED') {
            return redirect()->back()->with('error', 'Batch import ini sudah pernah di-commit.');
        }

        $targetStatus = $request->input('target_status', 'DRAFT'); // DRAFT or SUBMITTED

        $validStagings = $batch->stagings()->where('validation_status', 'VALID')->get();
        if ($validStagings->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada baris valid untuk di-commit.');
        }

        DB::transaction(function () use ($batch, $validStagings, $user, $targetStatus) {
            $departments = Department::all()->keyBy('code');
            $fiscalYears = FiscalYear::all()->keyBy('year');
            $txTypes = TransactionType::all()->keyBy('code');

            foreach ($validStagings as $stg) {
                $dept = $departments[$stg->department_code];
                $fy = $fiscalYears[$stg->fiscal_year];
                $tx = $txTypes[$stg->transaction_type_code] ?? null;

                $bucket = BudgetBucket::where('department_id', $dept->id)
                    ->where('fiscal_year_id', $fy->id)
                    ->where('account_code', $stg->account_code)
                    ->first();

                $subNumber = 'SUB/'.date('Y/m').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

                $submission = Submission::create([
                    'submission_number' => $subNumber,
                    'reference_no' => $stg->reference_no,
                    'title' => $stg->title,
                    'department_id' => $dept->id,
                    'fiscal_year_id' => $fy->id,
                    'transaction_type_id' => $tx?->id,
                    'budget_bucket_id' => $bucket->id,
                    'amount' => $stg->amount,
                    'beneficiary_name' => $stg->beneficiary,
                    'status' => $targetStatus,
                    'created_by' => $user->id,
                    'notes' => $stg->notes,
                ]);

                if ($stg->parsed_items) {
                    foreach ($stg->parsed_items as $item) {
                        SubmissionItem::create([
                            'submission_id' => $submission->id,
                            'item_name' => $item['item_name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'total_price' => $item['total_price'],
                        ]);
                    }
                }

                SubmissionStatusHistory::create([
                    'submission_id' => $submission->id,
                    'from_status' => null,
                    'to_status' => $targetStatus,
                    'actor_id' => $user->id,
                    'role' => $user->role,
                    'notes' => "Diimport masal via batch {$batch->batch_number}.",
                ]);
            }

            $batch->update(['status' => 'COMMITTED']);

            AuditLogService::log(
                'COMMIT_SUBMISSION_IMPORT',
                SubmissionImportBatch::class,
                $batch->id,
                null,
                ['count' => count($validStagings), 'status' => $targetStatus]
            );
        });

        return redirect()->route('submissions.index')
            ->with('success', "Berhasil me-commit {$validStagings->count()} usulan pengajuan ke dalam status {$targetStatus}.");
    }

    public function downloadTemplate()
    {
        $filename = 'Template_Import_Pengajuan_SIKARA.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, [
                'NO_REFERENSI',
                'TAHUN',
                'KODE_JURUSAN',
                'METODE_TRANSAKSI',
                'JUDUL_KEGIATAN',
                'KODE_AKUN',
                'NOMINAL',
                'PENERIMA',
                'KETERANGAN',
                'URAIAN_ITEM',
                'VOLUME',
            ]);
            fputcsv($file, [
                'UN23.FT.IF/KU/2026/101',
                '2026',
                'JTIF',
                'LS',
                'Pengadaan Modul Praktikum Cloud Computing',
                '521111',
                '15000000',
                'CV Edukasi Teknologi',
                'Keperluan perkuliahan semester ganjil',
                'Paket Akun Lisensi Cloud Server',
                '1',
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
