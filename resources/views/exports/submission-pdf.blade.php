<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Usulan & Bukti Belanja - {{ $submission->evidence_number ?: $submission->submission_number }}</title>
    <style>
        @page {
            margin: 1.5cm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 3px double #0f172a;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo {
            width: 70px;
            height: auto;
        }
        .institution-title {
            text-align: center;
        }
        .institution-title h3 {
            margin: 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .institution-title h2 {
            margin: 2px 0;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: bold;
            color: #1e3a8a;
        }
        .institution-title p {
            margin: 0;
            font-size: 9.5px;
            color: #475569;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 15px;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 13px;
            text-transform: uppercase;
            text-decoration: underline;
            color: #0f172a;
        }
        .doc-title p {
            margin: 2px 0 0 0;
            font-size: 11px;
            font-weight: bold;
            color: #475569;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 6px;
        }
        .meta-table td {
            padding: 2.5px 4px;
            font-size: 10.5px;
        }
        .meta-table td.label {
            color: #64748b;
            width: 25%;
        }
        .meta-table td.val {
            font-weight: 600;
            color: #0f172a;
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
        }
        .data-table td.text-center { text-align: center; }
        .data-table td.text-right { text-align: right; }
        .total-row {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 11px;
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
            font-size: 10.5px;
        }
        .signoff-space {
            height: 55px;
        }
        .signoff-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .security-badge {
            margin-top: 20px;
            padding: 6px 10px;
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
            font-size: 9px;
            color: #64748b;
            text-align: center;
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

    <!-- Judul Dokumen -->
    <div class="doc-title">
        <h2>SURAT USULAN &amp; BUKTI BELANJA ANGGARAN</h2>
        <p>Nomor Bukti: {{ $submission->evidence_number ?: $submission->submission_number }}</p>
    </div>

    <!-- Metadata Pengajuan -->
    <table class="meta-table">
        <tr>
            <td class="label">Unit / Jurusan</td>
            <td class="val">: {{ $submission->department?->code }} — {{ $submission->department?->name }}</td>
            <td class="label">Mata Anggaran (Akun)</td>
            <td class="val">: [{{ $submission->budgetBucket?->account_code }}] {{ $submission->budgetBucket?->account_name }}</td>
        </tr>
        <tr>
            <td class="label">Nama Kegiatan</td>
            <td class="val">: {{ $submission->title }}</td>
            <td class="label">Sumber Dana</td>
            <td class="val">: {{ $submission->budgetBucket?->fundingSource?->name ?? 'Rupiah Murni (RM)' }}</td>
        </tr>
        <tr>
            <td class="label">Penerima Pembayaran</td>
            <td class="val">: {{ $submission->beneficiary_name ?: '-' }}</td>
            <td class="label">Total Nilai Belanja</td>
            <td class="val">: <strong>Rp {{ number_format($submission->amount, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Tanggal Transaksi</td>
            <td class="val">: {{ date('d F Y', strtotime($submission->transaction_date ?: $submission->created_at)) }}</td>
            <td class="label">Status Transaksi</td>
            <td class="val">: {{ $submission->status }}</td>
        </tr>
    </table>

    <!-- Rincian Komponen Barang / Jasa -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 50%;">Uraian Spesifikasi Item / Belanja</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 17%;" class="text-right">Harga Satuan (Rp)</th>
                <th style="width: 18%;" class="text-right">Total Harga (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submission->items as $idx => $item)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $item->item_name }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->total_price, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td class="text-center">1</td>
                <td>{{ $submission->title }}</td>
                <td class="text-center">1</td>
                <td class="text-right">{{ number_format($submission->amount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($submission->amount, 0, ',', '.') }}</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL USULAN BELANJA:</td>
                <td class="text-right">Rp {{ number_format($submission->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Tanda Tangan Resmi -->
    <table class="signoff-table">
        <tr>
            <td>
                Mengetahui / Mengajukan,<br>
                Pengelola Transaksi Kegiatan (PTK)<br>
                <div class="signoff-space"></div>
                <div class="signoff-name">{{ $submission->creator?->name ?? 'Operator PTK' }}</div>
                <div>NIP. {{ $submission->creator?->nip ?? '........................' }}</div>
            </td>
            <td>
                Purwokerto, {{ date('d F Y') }}<br>
                Verifikator PTU / Bendahara Pengeluaran<br>
                <div class="signoff-space"></div>
                <div class="signoff-name">{{ $signoffApproval?->user?->name ?? 'Bendahara Pengeluaran FT' }}</div>
                <div>NIP. {{ $signoffApproval?->user?->nip ?? '........................' }}</div>
            </td>
        </tr>
    </table>

    <!-- Security & Audit Hash Footprint -->
    <div class="security-badge">
        Dokumen dicetak secara resmi melalui <strong>SIKARA FT UNSOED</strong> &bull; Nomor Autentikasi: {{ sha1($submission->id . $submission->created_at) }} &bull; Waktu Cetak: {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
