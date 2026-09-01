<?php

namespace Database\Seeders;

use App\Models\BudgetBucket;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Role;
use App\Models\RuleConfig;
use App\Models\StudyProgram;
use App\Models\Submission;
use App\Models\SubmissionTemplate;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Services\AuditLogService;
use App\Services\RuleEngineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipedaSeeder extends Seeder
{
    public function run(): void
    {
        // Cleanup obsolete roles/users
        User::where('email', 'ketuaptk@ft.unsoed.ac.id')->orWhere('role', 'KETUA_PTK')->delete();
        Role::where('code', 'KETUA_PTK')->delete();

        // 0. Seed Official Roles (Fakultas Teknik UNSOED)
        $rolesList = [
            ['code' => 'ADMIN', 'name' => 'Administrator Sistem', 'desc' => 'Pengelola konfigurasi, master data, dan audit trail sistem.'],
            ['code' => 'DEKAN', 'name' => 'Dekan Fakultas Teknik', 'desc' => 'Pimpinan tertinggi fakultas (Executive & Policy Monitoring).'],
            ['code' => 'WAKIL_DEKAN', 'name' => 'Wakil Dekan II Bidang Umum & Keuangan', 'desc' => 'Pengawas strategis serapan anggaran 5 jurusan & fakultas.'],
            ['code' => 'KABAG', 'name' => 'Kepala Bagian Tata Usaha / Keuangan', 'desc' => 'Kontrol operasional anggaran fakultas dan penguncian komitmen dana.'],
            ['code' => 'PTU', 'name' => 'Pengadministrasi Tata Usaha (Reviewer SPJ)', 'desc' => 'Pemeriksa kelengkapan administrasi dan kepatuhan SPJ transaksi.'],
            ['code' => 'BENDAHARA', 'name' => 'Bendahara Pengeluaran Pembantu', 'desc' => 'Pengelola kas dan pencairan transaksi operasional fakultas.'],
            ['code' => 'PTK', 'name' => 'Petugas Pengelola Keuangan (Operator Jurusan)', 'desc' => 'Pencatat transaksi dan belanja anggaran unit jurusan.'],
            ['code' => 'KAJUR', 'name' => 'Ketua Jurusan', 'desc' => 'Monitoring realisasi dan sisa pagu anggaran jurusan (Read-Only).'],
            ['code' => 'KAPRODI', 'name' => 'Ketua Program Studi', 'desc' => 'Monitoring kegiatan dan realisasi terkait program studi (Read-Only).'],
        ];

        $roleModels = [];
        foreach ($rolesList as $r) {
            $roleModels[$r['code']] = Role::updateOrCreate(
                ['code' => $r['code']],
                ['name' => $r['name'], 'description' => $r['desc'], 'is_active' => true]
            );
        }

        // 1. Create Departments (Fakultas Teknik UNSOED)
        $ft = Department::updateOrCreate(
            ['code' => 'FT-UNSOED'],
            ['name' => 'Fakultas Teknik Universitas Jenderal Soedirman', 'is_active' => true]
        );

        $ts = Department::updateOrCreate(
            ['code' => 'JTS'],
            ['name' => 'Jurusan Teknik Sipil', 'parent_id' => $ft->id, 'is_active' => true]
        );

        $te = Department::updateOrCreate(
            ['code' => 'JTE'],
            ['name' => 'Jurusan Teknik Elektro', 'parent_id' => $ft->id, 'is_active' => true]
        );

        $if = Department::updateOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Jurusan Informatika', 'parent_id' => $ft->id, 'is_active' => true]
        );

        $ti = Department::updateOrCreate(
            ['code' => 'JTI'],
            ['name' => 'Jurusan Teknik Industri', 'parent_id' => $ft->id, 'is_active' => true]
        );

        $tg = Department::updateOrCreate(
            ['code' => 'JTG'],
            ['name' => 'Jurusan Teknik Geologi', 'parent_id' => $ft->id, 'is_active' => true]
        );

        // 1.1 Create 11 Study Programs across 5 Departments
        // Teknik Sipil
        $prodiS1Ts = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TS'],
            ['department_id' => $ts->id, 'name' => 'S1 Teknik Sipil', 'is_active' => true]
        );
        $prodiS2Ts = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S2-TS'],
            ['department_id' => $ts->id, 'name' => 'S2 Teknik Sipil', 'is_active' => true]
        );
        $prodiS1Ars = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-ARS'],
            ['department_id' => $ts->id, 'name' => 'S1 Arsitektur', 'is_active' => true]
        );

        // Teknik Elektro
        $prodiS1Te = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TE'],
            ['department_id' => $te->id, 'name' => 'S1 Teknik Elektro', 'is_active' => true]
        );
        $prodiS2Te = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S2-TE'],
            ['department_id' => $te->id, 'name' => 'S2 Teknik Elektro', 'is_active' => true]
        );

        // Informatika
        $prodiS1If = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-IF'],
            ['department_id' => $if->id, 'name' => 'S1 Informatika', 'is_active' => true]
        );
        $prodiS1Tk = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TK'],
            ['department_id' => $if->id, 'name' => 'S1 Teknik Komputer', 'is_active' => true]
        );

        // Teknik Industri
        $prodiS1Ti = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TI'],
            ['department_id' => $ti->id, 'name' => 'S1 Teknik Industri', 'is_active' => true]
        );
        $prodiS1Tm = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TM'],
            ['department_id' => $ti->id, 'name' => 'S1 Teknik Mesin', 'is_active' => true]
        );

        // Teknik Geologi
        $prodiS1Tg = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TG'],
            ['department_id' => $tg->id, 'name' => 'S1 Teknik Geologi', 'is_active' => true]
        );
        $prodiS1Tp = StudyProgram::updateOrCreate(
            ['code' => 'PRODI-S1-TP'],
            ['department_id' => $tg->id, 'name' => 'S1 Teknik Pertambangan', 'is_active' => true]
        );

        // 2. Create Fiscal Year 2026
        $fy2026 = FiscalYear::updateOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );

        // 3. Create Funding Sources (RM & Non-RM)
        $rm = FundingSource::updateOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'description' => 'Dana anggaran pemerintah pusat APBN. (Active MVP)']
        );

        $ukt = FundingSource::updateOrCreate(
            ['code' => 'UKT'],
            ['name' => 'Uang Kuliah Tunggal (BPN)', 'description' => 'Dana operasional pendidikan bersumber dari UKT mahasiswa.']
        );

        $boptn = FundingSource::updateOrCreate(
            ['code' => 'BOPTN'],
            ['name' => 'Bantuan Operasional PTN', 'description' => 'Dana bantuan Kementerian untuk riset dan operasional PTN.']
        );

        $pnbpBlu = FundingSource::updateOrCreate(
            ['code' => 'PNBP_BLU'],
            ['name' => 'PNBP / BLU Operasional', 'description' => 'Pendapatan Negara Bukan Pajak / Badan Layanan Umum.']
        );

        $sbsn = FundingSource::updateOrCreate(
            ['code' => 'SBSN'],
            ['name' => 'Surat Berharga Syariah Negara (SBSN)', 'description' => 'Pembiayaan proyek gedung & sarana via SBSN.']
        );

        // 3.1 Create Budget Versions for RM 2026
        $vRev00 = BudgetVersion::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'funding_source_id' => $rm->id, 'revision_no' => 'Rev 00'],
            [
                'version_label' => 'DIPA Awal TA 2026',
                'status' => 'ARCHIVED',
                'effective_at' => '2026-01-02',
                'source_reference' => 'DIPA-023.17.2.677558/2026-00',
                'notes' => 'Alokasi pagu DIPA awal tahun anggaran 2026.',
            ]
        );

        $vRev01 = BudgetVersion::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'funding_source_id' => $rm->id, 'revision_no' => 'Rev 01'],
            [
                'version_label' => 'Revisi 01 Pergeseran Akun Operasional',
                'status' => 'ARCHIVED',
                'effective_at' => '2026-04-15',
                'source_reference' => 'DIPA-023.17.2.677558/2026-01',
                'notes' => 'Revisi internal pergeseran antar subkomponen jurusan.',
            ]
        );

        $activeVersion = BudgetVersion::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'funding_source_id' => $rm->id, 'revision_no' => 'Rev 02'],
            [
                'version_label' => 'Revisi 02 DIPA Operasional FT UNSOED (Aktif)',
                'status' => 'ACTIVE',
                'effective_at' => '2026-07-01',
                'source_reference' => 'DIPA-023.17.2.677558/2026-02',
                'notes' => 'Versi pagu aktif berjalan saat ini untuk seluruh jurusan.',
            ]
        );

        $vRev03 = BudgetVersion::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'funding_source_id' => $rm->id, 'revision_no' => 'Rev 03'],
            [
                'version_label' => 'Rancangan Usulan Revisi 03 Semester Genap',
                'status' => 'DRAFT',
                'effective_at' => null,
                'source_reference' => 'USULAN-REV3/KU/2026',
                'notes' => 'Draft pengajuan revisi anggaran yang belum disahkan.',
            ]
        );

        // 4. Create Transaction Types
        $ls = TransactionType::updateOrCreate(
            ['code' => 'LS'],
            ['name' => 'Pembayaran Langsung (LS)', 'description' => 'Pengeluaran anggaran via mekanisme pembayaran langsung ke rekanan.', 'is_active' => true]
        );

        $up = TransactionType::updateOrCreate(
            ['code' => 'UP'],
            ['name' => 'Uang Persediaan (UP)', 'description' => 'Pengeluaran anggaran via mekanisme uang persediaan/revolving operasional jurusan.', 'is_active' => true]
        );

        $tup = TransactionType::updateOrCreate(
            ['code' => 'TUP'],
            ['name' => 'Tambah Uang Persediaan (TUP)', 'description' => 'Mekanisme tambahan uang persediaan untuk kebutuhan belanja mendesak.', 'is_active' => true]
        );

        $other = TransactionType::updateOrCreate(
            ['code' => 'OTHER'],
            ['name' => 'Mekanisme Lainnya', 'description' => 'Pengeluaran di luar standar belanja biasa.', 'is_active' => true]
        );

        // 5. Create Document Types
        $docTypes = [
            ['code' => 'TOR', 'name' => 'Term of Reference (TOR) / Kerangka Acuan Kerja', 'is_required' => false],
            ['code' => 'RAB', 'name' => 'Rincian Anggaran Biaya (RAB)', 'is_required' => false],
            ['code' => 'INVOICE', 'name' => 'Faktur Tagihan / Invoice', 'is_required' => false],
            ['code' => 'KWITANSI', 'name' => 'Bukti Kwitansi Pembayaran', 'is_required' => false],
            ['code' => 'SURAT_TUGAS', 'name' => 'Surat Tugas / Perjadin', 'is_required' => false],
            ['code' => 'SPJ', 'name' => 'Surat Pertanggungjawaban (SPJ)', 'is_required' => false],
            ['code' => 'SUPPORTING_DOC', 'name' => 'Dokumen Pendukung Lainnya', 'is_required' => false],
        ];

        foreach ($docTypes as $dt) {
            DocumentType::updateOrCreate(
                ['code' => $dt['code']],
                [
                    'name' => $dt['name'],
                    'is_required' => $dt['is_required'],
                    'applicable_transaction_types' => ['LS', 'UP', 'TUP', 'OTHER'],
                    'max_file_size_mb' => 15,
                    'is_active' => true,
                ]
            );
        }

        // 6. Create Workflow Definitions & Steps
        $standardWf = WorkflowDefinition::updateOrCreate(
            ['code' => 'WF_STANDARD_FT'],
            [
                'name' => 'Alur Standar Pencatatan & Realisasi Fakultas Teknik',
                'description' => 'Alur: PTK (Input) -> PTU/Bendahara (Verifikasi & Finalisasi) -> Pimpinan (Monitoring)',
                'transaction_type_id' => $ls->id,
                'is_active' => true,
            ]
        );

        // 7. Create Submission Template & Fields
        $tpl = SubmissionTemplate::updateOrCreate(
            ['code' => 'TPL_STANDARD_BELANJA'],
            [
                'name' => 'Format Standar Pencatatan Transaksi Belanja',
                'transaction_type_id' => $ls->id,
                'version' => 'v2.0',
                'effective_date' => '2026-01-01',
                'is_active' => true,
            ]
        );

        // 8. Create Rule Configs (EWS & RBC)
        $rules = [
            ['code' => 'EWS-001', 'name' => 'Critical Available Balance Alert', 'cat' => 'EWS', 'params' => ['warning_threshold' => 15, 'critical_threshold' => 5], 'desc' => 'Peringatan ketika sisa saldo bebas berada di bawah 15% (Warning) atau 5% (Critical).'],
            ['code' => 'EWS-002', 'name' => 'High Budget Utilization Warning', 'cat' => 'EWS', 'params' => ['utilization_threshold' => 85], 'desc' => 'Peringatan ketika total utilization (Realisasi + Komitmen) melebihi 85% dari total pagu alokasi.'],
            ['code' => 'EWS-003', 'name' => 'Stale Processing Transaction Warning', 'cat' => 'EWS', 'params' => ['max_stale_days' => 3], 'desc' => 'Peringatan transaksi dalam proses yang tertahan lebih dari 3 hari kerja.'],
            ['code' => 'EWS-004', 'name' => 'Revision Conflict Guard', 'cat' => 'EWS', 'params' => ['reduction_threshold_pct' => 20], 'desc' => 'Peringatan jika pemotongan revisi pagu melebihi saldo bebas yang ada.'],
            ['code' => 'EWS-005', 'name' => 'Unmapped / Incomplete Data Warning', 'cat' => 'EWS', 'params' => ['check_missing_mapping' => true], 'desc' => 'Peringatan data pos anggaran tanpa mapping jurusan atau akun yang valid.'],
            ['code' => 'RBC-001', 'name' => 'Overbudget Protection Guard', 'cat' => 'RBC', 'params' => ['strict_blocking' => true], 'desc' => 'Pemblokiran otomatis server-side jika nominal transaksi > available balance.'],
            ['code' => 'RBC-002', 'name' => 'Active Status Commitment Locking', 'cat' => 'RBC', 'params' => ['lock_on_processing' => true], 'desc' => 'Penguncian saldo komitmen (Dalam Proses) saat status transaksi aktif.'],
            ['code' => 'RBC-003', 'name' => 'Release Commitment on Return/Cancel', 'cat' => 'RBC', 'params' => ['auto_release' => true], 'desc' => 'Pelepasan saldo komitmen kembali ke saldo bebas jika transaksi dikembalikan/dibatalkan.'],
            ['code' => 'RBC-004', 'name' => 'Atomic Finalization Realization', 'cat' => 'RBC', 'params' => ['prevent_double_count' => true], 'desc' => 'Pengurangan komitmen dan peningkatan realisasi tanpa double count saldo.'],
            ['code' => 'RBC-005', 'name' => 'Pessimistic Locking Concurrency Guard', 'cat' => 'RBC', 'params' => ['lock_for_update' => true], 'desc' => 'Transaksi database atomik dengan lockForUpdate untuk mencegah saldo negatif.'],
        ];

        foreach ($rules as $r) {
            RuleConfig::updateOrCreate(
                ['rule_code' => $r['code']],
                [
                    'rule_name' => $r['name'],
                    'category' => $r['cat'],
                    'parameters' => $r['params'],
                    'is_active' => true,
                    'description' => $r['desc'],
                ]
            );
        }

        // 9. Create Users with Multi-Role Assignment (Fakultas Level)
        $admin = User::updateOrCreate(
            ['email' => 'admin@ft.unsoed.ac.id'],
            [
                'name' => 'Super Administrator SIKARA',
                'password' => Hash::make('password'),
                'department_id' => $ft->id,
                'role' => 'ADMIN',
            ]
        );
        $admin->assignRole('ADMIN');

        $dekan = User::updateOrCreate(
            ['email' => 'dekan@ft.unsoed.ac.id'],
            [
                'name' => 'Prof. Dr. Ir. Dekan FT UNSOED',
                'password' => Hash::make('password'),
                'department_id' => $ft->id,
                'role' => 'DEKAN',
            ]
        );
        $dekan->assignRole('DEKAN');

        $wd = User::updateOrCreate(
            ['email' => 'wd2@ft.unsoed.ac.id'],
            [
                'name' => 'Dr. Ir. Wakil Dekan II Bidang Umum & Keuangan',
                'password' => Hash::make('password'),
                'department_id' => $ft->id,
                'role' => 'WAKIL_DEKAN',
            ]
        );
        $wd->assignRole('WAKIL_DEKAN');

        $kabag = User::updateOrCreate(
            ['email' => 'kabag@ft.unsoed.ac.id'],
            [
                'name' => 'Bapak Kepala Bagian Keuangan & TU',
                'password' => Hash::make('password'),
                'department_id' => $ft->id,
                'role' => 'KABAG',
            ]
        );
        $kabag->assignRole('KABAG');

        // Multi-role User: Ibu Alfi as PTU & Bendahara Fakultas
        $ptu = User::updateOrCreate(
            ['email' => 'ptu@ft.unsoed.ac.id'],
            [
                'name' => 'Ibu Alfi (PTU & Bendahara Fakultas)',
                'password' => Hash::make('password'),
                'department_id' => $ft->id,
                'role' => 'PTU',
            ]
        );
        $ptu->assignRole('PTU');
        $ptu->assignRole('BENDAHARA');

        // 5 Jurusan Setup (PTK, KAJUR)
        $depts = [
            'sipil' => ['dept' => $ts, 'name' => 'Teknik Sipil', 'code' => 'JTS'],
            'elektro' => ['dept' => $te, 'name' => 'Teknik Elektro', 'code' => 'JTE'],
            'if' => ['dept' => $if, 'name' => 'Informatika', 'code' => 'JTIF'],
            'industri' => ['dept' => $ti, 'name' => 'Teknik Industri', 'code' => 'JTI'],
            'geologi' => ['dept' => $tg, 'name' => 'Teknik Geologi', 'code' => 'JTG'],
        ];

        $ptkIf = null;
        $kajurIf = null;
        $ptkTs = null;

        foreach ($depts as $key => $d) {
            $ptk = User::updateOrCreate(
                ['email' => "ptk.{$key}@ft.unsoed.ac.id"],
                [
                    'name' => "Operator PTK {$d['name']}",
                    'password' => Hash::make('password'),
                    'department_id' => $d['dept']->id,
                    'role' => 'PTK',
                ]
            );
            $ptk->assignRole('PTK');

            $kajur = User::updateOrCreate(
                ['email' => "kajur.{$key}@ft.unsoed.ac.id"],
                [
                    'name' => "Ketua Jurusan {$d['name']}",
                    'password' => Hash::make('password'),
                    'department_id' => $d['dept']->id,
                    'role' => 'KAJUR',
                ]
            );
            $kajur->assignRole('KAJUR');

            if ($key === 'if') {
                $ptkIf = $ptk;
                $kajurIf = $kajur;
            } elseif ($key === 'sipil') {
                $ptkTs = $ptk;
            }
        }

        // 11 Program Studi Kaprodi Users (Ketua Program Studi)
        $prodis = [
            ['key' => 's1ts', 'prodi' => $prodiS1Ts, 'dept' => $ts, 'name' => 'S1 Teknik Sipil'],
            ['key' => 's2ts', 'prodi' => $prodiS2Ts, 'dept' => $ts, 'name' => 'S2 Teknik Sipil'],
            ['key' => 's1ars', 'prodi' => $prodiS1Ars, 'dept' => $ts, 'name' => 'S1 Arsitektur'],
            ['key' => 's1te', 'prodi' => $prodiS1Te, 'dept' => $te, 'name' => 'S1 Teknik Elektro'],
            ['key' => 's2te', 'prodi' => $prodiS2Te, 'dept' => $te, 'name' => 'S2 Teknik Elektro'],
            ['key' => 's1if', 'prodi' => $prodiS1If, 'dept' => $if, 'name' => 'S1 Informatika'],
            ['key' => 's1tk', 'prodi' => $prodiS1Tk, 'dept' => $if, 'name' => 'S1 Teknik Komputer'],
            ['key' => 's1ti', 'prodi' => $prodiS1Ti, 'dept' => $ti, 'name' => 'S1 Teknik Industri'],
            ['key' => 's1tm', 'prodi' => $prodiS1Tm, 'dept' => $ti, 'name' => 'S1 Teknik Mesin'],
            ['key' => 's1tg', 'prodi' => $prodiS1Tg, 'dept' => $tg, 'name' => 'S1 Teknik Geologi'],
            ['key' => 's1tp', 'prodi' => $prodiS1Tp, 'dept' => $tg, 'name' => 'S1 Teknik Pertambangan'],
        ];

        foreach ($prodis as $p) {
            $kaprodi = User::updateOrCreate(
                ['email' => "kaprodi.{$p['key']}@ft.unsoed.ac.id"],
                [
                    'name' => "Ketua Program Studi {$p['name']}",
                    'password' => Hash::make('password'),
                    'department_id' => $p['dept']->id,
                    'study_program_id' => $p['prodi']->id,
                    'role' => 'KAPRODI',
                ]
            );
            $kaprodi->assignRole('KAPRODI');
        }

        // 10. Create Budget Buckets for 2026 RM (Linked to Active Budget Version Rev 02)
        $bucket1 = BudgetBucket::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'department_id' => $if->id, 'account_code' => '521211'],
            [
                'budget_version_id' => $activeVersion->id,
                'funding_source_id' => $rm->id,
                'account_name' => 'Belanja Bahan',
                'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AA',
                'subcomponent_name' => 'Praktikum & Laboratorium Informatika',
                'budget_bucket_name' => 'Praktikum Pemrograman & Laboratorium Komputer',
                'description' => 'Pagu belanja bahan praktikum semester ganjil/genap.',
                'initial_budget' => 50000000.00,
                'allocated_budget' => 50000000.00,
                'reserved_budget' => 5000000.00,
                'realized_budget' => 30000000.00,
                'available_balance' => 15000000.00,
            ]
        );

        $bucket2 = BudgetBucket::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'department_id' => $if->id, 'account_code' => '521111'],
            [
                'budget_version_id' => $activeVersion->id,
                'funding_source_id' => $rm->id,
                'account_name' => 'Belanja Keperluan Perkantoran',
                'subcomponent_full_code' => '023.17.WA.4257.EBA.994.001.AB',
                'subcomponent_name' => 'Operasional Kantor Jurusan Informatika',
                'budget_bucket_name' => 'ATK & Operasional Kantor Informatika',
                'description' => 'Pagu belanja operasional ATK perkantoran jurusan.',
                'initial_budget' => 250000000.00,
                'allocated_budget' => 250000000.00,
                'reserved_budget' => 45000000.00,
                'realized_budget' => 175000000.00,
                'available_balance' => 30000000.00,
            ]
        );

        $bucket3 = BudgetBucket::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'department_id' => $ts->id, 'account_code' => '521111'],
            [
                'budget_version_id' => $activeVersion->id,
                'funding_source_id' => $rm->id,
                'account_name' => 'Belanja Keperluan Perkantoran',
                'subcomponent_full_code' => '023.17.WA.4257.EBA.994.002.AA',
                'subcomponent_name' => 'Operasional Laboratorium Bahan Struktur Sipil',
                'budget_bucket_name' => 'Peralatan Uji Kuat Bahan Struktur Lab Sipil',
                'description' => 'Pagu pengadaan material uji mekanika tanah dan beton.',
                'initial_budget' => 300000000.00,
                'allocated_budget' => 300000000.00,
                'reserved_budget' => 35000000.00,
                'realized_budget' => 150000000.00,
                'available_balance' => 115000000.00,
            ]
        );

        $bucket4 = BudgetBucket::updateOrCreate(
            ['fiscal_year_id' => $fy2026->id, 'department_id' => $te->id, 'account_code' => '524111'],
            [
                'budget_version_id' => $activeVersion->id,
                'funding_source_id' => $rm->id,
                'account_name' => 'Belanja Perjalanan Dinas Biasa',
                'subcomponent_full_code' => '023.17.WA.4257.EBA.994.003.AA',
                'subcomponent_name' => 'Praktikum Lapangan dan KKL Elektro',
                'budget_bucket_name' => 'Praktikum Lapangan dan KKL Mahasiswa Elektro',
                'description' => 'Pagu transportasi dan akomodasi kegiatan lapangan.',
                'initial_budget' => 120000000.00,
                'allocated_budget' => 120000000.00,
                'reserved_budget' => 10000000.00,
                'realized_budget' => 105000000.00,
                'available_balance' => 5000000.00,
            ]
        );

        // 11. Initial Transactions / Submissions
        $sub1 = Submission::updateOrCreate(
            ['submission_number' => 'TRX/2026/08/001'],
            [
                'evidence_number' => 'BKT-IF-001',
                'transaction_date' => '2026-08-15',
                'reference_no' => 'UN23.FT.IF/KU/2026/088',
                'title' => 'Pengadaan Lisensi Software Jaringan & Server Lab',
                'department_id' => $if->id,
                'study_program_id' => $prodiS1If->id,
                'fiscal_year_id' => $fy2026->id,
                'transaction_type_id' => $ls->id,
                'submission_template_id' => $tpl->id,
                'budget_bucket_id' => $bucket2->id,
                'amount' => 45000000.00,
                'beneficiary_name' => 'PT Cloud Integrasi Indonesia',
                'status' => 'PROCESSING',
                'created_by' => $ptkIf->id,
                'notes' => 'Pencatatan transaksi belanja lisensi lab komputer.',
            ]
        );

        $sub2 = Submission::updateOrCreate(
            ['submission_number' => 'TRX/2026/08/002'],
            [
                'evidence_number' => 'BKT-IF-002',
                'transaction_date' => '2026-08-20',
                'reference_no' => 'UN23.FT.IF/KU/2026/092',
                'title' => 'Bahan Praktikum Pemrograman Dasar & Algoritma',
                'department_id' => $if->id,
                'study_program_id' => $prodiS1If->id,
                'fiscal_year_id' => $fy2026->id,
                'transaction_type_id' => $ls->id,
                'submission_template_id' => $tpl->id,
                'budget_bucket_id' => $bucket1->id,
                'amount' => 5000000.00,
                'beneficiary_name' => 'CV Mitra Laboratorium',
                'status' => 'PROCESSING',
                'created_by' => $ptkIf->id,
                'notes' => 'Transaksi bahan praktikum semester ganjil.',
            ]
        );

        $sub3 = Submission::updateOrCreate(
            ['submission_number' => 'TRX/2026/08/003'],
            [
                'evidence_number' => 'BKT-TS-001',
                'transaction_date' => '2026-08-10',
                'reference_no' => 'UN23.FT.TS/KU/2026/045',
                'title' => 'Pengadaan Bahan Uji Kuat Tekan Beton Lab Sipil',
                'department_id' => $ts->id,
                'study_program_id' => $prodiS1Ts->id,
                'fiscal_year_id' => $fy2026->id,
                'transaction_type_id' => $ls->id,
                'submission_template_id' => $tpl->id,
                'budget_bucket_id' => $bucket3->id,
                'amount' => 35000000.00,
                'beneficiary_name' => 'CV Mandiri Material Teknik',
                'status' => 'FINAL',
                'created_by' => $ptkTs->id,
                'notes' => 'Transaksi selesai dan lunas.',
            ]
        );

        // 12. Evaluate Rule Engine
        $ruleEngine = new RuleEngineService;
        $ruleEngine->evaluateAllEws();

        // 13. Audit Log
        AuditLogService::log('SEED_MULTI_ROLE_SIKARA_DATA', 'System', 1, null, ['status' => 'SUCCESS']);
    }
}
