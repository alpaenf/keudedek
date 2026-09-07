<?php

namespace App\Http\Controllers;

use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetComponent;
use App\Models\BudgetKro;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\ImportHistory;
use App\Services\BudgetImportPipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $versionLabel = $request->version_label ?: "Pagu {$selectedRevision} TA {$selectedYear}";

        $history = BudgetImportPipelineService::processUpload(
            $path,
            $filename,
            $selectedYear,
            $selectedFunding,
            $selectedRevision,
            $versionLabel,
            $request->effective_date,
            auth()->user()
        );

        return redirect()->route('budgets.import.show', $history)
            ->with('success', "Batch [{$history->import_batch_id}] berhasil diunggah. {$history->valid_rows} baris valid dari total {$history->total_rows} baris siap diverifikasi.");
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
        try {
            $result = BudgetImportPipelineService::commitBatch($importHistory, auth()->user());

            return redirect()->route('budgets.import.show', $importHistory)
                ->with('success', "Batch [{$importHistory->import_batch_id}] berhasil di-commit! {$result['lines_count']} baris RBA dan {$result['buckets_count']} Control Bucket telah disinkronkan ke versi anggaran [{$result['budget_version']->revision_no} - {$result['budget_version']->status}]. Versi tetap Draft hingga diaktifkan terpisah.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
