<?php

namespace Database\Seeders;

use App\Models\Approval;
use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\RuleConfig;
use App\Models\Submission;
use App\Models\SubmissionItem;
use App\Models\SubmissionStatusHistory;
use App\Models\SubmissionTemplate;
use App\Models\SubmissionTemplateField;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use App\Services\AuditLogService;
use App\Services\RuleEngineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SipedaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Departments (Fakultas Teknik UNSOED)
        $ft = Department::create([
            'code' => 'FT-UNSOED',
            'name' => 'Fakultas Teknik Universitas Jenderal Soedirman',
            'is_active' => true,
        ]);

        $if = Department::create([
            'code' => 'JTIF',
            'name' => 'Jurusan Teknik Informatika',
            'parent_id' => $ft->id,
            'is_active' => true,
        ]);

        $ts = Department::create([
            'code' => 'JTS',
            'name' => 'Jurusan Teknik Sipil',
            'parent_id' => $ft->id,
            'is_active' => true,
        ]);

        $te = Department::create([
            'code' => 'JTE',
            'name' => 'Jurusan Teknik Elektro',
            'parent_id' => $ft->id,
            'is_active' => true,
        ]);

        $tg = Department::create([
            'code' => 'JTG',
            'name' => 'Jurusan Teknik Geologi',
            'parent_id' => $ft->id,
            'is_active' => true,
        ]);

        $ti = Department::create([
            'code' => 'JTI',
            'name' => 'Jurusan Teknik Industri',
            'parent_id' => $ft->id,
            'is_active' => true,
        ]);

        // 2. Create Fiscal Year 2026
        $fy2026 = FiscalYear::create([
            'year' => 2026,
            'status' => 'ACTIVE',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // 3. Create Funding Sources (RM & Non-RM)
        $rm = FundingSource::create([
            'code' => 'RM',
            'name' => 'Rupiah Murni',
            'description' => 'Dana anggaran pemerintah pusat APBN.',
        ]);

        $ukt = FundingSource::create([
            'code' => 'UKT',
            'name' => 'Uang Kuliah Tunggal (BPN)',
            'description' => 'Dana operasional pendidikan bersumber dari UKT mahasiswa.',
        ]);

        $boptn = FundingSource::create([
            'code' => 'BOPTN',
            'name' => 'Bantuan Operasional PTN',
            'description' => 'Dana bantuan dari Kementerian untuk riset dan sarana pendukung.',
        ]);

        // 4. Create Transaction Types
        $ls = TransactionType::create([
            'code' => 'LS',
            'name' => 'Pembayaran Langsung (LS)',
            'description' => 'Pengeluaran anggaran via mekanisme pembayaran langsung ke pihak ketiga/rekanan.',
            'is_active' => true,
        ]);

        $up = TransactionType::create([
            'code' => 'UP',
            'name' => 'Uang Persediaan (UP)',
            'description' => 'Pengeluaran anggaran via mekanisme uang persediaan/revolving operasional jurusan.',
            'is_active' => true,
        ]);

        $tup = TransactionType::create([
            'code' => 'TUP',
            'name' => 'Tambah Uang Persediaan (TUP)',
            'description' => 'Mekanisme tambahan uang persediaan untuk kebutuhan belanja mendesak/khusus.',
            'is_active' => true,
        ]);

        $other = TransactionType::create([
            'code' => 'OTHER',
            'name' => 'Mekanisme Lainnya',
            'description' => 'Pengeluaran di luar standar belanja biasa.',
            'is_active' => true,
        ]);

        // 5. Create Document Types
        $docTypes = [
            ['code' => 'TOR', 'name' => 'Term of Reference (TOR) / Kerangka Acuan Kerja', 'is_required' => true],
            ['code' => 'RAB', 'name' => 'Rincian Anggaran Biaya (RAB)', 'is_required' => true],
            ['code' => 'PROPOSAL', 'name' => 'Proposal Kegiatan', 'is_required' => false],
            ['code' => 'INVOICE', 'name' => 'Faktur Tagihan / Invoice', 'is_required' => false],
            ['code' => 'KWITANSI', 'name' => 'Bukti Kwitansi Pembayaran', 'is_required' => false],
            ['code' => 'SURAT_TUGAS', 'name' => 'Surat Tugas / Perjadin', 'is_required' => false],
            ['code' => 'SPJ', 'name' => 'Surat Pertanggungjawaban (SPJ)', 'is_required' => false],
            ['code' => 'SUPPORTING_DOC', 'name' => 'Dokumen Pendukung Lainnya', 'is_required' => false],
        ];

        foreach ($docTypes as $dt) {
            DocumentType::create([
                'code' => $dt['code'],
                'name' => $dt['name'],
                'is_required' => $dt['is_required'],
                'applicable_transaction_types' => ['LS', 'UP', 'TUP', 'OTHER'],
                'max_file_size_mb' => 15,
                'is_active' => true,
            ]);
        }

        // 6. Create Workflow Definitions & Steps
        $standardWf = WorkflowDefinition::create([
            'code' => 'WF_STANDARD_FT',
            'name' => 'Alur Standar Pengajuan Belanja Fakultas Teknik',
            'description' => 'Alur persetujuan: PTK -> KAJUR -> PTU -> KABAG -> WAKIL DEKAN II -> DEKAN',
            'transaction_type_id' => $ls->id,
            'is_active' => true,
        ]);

        $steps = [
            ['seq' => 1, 'role' => 'PTK', 'name' => 'Pengusulan & Rincian (Operator)', 'signoff' => false, 'reserve' => false, 'final' => false],
            ['seq' => 2, 'role' => 'KAJUR', 'name' => 'Verifikasi Prioritas Jurusan', 'signoff' => true, 'reserve' => false, 'final' => false],
            ['seq' => 3, 'role' => 'PTU', 'name' => 'Review Kepatuhan SPJ & Akun', 'signoff' => true, 'reserve' => false, 'final' => false],
            ['seq' => 4, 'role' => 'KABAG', 'name' => 'Eksekusi Komitmen Saldo (Reserve)', 'signoff' => true, 'reserve' => true, 'final' => false],
            ['seq' => 5, 'role' => 'WAKIL_DEKAN', 'name' => 'Persetujuan Pimpinan Fakultas & Finalisasi', 'signoff' => true, 'reserve' => false, 'final' => true],
            ['seq' => 6, 'role' => 'DEKAN', 'name' => 'Otorisasi Kebijakan Strategis / Pengawasan', 'signoff' => true, 'reserve' => false, 'final' => false],
        ];

        foreach ($steps as $s) {
            WorkflowStep::create([
                'workflow_definition_id' => $standardWf->id,
                'sequence' => $s['seq'],
                'role' => $s['role'],
                'name' => $s['name'],
                'can_approve' => true,
                'can_return' => true,
                'can_reject' => true,
                'requires_signoff' => $s['signoff'],
                'reserve_trigger' => $s['reserve'],
                'final_trigger' => $s['final'],
            ]);
        }

        // 7. Create Submission Template & Fields
        $tpl = SubmissionTemplate::create([
            'code' => 'TPL_STANDARD_BELANJA',
            'name' => 'Format Standar Pengajuan Belanja Barang & Jasa',
            'transaction_type_id' => $ls->id,
            'version' => 'v1.0',
            'effective_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $fields = [
            ['code' => 'title', 'label' => 'Judul / Nama Kegiatan', 'type' => 'TEXT', 'req' => true, 'order' => 1],
            ['code' => 'beneficiary_name', 'label' => 'Nama Penerima / Rekanan', 'type' => 'TEXT', 'req' => true, 'order' => 2],
            ['code' => 'reference_no', 'label' => 'Nomor Surat / Referensi', 'type' => 'TEXT', 'req' => false, 'order' => 3],
            ['code' => 'notes', 'label' => 'Keterangan Tambahan', 'type' => 'TEXTAREA', 'req' => false, 'order' => 4],
        ];

        foreach ($fields as $f) {
            SubmissionTemplateField::create([
                'submission_template_id' => $tpl->id,
                'field_code' => $f['code'],
                'label' => $f['label'],
                'data_type' => $f['type'],
                'is_required' => $f['req'],
                'is_editable' => true,
                'order_index' => $f['order'],
            ]);
        }

        // 8. Create Rule Configs (EWS & RBC)
        $rules = [
            ['code' => 'EWS-001', 'name' => 'Critical Available Balance Alert', 'cat' => 'EWS', 'params' => ['warning_threshold' => 15, 'critical_threshold' => 5], 'desc' => 'Peringatan ketika sisa saldo bebas berada di bawah 15% (Warning) atau 5% (Critical).'],
            ['code' => 'EWS-002', 'name' => 'High Budget Utilization Warning', 'cat' => 'EWS', 'params' => ['utilization_threshold' => 85], 'desc' => 'Peringatan ketika total utilization (Realisasi + Komitmen) melebihi 85% dari total pagu alokasi.'],
            ['code' => 'EWS-003', 'name' => 'Low Absorption Rate Alert', 'cat' => 'EWS', 'params' => ['min_absorption' => 30, 'check_month' => 6], 'desc' => 'Peringatan serapan belanja rendah pada pertengahan semester.'],
            ['code' => 'EWS-004', 'name' => 'Stale Submission Warning', 'cat' => 'EWS', 'params' => ['max_stale_days' => 3], 'desc' => 'Peringatan pengajuan yang tertahan tanpa tindakan status selama lebih dari 3 hari kerja.'],
            ['code' => 'EWS-005', 'name' => 'Repeated Return Warning', 'cat' => 'EWS', 'params' => ['max_returns' => 2], 'desc' => 'Peringatan pengajuan yang dikembalikan ke pemohon lebih dari 2 kali perbaikan.'],
            ['code' => 'EWS-006', 'name' => 'Revision Impact Guard Warning', 'cat' => 'EWS', 'params' => ['reduction_threshold_pct' => 20], 'desc' => 'Peringatan jika pemotongan revisi pagu melebihi 20% dari pagu sebelumnya.'],
            ['code' => 'EWS-007', 'name' => 'Unmapped / Incomplete Data Warning', 'cat' => 'EWS', 'params' => ['check_missing_mapping' => true], 'desc' => 'Peringatan data pos anggaran tanpa mapping jurusan atau sumber dana yang valid.'],
            ['code' => 'RBC-001', 'name' => 'Overbudget Prevention Guard', 'cat' => 'RBC', 'params' => ['strict_blocking' => true], 'desc' => 'Pemblokiran otomatis server-side jika nominal pengajuan > available balance.'],
            ['code' => 'RBC-002', 'name' => 'Configured Step Reservation Trigger', 'cat' => 'RBC', 'params' => ['trigger_step_role' => 'KABAG'], 'desc' => 'Penguncian saldo komitmen (Reserved) saat disetujui Kabag Keuangan.'],
            ['code' => 'RBC-003', 'name' => 'Release Reservation on Return/Reject', 'cat' => 'RBC', 'params' => ['auto_release' => true], 'desc' => 'Pelepasan saldo komitmen kembali ke available balance jika pengajuan ditolak/dikembalikan.'],
            ['code' => 'RBC-004', 'name' => 'Atomic Finalization Realization', 'cat' => 'RBC', 'params' => ['prevent_double_count' => true], 'desc' => 'Pengurangan reserved dan peningkatan realized tanpa double count saldo.'],
            ['code' => 'RBC-005', 'name' => 'Pre-Revision Floor Guard', 'cat' => 'RBC', 'params' => ['floor_guard' => true], 'desc' => 'Penolakan revisi pengurangan pagu jika pagu baru < total reserved + realized.'],
            ['code' => 'RBC-006', 'name' => 'Duplicate Submission Protection', 'cat' => 'RBC', 'params' => ['check_duplicate_ref' => true], 'desc' => 'Pencegahan nomor referensi ganda dalam satu tahun anggaran.'],
            ['code' => 'RBC-007', 'name' => 'Server-Side Jurisdiction Scope Guard', 'cat' => 'RBC', 'params' => ['enforce_dept_isolation' => true], 'desc' => 'Proteksi backend menjamin user jurusan tidak dapat memutasi data jurusan lain.'],
            ['code' => 'RBC-008', 'name' => 'Pessimistic Locking Data Integrity', 'cat' => 'RBC', 'params' => ['lock_for_update' => true], 'desc' => 'Transaksi atomik dengan lockForUpdate untuk mencegah race condition saldo.'],
        ];

        foreach ($rules as $r) {
            RuleConfig::create([
                'rule_code' => $r['code'],
                'rule_name' => $r['name'],
                'category' => $r['cat'],
                'parameters' => $r['params'],
                'is_active' => true,
                'description' => $r['desc'],
            ]);
        }

        // 9. Create Users for all 5 Jurusan & Faculty Roles
        $admin = User::create([
            'name' => 'Super Administrator SIKARA',
            'email' => 'admin@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'ADMIN',
        ]);

        $dekan = User::create([
            'name' => 'Prof. Dr. Ir. Dekan FT UNSOED',
            'email' => 'dekan@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'DEKAN',
        ]);

        $wd = User::create([
            'name' => 'Dr. Ir. Wakil Dekan II Bidang Umum & Keuangan',
            'email' => 'wd2@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'WAKIL_DEKAN',
        ]);

        $kabag = User::create([
            'name' => 'Bapak Kepala Bagian Keuangan',
            'email' => 'kabag@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'KABAG',
        ]);

        $ptu = User::create([
            'name' => 'Ibu Reviewer SPJ (PTU Fakultas)',
            'email' => 'ptu@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'PTU',
        ]);

        $ketuaPtk = User::create([
            'name' => 'Ketua PTK Fakultas Teknik UNSOED',
            'email' => 'ketuaptk@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'KETUA_PTK',
        ]);

        // Seeding 5 Jurusan: Informatika, Sipil, Elektro, Geologi, Industri
        $depts = [
            'if' => ['dept' => $if, 'name' => 'Informatika', 'code' => 'JTIF'],
            'sipil' => ['dept' => $ts, 'name' => 'Sipil', 'code' => 'JTS'],
            'elektro' => ['dept' => $te, 'name' => 'Elektro', 'code' => 'JTE'],
            'geologi' => ['dept' => $tg, 'name' => 'Geologi', 'code' => 'JTG'],
            'industri' => ['dept' => $ti, 'name' => 'Industri', 'code' => 'JTI'],
        ];

        $ptkIf = null;
        $kajurIf = null;
        $ptkTs = null;

        foreach ($depts as $key => $d) {
            $ptk = User::create([
                'name' => "Operator PTK {$d['name']}",
                'email' => "ptk.{$key}@ft.unsoed.ac.id",
                'password' => Hash::make('password'),
                'department_id' => $d['dept']->id,
                'role' => 'PTK',
            ]);

            $kajur = User::create([
                'name' => "Ketua Jurusan {$d['name']}",
                'email' => "kajur.{$key}@ft.unsoed.ac.id",
                'password' => Hash::make('password'),
                'department_id' => $d['dept']->id,
                'role' => 'KAJUR',
            ]);

            if ($key === 'if') {
                $ptkIf = $ptk;
                $kajurIf = $kajur;
            } elseif ($key === 'sipil') {
                $ptkTs = $ptk;
            }
        }

        // 10. Create Budget Buckets with separated official account and internal bucket names
        $bucket1 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $if->id,
            'funding_source_id' => $rm->id,
            'account_code' => '521111',
            'account_name' => 'Belanja Keperluan Perkantoran',
            'budget_bucket_name' => 'Operasional Laboratorium Komputer & Server Informatika',
            'description' => 'Pagu belanja bahan praktek dan pemeliharaan server lab komputer.',
            'initial_budget' => 250000000.00,
            'allocated_budget' => 250000000.00,
            'reserved_budget' => 45000000.00,
            'realized_budget' => 175000000.00,
            'available_balance' => 30000000.00, // 12% -> triggers EWS-001 (High)
        ]);

        $bucket2 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $if->id,
            'funding_source_id' => $ukt->id,
            'account_code' => '522112',
            'account_name' => 'Belanja Jasa Profesi',
            'budget_bucket_name' => 'Penyelenggaraan Seminar Internasional & Konferensi IT',
            'description' => 'Pagu honorarium narasumber dan kepanitiaan seminar ilmiah.',
            'initial_budget' => 180000000.00,
            'allocated_budget' => 180000000.00,
            'reserved_budget' => 20000000.00,
            'realized_budget' => 80000000.00,
            'available_balance' => 80000000.00,
        ]);

        $bucket3 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $ts->id,
            'funding_source_id' => $rm->id,
            'account_code' => '521111',
            'account_name' => 'Belanja Keperluan Perkantoran',
            'budget_bucket_name' => 'Peralatan Uji Kuat Bahan Struktur Lab Sipil',
            'description' => 'Pagu pengadaan material uji mekanika tanah dan beton.',
            'initial_budget' => 300000000.00,
            'allocated_budget' => 300000000.00,
            'reserved_budget' => 35000000.00,
            'realized_budget' => 150000000.00,
            'available_balance' => 115000000.00,
        ]);

        $bucket4 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $te->id,
            'funding_source_id' => $rm->id,
            'account_code' => '524111',
            'account_name' => 'Belanja Perjalanan Dinas Biasa',
            'budget_bucket_name' => 'Praktikum Lapangan dan KKL Mahasiswa Elektro',
            'description' => 'Pagu transportasi dan akomodasi kegiatan lapangan.',
            'initial_budget' => 120000000.00,
            'allocated_budget' => 120000000.00,
            'reserved_budget' => 10000000.00,
            'realized_budget' => 105000000.00,
            'available_balance' => 5000000.00, // 4.1% -> CRITICAL
        ]);

        // 11. Initial Submissions with timeline & electronic sign-off
        $sub1 = Submission::create([
            'submission_number' => 'SUB/2026/08/001',
            'reference_no' => 'UN23.FT.IF/KU/2026/088',
            'title' => 'Pengadaan Lisensi Software Simulasi Jaringan & Cloud Server Lab',
            'department_id' => $if->id,
            'fiscal_year_id' => $fy2026->id,
            'transaction_type_id' => $ls->id,
            'submission_template_id' => $tpl->id,
            'budget_bucket_id' => $bucket1->id,
            'amount' => 45000000.00,
            'beneficiary_name' => 'PT Cloud Integrasi Indonesia',
            'status' => 'RESERVED',
            'created_by' => $ptkIf->id,
            'notes' => 'Pengajuan telah diverifikasi PTU dan disetujui KAJUR & KABAG. Anggaran di-reserve.',
            'electronic_signoff_hash' => hash('sha256', 'SUB/2026/08/001-KABAG-APPROVED'),
        ]);

        SubmissionItem::create([
            'submission_id' => $sub1->id,
            'item_name' => 'Lisensi Software Cisco Packet Tracer & Lab Server',
            'quantity' => 15,
            'unit_price' => 2000000.00,
            'total_price' => 30000000.00,
        ]);

        SubmissionItem::create([
            'submission_id' => $sub1->id,
            'item_name' => 'Kredit AWS Educational Cloud Server 1 Tahun',
            'quantity' => 1,
            'unit_price' => 15000000.00,
            'total_price' => 15000000.00,
        ]);

        // Add timeline history & approval
        SubmissionStatusHistory::create([
            'submission_id' => $sub1->id,
            'from_status' => null,
            'to_status' => 'DRAFT',
            'actor_id' => $ptkIf->id,
            'role' => 'PTK',
            'notes' => 'Draft pengajuan dibuat oleh Operator PTK Informatika.',
        ]);
        SubmissionStatusHistory::create([
            'submission_id' => $sub1->id,
            'from_status' => 'DRAFT',
            'to_status' => 'SUBMITTED',
            'actor_id' => $ptkIf->id,
            'role' => 'PTK',
            'notes' => 'Pengajuan diajukan untuk verifikasi.',
        ]);
        SubmissionStatusHistory::create([
            'submission_id' => $sub1->id,
            'from_status' => 'SUBMITTED',
            'to_status' => 'APPROVED',
            'actor_id' => $kajurIf->id,
            'role' => 'KAJUR',
            'notes' => 'Disetujui oleh Ketua Jurusan.',
        ]);
        SubmissionStatusHistory::create([
            'submission_id' => $sub1->id,
            'from_status' => 'APPROVED',
            'to_status' => 'RESERVED',
            'actor_id' => $kabag->id,
            'role' => 'KABAG',
            'notes' => 'Disetujui dan saldo dikomitmenkan (Reserved) oleh Kabag Keuangan.',
        ]);

        Approval::create([
            'submission_id' => $sub1->id,
            'user_id' => $kajurIf->id,
            'role' => 'KAJUR',
            'decision' => 'APPROVED',
            'comment' => 'Kegiatan prioritas lab jurusan, disetujui.',
            'document_hash' => hash('sha256', 'SUB1-DOC'),
            'ip_address' => '127.0.0.1',
        ]);

        Approval::create([
            'submission_id' => $sub1->id,
            'user_id' => $kabag->id,
            'role' => 'KABAG',
            'decision' => 'APPROVED',
            'comment' => 'Pagu mencukupi, komitmen dana Rp 45.000.000 dikunci (RESERVED).',
            'document_hash' => hash('sha256', 'SUB1-DOC-RESERVED'),
            'ip_address' => '127.0.0.1',
        ]);

        $sub2 = Submission::create([
            'submission_number' => 'SUB/2026/08/002',
            'reference_no' => 'UN23.FT.IF/KU/2026/092',
            'title' => 'Penyelenggaraan Workshop International Conference on IT (ICIT)',
            'department_id' => $if->id,
            'fiscal_year_id' => $fy2026->id,
            'transaction_type_id' => $ls->id,
            'submission_template_id' => $tpl->id,
            'budget_bucket_id' => $bucket2->id,
            'amount' => 20000000.00,
            'beneficiary_name' => 'Panitia ICIT 2026',
            'status' => 'UNDER_REVIEW',
            'created_by' => $ptkIf->id,
            'notes' => 'Sedang diverifikasi kelengkapan dokumen SPJ oleh PTU Fakultas.',
        ]);

        SubmissionItem::create([
            'submission_id' => $sub2->id,
            'item_name' => 'Honorarium Keynote Speaker Internasional',
            'quantity' => 2,
            'unit_price' => 10000000.00,
            'total_price' => 20000000.00,
        ]);

        $sub3 = Submission::create([
            'submission_number' => 'SUB/2026/08/003',
            'reference_no' => 'UN23.FT.TS/KU/2026/045',
            'title' => 'Pengadaan Bahan Uji Kuat Tekan Beton & Aspal Lab Sipil',
            'department_id' => $ts->id,
            'fiscal_year_id' => $fy2026->id,
            'transaction_type_id' => $ls->id,
            'submission_template_id' => $tpl->id,
            'budget_bucket_id' => $bucket3->id,
            'amount' => 35000000.00,
            'beneficiary_name' => 'CV Mandiri Material Teknik',
            'status' => 'FINAL',
            'created_by' => $ptkTs->id,
            'notes' => 'Pencairan selesai dan SPJ telah diverifikasi lunas.',
        ]);

        // 12. Evaluate Rule Engine for initial buckets
        $ruleEngine = new RuleEngineService;
        $ruleEngine->evaluateAllEws();

        // 13. Initial Audit Log
        AuditLogService::log('SEED_ENTERPRISE_SIKARA_DATA', 'System', 1, null, ['status' => 'SUCCESS']);
    }
}
