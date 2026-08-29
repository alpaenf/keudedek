# DATABASE SPECIFICATION

# SIPEDA - Sistem Monitoring dan Pengendalian Anggaran
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. ERD & Schema Overview

Sistem basis data SIPEDA dirancang untuk mengelola struktur hirarki anggaran, organisasi/unit, alur pengajuan, komitmen/reservasi dana, sistem peringatan dini (EWS), serta histori perubahan (audit log).

---

## 2. Table Specifications

### 2.1 `departments` (Unit / Jurusan / Sub-Unit)
Memuat data unit atau jurusan di lingkungan Fakultas Teknik.

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `code` | VARCHAR(20) | NO | UNIQUE | Kode unit (misal: `JTIF`, `JTS`, `JTE`) |
| `name` | VARCHAR(255) | NO | - | Nama lengkap unit/jurusan |
| `parent_id` | BIGINT | YES | FK | Id unit atasan jika hirarkis |
| `is_active` | BOOLEAN | NO | - | Status aktif unit (default: true) |
| `created_at` | TIMESTAMP | YES | - | Waktu pembuatan |
| `updated_at` | TIMESTAMP | YES | - | Waktu pembaruan |

---

### 2.2 `users` (Pengguna Sistem)
Memuat informasi akun pengguna dan asosiasi unit kewenangan.

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `name` | VARCHAR(255) | NO | - | Nama pengguna |
| `email` | VARCHAR(255) | NO | UNIQUE | Alamat email (username login) |
| `password` | VARCHAR(255) | NO | - | Hash kata sandi |
| `department_id` | BIGINT | YES | FK | Id unit asal pengguna |
| `role` | VARCHAR(50) | NO | - | Role (`ADMIN`, `PTK`, `KAJUR`, `PTU`, `KABAG`, `WD`) |
| `created_at` | TIMESTAMP | YES | - | Waktu pembuatan |
| `updated_at` | TIMESTAMP | YES | - | Waktu pembaruan |

---

### 2.3 `fiscal_years` (Tahun Anggaran)

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `year` | INT | NO | UNIQUE | Tahun anggaran (misal: `2026`) |
| `status` | VARCHAR(20) | NO | - | Status tahun (`DRAFT`, `ACTIVE`, `CLOSED`) |
| `start_date` | DATE | NO | - | Tanggal mulai |
| `end_date` | DATE | NO | - | Tanggal selesai |
| `created_at` | TIMESTAMP | YES | - | Waktu pembuatan |
| `updated_at` | TIMESTAMP | YES | - | Waktu pembaruan |

---

### 2.4 `funding_sources` (Sumber Dana)

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `code` | VARCHAR(20) | NO | UNIQUE | Kode sumber dana (misal: `BOPTN`, `UKT`, `RM`) |
| `name` | VARCHAR(255) | NO | - | Nama sumber dana |
| `description` | TEXT | YES | - | Keterangan sumber dana |
| `created_at` | TIMESTAMP | YES | - | Waktu pembuatan |

---

### 2.5 `budget_buckets` (Pos Anggaran / Pagu Unit)
Memuat pagu, jumlah ter-reserved, realisasi, dan saldo tersedia.

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `fiscal_year_id` | BIGINT | NO | FK | Foreign key ke `fiscal_years` |
| `department_id` | BIGINT | NO | FK | Foreign key ke `departments` |
| `funding_source_id` | BIGINT | NO | FK | Foreign key ke `funding_sources` |
| `account_code` | VARCHAR(50) | NO | - | Kode akun/mata anggaran |
| `account_name` | VARCHAR(255) | NO | - | Nama akun anggaran |
| `initial_budget` | DECIMAL(15,2) | NO | - | Nominal pagu awal |
| `allocated_budget` | DECIMAL(15,2) | NO | - | Nominal pagu aktif setelah revisi |
| `reserved_budget` | DECIMAL(15,2) | NO | - | Total anggaran yang sedang berkomitmen |
| `realized_budget` | DECIMAL(15,2) | NO | - | Total anggaran yang sudah terealisasi |
| `available_balance` | DECIMAL(15,2) | NO | - | Saldo tersedia (`allocated` - `reserved` - `realized`) |
| `created_at` | TIMESTAMP | YES | - | Waktu pembuatan |
| `updated_at` | TIMESTAMP | YES | - | Waktu pembaruan |

