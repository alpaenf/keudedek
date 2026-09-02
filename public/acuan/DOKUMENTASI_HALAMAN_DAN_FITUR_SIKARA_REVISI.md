# DOKUMENTASI HALAMAN & FITUR SIKARA FT UNSOED — REVISI BASELINE

**SIKARA — Sistem Informasi Kendali Anggaran dan Realisasi**  
**Fakultas Teknik Universitas Jenderal Soedirman**

> Dokumen ini menggantikan arah fitur lama pada `DOKUMENTASI_HALAMAN_DAN_FITUR_SIKARA.md` sebagai baseline implementasi terbaru.  
> Fokus MVP: **Rupiah Murni (RM), monitoring dan pengendalian realisasi internal per jurusan, input PTK sederhana berbasis Nomor Urut RBA, Rule-Based Budget Control, monitoring, EWS, laporan, dan audit.**

---

## 0. POSISI SISTEM

SIKARA adalah **lapisan monitoring dan pengendalian internal Fakultas Teknik**, bukan aplikasi perencanaan anggaran dan bukan pengganti SAKTI/ELFINA.

SIKARA mengambil pola input sederhana yang sudah familiar pada SISTRAN:

**PTK memilih Nomor Urut RBA → mengisi tanggal, No FRA/bukti, uraian aktual, nominal → mengajukan.**

Perbedaannya, SIKARA menambahkan:

- pagu RM terversi/revisi,
- scope jurusan,
- pemisahan budget line dan control bucket,
- penguncian saldo saat transaksi diajukan,
- pencegahan overbudget,
- monitoring lintas role,
- early warning,
- laporan,
- audit trail.

### Prinsip desain utama

```text
MASTER NOMENKLATUR
        ≠
BUDGET LINE / NO URUT RBA
        ≠
CONTROL BUCKET
        ≠
TRANSAKSI
```

- **Master nomenklatur** tetap detail untuk traceability.
- **No Urut RBA / budget line** menjadi referensi transaksi.
- **Control bucket** menjadi level saldo yang dikendalikan.
- **Transaksi** mencatat realisasi/pengajuan aktual PTK.

---

# 1. RUANG LINGKUP MVP

## 1.1 In Scope

1. Login dan authorization berbasis role + scope.
2. Rupiah Murni (RM) sebagai sumber dana aktif MVP.
3. Tahun anggaran dan versi/revisi pagu.
4. Import data pagu/RBA dari spreadsheet.
5. Master struktur anggaran.
6. Budget line / Nomor Urut RBA.
7. Control bucket.
8. Input transaksi sederhana oleh PTK.
9. Status transaksi:
   - `DRAFT`
   - `DIAJUKAN`
   - `DIKEMBALIKAN`
   - `SELESAI`
   - `DITOLAK`
   - `DIBATALKAN`
10. Rule-Based Budget Control.
11. Pemeriksaan PTU/Bendahara.
12. Dashboard sesuai role.
13. Early Warning System dasar.
14. Laporan dan ekspor dasar.
15. Print detail transaksi.
16. Audit trail.

## 1.2 Di Luar MVP / Future

- Penyusunan/perencanaan anggaran.
- Ranking/alokasi jurusan dengan SAW.
- Forecasting / ML / AI.
- Integrasi langsung API SAKTI.
- Integrasi langsung API ELFINA.
- Workflow approval panjang Kajur → Kabag → WD → Dekan.
- Otomatisasi seluruh dokumen SPJ/TOR/KAK.
- Pencairan resmi APBN.
- Implementasi penuh PNBP/BLU/SBSN.
- Import transaksi massal, kecuali nanti benar-benar dibutuhkan.
- Tanda tangan elektronik resmi.

---

# 2. ROLE, PERMISSION, DAN SCOPE

SIKARA tidak menggunakan asumsi bahwa semua role berada pada satu rantai approval.

## 2.1 PTK

**Scope:** jurusan/penugasan yang diberikan.

**Kebutuhan utama:**
- memilih No Urut RBA dengan cepat,
- mencatat transaksi dengan input minimal,
- mengetahui saldo,
- memantau status.

**Aksi:**
- membuat draft,
- mengedit draft,
- mengajukan transaksi,
- memperbaiki transaksi yang dikembalikan,
- mengajukan ulang,
- membatalkan sesuai rule,
- melihat transaksi sendiri/scope,
- print detail transaksi.

## 2.2 KAJUR

**Scope:** jurusan sendiri.  
**Baseline MVP:** **monitoring/read-only.**

**Aksi:**
- melihat pagu jurusan,
- melihat diajukan/realisasi internal/saldo,
- melihat transaksi aktif,
- melihat warning jurusan,
- membuka laporan jurusan.

**Tidak menjadi approval wajib pada MVP.**

## 2.3 KAPRODI

