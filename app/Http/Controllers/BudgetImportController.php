<?php

namespace App\Http\Controllers;

use App\Models\BudgetBucket;
use App\Models\BudgetImportStaging;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\ImportHistory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BudgetImportController extends Controller
{
    public function index(): Response
    {
        $histories = ImportHistory::with('user')->latest()->paginate(10);

        return Inertia::render('Budgets/Import', [
            'histories' => $histories,
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,pdf,doc,docx,xls,xlsx|max:15360',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $path = $file->getRealPath();

        $history = ImportHistory::create([
            'user_id' => auth()->id() ?? 1,
            'filename' => $filename,
            'status' => 'PENDING',
        ]);

        $handle = fopen($path, 'r');
        // Remove UTF-8 BOM if present
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
            $firstLine = substr($firstLine, 3);
        }
        rewind($handle);

        $header = fgetcsv($handle, 2000, ',');
        // Fallback for semicolon separated CSVs
        if (count($header) === 1 && str_contains($header[0], ';')) {
            rewind($handle);
            $header = fgetcsv($handle, 2000, ';');
            $delimiter = ';';
        } else {
            $delimiter = ',';
        }

        $validCount = 0;
        $invalidCount = 0;
        $totalCount = 0;

        $departments = Department::all()->keyBy('code');
        $fiscalYears = FiscalYear::all()->keyBy('year');
        $fundingSources = FundingSource::all()->keyBy('code');

        // Check if header matches SIMAPAN 23-column schema
        $isSimapanSchema = (count($header) >= 19 && (str_contains(strtolower($header[3] ?? ''), 'unit') || str_contains(strtolower($header[16] ?? ''), 'akun')));

        while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
            if (empty(array_filter($data))) {
                continue;
            }

            $totalCount++;
            $errors = [];

            if ($isSimapanSchema) {
                // SIMAPAN 23-column format mapping
                $year = (int) trim($data[1] ?? 2026);
                $unitName = trim($data[3] ?? '');
                $subcomponentCode = trim($data[14] ?? '');
                $subcomponentName = trim($data[15] ?? '');
                $accountCode = trim($data[16] ?? '');
                $accountName = trim($data[17] ?? '');
                $initialBudget = (float) str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], trim($data[18] ?? '0'));
                $fundingCode = trim($data[21] ?? 'RM');

                // Map unitName to Department code (JTIF, JTS, JTE, JTG, JTI or FT)
                $deptCode = 'FT';
                foreach ($departments as $code => $dept) {
                    if (str_contains(strtolower($unitName), strtolower($code)) || str_contains(strtolower($unitName), strtolower($dept->name))) {
                        $deptCode = $code;
                        break;
                    }
                }
            } else {
                // Standard 6-column compact format
                $deptCode = trim($data[0] ?? '');
                $year = (int) trim($data[1] ?? 2026);
                $fundingCode = trim($data[2] ?? '');
                $accountCode = trim($data[3] ?? '');
                $accountName = trim($data[4] ?? '');
                $initialBudget = (float) str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], trim($data[5] ?? '0'));
                $subcomponentCode = '';
                $subcomponentName = '';
            }

            if (! isset($departments[$deptCode])) {
                $errors[] = "Kode Jurusan '{$deptCode}' tidak ditemukan di master data.";
            }

            if (! isset($fiscalYears[$year])) {
                $errors[] = "Tahun Anggaran '{$year}' belum terdaftar.";
            }

            if (! isset($fundingSources[$fundingCode])) {
                $fundingCode = 'RM'; // fallback to Rupiah Murni
            }

            if (empty($accountCode)) {
                $errors[] = 'Kode akun wajib diisi.';
            }

            if ($initialBudget <= 0) {
                $errors[] = 'Nominal pagu awal harus lebih besar dari 0.';
            }

            $status = empty($errors) ? 'VALID' : 'INVALID';
            if ($status === 'VALID') {
                $validCount++;
            } else {
                $invalidCount++;
            }

            BudgetImportStaging::create([
                'import_history_id' => $history->id,
                'department_code' => $deptCode,
                'fiscal_year' => $year,
                'funding_source_code' => $fundingCode,
                'account_code' => $accountCode,
                'account_name' => $accountName,
                'initial_budget' => $initialBudget,
                'status' => $status,
                'error_message' => implode(' | ', $errors),
            ]);
        }

        fclose($handle);

        $history->update([
            'total_rows' => $totalCount,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
        ]);

        AuditLogService::log('UPLOAD_BUDGET_IMPORT', ImportHistory::class, $history->id, null, [
            'filename' => $filename,
            'total_rows' => $totalCount,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
        ]);

        return redirect()->route('budgets.import.show', $history)
            ->with('success', "Berkas {$filename} (".($isSimapanSchema ? 'Schema SIMAPAN 23 Kolom' : 'Schema Compact').') berhasil diunggah dan masuk ke tabel Staging Validation.');
    }

    public function show(ImportHistory $importHistory): Response
    {
        $stagings = $importHistory->stagings()->paginate(20);

        return Inertia::render('Budgets/ImportShow', [
            'history' => $importHistory->load('user'),
            'stagings' => $stagings,
        ]);
    }

    public function commit(ImportHistory $importHistory): RedirectResponse
    {
        if ($importHistory->status === 'COMMITTED') {
            return redirect()->back()->with('error', 'Batch import ini sudah pernah dicommit.');
        }

        if ($importHistory->invalid_rows > 0) {
            return redirect()->back()->with('error', 'Batch import masih memiliki baris data bermasalah (Invalid). Harap perbaiki berkas terlebih dahulu.');
        }

        DB::transaction(function () use ($importHistory) {
            $validStagings = $importHistory->stagings()->where('status', 'VALID')->get();
            $departments = Department::all()->keyBy('code');
            $fiscalYears = FiscalYear::all()->keyBy('year');
            $fundingSources = FundingSource::all()->keyBy('code');

            foreach ($validStagings as $stg) {
                $dept = $departments[$stg->department_code] ?? $departments['JTIF'];
                $fy = $fiscalYears[$stg->fiscal_year] ?? $fiscalYears->first();
                $fs = $fundingSources[$stg->funding_source_code] ?? $fundingSources['RM'];

                BudgetBucket::updateOrCreate(
                    [
                        'fiscal_year_id' => $fy->id,
                        'department_id' => $dept->id,
                        'account_code' => $stg->account_code,
                    ],
                    [
                        'funding_source_id' => $fs->id,
                        'account_name' => $stg->account_name,
                        'initial_budget' => $stg->initial_budget,
                        'allocated_budget' => $stg->initial_budget,
                        'available_balance' => $stg->initial_budget,
                    ]
                );
            }

            $importHistory->update(['status' => 'COMMITTED']);

            AuditLogService::log('COMMIT_BUDGET_IMPORT', ImportHistory::class, $importHistory->id, null, [
                'imported_count' => count($validStagings),
            ]);
        });

        return redirect()->route('budgets.index')
            ->with('success', "Berhasil me-commit {$importHistory->valid_rows} pos alokasi anggaran ke basis data aktif.");
    }

    public function downloadTemplate(Request $request)
    {
        $type = $request->query('schema', 'simapan');

        if ($type === 'simapan') {
            $filename = 'Template_Import_SIMAPAN_23Kolom_SIKARA.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                // Sheet 35: Export_SIMAPAN_Schema (23 official columns)
                fputcsv($file, [
                    'No', 'Tahun', 'Revisi ke', 'Nama Unit', 'Kode Program', 'Nama Program',
                    'Kode Kegiatan', 'Nama Kegiatan', 'Kode KRO', 'Nama KRO',
                    'Kode RO', 'Nama RO', 'Kode Komponen', 'Nama Komponen',
                    'Kode SubKomponen', 'Nama SubKomponen', 'Kode Akun', 'Nama Akun',
                    'Alokasi', 'Realisasi', 'Sisa Pagu', 'Sumber Dana', 'Keterangan',
                ]);
                fputcsv($file, [
                    '1', '2026', '0', 'Jurusan Teknik Informatika', 'P01', 'Program Pendidikan Tinggi',
                    '4257', 'Penyelenggaraan Pendidikan', 'EBA', 'Layanan Pendidikan Informatika',
                    '994', 'Layanan Akademik Informatika', '001', 'Operasional Perkuliahan Informatika',
                    '4257.EBA.994.001.AA', 'Praktikum & Laboratorium Informatika', '521111', 'Belanja Bahan Praktek Laboratorium Informatika',
                    '75000000', '0', '75000000', 'RM', 'Alokasi Pagu Murni TA 2026',
                ]);
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $filename = 'Template_Import_Pagu_Compact_SIKARA.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['KODE_JURUSAN', 'TAHUN', 'KODE_SUMBER', 'KODE_AKUN', 'NAMA_AKUN', 'PAGU_AWAL']);
            fputcsv($file, ['JTIF', '2026', 'RM', '521111', 'Belanja Bahan Praktek Laboratorium Informatika', '75000000']);
            fputcsv($file, ['JTE', '2026', 'RM', '521112', 'Belanja Perlengkapan Komputer Elektro', '60000000']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
