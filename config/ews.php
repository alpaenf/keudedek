<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Early Warning System (EWS) Configuration
    |--------------------------------------------------------------------------
    |
    | Acuan Utama: Proposal Tugas Akhir SIKARA (BAB III, Tabel 24-26, Persamaan 7-12)
    |
    | Indikator Inti Penelitian (Core Research Indicators):
    | 1. EWS-01: Saldo Tersedia Menipis (0 <= q <= theta_s, q = S/P * 100%)
    | 2. EWS-02: Transaksi DP Terlalu Lama dalam Proses (s_i = DP AND a_i > tau)
    | 3. EWS-03: Realisasi Internal di Bawah Target Periode (u < T)
    | 4. EX-01: Pengecualian Penggunaan Melampaui Pagu (S < 0)
    |
    | Nilai di bawah merupakan default development / ilustrasi proposal dan
    | dapat dikonfigurasi melalui tabel database `rule_configs` dengan versioning.
    |
    */

    'rules' => [
        // =========================================================================
        // 1. INDIKATOR INTI PENELITIAN (PROPOSAL SKRIPSI TABEL 24)
        // =========================================================================

        // EWS-01: Saldo Tersedia Menipis (Persamaan 7 & 8)
        'EWS-01' => [
            'name' => 'Saldo Tersedia Menipis',
            'category' => 'BUDGET',
            'indicator_type' => 'CORE',
            'is_active' => true,
            'default_parameters' => [
                'threshold_ratio' => 0.20, // theta_s: 20% ilustrasi proposal
                'warning_ratio' => 0.20,
                'critical_ratio' => 0.05,
                'operator' => '<=',
            ],
            'description' => 'Mendeteksi pos anggaran dengan rasio saldo tersedia (q = S/P * 100%) mencapai ambang batas perhatian (0 <= q <= theta_s).',
        ],

        // EWS-02: Transaksi Terlalu Lama Dalam Proses (Persamaan 9 & 10)
        'EWS-02' => [
            'name' => 'Transaksi Terlalu Lama Dalam Proses',
            'category' => 'TRANSACTION',
            'indicator_type' => 'CORE',
            'is_active' => true,
            'default_parameters' => [
                'threshold_days' => 30, // tau: 30 hari kalender ilustrasi proposal
                'stale_days' => 30,
                'critical_days' => 45,
                'operator' => '>',
            ],
            'description' => 'Mendeteksi transaksi DP yang umurnya (a_i = selisihHari(d_i, t)) melewati ambang batas hari kalender (a_i > tau).',
        ],

        // EWS-03: Realisasi Internal di Bawah Target Periode (Persamaan 11 & 12)
        'EWS-03' => [
            'name' => 'Realisasi Internal di Bawah Target Periode',
            'category' => 'REALIZATION',
            'indicator_type' => 'CORE',
            'is_active' => false, // Hanya aktif bila target kumulatif sah tersedia
            'default_parameters' => [
                'target_ratio' => null, // T_k(t) dalam persentase atau nominal
                'operator' => '<',
            ],
            'description' => 'Mendeteksi pos anggaran dengan rasio realisasi internal (u = R/P * 100%) di bawah target kumulatif yang sah (u < T). Berstatus belum dapat dievaluasi jika target tidak tersedia.',
        ],

        // EX-01: Informasi Pengecualian Saldo Negatif
        'EX-01' => [
            'name' => 'Penggunaan Melampaui Pagu',
            'category' => 'EXCEPTION',
            'indicator_type' => 'EXCEPTION',
            'is_active' => true,
            'default_parameters' => [
                'operator' => '<',
                'threshold_value' => 0.0,
            ],
            'description' => 'Pengecualian sistem yang terpicu ketika posisi saldo anggaran tersedia bernilai negatif (S < 0).',
        ],

        // =========================================================================
        // 2. ATURAN OPERASIONAL TAMBAHAN SISTEM (DI LUAR INDIKATOR INTI PROPOSAL)
        // =========================================================================

        'EWS-003' => [
            'name' => 'Revision Conflict',
            'category' => 'OPERATIONAL',
            'indicator_type' => 'OPERATIONAL',
            'is_active' => true,
            'default_parameters' => [
                'allow_deficit' => false,
            ],
            'description' => 'Mendeteksi usulan pagu revisi baru yang lebih rendah daripada total komitmen diajukan dan realisasi selesai.',
        ],

        'EWS-004' => [
            'name' => 'Unmapped Data',
            'category' => 'OPERATIONAL',
            'indicator_type' => 'OPERATIONAL',
            'is_active' => true,
            'default_parameters' => [
                'days_window' => 14,
            ],
            'description' => 'Mendeteksi baris data pada staging import atau transaksi yang belum memiliki pemetaan resmi ke master nomenklatur.',
        ],

        'EWS-005' => [
            'name' => 'Repeated Return',
            'category' => 'OPERATIONAL',
            'indicator_type' => 'OPERATIONAL',
            'is_active' => true,
            'default_parameters' => [
                'threshold_return_count' => 2,
            ],
            'description' => 'Mendeteksi transaksi yang berulang kali dikembalikan oleh verifikator karena kelengkapan administrasi bermasalah.',
        ],
    ],
];
