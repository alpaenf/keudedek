<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\StudyProgram;
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
use PhpOffice\PhpSpreadsheet\IOFactory;

class SubmissionImportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = SubmissionImportBatch::with('user');

        if ($user && in_array($user->role, ['PTK', 'KAJUR'])) {
            $query->where('user_id', $user->id);
        }

        $batches = $query->latest()->paginate(10);
        $departments = ScopeService::getSelectableDepartments($user);
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first()?->year ?? 2026;
        $activeFundingSource = FundingSource::where('code', 'RM')->first() ?? FundingSource::first();

        return Inertia::render('Submissions/Import', [
            'batches' => $batches,
            'departments' => $departments,
            'activeFiscalYear' => $activeFiscalYear,
            'activeFundingSource' => $activeFundingSource,
            'userDepartmentId' => $user?->department_id,
            'userRole' => $user?->role === 'WD' ? 'WAKIL_DEKAN' : ($user?->role ?? 'GUEST'),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480', // 20MB max
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $path = $file->getRealPath();
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        $parsedRows = [];

        // Support CSV / TXT / TSV or Excel (XLSX, XLS, ODS)
        if (in_array($extension, ['csv', 'txt', 'tsv'])) {
            $delimiter = $extension === 'tsv' ? "\t" : ',';
            $fileHandle = fopen($path, 'r');
            // Check BOM
            $bom = fread($fileHandle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($fileHandle);
            }
            while (($row = fgetcsv($fileHandle, 0, $delimiter)) !== false) {
                if (! empty(array_filter($row))) {
                    $parsedRows[] = $row;
                }
            }
            fclose($fileHandle);
        } else {
            // Excel / OpenDocument format
            try {
                $spreadsheet = IOFactory::load($path);
                $worksheet = $spreadsheet->getActiveSheet();
                $rawRows = $worksheet->toArray(null, true, true, false);
                foreach ($rawRows as $r) {
                    if (! empty(array_filter($r, fn ($v) => $v !== null && trim($v) !== ''))) {
                        $parsedRows[] = array_map(fn ($v) => is_null($v) ? '' : (string) $v, $r);
                    }
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal membaca file spreadsheet: '.$e->getMessage());
            }
        }

        if (count($parsedRows) < 2) {
            return redirect()->back()->with('error', 'File tidak memiliki data transaksi yang dapat diproses (minimal 1 baris header dan 1 baris data).');
        }

        $rawHeader = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($parsedRows));

        // Smart Header Column Mapping Index
        $colMap = [
            'evidence_number' => -1,
            'transaction_date' => -1,
            'title' => -1,
            'amount' => -1,
            'account_code' => -1,
            'department_code' => -1,
            'subcomponent_code' => -1,
            'study_program_code' => -1,
            'budget_control_key' => -1,
            'notes' => -1,
        ];

        foreach ($rawHeader as $idx => $headerName) {
            $cleaned = preg_replace('/[^a-z0-9_]/', '_', $headerName);

            if ($colMap['evidence_number'] === -1 && (str_contains($cleaned, 'bukti') || str_contains($cleaned, 'kuitansi') || str_contains($cleaned, 'referensi') || str_contains($cleaned, 'nomor') || str_contains($cleaned, 'no_'))) {
                $colMap['evidence_number'] = $idx;
            } elseif ($colMap['transaction_date'] === -1 && (str_contains($cleaned, 'tanggal') || str_contains($cleaned, 'tgl') || str_contains($cleaned, 'date'))) {
                $colMap['transaction_date'] = $idx;
            } elseif ($colMap['title'] === -1 && (str_contains($cleaned, 'uraian') || str_contains($cleaned, 'judul') || str_contains($cleaned, 'kegiatan') || str_contains($cleaned, 'deskripsi') || str_contains($cleaned, 'belanja'))) {
                $colMap['title'] = $idx;
            } elseif ($colMap['amount'] === -1 && (str_contains($cleaned, 'nominal') || str_contains($cleaned, 'jumlah') || str_contains($cleaned, 'total') || str_contains($cleaned, 'amount') || str_contains($cleaned, 'biaya') || str_contains($cleaned, 'harga'))) {
                $colMap['amount'] = $idx;
            } elseif ($colMap['account_code'] === -1 && (str_contains($cleaned, 'akun') || str_contains($cleaned, 'mak') || str_contains($cleaned, 'account'))) {
                $colMap['account_code'] = $idx;
            } elseif ($colMap['department_code'] === -1 && (str_contains($cleaned, 'jurusan') || str_contains($cleaned, 'unit') || str_contains($cleaned, 'dept'))) {
                $colMap['department_code'] = $idx;
            } elseif ($colMap['subcomponent_code'] === -1 && (str_contains($cleaned, 'subkomponen') || str_contains($cleaned, 'sub_komp') || str_contains($cleaned, 'kro') || str_contains($cleaned, 'ro'))) {
                $colMap['subcomponent_code'] = $idx;
            } elseif ($colMap['study_program_code'] === -1 && (str_contains($cleaned, 'prodi') || str_contains($cleaned, 'program_studi'))) {
                $colMap['study_program_code'] = $idx;
            } elseif ($colMap['budget_control_key'] === -1 && (str_contains($cleaned, 'control') || str_contains($cleaned, 'key') || str_contains($cleaned, 'pos'))) {
                $colMap['budget_control_key'] = $idx;
            } elseif ($colMap['notes'] === -1 && (str_contains($cleaned, 'catatan') || str_contains($cleaned, 'ket') || str_contains($cleaned, 'notes'))) {
                $colMap['notes'] = $idx;
            }
        }

        // Fallback default index if standard headers were omitted
        if ($colMap['evidence_number'] === -1) {
            $colMap['evidence_number'] = 0;
        }
        if ($colMap['transaction_date'] === -1) {
            $colMap['transaction_date'] = 1;
        }
        if ($colMap['title'] === -1) {
            $colMap['title'] = 2;
        }
        if ($colMap['amount'] === -1) {
            $colMap['amount'] = 3;
        }
        if ($colMap['account_code'] === -1) {
            $colMap['account_code'] = 4;
        }
        if ($colMap['department_code'] === -1 && count($rawHeader) > 5) {
            $colMap['department_code'] = 5;
        }

        // Master Reference Caches
        $departments = Department::all()->keyBy(fn ($d) => strtoupper($d->code));
        $studyPrograms = StudyProgram::all()->keyBy(fn ($sp) => strtoupper($sp->code));
        $activeFy = FiscalYear::where('status', 'ACTIVE')->first() ?? FiscalYear::first();
        $defaultDept = $user->department ?? Department::first();

        // Create Staging Batch
        $batchNumber = 'SIB-'.date('Ymd-His').'-'.rand(100, 999);
        $batch = SubmissionImportBatch::create([
            'batch_number' => $batchNumber,
            'user_id' => $user->id,
            'total_rows' => count($parsedRows),
            'status' => 'PENDING',
        ]);

        $validCount = 0;
        $invalidCount = 0;
        $seenEvidenceNumbersInFile = [];

        foreach ($parsedRows as $index => $row) {
            $rowNumber = $index + 2;

            $evidenceNo = trim($row[$colMap['evidence_number']] ?? '');
            $rawDate = trim($row[$colMap['transaction_date']] ?? '');
            $title = trim($row[$colMap['title']] ?? '');
            $rawAmount = trim($row[$colMap['amount']] ?? '0');
            $accCode = trim($row[$colMap['account_code']] ?? '');
            $deptStr = strtoupper(trim($colMap['department_code'] !== -1 ? ($row[$colMap['department_code']] ?? '') : ''));
            $subcompStr = trim($colMap['subcomponent_code'] !== -1 ? ($row[$colMap['subcomponent_code']] ?? '') : '');
            $prodiStr = strtoupper(trim($colMap['study_program_code'] !== -1 ? ($row[$colMap['study_program_code']] ?? '') : ''));
            $controlKey = trim($colMap['budget_control_key'] !== -1 ? ($row[$colMap['budget_control_key']] ?? '') : '');
            $notes = trim($colMap['notes'] !== -1 ? ($row[$colMap['notes']] ?? '') : '');

            // Clean numeric amount
            $cleanAmount = preg_replace('/[^0-9.]/', '', str_replace(',', '.', str_replace('.', '', $rawAmount)));
            $amount = (float) $cleanAmount;

            // Parse Date
            $parsedDate = date('Y-m-d');
            if (! empty($rawDate)) {
                $time = strtotime($rawDate);
                if ($time !== false) {
                    $parsedDate = date('Y-m-d', $time);
                }
            }

            // Auto-generate evidence number if empty
            if (empty($evidenceNo)) {
                $evidenceNo = 'BKT/'.($deptStr ?: ($defaultDept->code ?? 'FT')).'/'.date('Y').'/'.date('m').'/'.str_pad($rowNumber, 3, '0', STR_PAD_LEFT);
            }

            $errors = [];
            $duplicateStatus = 'NONE';

            // Resolve Department
            $department = null;
            if (! empty($deptStr) && isset($departments[$deptStr])) {
                $department = $departments[$deptStr];
            } else {
                $department = $defaultDept;
            }

            if (! ScopeService::canAccessDepartment($user, $department->id)) {
                $errors[] = "Akses ditolak: Anda tidak memiliki izin mengimpor transaksi untuk Jurusan {$department->code}.";
            }

            // Resolve Study Program (Optional)
            $studyProgram = null;
            if (! empty($prodiStr) && isset($studyPrograms[$prodiStr])) {
                $studyProgram = $studyPrograms[$prodiStr];
            }

            // Basic Field Validation
            if (empty($title)) {
                $errors[] = 'Uraian belanja transaksi wajib diisi.';
            }
            if ($amount <= 0) {
                $errors[] = 'Nominal transaksi harus lebih besar dari Rp 0.';
            }
            if (empty($accCode)) {
                $errors[] = 'Kode akun belanja (misal: 521211) wajib diisi.';
            }

            // Duplicate Detection
            // 1. Duplicate in file
            if (isset($seenEvidenceNumbersInFile[$evidenceNo])) {
                $duplicateStatus = 'DUPLICATE_IN_FILE';
                $errors[] = "Duplikasi Berkas: Nomor bukti '{$evidenceNo}' ditemukan ganda pada baris {$seenEvidenceNumbersInFile[$evidenceNo]} dan baris {$rowNumber}.";
            } else {
                $seenEvidenceNumbersInFile[$evidenceNo] = $rowNumber;
            }

            // 2. Duplicate in database
            $dbDuplicate = Submission::where('evidence_number', $evidenceNo)
                ->orWhere('submission_number', $evidenceNo)
                ->exists();

            if ($dbDuplicate) {
                $duplicateStatus = 'DUPLICATE_IN_DB';
                $errors[] = "Duplikasi Sistem: Nomor bukti '{$evidenceNo}' sudah terdaftar dalam database transaksi sebelumnya.";
            }

            // Master Budget Matching & Auto Hierarchy Resolution
            $matchedBucket = null;
            $matchedHierarchy = null;

            if (! empty($accCode) && $department) {
                $bucketQuery = BudgetBucket::where('department_id', $department->id)
                    ->where('account_code', $accCode);

                if (! empty($subcompStr)) {
                    $bucketQuery->where(function ($q) use ($subcompStr) {
                        $q->where('subcomponent_code', $subcompStr)
                            ->orWhere('subcomponent_name', 'like', "%{$subcompStr}%");
                    });
                }

                $matchedBucket = $bucketQuery->first();

                // If not found in specific department, try faculty level bucket
                if (! $matchedBucket) {
                    $matchedBucket = BudgetBucket::where('account_code', $accCode)->first();
                }

                if ($matchedBucket) {
                    // Resolve Full 7-Segment Hierarchy from Master Data automatically
                    $deptName = $department->name ?? 'Fakultas Teknik';
                    $matchedHierarchy = [
                        'ta' => $activeFy->year ?? 2026,
                        'sumber_dana' => 'RM',
                        'revision' => 'Rev 02',
                        'jurusan_code' => $department->code,
                        'jurusan_name' => $department->name,
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
                        'subcomponent_code' => $matchedBucket->subcomponent_code ?? 'AA',
                        'subcomponent_name' => $matchedBucket->subcomponent_name ?? "Operasional & Praktikum {$deptName}",
                        'account_code' => $matchedBucket->account_code,
                        'account_name' => $matchedBucket->account_name,
                        'subaccount_code' => $matchedBucket->account_code.'.001',
                        'subaccount_name' => 'Alokasi Operasional Standar Unit',
                        'available_balance' => (float) $matchedBucket->available_balance,
                        'is_auto_resolved' => true,
                    ];

                    // Solvency Budget Check (RBC-001)
                    if ($amount > $matchedBucket->available_balance) {
                        $errors[] = 'Peringatan Overbudget (RBC-001): Nominal (Rp '.number_format($amount, 0, ',', '.').') melebihi sisa saldo tersedia (Rp '.number_format($matchedBucket->available_balance, 0, ',', '.').').';
                    }
                } else {
                    $errors[] = "Pos anggaran dengan kode akun '{$accCode}' pada jurusan {$department->code} tidak ditemukan dalam Master Data Pagu Aktif.";
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
                'evidence_number' => $evidenceNo,
                'transaction_date' => $parsedDate,
                'reference_no' => $evidenceNo,
                'fiscal_year' => $activeFy->year ?? '2026',
                'department_code' => $department->code,
                'study_program_id' => $studyProgram?->id,
                'study_program_code' => $studyProgram?->code,
                'transaction_type_code' => 'UP',
                'title' => $title,
                'account_code' => $accCode,
                'subcomponent_code' => $subcompStr ?: null,
                'budget_control_key' => $controlKey ?: null,
                'matched_bucket_id' => $matchedBucket?->id,
                'matched_hierarchy' => $matchedHierarchy,
                'amount' => $amount,
                'beneficiary' => null,
                'notes' => $notes ?: null,
                'validation_status' => $isValid ? 'VALID' : 'INVALID',
                'duplicate_status' => $duplicateStatus,
                'error_messages' => $errors,
                'parsed_items' => [
                    [
                        'item_name' => $title,
                        'quantity' => 1,
                        'unit_price' => $amount,
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
            ->with('success', "Import staging berhasil. Ditemukan {$validCount} baris valid dan {$invalidCount} baris invalid/perlu perhatian.");
    }

    public function show(SubmissionImportBatch $batch): Response
    {
        $batch->load(['stagings', 'user']);

        $stagings = $batch->stagings;
        $totalRows = $stagings->count();
        $validRows = $stagings->where('validation_status', 'VALID')->count();
        $invalidRows = $stagings->where('validation_status', 'INVALID')->count();
        $duplicateRows = $stagings->whereIn('duplicate_status', ['DUPLICATE_IN_FILE', 'DUPLICATE_IN_DB'])->count();
        $unmatchedRows = $stagings->whereNull('matched_bucket_id')->count();

        $summary = [
            'total' => $totalRows,
            'valid' => $validRows,
            'invalid' => $invalidRows,
            'duplicate' => $duplicateRows,
            'unmatched' => $unmatchedRows,
            'is_ready_to_commit' => $validRows > 0,
        ];

        return Inertia::render('Submissions/ImportShow', [
            'batch' => $batch,
            'summary' => $summary,
            'stagings' => $stagings,
        ]);
    }

    public function commit(Request $request, SubmissionImportBatch $batch): RedirectResponse
    {
        $user = $request->user();

        if ($batch->status === 'COMMITTED') {
            return redirect()->back()->with('error', 'Batch import transaksi ini sudah pernah di-commit.');
        }

        $targetStatus = $request->input('target_status', 'PROCESSING'); // PROCESSING (default) or DRAFT

        $validStagings = $batch->stagings()->where('validation_status', 'VALID')->get();
        if ($validStagings->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada baris data valid yang siap untuk di-commit.');
        }

        DB::transaction(function () use ($batch, $validStagings, $user, $targetStatus) {
            $departments = Department::all()->keyBy('code');
            $activeFy = FiscalYear::where('status', 'ACTIVE')->first() ?? FiscalYear::first();
            $defaultTx = TransactionType::where('is_active', true)->first();

            foreach ($validStagings as $stg) {
                $dept = $departments[$stg->department_code] ?? $departments->first();
                $bucket = BudgetBucket::find($stg->matched_bucket_id);

                if (! $bucket) {
                    $bucket = BudgetBucket::where('department_id', $dept->id)
                        ->where('account_code', $stg->account_code)
                        ->first();
                }

                $subNumber = 'SUB/'.date('Y/m').'/'.str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

                $submission = Submission::create([
                    'submission_number' => $subNumber,
                    'evidence_number' => $stg->evidence_number ?: $stg->reference_no,
                    'transaction_date' => $stg->transaction_date ?: date('Y-m-d'),
                    'reference_no' => $stg->reference_no,
                    'title' => $stg->title,
                    'department_id' => $dept->id,
                    'study_program_id' => $stg->study_program_id,
                    'fiscal_year_id' => $activeFy->id,
                    'transaction_type_id' => $defaultTx?->id,
                    'budget_bucket_id' => $bucket?->id,
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
                            'item_name' => $item['item_name'] ?? $stg->title,
                            'quantity' => $item['quantity'] ?? 1,
                            'unit_price' => $item['unit_price'] ?? $stg->amount,
                            'total_price' => $item['total_price'] ?? $stg->amount,
                        ]);
                    }
                }

                // If target status is PROCESSING, update reserved balance
                if ($targetStatus === 'PROCESSING' && $bucket) {
                    $bucket->increment('reserved_budget', $stg->amount);
                    $bucket->update([
                        'available_balance' => $bucket->allocated_budget - $bucket->reserved_budget - $bucket->realized_budget,
                    ]);
                }

                SubmissionStatusHistory::create([
                    'submission_id' => $submission->id,
                    'from_status' => null,
                    'to_status' => $targetStatus,
                    'actor_id' => $user->id,
                    'role' => $user->role,
                    'notes' => "Diimpor masal via staging batch {$batch->batch_number}.",
                ]);
            }

            $batch->update(['status' => 'COMMITTED']);

            AuditLogService::log(
                'COMMIT_BULK_SUBMISSION_IMPORT',
                SubmissionImportBatch::class,
                $batch->id,
                null,
                ['count' => count($validStagings), 'status' => $targetStatus]
            );
        });

        return redirect()->route('submissions.index')
            ->with('success', "Berhasil me-commit {$validStagings->count()} transaksi ke dalam status {$targetStatus}.");
    }

    public function downloadTemplate()
    {
        $filename = 'Template_Import_Transaksi_PTK_SIKARA.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // 1. Header with minimal columns & optional helpers
            fputcsv($file, [
                'NOMOR_BUKTI',
                'TANGGAL',
                'URAIAN',
                'NOMINAL',
                'KODE_AKUN',
                'JURUSAN',
                'SUBKOMPONEN',
                'PRODI',
                'BUDGET_CONTROL_KEY',
                'CATATAN',
            ]);

            // 2. Sample Data Row 1 (Informatika)
            fputcsv($file, [
                'BKT/IF/2026/08/001',
                '2026-08-15',
                'Belanja Komponen Elektronik Praktikum Robotika',
                '4500000',
                '521211',
                'JTIF',
                'AA',
                'IF',
                'POS-IF-01',
                'Praktikum Semester Ganjil',
            ]);

            // 3. Sample Data Row 2 (Teknik Sipil)
            fputcsv($file, [
                'BKT/TS/2026/08/002',
                '2026-08-20',
                'Belanja Pengujian Kuat Tekan Beton Laboratorium Struktur',
                '7800000',
                '521211',
                'JTS',
                'AA',
                'TS',
                'POS-TS-01',
                'Laboratorium Mekanika Tanah & Struktur',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