**Scope:** program studi.  
**Baseline MVP:** **monitoring/read-only.**

Kaprodi melihat transaksi yang memang ditandai/terkait dengan program studinya.

> Jangan menampilkan “Pagu Prodi” apabila tidak ada alokasi pagu formal pada tingkat prodi.

## 2.4 PTU — Penguji Tagihan Unit BLU

**Scope:** fakultas sesuai permission.

PTU merupakan aktor pemeriksaan transaksi/dokumen.

**Aksi potensial:**
- melihat antrean,
- membuka detail,
- mengembalikan,
- menolak,
- menyelesaikan transaksi jika diberi permission.

> Istilah PTU tidak boleh ditulis sebagai “Pelaksana Tata Usaha”.

## 2.5 Bendahara

Secara domain, Bendahara adalah fungsi yang berbeda dari PTU.

Satu pengguna lokal boleh memiliki lebih dari satu role/permission apabila praktik Fakultas memang demikian.

**Aksi potensial:**
- pemeriksaan sesuai kewenangan,
- menyelesaikan transaksi,
- monitoring transaksi dan saldo.

## 2.6 KABAG

**Scope:** seluruh Fakultas Teknik.

**Baseline MVP:** monitoring dan kontrol operasional, bukan approval panjang.

**Aksi:**
- dashboard fakultas,
- drill-down jurusan,
- melihat warning,
- melihat transaksi lintas jurusan,
- laporan,
- melihat dampak revisi,
- konfigurasi terbatas jika diizinkan.

## 2.7 WAKIL DEKAN II

**Scope:** seluruh Fakultas Teknik.  
**Aksi utama:** monitoring strategis.

- dashboard fakultas,
- kondisi jurusan,
- warning penting,
- laporan.

Tidak otomatis menjadi approval transaksi harian pada MVP.

## 2.8 DEKAN

**Scope:** seluruh Fakultas Teknik.  
**Aksi utama:** monitoring eksekutif.

Tidak otomatis menjadi approval transaksi harian pada MVP.

## 2.9 ADMIN

**Scope:** sistem/master.  
Admin bukan financial approver.

**Aksi:**
- tahun anggaran,
- sumber dana,
- versi/revisi,
- import,
- mapping,
- master nomenklatur,
- pengguna,
- role,
- scope,
- konfigurasi rule,
- audit log.

---

# 3. STATUS TRANSAKSI DAN DAMPAK SALDO

| Status | Arti | Dampak Saldo |
|---|---|---|
| `DRAFT` | Belum diajukan PTK | Tidak mengunci saldo |
| `DIAJUKAN` | Sudah dikirim untuk diproses | Mengunci saldo sebagai komitmen internal |
| `DIKEMBALIKAN` | Perlu diperbaiki PTK | Komitmen dilepas pada baseline MVP |
| `SELESAI` | Proses internal SIKARA selesai | Komitmen berubah menjadi realisasi internal |
| `DITOLAK` | Tidak dilanjutkan | Komitmen dilepas |
| `DIBATALKAN` | Dibatalkan sesuai permission | Komitmen dilepas bila sebelumnya terkunci |

## 3.1 Formula utama

```text
Saldo Tersedia
=
Pagu Aktif
- Komitmen Aktif (DIAJUKAN)
- Realisasi Internal (SELESAI)
```

`SELESAI` berarti **realisasi internal untuk monitoring SIKARA**. Jangan menuliskan bahwa `SELESAI` otomatis sama dengan realisasi APBN resmi sebelum ada rekonsiliasi data resmi dengan SAKTI.

---

# 4. PETA HALAMAN MVP

## Halaman publik
- `/`
- `/login`

## Dashboard
- `/dashboard`

## Transaksi
- `/submissions`
- `/submissions/create`
- `/submissions/{id}`
- `/submissions/{id}/edit`
- `/submissions/{id}/print`

## Pemeriksaan
- `/approvals`

## Anggaran
- `/budgets`
- `/budgets/{id}`
- `/budget-versions`
- `/budget-versions/compare`
- `/budgets-import`

## Early Warning
- `/warnings`
- `/warnings/{id}`

## Laporan
- `/reports`

## Master/Admin
- `/master/departments`
- `/master/fiscal-years`
- `/master/budget-structure`
- `/users`
- `/audit-logs`

---

# 5. LANDING PAGE DAN LOGIN

## 5.1 Landing Page `/`

Landing page dibuat ringan.

### Konten
- Nama: **SIKARA — Sistem Informasi Kendali Anggaran dan Realisasi**
- Fakultas Teknik Universitas Jenderal Soedirman.
- Deskripsi singkat sistem.
- Tombol `Masuk`.

### Yang dihapus dari desain lama
Jangan tampilkan data keuangan internal secara publik seperti total pagu, realisasi per jurusan, grafik serapan, atau warning. Informasi tersebut hanya tersedia setelah login sesuai scope.

