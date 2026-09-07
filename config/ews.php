<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Early Warning System (EWS) Configuration
    |--------------------------------------------------------------------------
    |
    | Baseline parameter thresholds for EWS.
    | EWS berfungsi sebagai sistem pemantauan dan peringatan dini (monitoring & advisory),
    | BUKAN financial blocking engine utama. Financial blocking tetap dijalankan oleh RBC.
    |
    | Nilai di bawah merupakan default development dan dapat di-override melalui
    | tabel database `rule_configs` tanpa mengubah hardcode di controller/komponen.
    |
    */

    'rules' => [
        // EWS-001: Saldo Kritis (available / current_budget <= X)
        'EWS-001' => [
            'name' => 'Saldo Kritis',
            'category' => 'BUDGET',
            'is_active' => true,
            'default_parameters' => [
                'critical_ratio' => 0.05, // 5%
                'warning_ratio' => 0.15,  // 15%
            ],
            'description' => 'Mendeteksi pos alokasi anggaran dengan rasio sisa saldo bebas terhadap pagu alokasi berjalan telah menipis atau habis.',
        ],

        // EWS-002: Stale Submission (DIAJUKAN > N hari tanpa perubahan)
        'EWS-002' => [
            'name' => 'Stale Submission',
            'category' => 'TRANSACTION',
            'is_active' => true,
            'default_parameters' => [
                'stale_days' => 3,       // N hari (default dev 3 hari)
                'critical_days' => 7,    // Eskalasi status ke CRITICAL jika > 7 hari
            ],
            'description' => 'Mendeteksi pengajuan berstatus DIAJUKAN yang tertahan di antrean pemeriksaan tanpa tindak lanjut.',
        ],

        // EWS-003: Revision Conflict (budget baru < commitment + realization)
        'EWS-003' => [
            'name' => 'Revision Conflict',
            'category' => 'REVISION',
            'is_active' => true,
            'default_parameters' => [
                'allow_deficit' => false,
            ],
            'description' => 'Mendeteksi usulan pagu revisi baru yang lebih rendah daripada total komitmen diajukan dan realisasi selesai.',
        ],

        // EWS-004: Unmapped Data (import/master belum termapping)
        'EWS-004' => [
            'name' => 'Unmapped Data',
            'category' => 'INTEGRATION',
            'is_active' => true,
            'default_parameters' => [
                'days_window' => 14,
            ],
            'description' => 'Mendeteksi baris data pada staging import atau transaksi yang belum memiliki pemetaan resmi ke master nomenklatur.',
        ],

        // EWS-005: Repeated Return (Pengembalian berulang)
        'EWS-005' => [
            'name' => 'Repeated Return',
            'category' => 'COMPLIANCE',
            'is_active' => true,
            'default_parameters' => [
                'threshold_return_count' => 2, // Dikembalikan >= 2 kali
            ],
            'description' => 'Mendeteksi transaksi yang berulang kali dikembalikan oleh verifikator karena kelengkapan administrasi bermasalah.',
        ],
    ],
];
