# DOKUMENTASI LENGKAP FITUR & HALAMAN SISTEM SIKARA FT UNSOED
**Sistem Informasi Keuangan dan Realisasi Anggaran (SIKARA) — Fakultas Teknik Universitas Jenderal Soedirman**

---

## DAFTAR ISI
1. [Struktur Pengguna & Peran (Role & Permission)](#1-struktur-pengguna--peran-role--permission)
2. [Halaman Publik / Autentikasi](#2-halaman-publik--autentikasi)
   - Landing Page (`/`)
   - Login Page (`/login`)
3. [Dashboard Overview (`/dashboard`) Berdasarkan Peran](#3-dashboard-overview-dashboard-berdasarkan-peran)
   - Dashboard PTK (Pelaksana Teknis Kegiatan)
   - Dashboard KAJUR (Ketua Jurusan)
   - Dashboard KAPRODI (Koordinator Program Studi)
   - Dashboard PTU (Pelaksana Tata Usaha / Verifikator SPJ)
   - Dashboard KABAG (Kepala Bagian Tata Usaha)
   - Dashboard WAKIL DEKAN (WD II / Pimpinan)
   - Dashboard DEKAN (Pimpinan Tertinggi Fakultas)
   - Dashboard ADMIN (Administrator Sistem)
4. [Modul Transaksi Anggaran](#4-modul-transaksi-anggaran)
   - Daftar Transaksi (`/submissions`)
   - Form Catat Transaksi Belanja PTK (`/submissions/create`) — *Input, Field, Dropdown, Rules*
   - Detail Transaksi Belanja (`/submissions/{id}`)
   - Cetak Bukti Kuitansi & Dokumen SPJ (`/submissions/{id}/print`)
   - Import Transaksi Batch Excel (`/submissions-import`)
5. [Modul Verifikasi & Persetujuan (`/approvals`)](#5-modul-verifikasi--persetujuan-approvals)
   - Workbench Pemeriksaan Transaksi & SPJ
   - Drawer Detail Pemeriksaan & Aturan RBC
   - Modal Aksi Keputusan: Verify, Return, Finalize
6. [Modul Early Warning System / EWS (`/warnings`)](#6-modul-early-warning-system--ews-warnings)
   - Early Warning Center (`/warnings`)
   - Detail Warning (`/warnings/{id}`)
7. [Modul Anggaran & Pagu](#7-modul-anggaran--pagu)
   - Master Pagu & Realisasi (`/budgets`)
   - Detail Pos Anggaran (`/budgets/{id}`)
   - Versi & Revisi Pagu (`/budget-versions`)
   - Komparasi Revisi Pagu (`/budget-versions/compare`)
   - Import Pagu DIPA Excel (`/budgets-import`)
8. [Modul Laporan & Ekspor (`/reports`)](#8-modul-laporan--ekspor-reports)
   - 8 Tipe Tab Laporan Realisasi
   - 13 Dimensi Filter
   - Ekspor: PDF, XLSX, CSV, DOCX, & Print Resmi
9. [Modul Administrasi & Master Data (Admin & Kabag)](#9-modul-administrasi--master-data-admin--kabag)
   - Master Organisasi: Unit Kerja, Jurusan, & Prodi (`/master/departments`)
   - Master Tahun Anggaran & Versi Pagu (`/master/fiscal-years`)
   - Master Struktur Anggaran DIPA / RKAKL (`/master/budget-structure`)
   - Manajemen Pengguna & Peran (`/users`)
   - Log Audit Trail (`/audit-logs`)

---

## 1. STRUKTUR PENGGUNA & PERAN (ROLE & PERMISSION)

Sistem SIKARA memiliki 8 tingkat peran (role) hierarkis:
1. **PTK (Pelaksana Teknis Kegiatan / Dosen / Lab)**:
   - Akses: Mencatat transaksi belanja kuitansi, melihat saldo pos anggarannya, memantau status persetujuan, cetak SPJ miliknya.
   - Ruang Lingkup: Terkunci ke Jurusan / Unit kerjanya sendiri.
2. **KAJUR (Ketua Jurusan)**:
   - Akses: Monitoring pagu jurusan, serapan prodi/lab, persetujuan internal jurusan, laporan keuangan jurusan.
   - Ruang Lingkup: 5 Jurusan (JTIF, JTS, JTE, JTG, JTI).
3. **KAPRODI (Koordinator Program Studi)**:
   - Akses: Monitoring pagu dan belanja yang dialokasikan khusus ke prodinya.
4. **PTU (Pelaksana Tata Usaha / Staf Keuangan)**:
   - Akses: Verifikator SPJ, memeriksa kelengkapan bukti fisik, verifikasi, pengembalian (return), dan finalisasi pencairan belanja.
5. **KABAG (Kepala Bagian Tata Usaha)**:
   - Akses: Rekapitulasi fakultas, verifikasi level lanjut, kontrol distribusi operasional, view konfigurasi master.
6. **WAKIL DEKAN (WD II Bidang Umum & Keuangan)**:
   - Akses: Persetujuan strategis fakultas, monitoring serapan makro, pengawasan EWS kritis.
7. **DEKAN**:
   - Akses: Eksekutif dashboard fakultas penuh, disposisi, pengawasan serapan total universitas/fakultas.
8. **ADMIN (Administrator Sistem)**:
   - Akses: Pengaturan master organisasi, tahun anggaran, impor DIPA, struktur kode RKAKL, role user, dan audit log.

---

## 2. HALAMAN PUBLIK / AUTENTIKASI

### A. Landing Page (`/`)
Halaman beranda portal resmi sebelum login:
- **Hero Section**: Judul SIKARA FT UNSOED, Ringkasan Pagu DIPA Fakultas, dan status transparansi keuangan.
- **Kartu Ringkasan Realisasi**: Total Pagu, Realisasi Fisik & Keuangan, Rasio Serapan.
- **Bagan Realisasi 5 Jurusan**: Grafik serapan JTIF, JTS, JTE, JTG, JTI.
- **Tombol Navigasi**: "Masuk ke Sistem" menuju `/login` atau langsung ke `/dashboard` jika sesi aktif.

### B. Login Page (`/login`)
Halaman autentikasi pengguna:
- **Form Login Mandiri**:
  - Input: *Email / Username* (Teks)
  - Input: *Password* (Password field dengan toggle lihat)
  - Tombol: *Masuk ke Akun*
- **Quick Role Switcher (Mode Demo/Pengembangan)**:
  - Tombol 1-klik untuk login cepat sebagai: `Admin`, `Dekan`, `Wakil Dekan`, `Kabag TU`, `Ketua Jurusan`, `Pelaksana TU (PTU)`, `Ketua Tim PTK`.

---

## 3. DASHBOARD OVERVIEW (`/dashboard`) BERDASARKAN PERAN

Setiap role disajikan tampilan dashboard yang disesuaikan secara otomatis:

### A. Dashboard PTK (`PtkDashboard.vue`)
- **Kartu KPI Finansial Scope Unit**:
  - *Sisa Saldo Bebas*: Nominal sisa yang dapat dibelanjakan saat ini.
  - *Dalam Proses (Komitmen)*: Nominal kuitansi yang sedang diperiksa bendahara.
  - *Realisasi Definitif*: Total kuitansi yang sudah disetujui final.
  - *Pagu Alokasi*: Total alokasi anggaran tahun berjalan.
- **Aksi Cepat (Quick Actions)**:
  - Tombol pintas `+ Catat Transaksi Belanja Baru`.
  - Tombol pintas `Lihat Rekap SPJ Saya`.
- **Tabel Transaksi Terkini PTK**:
  - Kolom: No. Bukti, Tanggal, Uraian Belanja, Pos Akun, Nominal, Status Badge (`Draft`, `Dalam Proses`, `Dikembalikan`, `Final`), Aksi (Detail, Cetak).
- **Peringatan Pagu Kritis**: Notifikasi otomatis jika sisa saldo pos belanja PTK di bawah batas aman.

### B. Dashboard KAJUR (`KajurDashboard.vue`)
- **Kartu Ringkasan Jurusan**:
  - Pagu Total Jurusan, Realisasi Berjalan, Dalam Proses, dan Rasio Persentase Serapan Jurusan.
- **Tabel Pagu per Subkomponen / Lab**:
  - Rincian belanja praktikum lab, operasional prodi, dan pemeliharaan alat.
- **Daftar Pengajuan Butuh Perhatian**:
  - Daftar transaksi staf dosen/lab yang statusnya `Dikembalikan (Returned)` atau tertahan di verifikasi.

### C. Dashboard KAPRODI (`KaprodiDashboard.vue`)
- **Ringkasan Pagu Spesifik Prodi**:
  - Anggaran khusus akreditasi, kurikulum, praktikum mahasiswa prodi.
  - Realisasi dan sisa alokasi operasional prodi.

### D. Dashboard PTU / Verifikator (`PtuDashboard.vue`)
- **Antrean Kerja Pemeriksaan**:
  - *Pengajuan Baru*: Jumlah berkas masuk yang belum disentuh.
  - *Dalam Pemeriksaan*: Sedang diverifikasi fisik SPJ-nya.
  - *Perlu Revisi (Returned)*: Berkas yang dikembalikan ke PTK.
  - *Siap Finalisasi*: Menunggu pencairan definitif.
- **Tombol Langsung**: Menuju *Workbench Verifikasi SPJ* (`/approvals`).

### E. Dashboard KABAG & WAKIL DEKAN & DEKAN (`KabagDashboard`, `WdDashboard`, `DekanDashboard`)
- **Metrik Keuangan Makro Fakultas**:
  - Total Pagu Fakultas (contoh: Rp 24,8 Milyar).
  - Total Realisasi Fakultas & Persentase Serapan (Target vs Capaian).
  - Total Saldo Belum Terserap.
- **Grafik Komparasi Antar Jurusan**:
  - Diagram batang serapan 5 jurusan: Teknik Informatika, Sipil, Elektro, Geologi, Industri.
- **Widget Early Warning Kritis**:
  - Peringatan instan jurusan yang serapannya sangat rendah atau memiliki transaksi macet > 3 hari.

### F. Dashboard ADMIN (`AdminDashboard.vue`)
- Status sistem, total transaksi tersimpan, jumlah pengguna aktif per peran, status versi pagu DIPA yang sedang aktif, dan jalan pintas ke Impor Data dan Log Audit.

---

## 4. MODUL TRANSAKSI ANGGARAN

### A. Daftar Transaksi Anggaran (`/submissions`)
Halaman rekap seluruh transaksi belanja:
- **Bilah Filter Lengkap**:
  - *Pencarian*: Nomor bukti, uraian belanja, atau nama pembuat.
  - *Dropdown Tahun Anggaran*: 2026, 2025, 2024.
  - *Dropdown Sumber Dana*: Rupiah Murni (RM), BOPTN, PNBP/BLU.
  - *Dropdown Unit Kerja / Jurusan*: Fakultas Teknik, JTIF, JTS, JTE, JTG, JTI.
  - *Dropdown Program Studi*: S1 Informatika, S1 Sipil, S1 Elektro, dll.
  - *Dropdown Status*: `DRAFT`, `PROCESSING`, `RETURNED`, `FINAL`, `CANCELLED`.
  - *Dropdown Akun Belanja*: 521111, 521211, 524111, dll.
  - *Input Rentang Tanggal*: Dari tanggal s/d tanggal.
- **Tabel Transaksi**:
  - No. Bukti Kuitansi & Tanggal Transaksi.
  - Uraian Transaksi & Nama PTK Pengusul.
  - Unit Kerja / Jurusan & Kode Akun DIPA.
  - Nominal Transaksi (Format Rupiah).
  - Status Badge (Warna standar: Biru=Draft, Oranye=Dalam Proses, Kuning=Dikembalikan, Hijau=Final, Merah=Dibatalkan).
  - Tombol Aksi: Lihat Detail, Cetak Kuitansi.

---

### B. Form Catat Transaksi Belanja PTK (`/submissions/create`)
Formulir pencatatan transaksi yang mengadopsi prinsip **Minimum Input & Otomatisasi Struktur Anggaran**:

#### 1. Context Bar Atas (Otomatis & Read-Only)
Menampilkan konteks aktif:
- Tahun Anggaran (cth: `2026`)
- Sumber Dana (cth: `RM - Rupiah Murni`)
- Versi Revisi Pagu (cth: `Rev 02`)
- Jurusan Pengelola (cth: `JTIF`)

#### 2. STEP 1 — Pilih Pos Pagu Anggaran
PTK memilih pos belanja tanpa mengetik ulang kode RKAKL:
- **Input Search Bar**: Pencarian cepat real-time berdasarkan kode akun, nama akun, nama kegiatan, atau subkomponen.
- **Daftar Kartu Pos Anggaran Tersedia**:
  - Menampilkan: `[Kode Akun] Nama Akun`, Jurusan, Kegiatan, KRO, RO, Subkomponen.
  - Ringkasan Saldo: Nilai Pagu, Nilai Dalam Proses, Realisasi, dan **Sisa Saldo Bebas**.
  - Tombol `Pilih Pos Ini`.

#### 3. STEP 2 — Detail Struktur Anggaran Resmi (Expandable Accordion)
Jika pos anggaran dipilih, sistem menampilkan pohon hierarki RKAKL DIPA secara otomatis (Read-Only):
- **Tahun Anggaran**
- **Sumber Dana**
- **Versi Revisi**
- **Jurusan Pengelola**
- **Program**: Kode & Nama Resmi (cth: `023.17.WA Program Dukungan Manajemen`)
- **Kegiatan**: Kode & Nama Resmi (cth: `4257 Dukungan Manajemen Ditjen Dikti`)
- **KRO**: Klasifikasi Rincian Output (cth: `7734.EBA Layanan Dukungan Manajemen Internal`)
- **RO**: Rincian Output (cth: `994 Layanan Perkantoran`)
- **Komponen**: (cth: `001 Operasional & Pemeliharaan Kantor`)
- **Subkomponen**: (cth: `AA Operasional & Praktikum Informatika`)
- **Akun Belanja 6 Digit**: (cth: `521211 Belanja Bahan`)
- **Subakun**: (cth: `521211.001 Alokasi Bahan Lab Komputer`)

#### 4. STEP 3 — Input Rincian Transaksi PTK (Minimum Input)
Field yang diisi secara manual oleh PTK:
1. **Nomor Bukti / Kuitansi** *(Wajib)*:
   - Tipe: Teks.
   - Fitur: Terisi otomatis dengan format standar rekomendasi: `BKT/{KODE_JURUSAN}/{TAHUN}/{BULAN}/{RANDOM_NO}` (dapat diedit sesuai kuitansi fisik).
2. **Tanggal Transaksi** *(Wajib)*:
   - Tipe: Date Picker (default ke hari ini).
3. **Uraian Belanja / Keterangan Transaksi** *(Wajib)*:
   - Tipe: Teks panjang (Contoh: *"Pembelian modul praktikum mikrokontroler semester gasal"*).
4. **Nominal Transaksi (Rp)** *(Wajib)*:
   - Tipe: Angka (Rupiah).
   - Fitur: Live format currency rupiah di bawah input saat mengetik.
5. **Program Studi Terkait** *(Opsional)*:
   - Tipe: Dropdown Pilihan.
   - Pilihan: `-- Tidak Terikat Prodi Khusus (Level Jurusan) --`, atau daftar nama prodi aktif (S1 Informatika, S1 Teknik Sipil, dll).
6. **Catatan Tambahan** *(Opsional)*:
   - Tipe: Textarea untuk pesan ke verifikator PTU/Bendahara.
7. **Lampiran Berkas / Bukti SPJ** *(Opsional/Fleksibel)*:
   - Tipe: File Upload (Mendukung PDF, PNG, JPG, ZIP).

#### 5. STEP 4 — Budget Check & Simulasi Saldo Real-Time (Aturan RBC-001)
Kalkulator real-time sebelum submit:
- Menampilkan 4 Kartu: `Pagu Aktif`, `Dalam Proses`, `Realisasi Saat Ini`, dan `Saldo Saat Ini`.
- **Banner Validasi Real-Time (RBC-001 Overbudget Protection)**:
  - **Kondisi 1: Saldo Cukup**: Banner hijau bertuliskan *"✓ Anggaran mencukupi. Pos anggaran memiliki saldo yang cukup untuk memproses transaksi ini."* Menampilkan nilai *Projected Sisa Saldo*.
  - **Kondisi 2: Saldo Kurang (Overbudget)**: Banner merah bertuliskan *"✕ Anggaran tidak mencukupi (Overbudget). Nominal transaksi melebihi saldo tersedia. Defisit kekurangan: Rp X.XXX.XXX. Transaksi diblokir oleh aturan RBC-001."*

#### 6. STEP 5 — Tombol Simpan
- **Tombol "Simpan Draft"**: Menyimpan transaksi tanpa mengurangi saldo komitmen.
- **Tombol "Simpan & Proses"**: Mengunci saldo (masuk ke `reserved_budget`), status berubah menjadi `PROCESSING`, dan berkas diteruskan ke antrean PTU. *Tombol ini otomatis disabled jika kondisi Overbudget.*

---

### C. Detail Transaksi Belanja (`/submissions/{id}`)
- Ringkasan identitas kuitansi dan biodata pengusul.
- Rincian hierarki RKAKL DIPA yang dipotong.
- Riwayat Perubahan Status (Timeline Jejak Audit): Siapa yang membuat, memverifikasi, mengembalikan, atau memfinalisasi beserta tanggal & jamnya.
- Dokumen lampiran kuitansi/bukti transaksi yang dapat diunduh.
- Tombol Cetak / Ekspor PDF / Ekspor DOCX.

### D. Cetak Bukti Kuitansi & SPJ (`/submissions/{id}/print`)
- Template resmi kuitansi standar Fakultas Teknik UNSOED.
- Siap cetak (Print CSS responsive) lengkap dengan kolom tanda tangan:
  - Pelaksana Kegiatan / Yang Menerima
  - Ketua Jurusan / Pejabat Pembuat Komitmen
  - Bendahara Pengeluaran Pembantu / Verifikator PTU.

### E. Import Transaksi Batch Excel (`/submissions-import`)
- Fitur untuk mengunggah ratusan transaksi sekaligus dari file Excel/CSV:
  - Upload file batch.
  - Layar Staging: validasi kode pos anggaran dan kecukupan saldo tiap baris.
  - Komit transaksi massal ke database utama.

---

## 5. MODUL VERIFIKASI & PERSETUJUAN (`/approvals`)

Khusus untuk role verifikator finansial (`PTU`, `KABAG`, `WD`, `DEKAN`):

### A. Workbench Pemeriksaan Transaksi & SPJ
- **5 Tab Antrean Status Transaksi**:
  1. `[BARU / DRAFT]` (Jumlah pengajuan baru masuk).
  2. `[DALAM PROSES]` (Sedang diperiksa berkas fisiknya).
  3. `[DIKEMBALIKAN]` (Pengajuan yang membutuhkan revisi PTK).
  4. `[FINAL]` (Transaksi yang telah cair definitif).
  5. `[ISSUE / PERMASALAHAN]` (Transaksi yang overbudget atau dibatalkan).
- **Filter Pencarian**: No bukti, nama pengusul, jurusan, atau akun.
- **Tabel Antrean**: No Bukti, Tanggal, Pengusul, Jurusan, Akun, Nominal, Usia Pengajuan (cth: *"2 hari yang lalu"*), Tombol Periksa.

### B. Drawer Detail Pemeriksaan (Slide-over Panel)
Saat tombol "Periksa" diklik, drawer samping terbuka menampilkan:
- **Snapshot Finansial 7 Segmen**: Konteks RKAKL lengkap.
- **Status RBC-001 (Solvensi Saldo)**: Indikator hijau PASSED atau merah DEFISIT.
- **Status RBC-006 (Pemeriksaan Duplikasi Bukti)**: Memastikan nomor kuitansi belum pernah digunakan.
- **Daftar Dokumen Lampiran SPJ**: Tombol lihat/unduh file bukti.

### C. Modal Aksi Keputusan Verifikator
Tiga tombol aksi verifikasi:
1. **Aksi 1: VERIFIKASI (Lolos Tahap Awal)**:
   - Status: Diubah menjadi `PROCESSING`.
   - Saldo komitmen tetap terkunci aman.
   - Input: Catatan verifikator (opsional).
2. **Aksi 2: KEMBALIKAN KE PTK (Return / Butuh Revisi)**:
   - Status: Diubah menjadi `RETURNED`.
   - **Efek Saldo**: Saldo komitmen dilepas kembali menjadi saldo bebas (`auto-release`).
   - Input: *Alasan / Catatan Perbaikan* **(WAJIB DIISI)**.
3. **Aksi 3: FINALISASI (Cair / Definitif)**:
   - Status: Diubah menjadi `FINAL`.
   - **Efek Saldo**: Saldo komitmen dikurangi dan nilai realisasi definitif (`realized_budget`) bertambah secara atomik di database.

---

## 6. MODUL EARLY WARNING SYSTEM / EWS (`/warnings`)

Pusat deteksi dini anomali anggaran dan transaksi secara otomatis:

### A. Early Warning Center (`/warnings`)
- **Kartu Ringkasan EWS**: Total Peringatan Terbuka, Total Kritis, Peringatan Sedang Diproses, dan Peringatan Selesai.
- **Tombol "Jalankan Re-Evaluasi Sistem"**: Menjalankan engine algoritma EWS real-time di server untuk memindai seluruh database.
- **5 Aturan Deteksi Dini (Rule EWS)**:
  1. `EWS-001: Saldo Kritis (Critical Available Balance)`: Muncul jika sisa saldo pos belanja <= 5% (Kritis) atau <= 15% (Peringatan).
  2. `EWS-002: Serapan Sangat Tinggi (High Budget Utilization)`: Muncul jika total serapan + komitmen > 85%.
  3. `EWS-003: Transaksi Tertahan (Stale Processing)`: Muncul jika transaksi statusnya 'Dalam Proses' > 3 hari kerja tanpa keputusan.
  4. `EWS-004: Konflik Revisi (Revision Conflict)`: Muncul jika revisi pemotongan pagu lebih besar dari saldo yang tersisa.
  5. `EWS-005: Data Belum Terpetakan (Unmapped Data)`: Pos pagu yang belum memiliki mapping jurusan/prodi valid.
- **Filter EWS**: Filter Tahun Anggaran, Jurusan, Akun Belanja, Kode Rule, Severity (`INFO`, `WARNING`, `HIGH`, `CRITICAL`), dan State (`OPEN`, `ACKNOWLEDGED`, `RESOLVED`).

### B. Halaman Warning Detail (`/warnings/{id}`)
- Menampilkan rumus kalkulasi yang memicu alarm.
- Context anggaran yang bermasalah.
- Tombol `Acknowledge (Tandai Sedang Ditangani)` dan `Resolve (Tandai Selesai)`.
- Link langsung ke Pos Anggaran terkait dan Transaksi terkait.

---

## 7. MODUL ANGGARAN & PAGU

### A. Master Pagu & Realisasi (`/budgets`)
- Daftar seluruh alokasi anggaran (Pos Pagu) di Fakultas Teknik.
- Kolom: Kode Lengkap Subkomponen, Akun Belanja, Jurusan Pengelola, Pagu DIPA, Dalam Proses, Realisasi, Saldo Bebas, dan Rasio Serapan (%).
- Pencarian multi-dimensi (Program, Kegiatan, KRO, RO, Komponen, Akun).

### B. Versi & Revisi Pagu (`/budget-versions`)
- Mengelola versi DIPA (DIPA Induk, Revisi 01, Revisi 02, dst).
- Melihat tanggal SK pengesahan, status versi (`Aktif`, `Draft`, `Arsip`).
- Tombol `Aktivasi Versi Pagu`: Mengubah versi aktif secara transaksional di database.

### C. Komparasi Revisi Pagu (`/budget-versions/compare`)
- Memilih dua versi DIPA (contoh: DIPA Awal vs Revisi 01).
- Menampilkan perbandingan nominal baris per baris: Pos mana yang bertambah, berkurang, atau pos baru.

### D. Import Pagu DIPA Excel (`/budgets-import`)
- Fitur mengunggah file Buku Pagu Alokasi DIPA resmi format Excel kementerian.
- Sistem mengekstrak struktur kode otomatis ke staging area sebelum dikomit ke tabel definitif.

---

## 8. MODUL LAPORAN & EKSPOR (`/reports`)

Modul pelaporan komprehensif bagi pimpinan dan audit internal:

### A. 8 Tab Laporan Realisasi
1. **Realisasi per Jurusan**: Rekapitulasi serapan 5 jurusan + fakultas.
2. **Pagu vs Dalam Proses vs Realisasi**: Komparasi saldo aman vs komitmen belanja.
3. **Realisasi per Akun Belanja**: Rekap per kode 521111, 521211, 524111, dsb.
4. **Realisasi per Kegiatan DIPA**: Berdasarkan kegiatan kementerian (cth: 4257).
5. **Realisasi per Program Studi**: Khusus belanja yang terpetakan ke prodi.
6. **Transaksi per Periode**: Rincian transaksi harian/bulanan.
7. **Saldo Anggaran**: Sisa saldo yang belum terserap.
8. **Early Warning Summary & Revision Comparison**: Rekap riwayat alarm dan perubahan pagu.

### B. 13 Dimensi Filter
Dapat difilter secara fleksibel berdasarkan:
1. Tahun Anggaran (TA)
2. Versi Revisi Pagu
3. Sumber Dana
4. Jurusan / Unit Kerja
5. Program Studi
6. Program
7. Kegiatan
8. KRO
9. RO
10. Subkomponen
11. Kode Akun
12. Rentang Periode Tanggal
13. Status Transaksi

### C. Fitur Ekspor 5 Format
- **Export PDF**: Menggunakan format resmi naskah dinas universitas.
- **Export XLSX**: Spreadsheet Excel dengan format angka siap olah.
- **Export CSV**: Data tabular mentah untuk analisis lanjut.
- **Export DOCX**: Dokumen Word yang dapat diedit untuk lampiran laporan dekan.
- **Print Langsung**: Format cetak printer ramah kertas (Print CSS khusus).
- *Setiap aksi ekspor tercatat otomatis di Log Audit Sistem.*

---

## 9. MODUL ADMINISTRASI & MASTER DATA (ADMIN & KABAG)

Hanya dapat diakses oleh Administrator dan Kabag TU:

### A. Master Organisasi (`/master/departments`)
- **Tab 1 — Unit Kerja & Jurusan**:
  - Mengelola data Fakultas (`FACULTY`) dan 5 Jurusan (`DEPARTMENT`).
  - Field: Kode Unit, Nama Unit, Total Pos Pagu Terhubung, Pengguna Terdaftar, Status Aktif.
  - Pengaman *Delete Safety*: Unit yang memiliki pos pagu atau transaksi dilarang dihapus (hanya bisa dinonaktifkan).
- **Tab 2 — Program Studi**:
  - Mengelola data 10 Program Studi (S1 & S2) di bawah naungan jurusan.

### B. Master Tahun Anggaran & Versi Pagu (`/master/fiscal-years`)
- **Tab 1 — Tahun Anggaran**: Tambah TA baru (cth: 2026, 2027), tanggal mulai, tanggal selesai, dan status aktif.
- **Tab 2 — Sumber Dana**: Kelola kode Rupiah Murni (RM), BOPTN, PNBP, BLU.
- **Tab 3 — Versi Pagu DIPA**: Registrasi dokumen revisi pagu dan switch versi aktif.

### C. Master Struktur Anggaran DIPA (`/master/budget-structure`)
Pusat kontrol nomenklatur anggaran resmi dengan 8 Tab:
1. `[Program]` (Kode, Nama, Tahun, Sumber, Status, Digunakan oleh X pos)
2. `[Kegiatan]` (Kode, Nama, Program Induk, Status)
3. `[KRO]` (Klasifikasi Rincian Output, Kegiatan Induk)
4. `[RO]` (Rincian Output, KRO Induk)
5. `[Komponen]` (Kode, Nama Komponen)
6. `[Subkomponen]` (Kode Subkomponen, Warna Header)
7. `[Akun]` (Kode Akun 6 digit, Nama Resmi Akun DIPA Kementerian)
8. `[Subakun]` (Rincian subakun belanja)
- Fitur: Pencarian kode/nama, badge tipe sumber (`Official Import`, `Official Document`, `Internal`, `Needs Validation`), perlindungan kode resmi agar tidak diubah sembarangan oleh user biasa.

### D. Manajemen Pengguna & Peran (`/users`)
- Tambah, edit, dan hapus akun sistem.
- Penugasan peran: `ADMIN`, `DEKAN`, `WD`, `KABAG`, `KAJUR`, `KAPRODI`, `PTU`, `PTK`.
- Penugasan Unit Kerja: Menentukan jurusan/fakultas yang menjadi wewenang user tersebut.

### E. Log Audit Trail (`/audit-logs`)
- Merekam seluruh aktivitas penting di sistem demi transparansi dan audit BPK/Inspektorat:
  - Pencatatan transaksi belanja baru.
  - Verifikasi, penolakan, dan finalisasi oleh PTU.
  - Ekspor laporan PDF/Excel.
  - Perubahan master data organisasi atau versi pagu.
  - Detail payload menampilkan data sebelum dan sesudah diubah beserta identitas aktor, IP address, dan timestamp.