## 5.2 Login `/login`

### Field
- Username/email.
- Password.
- Show/hide password.
- Tombol `Masuk`.

Quick role switcher hanya tersedia pada mode development/demo, bukan production.

---

# 6. DASHBOARD PTK

Dashboard PTK dibuat **sederhana dan mirip pola kerja SISTRAN**, bukan dashboard analitik yang terlalu berat.

## 6.1 KPI utama
1. **Pagu Aktif**
2. **Diajukan**
3. **Realisasi Internal**
4. **Saldo Tersedia**

Konteks selalu menampilkan Tahun Anggaran, revisi aktif, sumber dana `RM`, dan jurusan/scope.

## 6.2 Quick Action
Tombol utama: `+ Catat Transaksi`

Tombol sekunder:
- `Lihat Semua Transaksi`
- `Lihat Rekap`

## 6.3 Transaksi Terkini
Kolom:
- Tanggal,
- No FRA/Bukti,
- No RBA,
- Uraian,
- Nominal,
- Status,
- Aksi.

## 6.4 Warning ringkas
PTK hanya melihat warning yang relevan dengan scope/transaksinya, misalnya saldo hampir habis, transaksi dikembalikan, atau pengajuan lama belum selesai.

---

# 7. FORM CATAT TRANSAKSI PTK

Route: `/submissions/create`

Ini adalah halaman yang paling penting dan harus paling sederhana.

## 7.1 Prinsip UX

**PTK tidak memilih Program → Kegiatan → KRO → RO → Komponen → Subkomponen → Akun satu per satu.**

PTK cukup:

```text
Pilih No Urut RBA
→ isi Tanggal
→ isi No FRA/Bukti
→ isi Uraian
→ isi Nominal
→ Ajukan
```

## 7.2 Context Bar — otomatis/read-only

Tampilkan:
- Tahun Anggaran,
- Sumber Dana: RM,
- Revisi aktif,
- Jurusan/scope aktif.

Jika PTK hanya punya satu scope, jurusan tidak perlu dipilih lagi. Jika user sah memiliki beberapa scope, tampilkan selector scope terlebih dahulu.

## 7.3 Field 1 — Pos Anggaran / Nomor Urut RBA

Gunakan **searchable combobox/autocomplete**, bukan daftar hierarchy.

### Search by
- Nomor Urut RBA,
- uraian RBA,
- kode akun,
- nama akun,
- kegiatan,
- subkomponen.

### Setiap hasil menampilkan

```text
No RBA 405
Verifikasi Akreditasi Program Studi Teknik Komputer

Akun      : 521211
Pagu      : Rp 40.000.000
Diajukan  : Rp 5.000.000
Selesai   : Rp 20.000.000
Saldo     : Rp 15.000.000
```

## 7.4 Detail Anggaran setelah No RBA dipilih

Tampilkan dalam panel read-only.

### Ringkas di depan
- No RBA.
- Uraian RBA.
- Akun.
- Subkomponen.
- Pagu budget line sebagai informasi.
- Saldo control bucket yang benar-benar dikendalikan.

### Hierarki lengkap — accordion
- Program
- Kegiatan
- KRO
- RO
- Komponen
- Subkomponen
- Akun
- Subakun/detail bila tersedia

## 7.5 Field transaksi manual

### Wajib
1. **Tanggal**
2. **No FRA / Nomor Bukti**
3. **Uraian Aktual**
4. **Nominal**

### Opsional
5. Program Studi Terkait
6. Catatan
7. Lampiran

Nomor bukti/FRA **tidak perlu dibuat otomatis dengan format buatan sistem** kecuali stakeholder memang meminta.

## 7.6 Preview saldo

```text
Pagu Control Bucket     Rp 100.000.000
Diajukan Aktif          Rp  25.000.000
Realisasi Internal      Rp  50.000.000
Saldo Tersedia          Rp  25.000.000
Nominal Baru            Rp  10.000.000
Projected Saldo         Rp  15.000.000
```

Preview frontend hanya membantu pengguna. **Keputusan saldo di backend tetap authoritative.**

## 7.7 Tombol

### `Simpan Draft`
- status `DRAFT`,
- tidak mengunci saldo.

### `Ajukan`
Backend menjalankan Rule-Based Budget Control.

Jika saldo cukup:
- status → `DIAJUKAN`,
- nominal masuk active commitment,
- saldo tersedia berkurang,
- transaksi masuk antrean PTU/Bendahara.

Jika saldo tidak cukup:
- transaksi tidak dapat diajukan,
- tampilkan alasan dan nilai kekurangan.

---

# 8. DAFTAR TRANSAKSI

Route: `/submissions`

