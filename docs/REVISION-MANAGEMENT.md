# REVISION MANAGEMENT SPECIFICATION

# SIPEDA - Pengelolaan Revisi & Versi Anggaran
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Background & Concepts

Dalam operasional anggaran fakultas, pergeseran atau revisi pagu antar unit/akun sering terjadi seiring dengan dinamika kegiatan akademik. Modul **Revision Management** menangani pencatatan versi pagu anggaran tanpa menghilangkan riwayat histori pagu awal.

---

## 2. Types of Budget Revisions

1. **Pergeseran Antar Akun Dalam Unit (Internal Shift):**
   - Mengurangi pagu Akun A dan menambah pagu Akun B dalam satu jurusan yang sama.
2. **Revisi Tambahan Pagu (Top-up Budget):**
   - Penambahan pagu total fakultas/unit dari sumber dana tertentu.
3. **Revisi Pengurangan Pagu (Reduction):**
   - Pengurangan pagu total karena efisiensi atau pemotongan anggaran pusat.

---

## 3. Impact Calculation Rules

Setiap kali revisi pagu disetujui, kalkulasi saldo pada `budget_buckets` diperbarui:

```text
allocated_budget = initial_budget + sum(approved_revisions)

available_balance = allocated_budget - reserved_budget - realized_budget
```

### ⚠️ Pre-Revision Validation Guard

Sistem melarang revisi pengurangan pagu jika:
$$\text{Revisi Proposed Allocated} < (\text{reserved\_budget} + \text{realized\_budget})$$

Jika kondisi tersebut dilanggar, sistem memblokir revisi dengan pesan:
> *"Pagu baru tidak mencukupi untuk menutup komitmen (reserved) dan realisasi yang sedang berjalan."*

---

## 4. Revision Versioning & History Schema

Tabel `budget_revisions` mencatat setiap event revisi:

| Field | Description |
|---|---|
| `revision_number` | Kode unik revisi (misal: `REV/2026/01`) |
| `budget_bucket_id` | Bucket anggaran yang direvisi |
| `previous_amount` | Nominal `allocated_budget` sebelum revisi |
| `revised_amount` | Nominal `allocated_budget` setelah revisi |
| `difference` | Selisih perubahan nominal (+ / -) |
| `reason` | Justifikasi / latar belakang revisi |
| `approved_by` | User KABAG / Admin yang menyetujui revisi |
| `created_at` | Tanggal eksekusi revisi |

---

## 5. Revision Comparison Reporting

Aplikasi menyediakan tampilan **Revision Comparison View** untuk melihat perbandingan pagu awal vs pagu aktif saat ini per unit dan per akun, sehingga pengelola keuangan dapat melakukan audit historis dengan transparan.
