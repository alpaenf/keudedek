# RULE ENGINE SPECIFICATION

# SIPEDA - Early Warning System (EWS) Rules
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Overview Rule Engine

Rule Engine pada SIPEDA bertugas melakukan evaluasi otomatis terhadap kondisi anggaran unit/jurusan dan pengajuan yang masuk. Tujuannya adalah mendeteksi bahaya *overbudget*, penyerapan anggaran yang lambat, atau deviasi signifikan dari alokasi yang ditetapkan.

---

## 2. Rule Definitions

### 2.1 Rule `EWS-001`: Critical Available Balance

- **Nama Rule:** Critical Available Balance
- **Tipe:** Early Warning
- **Tujuan:** Mendeteksi ketika persentase sisa anggaran pada suatu pos/bucket berada di bawah ambang batas kritis.
- **Logika Evaluasi:**
  ```text
  IF (available_balance / allocated_budget) * 100 < threshold_percentage THEN
      severity = HIGH
      trigger_warning = TRUE
  ```
- **Threshold Default:** 10% dari allocated budget.
- **Severity Level:** `HIGH` / `CRITICAL`
- **Recommended Action:** Peninjauan ulang pengajuan baru dan persiapan revisi anggaran jika diperlukan.

---

### 2.2 Rule `EWS-002`: Overbudget Submission Block

- **Nama Rule:** Overbudget Submission Block
- **Tipe:** Budget Control (Hard Guard)
- **Tujuan:** Mencegah pengajuan yang nominalnya melebihi saldo anggaran yang tersedia.
- **Logika Evaluasi:**
  ```text
  IF submission_amount > available_balance THEN
      severity = CRITICAL
      allow_submission = FALSE
      message = "Nominal pengajuan melebihi saldo anggaran yang tersedia."
  ```
- **Severity Level:** `CRITICAL`
- **Recommended Action:** Menyesuaikan nominal pengajuan atau mengajukan revisi pagu anggaran terlebih dahulu.

---

### 2.3 Rule `EWS-003`: Rapid Burn Rate Warning

- **Nama Rule:** Rapid Burn Rate Warning
- **Tipe:** Trend Monitoring
- **Tujuan:** Mengidentifikasi unit/jurusan yang menyerap anggaran terlalu cepat dalam periode waktu singkat.
- **Logika Evaluasi:**
  ```text
  IF (monthly_realized / allocated_budget) > (monthly_threshold_percentage) THEN
      severity = MEDIUM
      trigger_warning = TRUE
  ```
- **Severity Level:** `MEDIUM`
- **Recommended Action:** Evaluasi distribusi kegiatan agar anggaran mencukupi hingga akhir tahun anggaran.

---

## 3. Severity Levels

| Severity | Color Code | Deskripsi | Tindakan Sistem |
|---|---|---|---|
| **CRITICAL** | Red (`#EF4444`) | Mengancam ketersediaan anggaran / blokir transaksi | Pemblokiran otomatis transaksi overbudget |
| **HIGH** | Orange (`#F97316`) | Saldo mendekati habis (< 10%) | Notifikasi langsung ke KAJUR & KABAG Keuangan |
| **MEDIUM** | Amber (`#F59E0B`) | Tren penyerapan terlalu cepat / tidak wajar | Tampil pada Widget EWS Dashboard |
| **LOW** | Blue (`#3B82F6`) | Catatan edukatif / indikator operasional | Informasi pada laporan bulanan |
| **INFO** | Slate (`#64748B`) | Catatan aktivitas sistem | Log internal |

---

## 4. Lifecycle Early Warning

1. **Detection:** Rule Engine mendeteksi kondisi terpicu saat pengajuan dibuat / diubah atau melalui scheduled checker.
2. **Alert Generation:** Warning dicatat pada tabel `early_warnings` dengan status `ACTIVE`.
3. **Notification:** Pengguna terkait mendapatkan indikasi pada Dashboard EWS.
4. **Acknowledgement:** KAJUR/KABAG dapat melakukan *acknowledge* (membaca & mengonfirmasi).
5. **Resolution:** Warning otomatis berubah menjadi `RESOLVED` apabila saldo kembali aman (misal setelah revisi pagu).