## 8.1 Filter MVP
- Search: No FRA/bukti, No RBA, uraian.
- Status.
- Bulan/rentang tanggal.
- Jurusan — hanya untuk role fakultas.
- Akun — opsional.
- Tahun Anggaran/Revisi.

Sumber dana pada MVP default RM dan tidak perlu menjadi selector utama.

## 8.2 Kolom
- No FRA/Bukti.
- Tanggal.
- No RBA.
- Uraian.
- Jurusan.
- Nominal.
- Status.
- Aksi.

Scope menentukan row yang boleh dilihat pengguna.

---

# 9. DETAIL TRANSAKSI

Route: `/submissions/{id}`

## Konten

### A. Header
- ID transaksi.
- Status.
- Tanggal.
- PTK.
- Jurusan.
- No FRA/Bukti.
- Nominal.

### B. Pos Anggaran
- No RBA.
- Uraian RBA.
- Akun.
- Hierarki anggaran lengkap read-only.

### C. Snapshot Finansial
- Pagu control bucket.
- Diajukan aktif.
- Realisasi internal.
- Saldo.
- Nominal transaksi.

### D. Data aktual
- Uraian transaksi.
- Program studi jika ada.
- Catatan.
- Lampiran.

### E. Timeline Status

```text
02 Sep 09:10 — DRAFT dibuat oleh PTK
02 Sep 09:25 — DIAJUKAN oleh PTK
02 Sep 13:12 — DIKEMBALIKAN oleh PTU
02 Sep 14:20 — DIAJUKAN ulang oleh PTK
03 Sep 10:03 — SELESAI oleh Bendahara
```

Simpan actor, status lama, status baru, waktu, dan catatan/alasan.

---

# 10. EDIT / PERBAIKAN TRANSAKSI

Route: `/submissions/{id}/edit`

## Dapat diedit jika
- `DRAFT`, atau
- `DIKEMBALIKAN`.

Saat `DIKEMBALIKAN`, tampilkan jelas alasan pengembalian. PTK memperbaiki data lalu klik `Ajukan Ulang`. Backend melakukan RBC lagi karena saldo sebelumnya sudah dilepas.

---

# 11. PTU / BENDAHARA WORKBENCH

Route: `/approvals`

Nama halaman UI lebih baik: **Pemeriksaan Transaksi**, bukan “Approval Berjenjang”.

## 11.1 Summary
- Diajukan.
- Dikembalikan.
- Selesai hari ini.
- Ditolak.

## 11.2 Queue
Default menampilkan `DIAJUKAN`.

Kolom:
- No FRA/Bukti.
- Tanggal.
- PTK.
- Jurusan.
- No RBA.
- Uraian.
- Nominal.
- Umur pengajuan.
- Aksi `Periksa`.

## 11.3 Detail pemeriksaan
- informasi transaksi,
- No RBA,
- konteks anggaran,
- snapshot saldo,
- hasil rule,
- lampiran,
- timeline.

## 11.4 Aksi

### `Kembalikan`
Wajib alasan.

Efek:
- `DIAJUKAN` → `DIKEMBALIKAN`
- commitment dilepas.

### `Tolak`
Wajib alasan.

Efek:
- `DIAJUKAN` → `DITOLAK`
- commitment dilepas.

### `Selesai`
Hanya role/permission yang disahkan stakeholder.

Efek:
- `DIAJUKAN` → `SELESAI`
- commitment berkurang,
- realisasi internal bertambah,
- saldo tersedia tidak dikurangi dua kali.

> Tidak ada tombol “Verifikasi → Processing → Final” pada baseline terbaru.

---

# 12. DASHBOARD KAJUR

Read-only.

## KPI
- Pagu jurusan.
- Diajukan.
- Realisasi internal.
- Saldo tersedia.
- Persentase penggunaan.

## Konten
- kondisi per akun/control bucket,
- transaksi aktif,
- transaksi terakhir,
- warning jurusan,
- tren sederhana bila diperlukan.

Tidak ada tombol approval.

---

# 13. DASHBOARD KAPRODI

Read-only.

## Tampilkan
- jumlah transaksi terkait prodi,
- nominal diajukan,
- nominal selesai,
- daftar transaksi terbaru,
- status transaksi.

Jika tidak ada pagu resmi per prodi, jangan tampilkan `Pagu Prodi`. Gunakan label **Realisasi / Transaksi Terkait Program Studi**.

---

# 14. DASHBOARD PTU / BENDAHARA

Fokus ke pekerjaan pemeriksaan.

## KPI
- Diajukan.
- Dikembalikan.
- Aging tertinggi.
- Selesai periode berjalan.

## Konten
- transaksi menunggu pemeriksaan,
- transaksi dikembalikan,
- transaksi lama,
- warning relevan.

CTA: `Buka Pemeriksaan Transaksi`

---

# 15. DASHBOARD KABAG / WD II / DEKAN

