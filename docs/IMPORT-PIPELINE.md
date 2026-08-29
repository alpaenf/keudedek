# IMPORT PIPELINE SPECIFICATION

# SIPEDA - Budget Import & Staging Pipeline
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Overview Import Module

Fitur **Budget Import** memungkinkan Administrator mengunggah berkas anggaran berbasis Excel (`.xlsx`) atau CSV untuk memperbarui atau menginisialisasi pagu anggaran unit secara masal.

---

## 2. Multi-Stage Pipeline Process

Proses import dilakukan secara aman melalui 6 tahapan (*Multi-Stage Architecture*):

```text
[ 1. Upload File ] (XLSX / CSV)
       │
       ▼
[ 2. File Parsing & Chunking ] (Stream Reader)
       │
       ▼
[ 3. Staging Table Insertion ] (`budget_import_stagings`)
       │
       ▼
[ 4. Validation & Mapping Engine ] (Check Master Data & Format)
       │
       ▼
[ 5. Preview & Validation Result UI ] (User Confirm / Cancel)
       │
       ▼
[ 6. Database Commit & History ] (`budget_buckets` Bulk Insert)
```

---

## 3. Required File Format & Column Mapping

File yang diunggah wajib memiliki struktur header berikut:

| Kolom File | Nama Kolom Excel | Format / Tipe Data | Wajib | Pemetaan Master Data |
|---|---|---|:---:|---|
| A | `KODE_JURUSAN` | String (misal: `JTIF`) | YA | Maps to `departments.code` |
| B | `TAHUN` | Numeric (misal: `2026`) | YA | Maps to `fiscal_years.year` |
| C | `KODE_SUMBER` | String (misal: `UKT`) | YA | Maps to `funding_sources.code` |
| D | `KODE_AKUN` | String (misal: `521111`) | YA | Maps to `budget_buckets.account_code` |
| E | `NAMA_AKUN` | String | YA | Maps to `budget_buckets.account_name` |
| F | `PAGU_AWAL` | Numeric / Decimal | YA | Maps to `budget_buckets.initial_budget` |

---

## 4. Validation Rules & Staging Schema

Setiap baris pada tabel staging (`budget_import_stagings`) akan diverifikasi dengan aturan:

1. **Existence Check:** `KODE_JURUSAN` dan `KODE_SUMBER` harus terdaftar di tabel master data.
2. **Format Check:** `PAGU_AWAL` harus bernilai numerik positif $\ge 0$.
3. **Duplicate Check:** Tidak boleh ada duplikasi kombinasi `(TAHUN, KODE_JURUSAN, KODE_AKUN)` dalam satu berkas import.

### Staging Record Status:
- `VALID`: Data memenuhi seluruh aturan dan siap dicommit.
- `INVALID`: Terisi pesan error spesifik (misal: `"Kode jurusan X tidak ditemukan di master data"`).

---

## 5. Preview & Bulk Commit

- **Preview Page:** Pengguna dapat melihat total baris data, jumlah baris valid, dan daftar baris error sebelum data masuk ke basis data utama.
- **Atomic Commit:** Menggunakan `DB::transaction()` saat meluncurkan data valid dari staging ke `budget_buckets`. Jika ada kesalahan fatal, seluruh transaksi dibatalkan (*rollback*).
- **Import Audit:** Aktivitas import dicatat di `import_histories` termasuk `user_id`, `filename`, `total_rows`, `imported_rows`, dan `failed_rows`.
