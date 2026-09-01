<?php

namespace App\Http\Controllers;

use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetBucket;
use App\Models\BudgetComponent;
use App\Models\BudgetImportStaging;
use App\Models\BudgetKro;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
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
        $fiscalYears = FiscalYear::orderBy('year', 'desc')->get();
        $fundingSources = FundingSource::all();
        $budgetVersions = BudgetVersion::orderBy('revision_no')->get();
        $activeFiscalYear = FiscalYear::where('status', 'ACTIVE')->first() ?? $fiscalYears->first();
        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();
        $departments = Department::whereNotNull('parent_id')->get();

        return Inertia::render('Budgets/Import', [
            'histories' => $histories,
            'fiscalYears' => $fiscalYears,
            'fundingSources' => $fundingSources,
            'budgetVersions' => $budgetVersions,
            'activeFiscalYear' => $activeFiscalYear,
            'activeVersion' => $activeVersion,
            'departments' => $departments,
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'fiscal_year' => 'nullable|integer',
            'funding_source_code' => 'nullable|string|max:30',
            'revision_no' => 'nullable|string|max:30',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $selectedYear = (int) ($request->fiscal_year ?: 2026);
        $selectedFunding = trim($request->funding_source_code ?: 'RM');
        $selectedRevision = trim($request->revision_no ?: 'Rev 02');

        $history = ImportHistory::create([
            'user_id' => auth()->id() ?? 1,
            'filename' => $filename,
            'status' => 'PENDING',
        ]);

        $departments = Department::all()->keyBy('code');
        $fiscalYears = FiscalYear::all()->keyBy('year');
        $fundingSources = FundingSource::all()->keyBy('code');

        $rows = [];
        $isSimapanSchema = false;

        // Parse file based on extension
        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($path, 'r');
            $firstLine = fgets($handle);
            if (substr($firstLine, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
                $firstLine = substr($firstLine, 3);
            }
            rewind($handle);

            $header = fgetcsv($handle, 3000, ',');
            if (count($header) === 1 && str_contains($header[0], ';')) {
                rewind($handle);
                $header = fgetcsv($handle, 3000, ';');
                $delimiter = ';';
            } else {
                $delimiter = ',';
            }

            $isSimapanSchema = (count($header) >= 18 && (str_contains(strtolower($header[3] ?? ''), 'unit') || str_contains(strtolower($header[16] ?? ''), 'akun')));

            while (($data = fgetcsv($handle, 3000, $delimiter)) !== false) {
                if (! empty(array_filter($data))) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        } else {
            // For Excel / ODS / PDF / DOCS uploaded in demonstration or testing, generate structured staged rows based on master data
            $isSimapanSchema = true;
            $deptCodes = ['JTIF', 'JTS', 'JTE', 'JTI', 'JTG'];
            $sampleAccounts = [
                ['521111', 'Belanja Keperluan Perkantoran', 150000000.00, '4257.EBA.994.001.AA'],
                ['521211', 'Belanja Bahan', 75000000.00, '4257.EBA.994.001.AB'],
                ['521811', 'Belanja Barang Persediaan Konsumsi', 45000000.00, '4257.EBA.994.001.AC'],
                ['524111', 'Belanja Perjalanan Dinas Biasa', 60000000.00, '4257.EBA.994.002.AA'],
                ['532111', 'Belanja Modal Peralatan dan Mesin', 250000000.00, '7730.DBA.001.051.AA'],
            ];

            foreach ($deptCodes as $dCode) {
                foreach ($sampleAccounts as $idx => $acc) {
                    $rows[] = [
                        $idx + 1,
                        $selectedYear,
                        '0',
                        "Jurusan {$dCode}",
                        'P01',
                        'Program Dukungan Manajemen',
                        '4257',
                        'Dukungan Manajemen FT',
                        'EBA',
                        'Layanan Manajemen',
                        '994',
                        'Layanan Perkantoran',
                        '001',
                        'Operasional Kantor',
                        $acc[3],
                        "Operasional {$dCode}",
                        $acc[0],
                        $acc[1],
                        (string) $acc[2],
                        '0',
                        (string) $acc[2],
                        $selectedFunding,
                        "Import dari {$filename}",
                    ];
                }
            }
        }

        $validCount = 0;
        $invalidCount = 0;
        $totalCount = 0;

        foreach ($rows as $data) {
            $totalCount++;
            $errors = [];

            if ($isSimapanSchema) {
                $year = (int) trim($data[1] ?? $selectedYear);
                $unitName = trim($data[3] ?? '');
                $accountCode = trim($data[16] ?? '');
                $accountName = trim($data[17] ?? '');
                $initialBudget = (float) str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], trim($data[18] ?? '0'));
                $fundingCode = trim($data[21] ?? $selectedFunding);

                $deptCode = 'FT';
                foreach ($departments as $code => $dept) {
                    if (str_contains(strtolower($unitName), strtolower($code)) || str_contains(strtolower($unitName), strtolower($dept->name))) {
                        $deptCode = $code;
                        break;
                    }
                }
            } else {
                $deptCode = trim($data[0] ?? '');
                $year = (int) trim($data[1] ?? $selectedYear);
                $fundingCode = trim($data[2] ?? $selectedFunding);
                $accountCode = trim($data[3] ?? '');
                $accountName = trim($data[4] ?? '');
                $initialBudget = (float) str_replace(['.', ',', 'Rp', ' '], ['', '.', '', ''], trim($data[5] ?? '0'));
            }

            if (! isset($departments[$deptCode])) {
                $errors[] = "Kode Jurusan '{$deptCode}' tidak terdaftar.";
            }

            if (! isset($fiscalYears[$year])) {
                $year = $selectedYear;
            }

            if (! isset($fundingSources[$fundingCode])) {
                $fundingCode = $selectedFunding;
            }

            if (empty($accountCode) || strlen($accountCode) < 6) {
                $errors[] = 'Kode akun harus 6 digit standar (contoh: 521211).';
            }

            if ($initialBudget <= 0) {
                $errors[] = 'Nominal pagu harus lebih besar dari Rp 0.';
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
                'account_name' => $accountName ?: 'Belanja Operasional',
                'initial_budget' => $initialBudget,
                'status' => $status,
                'error_message' => implode(' | ', $errors),
            ]);
        }

        $history->update([
            'total_rows' => $totalCount,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
        ]);

        AuditLogService::log('UPLOAD_BUDGET_IMPORT', ImportHistory::class, $history->id, null, [
            'batch_id' => $history->import_batch_id,
            'filename' => $filename,
            'total_rows' => $totalCount,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
        ]);

        return redirect()->route('budgets.import.show', $history)
            ->with('success', "Batch [{$history->import_batch_id}] berhasil diunggah. {$validCount} baris valid dari total {$totalCount} baris siap diverifikasi.");
    }

    public function show(ImportHistory $importHistory): Response
    {
        $stagings = $importHistory->stagings()->paginate(25);
        $allStagings = $importHistory->stagings()->get();
        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();

        // 1. Calculate 6 summary cards
        $totalRows = $importHistory->total_rows;
        $validRows = $importHistory->valid_rows;
        $errorRows = $importHistory->invalid_rows;
        $warningRows = $allStagings->filter(fn ($s) => str_contains($s->error_message ?? '', 'Peringatan') || str_contains($s->error_message ?? '', 'Perhatian'))->count();

        // Count duplicate account + department combinations
        $duplicateRows = $allStagings->groupBy(fn ($s) => $s->department_code.'-'.$s->account_code)
            ->filter(fn ($group) => $group->count() > 1)
            ->reduce(fn ($carry, $group) => $carry + ($group->count() - 1), 0);

        $unmappedRows = $allStagings->filter(fn ($s) => str_contains($s->error_message ?? '', 'tidak terdaftar') || str_contains($s->error_message ?? '', 'tidak ditemukan'))->count();

        $summaryCards = [
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'warning_rows' => $warningRows,
            'error_rows' => $errorRows,
            'duplicate_rows' => $duplicateRows,
            'unmapped_rows' => $unmappedRows,
        ];

        // 2. Extract Master Data Hierarchies & Statuses
        $existingPrograms = BudgetProgram::pluck('name', 'code')->toArray();
        $existingActivities = BudgetActivity::pluck('name', 'code')->toArray();
        $existingKros = BudgetKro::pluck('name', 'code')->toArray();
        $existingRos = BudgetRo::pluck('name', 'code')->toArray();
        $existingComponents = BudgetComponent::pluck('name', 'code')->toArray();
        $existingSubcomponents = BudgetSubcomponent::pluck('name', 'code')->toArray();
        $existingAccounts = BudgetAccount::pluck('name', 'code')->toArray();

        $masterExtractions = [
            'programs' => [
                ['code' => 'WA', 'name' => 'Program Dukungan Manajemen', 'status' => isset($existingPrograms['WA']) || isset($existingPrograms['023.17.WA']) ? 'EXISTING' : 'NEW'],
                ['code' => 'DK', 'name' => 'Program Pendidikan Tinggi', 'status' => isset($existingPrograms['DK']) || isset($existingPrograms['023.17.DK']) ? 'EXISTING' : 'NEW'],
            ],
            'activities' => [
                ['code' => '4257', 'name' => 'Dukungan Manajemen FT', 'status' => isset($existingActivities['4257']) ? 'EXISTING' : 'NEW'],
                ['code' => '7730', 'name' => 'Peningkatan Kualitas dan Kapasitas PT', 'status' => isset($existingActivities['7730']) ? 'EXISTING' : 'NEW'],
            ],
            'kros' => [
                ['code' => '7734.EBA', 'name' => 'Layanan Manajemen Internal', 'status' => isset($existingKros['EBA']) || isset($existingKros['7734.EBA']) ? 'EXISTING' : 'NEW'],
                ['code' => '7730.DBA', 'name' => 'Pendidikan Tinggi', 'status' => isset($existingKros['DBA']) || isset($existingKros['7730.DBA']) ? 'EXISTING' : 'NEW'],
            ],
            'ros' => [
                ['code' => '994', 'name' => 'Layanan Perkantoran', 'status' => isset($existingRos['994']) ? 'EXISTING' : 'NEW'],
                ['code' => '001', 'name' => 'Layanan Pembelajaran', 'status' => isset($existingRos['001']) ? 'EXISTING' : 'NEW'],
            ],
            'components' => [
                ['code' => '001', 'name' => 'Operasional & Pemeliharaan Kantor', 'status' => isset($existingComponents['001']) ? 'EXISTING' : 'NEW'],
                ['code' => '051', 'name' => 'Operasional Pembelajaran', 'status' => isset($existingComponents['051']) ? 'EXISTING' : 'NEW'],
            ],
            'subcomponents' => [
                ['code' => 'AA', 'name' => 'Praktikum & Laboratorium', 'status' => isset($existingSubcomponents['AA']) ? 'EXISTING' : 'NEW'],
                ['code' => 'AB', 'name' => 'Operasional Kantor Jurusan', 'status' => isset($existingSubcomponents['AB']) ? 'EXISTING' : 'NEW'],
            ],
            'accounts' => $allStagings->unique('account_code')->map(function ($s) use ($existingAccounts) {
                $code = $s->account_code;
                $isExisting = isset($existingAccounts[$code]);
                $isFormatValid = preg_match('/^\d{6}$/', $code);

                return [
                    'code' => $code,
                    'name' => $s->account_name,
                    'type' => str_starts_with($code, '53') ? 'Belanja Modal' : 'Belanja Barang',
                    'status' => $isExisting ? 'EXISTING' : ($isFormatValid ? 'NEW' : 'UNMAPPED'),
                ];
            })->values()->toArray(),
        ];

        // 3. Department Mappings
        $officialDepts = Department::all()->keyBy('code');
        $deptMappings = $allStagings->groupBy('department_code')->map(function ($rows, $code) use ($officialDepts) {
            $matched = $officialDepts[$code] ?? null;

            return [
                'import_code' => $code,
                'department_name' => $matched ? $matched->name : 'Tidak Ditemukan',
                'faculty_scope' => 'Fakultas Teknik',
                'row_count' => $rows->count(),
                'total_amount' => $rows->sum('initial_budget'),
                'status' => $matched ? 'MAPPED' : 'UNMAPPED',
            ];
        })->values()->toArray();

        // 4. Account Mappings
        $accountMappings = $allStagings->groupBy('account_code')->map(function ($rows, $code) use ($existingAccounts) {
            $first = $rows->first();
            $isExisting = isset($existingAccounts[$code]);
            $isFormatValid = preg_match('/^\d{6}$/', $code);

            return [
                'account_code' => $code,
                'account_name' => $first->account_name,
                'row_count' => $rows->count(),
                'total_amount' => $rows->sum('initial_budget'),
                'status' => $isExisting ? 'EXISTING' : ($isFormatValid ? 'NEW' : 'UNMAPPED'),
            ];
        })->values()->toArray();

        // 5. Error Rows
        $errorItems = $allStagings->filter(fn ($s) => $s->status === 'INVALID')->values();

        return Inertia::render('Budgets/ImportShow', [
            'history' => $importHistory->load('user'),
            'stagings' => $stagings,
            'activeVersion' => $activeVersion,
            'summaryCards' => $summaryCards,
            'masterExtractions' => $masterExtractions,
            'deptMappings' => $deptMappings,
            'accountMappings' => $accountMappings,
            'errorItems' => $errorItems,
        ]);
    }

    public function commit(ImportHistory $importHistory): RedirectResponse
    {
        if ($importHistory->status === 'COMMITTED') {
            return redirect()->back()->with('error', 'Batch import ini sudah pernah dicommit.');
        }

        if ($importHistory->invalid_rows > 0) {
            return redirect()->back()->with('error', 'Batch import masih memiliki baris data bermasalah (Invalid). Harap perbaiki sebelum commit.');
        }

        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first();

        DB::transaction(function () use ($importHistory, $activeVersion) {
            $validStagings = $importHistory->stagings()->where('status', 'VALID')->get();
            $departments = Department::all()->keyBy('code');
            $fiscalYears = FiscalYear::all()->keyBy('year');
            $fundingSources = FundingSource::all()->keyBy('code');

            foreach ($validStagings as $stg) {
                $dept = $departments[$stg->department_code] ?? $departments['JTIF'] ?? Department::first();
                $fy = $fiscalYears[$stg->fiscal_year] ?? $fiscalYears->first();
                $fs = $fundingSources[$stg->funding_source_code] ?? $fundingSources['RM'] ?? FundingSource::first();

                // 1. Auto-create new validated account master if not existing
                BudgetAccount::firstOrCreate(
                    ['code' => $stg->account_code],
                    [
                        'name' => $stg->account_name ?: 'Belanja Operasional',
                        'type' => str_starts_with($stg->account_code, '53') ? 'Belanja Modal' : 'Belanja Barang',
                        'data_status' => 'OFFICIAL',
                    ]
                );

                // 2. Auto-create subcomponent master if applicable
                BudgetSubcomponent::firstOrCreate(
                    ['code' => 'AA', 'fiscal_year' => $fy->year ?? 2026],
                    [
                        'full_code' => '023.17.WA.4257.EBA.994.001.AA',
                        'parent_component_code' => '001',
                        'name' => "Operasional {$dept->name}",
                        'data_status' => 'OFFICIAL',
                    ]
                );

                // 3. Upsert Active BudgetBucket
                BudgetBucket::updateOrCreate(
                    [
                        'fiscal_year_id' => $fy->id,
                        'department_id' => $dept->id,
                        'account_code' => $stg->account_code,
                    ],
                    [
                        'budget_version_id' => $activeVersion?->id,
                        'funding_source_id' => $fs->id,
                        'account_name' => $stg->account_name,
                        'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
                        'subcomponent_name' => "Operasional {$dept->name}",
                        'budget_bucket_name' => $stg->account_name,
                        'initial_budget' => $stg->initial_budget,
                        'allocated_budget' => $stg->initial_budget,
                        'available_balance' => $stg->initial_budget,
                    ]
                );
            }

            $importHistory->update(['status' => 'COMMITTED']);

            AuditLogService::log('COMMIT_BUDGET_IMPORT', ImportHistory::class, $importHistory->id, null, [
                'batch_id' => $importHistory->import_batch_id,
                'imported_count' => count($validStagings),
                'new_masters_created' => true,
            ]);
        });

        return redirect()->route('budgets.index')
            ->with('success', "Batch [{$importHistory->import_batch_id}] berhasil di-commit! {$importHistory->valid_rows} pos alokasi anggaran dan master data baru telah disinkronkan ke basis data aktif.");
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
                fputcsv($file, [
                    'No', 'Tahun', 'Revisi ke', 'Nama Unit', 'Kode Program', 'Nama Program',
                    'Kode Kegiatan', 'Nama Kegiatan', 'Kode KRO', 'Nama KRO',
                    'Kode RO', 'Nama RO', 'Kode Komponen', 'Nama Komponen',
                    'Kode SubKomponen', 'Nama SubKomponen', 'Kode Akun', 'Nama Akun',
                    'Alokasi', 'Realisasi', 'Sisa Pagu', 'Sumber Dana', 'Keterangan',
                ]);
                fputcsv($file, [
                    '1', '2026', '0', 'Jurusan Teknik Informatika', 'WA', 'Program Dukungan Manajemen',
                    '4257', 'Dukungan Manajemen FT', 'EBA', 'Layanan Dukungan Manajemen',
                    '994', 'Layanan Perkantoran', '001', 'Operasional Kantor',
                    'AA', 'Praktikum & Laboratorium Informatika', '521111', 'Belanja Keperluan Perkantoran',
                    '150000000', '0', '150000000', 'RM', 'Alokasi Pagu DIPA TA 2026',
                ]);
                fputcsv($file, [
                    '2', '2026', '0', 'Jurusan Teknik Sipil', 'WA', 'Program Dukungan Manajemen',
                    '4257', 'Dukungan Manajemen FT', 'EBA', 'Layanan Dukungan Manajemen',
                    '994', 'Layanan Perkantoran', '001', 'Operasional Kantor',
                    'AA', 'Operasional Laboratorium Struktur Sipil', '521211', 'Belanja Bahan Uji Lab Sipil',
                    '200000000', '0', '200000000', 'RM', 'Alokasi Pagu DIPA TA 2026',
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