Dashboard tidak perlu identik, tetapi menggunakan sumber data yang sama.

## KPI fakultas
- Pagu aktif RM.
- Diajukan/komitmen.
- Realisasi internal.
- Saldo tersedia.
- Jumlah warning terbuka.

## Kondisi per jurusan
Tabel:
- Jurusan.
- Pagu.
- Diajukan.
- Realisasi internal.
- Saldo.
- Utilization.
- Warning.

Boleh menggunakan chart untuk membantu membaca, tetapi chart bukan fitur utama.

## Top Attention
Tampilkan exception, bukan ranking “jurusan terbaik/terburuk”. Contoh:
- saldo kritis,
- transaksi terlalu lama,
- revisi menyebabkan konflik,
- data belum termapping.

---

# 16. DASHBOARD ADMIN

Admin fokus pada kesehatan data dan konfigurasi.

## KPI
- TA aktif.
- Revisi aktif.
- Jumlah budget line.
- Jumlah baris unmapped.
- User aktif.
- Import terakhir.

## CTA
- Import pagu.
- Kelola mapping.
- Master nomenklatur.
- User & scope.
- Audit log.

---

# 17. MASTER PAGU / BUDGET LINE

Route: `/budgets`

## 17.1 Setiap budget line minimal memiliki
- Tahun Anggaran.
- Budget Version/Revisi.
- Jurusan.
- Nomor Urut RBA.
- Program.
- Kegiatan.
- KRO.
- RO.
- Komponen.
- Subkomponen.
- Akun.
- Subakun/detail bila tersedia.
- Uraian.
- Volume.
- Satuan.
- Harga Satuan.
- Nilai Pagu.

## 17.2 Kolom utama UI
Jangan tampilkan semua hierarchy sebagai kolom tabel.

Gunakan:
- No RBA.
- Jurusan.
- Uraian.
- Akun.
- Pagu.
- Diajukan.
- Realisasi Internal.
- Saldo.
- Aksi `Detail`.

---

# 18. DETAIL POS ANGGARAN

Route: `/budgets/{id}`

Tampilkan:
- No RBA,
- full hierarchy,
- pagu budget line,
- control bucket terkait,
- transaksi yang menggunakan budget line ini.

**Saldo pengendalian tidak harus identik dengan pagu satu budget line.** Beberapa budget line dapat berada dalam satu control bucket.

---

# 19. CONTROL BUCKET

Control bucket tidak harus mempunyai menu utama untuk PTK. Ia adalah konsep backend yang dapat ditampilkan pada halaman budget/admin.

## Baseline working design

```text
Budget Version
+ Jurusan
+ Subkomponen
+ Akun
```

## Alternatif jika stakeholder memutuskan lebih sederhana

```text
Budget Version
+ Jurusan
+ Akun
```

Karena keputusan grain masih perlu validasi, implementasi jangan mengikat seluruh kode ke satu grain yang sulit diubah.

---

# 20. RULE-BASED BUDGET CONTROL

## RBC-001 — Available Balance Check

Saat `Ajukan`:

```text
available =
current_budget
- active_commitment
- internal_realization
```

Jika `requested_amount <= available`, maka lolos. Jika tidak, transaksi diblokir.

## RBC-002 — Draft Does Not Lock
`DRAFT` tidak mempengaruhi saldo.

## RBC-003 — Commitment on Submission
`DIAJUKAN` mengunci nominal sebagai active commitment.

## RBC-004 — Release Commitment
`DIKEMBALIKAN`, `DITOLAK`, atau `DIBATALKAN` melepaskan commitment sesuai transisi yang sah.

## RBC-005 — Complete Without Double Count
Saat `DIAJUKAN → SELESAI`:
- commitment turun sejumlah transaksi,
- realisasi internal naik sejumlah transaksi,
- available tidak dipotong lagi.

## RBC-006 — Duplicate Reference Check
Sistem dapat memberi warning/validasi terhadap kombinasi referensi yang terindikasi duplikat. Jangan menganggap hanya No FRA sebagai global unique jika praktik lapangan belum memastikan formatnya.

## RBC-007 — Scope Guard
PTK tidak dapat menggunakan budget line jurusan yang tidak masuk scope.

## RBC-008 — Version Integrity
Transaksi harus terhubung dengan budget version yang valid dan jejak revisinya tidak boleh hilang.

---

# 21. VERSI & REVISI PAGU

Route: `/budget-versions`

## Data
- Tahun Anggaran.
- Nomor revisi.
- Label.
- Tanggal dokumen/aktivasi.
- Status: Draft, Active, Archived.

Versi aktif lama tidak di-overwrite. Revisi baru menjadi version baru agar histori tetap tersedia.

---

# 22. PERBANDINGAN REVISI

Route: `/budget-versions/compare`

