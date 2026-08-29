# REPORTING & EXPORT SPECIFICATION

# SIPEDA - Spesifikasi Modul Laporan & Ekspor Data
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Overview Reporting Module

Modul **Reporting** menyajikan rekapitulasi data keuangan, kinerja penyerapan anggaran, status pengajuan, serta riwayat EWS untuk kebutuhan analisis pimpinan (Wakil Dekan, KABAG, KAJUR).

---

## 2. Standard Reports Catalog

### 2.1 Laporan Realisasi Anggaran (LRA) Unit & Fakultas
- **Tujuan:** Menampilkan ringkasan Pagu, Reservasi, Realisasi, Saldo, dan Persentase Serapan per Jurusan/Unit.
- **Group By:** Tahun Anggaran, Sumber Dana, Unit.
- **Filter:** Rentang Tanggal, Sumber Dana, Jurusan.

### 2.2 Laporan Status Pengajuan Anggaran
- **Tujuan:** Memantau posisi dan durasi pengajuan pada setiap tahap workflow (Draft, Review, Approved, Completed).
- **Metric Key:** Total pengajuan, rata-rata waktu pemrosesan, pengajuan dalam komitmen (*reserved*).

### 2.3 Laporan Histori Early Warning System (EWS)
- **Tujuan:** Merekam kejadian warning EWS yang pernah terpicu, tingkat severity, serta tindakan penyelesaian yang telah diambil.

### 2.4 Laporan Matriks Perbandingan Revisi
- **Tujuan:** Menyajikan analisis *before-vs-after* dari setiap revisi pagu yang terjadi selama tahun anggaran berjalan.

---

## 3. Export Formats & Technical Implementation

| Format | Pustaka Backend / Adapter | Penggunaan Utama | Ciri Khas |
|---|---|---|---|
| **Excel (`.xlsx`)** | Laravel Excel (`maatwebsite/excel`) / PhpSpreadsheet | Operasional & Olah Data | Multiple Worksheets, Formula Jumlah, Formatting Currency |
| **PDF (`.pdf`)** | Dompdf / Snappy (wkhtmltopdf) | Cetak Dokumen / Arsip Resmi | Clean Layout Print, Header/Footer Resmi Fakultas Teknik, Sign-off Block |
| **CSV (`.csv`)** | Native Stream Writer | Export Data Mentah | Ringan, Cocok untuk integrasi data tingkat lanjut |

---

## 4. PDF Export Layout Template Guidelines

Dokumen PDF yang dicetak wajib mengikuti standar berikut:
- **Header:** Logo Universitas Jenderal Soedirman + Kop Surat Resmi Fakultas Teknik.
- **Format Angka:** Format Rupiah standar (`Rp 150.000.000,00`).
- **Sign-off Block:** Penandatanganan resmi oleh KABAG Keuangan / KAJUR di bagian bawah dokumen.