---

### 2.6 `submissions` (Pengajuan Anggaran)

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `submission_number` | VARCHAR(50) | NO | UNIQUE | Nomor unik pengajuan (misal: `REG/2026/08/001`) |
| `title` | VARCHAR(255) | NO | - | Judul pengajuan/kegiatan |
| `department_id` | BIGINT | NO | FK | Unit penanggung jawab |
| `fiscal_year_id` | BIGINT | NO | FK | Tahun anggaran |
| `budget_bucket_id` | BIGINT | NO | FK | Budget bucket yang digunakan |
| `amount` | DECIMAL(15,2) | NO | - | Nominal dana yang diajukan |
| `status` | VARCHAR(30) | NO | - | Status (`DRAFT`, `SUBMITTED`, `REVIEW`, `RETURNED`, `APPROVED`, `RESERVED`, `COMPLETED`, `REJECTED`) |
| `created_by` | BIGINT | NO | FK | User pembuat pengajuan |
| `notes` | TEXT | YES | - | Catatan / alasan pengembalian |
| `created_at` | TIMESTAMP | YES | - | Waktu pengajuan dibuat |
| `updated_at` | TIMESTAMP | YES | - | Waktu pembaruan terakhir |

---

### 2.7 `submission_items` (Rincian Item Pengajuan)

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `submission_id` | BIGINT | NO | FK | Foreign key ke `submissions` |
| `item_name` | VARCHAR(255) | NO | - | Nama barang / belanja kegiatan |
| `quantity` | INT | NO | - | Jumlah unit |
| `unit_price` | DECIMAL(15,2) | NO | - | Harga satuan |
| `total_price` | DECIMAL(15,2) | NO | - | Total harga per item |
| `created_at` | TIMESTAMP | YES | - | Waktu pembuatan |

---

### 2.8 `early_warnings` (Catatan Early Warning System)

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `rule_code` | VARCHAR(50) | NO | - | Kode rule (misal: `EWS-001`) |
| `severity` | VARCHAR(20) | NO | - | Tingkat keparahan (`CRITICAL`, `HIGH`, `MEDIUM`, `LOW`, `INFO`) |
| `department_id` | BIGINT | YES | FK | Unit terdampak |
| `budget_bucket_id` | BIGINT | YES | FK | Bucket terdampak |
| `current_value` | DECIMAL(15,2) | NO | - | Nilai aktual saat warning terpicu |
| `threshold_value` | DECIMAL(15,2) | NO | - | Batas ambang yang ditetapkan |
| `message` | TEXT | NO | - | Penjelasan dan rekomendasi tindakan |
| `status` | VARCHAR(20) | NO | - | Status warning (`ACTIVE`, `ACKNOWLEDGED`, `RESOLVED`) |
| `acknowledged_by` | BIGINT | YES | FK | User yang menyetujui/mengetahui |
| `created_at` | TIMESTAMP | YES | - | Waktu terdeteksi |
| `updated_at` | TIMESTAMP | YES | - | Waktu pembaruan |

---

### 2.9 `audit_logs` (Rekam Jejak Aktivitas / Audit Trail)

| Field | Type | Nullable | Key | Description |
|---|---|---|---|---|
| `id` | BIGINT | NO | PK | Primary Key |
| `user_id` | BIGINT | YES | FK | User pelaku tindakan |
| `action` | VARCHAR(100) | NO | - | Nama tindakan (`CREATE_SUBMISSION`, `APPROVE_SUBMISSION`, `IMPORT_BUDGET`, dll) |
| `model_type` | VARCHAR(100) | YES | - | Nama class Model |
| `model_id` | BIGINT | YES | - | ID record yang diubah |
| `old_values` | JSON | YES | - | Data sebelum perubahan |
| `new_values` | JSON | YES | - | Data setelah perubahan |
| `ip_address` | VARCHAR(45) | YES | - | Alamat IP pengguna |
| `user_agent` | TEXT | YES | - | Browser agent pengguna |
| `created_at` | TIMESTAMP | YES | - | Waktu eksekusi |