Tampilkan:
- budget line baru,
- budget line hilang,
- pagu naik,
- pagu turun,
- perubahan mapping jika ada.

## Revision Impact
Tampilkan warning bila revisi baru menyebabkan:

```text
Pagu control bucket baru
<
Commitment aktif + Realisasi internal
```

Sistem tidak diam-diam mengubah transaksi lama.

---

# 23. IMPORT PAGU

Route: `/budgets-import`

## Flow

```text
Upload Excel
→ Staging
→ Validasi Schema
→ Mapping Jurusan
→ Mapping Master
→ Buat/Update Master
→ Buat Budget Line
→ Bentuk Control Bucket
→ Preview
→ Commit
→ Aktivasi versi secara terpisah
```

## Validasi minimal
- Tahun Anggaran.
- Revisi.
- Jurusan/subunit.
- No RBA bila tersedia dalam sumber.
- Hierarki.
- Akun.
- Uraian.
- Jumlah.
- duplicate candidate.
- data belum termapping.

Import tidak boleh langsung mengaktifkan versi baru tanpa preview.

---

# 24. EARLY WARNING SYSTEM

Route: `/warnings`

EWS membantu monitoring, bukan menggantikan Rule-Based Budget Control.

## Baseline warning

### EWS-001 — Saldo Kritis
```text
available / current_budget <= X%
```
`X` configurable.

### EWS-002 — Stale Submission
```text
DIAJUKAN tidak berubah > N hari
```
`N` configurable.

### EWS-003 — Revision Conflict
Revisi baru membuat pagu lebih kecil daripada commitment + realisasi internal.

### EWS-004 — Unmapped Data
Import/master memiliki baris yang belum dapat dipetakan dengan aman.

### EWS-005 — Repeated Return
Opsional jika data status history sudah stabil.

Jangan hardcode dan mengklaim threshold 5%, 15%, 85%, atau 3 hari sebagai aturan resmi. Boleh digunakan sebagai dummy development, tetapi setting harus configurable dan diberi label belum disahkan jika belum divalidasi.

---

# 25. DETAIL WARNING

Route: `/warnings/{id}`

Tampilkan:
- kode rule,
- severity,
- object terkait,
- nilai saat ini,
- threshold,
- alasan rule terpicu,
- tanggal pertama muncul,
- status warning,
- link ke transaksi/budget.

Aksi:
- Acknowledge.
- Resolve dengan catatan jika dibutuhkan.

---

# 26. LAPORAN

Route: `/reports`

MVP tidak perlu 8 tab dan 13 filter sekaligus.

## Laporan utama
1. Pagu vs Diajukan vs Realisasi Internal vs Saldo per jurusan.
2. Realisasi Internal per Akun.
3. Transaksi per Status.
4. Transaksi per Periode.
5. Rekap Saldo Anggaran.
6. Revision Comparison.
7. Early Warning Summary.

## Filter utama
- Tahun Anggaran.
- Revisi.
- Jurusan.
- Akun.
- Status.
- Periode.

## Export MVP
Prioritas:
- XLSX.
- PDF.
- Print.

CSV dapat disediakan jika mudah. DOCX tidak wajib untuk MVP.

---

# 27. PRINT TRANSAKSI

Route: `/submissions/{id}/print`

Print adalah **dokumen internal SIKARA**, bukan otomatis dokumen pencairan resmi.

## Tampilkan
- identitas transaksi,
- No FRA/Bukti,
- tanggal,
- No RBA,
- uraian RBA,
- uraian aktual,
- nominal,
- jurusan,
- status,
- informasi generate.

Kop Fakultas dapat configurable. Blok tanda tangan jangan hardcode jabatan tertentu sebelum format disahkan stakeholder.

---

# 28. MASTER ORGANISASI

Route: `/master/departments`

Kelola:
- Fakultas.
- Jurusan.
- Program Studi.

Field:
- kode,
- nama,
- parent,
- tipe,
- status.

Data yang sudah memiliki transaksi tidak boleh hard delete.

---

# 29. MASTER TAHUN ANGGARAN & SUMBER DANA

Route: `/master/fiscal-years`

Kelola:
- Tahun Anggaran.
- Budget Version.
- Sumber dana.

## MVP
Sumber dana aktif implementasi: `RM — Rupiah Murni`.

Source lain boleh tersimpan sebagai referensi arsitektur, tetapi jangan menjadi flow aktif yang belum dikerjakan.

---

# 30. MASTER STRUKTUR ANGGARAN

Route: `/master/budget-structure`

Master:
1. Program
2. Kegiatan
3. KRO
4. RO
5. Komponen
6. Subkomponen
7. Akun
8. Subakun/detail opsional

Master terutama dikelola melalui import/mapping, bukan entry manual besar-besaran. Kode harus year/version aware jika nomenklatur berubah antar tahun.

---

