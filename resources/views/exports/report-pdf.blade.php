<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Realisasi Anggaran - FT UNSOED</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 3px double #0f172a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo {
            width: 75px;
            height: auto;
        }
        .institution-title {
            text-align: center;
        }
        .institution-title h3 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .institution-title h2 {
            margin: 2px 0;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
            color: #1e3a8a;
        }
        .institution-title p {
            margin: 0;
            font-size: 10px;
            color: #475569;
        }
        .report-title {
            text-align: center;
            margin-bottom: 15px;
        }
        .report-title h1 {
            margin: 0;
            font-size: 15px;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .report-title p {
            margin: 3px 0 0 0;
            font-size: 11px;
            color: #64748b;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .meta-table td {
            padding: 3px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 10px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .data-table td.text-center {
            text-align: center;
        }
        .data-table td.text-right {
            text-align: right;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table .total-row {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 10.5px;
        }
        .signoff-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signoff-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .signoff-space {
            height: 60px;
        }
        .signoff-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .badge-active {
            color: #16a34a;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Kop Surat Resmi FT UNSOED -->
    <table class="header-table">
        <tr>
            <td style="width: 10%;">
                <img src="https://unsoed.ac.id/wp-content/uploads/2023/11/logo-unsoed.png" alt="Logo UNSOED" class="logo" onerror="this.style.display='none';">
            </td>
            <td class="institution-title" style="width: 90%;">
                <h3>KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
                <h2>UNIVERSITAS JENDERAL SOEDIRMAN</h2>
                <h2>FAKULTAS TEKNIK</h2>
                <p>Jl. Mayjen HR. Boenyamin No. 708 Purwokerto 53122 | Telp. (0281) 638792 | Web: ft.unsoed.ac.id</p>
            </td>
        </tr>
    </table>

    <!-- Judul Laporan -->
    <div class="report-title">
        <h1>LAPORAN REALISASI &amp; PENGENDALIAN ANGGARAN</h1>
        <p>SIKARA — Sistem Informasi Kendali Anggaran dan Realisasi</p>
    </div>

    <!-- Metadata Laporan -->
    <table class="meta-table">
        <tr>
            <td style="width: 15%;"><strong>Tahun Anggaran</strong></td>
            <td style="width: 35%;">: {{ $activeYear ?? '2026' }}</td>
            <td style="width: 15%;"><strong>Tanggal Cetak</strong></td>
            <td style="width: 35%;">: {{ date('d F Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Sumber Dana</strong></td>
            <td>: Rupiah Murni (RM)</td>
            <td><strong>Filter Unit / Jurusan</strong></td>
            <td>: {{ $selectedDepartmentName ?? 'Seluruh Unit / Jurusan Fakultas Teknik' }}</td>
        </tr>
    </table>

    <!-- Tabel Data Rekapitulasi -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">Kode Akun</th>
                <th style="width: 24%;">Nama Pos / Akun Anggaran</th>
                <th style="width: 18%;">Unit / Jurusan</th>
                <th style="width: 11%;">Pagu Alokasi</th>
                <th style="width: 11%;">Komitmen (Reserved)</th>
                <th style="width: 11%;">Realisasi (Final)</th>
                <th style="width: 11%;">Saldo Tersedia</th>
            </tr>
        </thead>
        <tbody>
            @forelse($buckets as $index => $bucket)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center"><strong>{{ $bucket->account_code }}</strong></td>
                    <td>{{ $bucket->account_name }}</td>
                    <td>{{ $bucket->department->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($bucket->allocated_budget, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($bucket->reserved_budget, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($bucket->realized_budget, 0, ',', '.') }}</td>
                    <td class="text-right" style="{{ $bucket->available_balance < ($bucket->allocated_budget * 0.15) ? 'color: #dc2626; font-weight: bold;' : '' }}">
                        Rp {{ number_format($bucket->available_balance, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">Tidak ada data pos anggaran yang ditemukan.</td>
                </tr>
            @endforelse
            
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL ANGGARAN FAKULTAS</strong></td>
                <td class="text-right">Rp {{ number_format($totalAllocated, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalReserved, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalRealized, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalAvailable, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Sign-off Block -->
    <table class="signoff-table">
        <tr>
            <td>
                <p>Mengetahui,<br><strong>Penanggung Jawab Unit / Jurusan</strong></p>
                <div class="signoff-space"></div>
                <p class="signoff-name">( .................................................... )</p>
                <p>NIP. ....................................................</p>
            </td>
            <td>
                <p>Purwokerto, {{ date('d F Y') }}<br><strong>Kepala Bagian Keuangan / Tata Usaha</strong></p>
                <div class="signoff-space"></div>
                <p class="signoff-name">Kabag Keuangan FT UNSOED</p>
                <p>NIP. 19780512 200312 1 002</p>
            </td>
        </tr>
    </table>

</body>
</html>
