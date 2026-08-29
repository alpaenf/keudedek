<?php

namespace Database\Seeders;

use App\Models\BudgetBucket;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\Submission;
use App\Models\SubmissionItem;
use App\Models\User;
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

        // 3. Create Funding Sources
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

        $rm = FundingSource::create([
            'code' => 'RM',
            'name' => 'Rupiah Murni',
            'description' => 'Dana anggaran pemerintah pusat.',
        ]);

        // 4. Create Users (7 Roles)
        $admin = User::create([
            'name' => 'Admin Keuangan FT',
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
            'name' => 'Dr. Ir. Wakil Dekan II',
            'email' => 'wd2@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'WD',
        ]);

        $kabag = User::create([
            'name' => 'Bapak Kepala Bagian Keuangan',
            'email' => 'kabag@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'KABAG',
        ]);

        $ptu = User::create([
            'name' => 'Ibu Reviewer PTU',
            'email' => 'ptu@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ft->id,
            'role' => 'PTU',
        ]);

        $kajurIf = User::create([
            'name' => 'Ketua Jurusan Informatika',
            'email' => 'kajur.if@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $if->id,
            'role' => 'KAJUR',
        ]);

        $ptkIf = User::create([
            'name' => 'Operator PTK Informatika',
            'email' => 'ptk.if@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $if->id,
            'role' => 'PTK',
        ]);

        $ptkTs = User::create([
            'name' => 'Operator PTK Sipil',
            'email' => 'ptk.ts@ft.unsoed.ac.id',
            'password' => Hash::make('password'),
            'department_id' => $ts->id,
            'role' => 'PTK',
        ]);

        // 5. Create Budget Buckets
        $bucket1 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $if->id,
            'funding_source_id' => $ukt->id,
            'account_code' => '521111',
            'account_name' => 'Belanja Bahan & Operasional Laboratorium Komputer',
            'initial_budget' => 250000000.00,
            'allocated_budget' => 250000000.00,
            'reserved_budget' => 45000000.00,
            'realized_budget' => 175000000.00,
            'available_balance' => 30000000.00, // 12% -> triggers EWS-001 (High)
        ]);

        $bucket2 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $if->id,
            'funding_source_id' => $boptn->id,
            'account_code' => '522112',
            'account_name' => 'Belanja Jasa Riset & Seminar Internasional',
            'initial_budget' => 180000000.00,
            'allocated_budget' => 180000000.00,
            'reserved_budget' => 20000000.00,
            'realized_budget' => 80000000.00,
            'available_balance' => 80000000.00,
        ]);

        $bucket3 = BudgetBucket::create([
            'fiscal_year_id' => $fy2026->id,
            'department_id' => $ts->id,
            'funding_source_id' => $ukt->id,
            'account_code' => '521111',
            'account_name' => 'Belanja Peralatan Uji Bahan Struktur Sipil',
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
            'account_name' => 'Belanja Perjalanan Dinas Praktikum Lapangan Elektro',
            'initial_budget' => 120000000.00,
            'allocated_budget' => 120000000.00,
            'reserved_budget' => 10000000.00,
            'realized_budget' => 105000000.00,
            'available_balance' => 5000000.00, // 4.1% -> CRITICAL
        ]);

        // 6. Evaluate Rule Engine for initial buckets
        $ruleEngine = new RuleEngineService;
        $ruleEngine->evaluateBucket($bucket1);
        $ruleEngine->evaluateBucket($bucket2);
        $ruleEngine->evaluateBucket($bucket3);
        $ruleEngine->evaluateBucket($bucket4);

        // 7. Create Submissions
        $sub1 = Submission::create([
            'submission_number' => 'SUB/2026/08/001',
            'title' => 'Pengadaan Lisensi Software Simulasi Jaringan & Cloud Server',
            'department_id' => $if->id,
            'fiscal_year_id' => $fy2026->id,
            'budget_bucket_id' => $bucket1->id,
            'amount' => 45000000.00,
            'status' => 'RESERVED',
            'created_by' => $ptkIf->id,
            'notes' => 'Pengajuan telah diverifikasi PTU dan disetujui KAJUR. Anggaran di-reserve.',
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

        $sub2 = Submission::create([
            'submission_number' => 'SUB/2026/08/002',
            'title' => 'Penyelenggaraan Workshop International Conference on IT (ICIT)',
            'department_id' => $if->id,
            'fiscal_year_id' => $fy2026->id,
            'budget_bucket_id' => $bucket2->id,
            'amount' => 20000000.00,
            'status' => 'REVIEW',
            'created_by' => $ptkIf->id,
            'notes' => 'Sedang diverifikasi kelengkapan dokumen oleh PTU.',
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
            'title' => 'Pengadaan Bahan Uji Kuat Tekan Beton & Aspal Lab Sipil',
            'department_id' => $ts->id,
            'fiscal_year_id' => $fy2026->id,
            'budget_bucket_id' => $bucket3->id,
            'amount' => 35000000.00,
            'status' => 'COMPLETED',
            'created_by' => $ptkTs->id,
            'notes' => 'Pencairan selesai dan SPJ telah diverifikasi.',
        ]);

        // 8. Log initial audit trail entries
        AuditLogService::log('SEED_INITIAL_DATA', 'System', 1, null, ['status' => 'SUCCESS']);
    }
}