# 31. MANAJEMEN PENGGUNA

Route: `/users`

Model authorization:

```text
USER
→ ROLE
→ PERMISSION
→ SCOPE
```

Satu user dapat memiliki beberapa role dan beberapa scope yang sah.

---

# 32. AUDIT LOG

Route: `/audit-logs`

Catat minimal:
- create/edit transaction,
- submit,
- resubmit,
- return,
- reject,
- complete,
- cancel,
- import,
- activate budget version,
- perubahan mapping/master kritis,
- perubahan role/scope,
- print/export penting.

## Data audit
- actor,
- action,
- object,
- before,
- after,
- timestamp,
- metadata request seperlunya.

Transaksi finansial tidak boleh di-hard-delete.

---

# 33. FITUR YANG DIPERTAHANKAN, DIREVISI, DAN DISEMBUNYIKAN DARI PROTOTYPE LAMA

## 33.1 KEEP
- Login.
- Dashboard structure.
- Daftar transaksi.
- Detail transaksi.
- Print.
- Workbench pemeriksaan.
- EWS.
- Budget/master.
- Budget version.
- Compare revision.
- Import pagu.
- Reports.
- Users.
- Audit.

## 33.2 REVISE

### Nama produk
Dari `Sistem Informasi Keuangan dan Realisasi Anggaran` menjadi `Sistem Informasi Kendali Anggaran dan Realisasi`.

### PTU
Dari `Pelaksana Tata Usaha` menjadi `Penguji Tagihan Unit BLU`.

### Form PTK
Dari `Pilih Pos Akun / hierarki` menjadi `Pilih Nomor Urut RBA / Pos Anggaran`.

### Status
Dari `DRAFT / PROCESSING / RETURNED / FINAL / CANCELLED` menjadi `DRAFT / DIAJUKAN / DIKEMBALIKAN / SELESAI / DITOLAK / DIBATALKAN`.

### Kajur
Dari approval internal menjadi monitoring/read-only.

### Kaprodi
Dari pagu prodi menjadi transaksi/realisasi yang terkait prodi kecuali pagu formal memang tersedia.

### Approval
Dari PTU → Kabag → WD → Dekan menjadi pemeriksaan PTU/Bendahara sesuai permission.

### Realisasi
Dari `Realisasi Definitif` menjadi `Realisasi Internal / Selesai SIKARA` sampai terdapat rekonsiliasi resmi.

### EWS
Threshold hardcoded menjadi configurable.

### Sumber dana
MVP aktif RM saja.

## 33.3 HIDE / DEFER
- Approval Kajur.
- Approval Kabag.
- Approval WD.
- Approval Dekan.
- Import transaksi batch.
- Otomatisasi dokumen SPJ kompleks.
- Multi-fund workflow.
- Ranking jurusan.
- ML/AI/Forecasting.
- Analytics yang tidak mempunyai kebutuhan stakeholder jelas.

---

# 34. FLOW UTAMA SISTEM

```text
ADMIN
  │
  ▼
IMPORT PAGU RM
  │
  ▼
BUDGET VERSION
  │
  ▼
BUDGET LINE / NO URUT RBA
  │
  ▼
CONTROL BUCKET
  │
  ▼
PTK LOGIN
  │
  ▼
PILIH NO RBA
  │
  ├─ Tanggal
  ├─ No FRA/Bukti
  ├─ Uraian aktual
  └─ Nominal
  │
  ▼
SIMPAN DRAFT
  │
  ▼
AJUKAN
  │
  ▼
RULE-BASED BUDGET CONTROL
  │
  ├─ SALDO CUKUP ────────────────┐
  │                               │
  └─ SALDO TIDAK CUKUP → BLOCK   │
                                  ▼
                              DIAJUKAN
                                  │
                                  ▼
                           PTU/BENDAHARA
                        ┌─────────┼─────────┐
                        ▼         ▼         ▼
                 DIKEMBALIKAN  DITOLAK   SELESAI
                        │         │         │
                        │         │         ▼
                        │         │   REALISASI INTERNAL
                        │         │
                        └────┬────┘
                             ▼
                       COMMITMENT DILEPAS

SEMUA DATA
  │
  ├─ Dashboard
  ├─ EWS
  ├─ Laporan
  ├─ Print
  └─ Audit
```

---

# 35. FORMULA DAN INVARIANT YANG HARUS DIJAGA

```text
Available
=
Current Budget
- Active Commitment
- Internal Realization
```

Dengan:

```text
Active Commitment
=
SUM(transactions.amount WHERE status = DIAJUKAN)
```

```text
Internal Realization
=
SUM(transactions.amount WHERE status = SELESAI)
```

Invariant:

```text
Current Budget
=
Active Commitment
+ Internal Realization
+ Available
```

untuk bucket yang sama, dengan asumsi tidak ada adjustment lain yang belum dimodelkan.

Backend harus mencegah race condition ketika dua transaksi diajukan ke bucket yang sama.

---

# 36. CATATAN IMPLEMENTASI KRITIS

1. Backend adalah sumber kebenaran saldo.
2. Submit financial mutation harus atomic.
3. Gunakan locking/transaction database untuk pengecekan dan commitment.
4. Jangan gunakan floating point untuk Rupiah.
5. Jangan hard-delete financial transaction.
6. Budget version aktif lama tidak di-overwrite.
7. No Urut RBA bukan global primary key.
8. No RBA harus unik dalam context yang sesuai, misalnya budget version + jurusan + nomor urut.
9. PTK tidak boleh mengirim department bebas dari frontend lalu dipercaya backend.
10. Scope dihitung dari authorization user + budget line.
11. Full hierarchy tetap tersimpan walaupun UI PTK sederhana.

---

# 37. OPEN DECISION YANG MASIH PERLU VALIDASI

## OD-01 — Control Bucket Grain
Pilih salah satu:

```text
Jurusan + Subkomponen + Akun
```

atau:

```text
Jurusan + Akun
```

## OD-02 — Hak menetapkan SELESAI
Apakah PTU, Bendahara, atau user yang memiliki permission tertentu?

## OD-03 — Label bukti transaksi
Apakah field utama ditulis `No FRA`, `Nomor Bukti`, atau `No FRA / Nomor Bukti`?

> Baseline sistem tetap dapat dibangun tanpa menambah requirement lain.

---

# 38. URUTAN IMPLEMENTASI YANG DISARANKAN

## Tahap 1 — Bersihkan semantics prototype
- nama produk,
- istilah PTU,
- status,
- role behavior,
- RM-only,
- label realisasi internal.

## Tahap 2 — Data model
- budget version,
- budget line / No RBA,
- control bucket,
- transaction,
- status history,
- user scope.

## Tahap 3 — PTK flow
- dashboard PTK,
- searchable No RBA,
- form minimal,
- draft,
- ajukan,
- daftar/detail.

## Tahap 4 — RBC
- saldo backend,
- commitment,
- release,
- complete,
- locking.

## Tahap 5 — PTU/Bendahara
- queue,
- return,
- reject,
- complete.

## Tahap 6 — Monitoring
- Kajur,
- Kaprodi,
- Kabag,
- WD,
- Dekan.

## Tahap 7 — Data administration
- import pagu,
- versioning,
- revision compare.

## Tahap 8 — Supporting feature
- EWS,
- reports,
- print,
- audit.

---

# 39. DEFINITION OF DONE MVP

MVP dianggap selesai apabila:

1. Admin dapat mengimpor pagu RM sebagai budget version.
2. Setiap budget line dapat mempunyai No Urut RBA dan full hierarchy.
3. PTK hanya melihat budget line pada scope yang sah.
4. PTK dapat mencari dan memilih No RBA.
5. PTK hanya perlu mengisi tanggal, No FRA/bukti, uraian, dan nominal sebagai field inti.
6. Draft tidak mengunci saldo.
7. Ajukan menjalankan RBC di backend.
8. Overbudget diblokir.
9. DIAJUKAN mengunci commitment.
10. Return/reject/cancel melepaskan commitment sesuai transisi.
11. SELESAI mengubah commitment menjadi realisasi internal tanpa double count.
12. PTU/Bendahara memiliki queue pemeriksaan.
13. Kajur dan Kaprodi memiliki monitoring read-only.
14. Pimpinan dapat melihat kondisi per jurusan.
15. Warning dasar dapat tampil.
16. Laporan pagu vs diajukan vs selesai vs saldo dapat dibuat.
17. Riwayat status dan tindakan kritis masuk audit log.
18. Tidak ada workflow panjang yang tidak pernah divalidasi stakeholder.
19. Tidak ada fitur SAW/ML/forecasting pada core MVP.
20. Seluruh nominal yang ditampilkan pada dashboard, detail, dan laporan reconcile pada filter/scope yang sama.

---

# 40. RINGKASAN PALING SINGKAT

```text
SIKARA BUKAN APLIKASI PERENCANAAN.

SIKARA menerima pagu RM yang sudah ada,
menyimpan struktur lengkapnya,
dan membuat PTK bekerja sederhana seperti pola SISTRAN:

Pilih No RBA
+ Tanggal
+ No FRA/Bukti
+ Uraian
+ Nominal

Tetapi SIKARA menambahkan:
control bucket,
penguncian saldo,
pencegahan overbudget,
versioning,
scope jurusan,
monitoring,
EWS,
laporan,
dan audit.
```

**Baseline ini yang dipakai untuk membenahi prototype terlebih dahulu. Fitur khusus penelitian/ML/SPK tidak dimasukkan sampai core system stabil.**
