# ARCHITECTURE SPECIFICATION

# SIPEDA - Sistem Monitoring dan Pengendalian Anggaran
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Architecture Overview

SIPEDA dirancang menggunakan arsitektur **Monolith Modern** yang menggabungkan kekuatan backend Laravel dengan reaktivitas frontend Single-Page Application (SPA) melalui **Inertia.js** dan **Vue 3**.

```text
[ Client / Browser ]
         │
         ▼
[ Vue 3 / Inertia.js Frontend ]
         │ (Inertia HTTP Protocol / JSON Props)
         ▼
[ Laravel Controller & Middleware ]
         │
         ▼
[ Service Layer & Rule Engine ]
         │
         ▼
[ Eloquent ORM & Relational DB ]
```

---

## 2. Technology Stack

- **Backend Framework:** Laravel 11.x / 12.x (PHP 8.3+)
- **Frontend Adapter:** Inertia.js
- **Frontend Framework:** Vue 3 (Composition API / `<script setup>`)
- **Styling:** CSS / Tailwind CSS
- **Database:** MySQL / MariaDB (atau SQLite untuk environment development)
- **Queue / Background Jobs:** Laravel Queue (Database Driver / Redis)
- **Import Engine:** Spout / PhpSpreadsheet / Custom Chunked Reader

---

## 3. Application Layers

Aplikasi ini dibagi menjadi beberapa layer arsitektur utama:

1. **HTTP / Presentation Layer (Controllers & Inertia Pages)**
   - Menangani request pengguna, validasi input dasar, dan mengembalikan `Inertia::render()`.
2. **Authorization Layer (Policies & Gates)**
   - Memastikan pengguna hanya dapat mengakses data anggaran sesuai unit dan kewenangannya (Role-Based & Department-Scoped Access Control).
3. **Service / Business Logic Layer**
   - Menampung logika bisnis kompleks seperti reservasi anggaran, pelepasan komitmen, kalkulasi saldo tersedia, dan eksekusi workflow pengajuan.
4. **Rule Engine Layer**
   - Modul khusus yang mengevaluasi kondisi anggaran terhadap aturan EWS (Early Warning System) secara proaktif.
5. **Persistence / Data Access Layer (Eloquent Models & Migrations)**
   - Menangani relasi data, mutator, query scopes, dan transaksi basis data.

---

## 4. Laravel Structure

Repository ini menggunakan struktur standar Laravel:

```text
app/
├── Http/
│   ├── Controllers/       # Controller per domain (Budget, Submission, EWS, Import)
│   ├── Middleware/        # Middleware otentikasi & permission
│   └── Requests/          # Form Request Validation
├── Models/                # Eloquent Models (Department, BudgetBucket, Submission, etc.)
├── Services/              # Business Logic (BudgetService, SubmissionService, EWSManager)
├── Rules/                 # Rule Engine Classes (CriticalBalanceRule, OverbudgetRule)
└── Policies/              # Authorization Policies
resources/
└── js/
    ├── Layouts/           # AppLayout, AuthLayout, DashboardLayout
    ├── Pages/             # Vue Pages (Dashboard, Submissions, Budget, EWS, Reports)
    └── Components/        # UI Components (KPI Cards, Tables, Modals, Badges)
```

---

## 5. Inertia Architecture

Inertia.js bertindak sebagai jembatan antara Laravel Controllers dan Vue components:
- Controller mengembalikan data dalam bentuk props tanpa membutuhkan REST/GraphQL API terpisah.
- Shared Data (User Session, Flash Messages, active permissions) dipasang via Inertia Middleware (`HandleInertiaRequests`).
- State navigasi dikelola secara seamlessly tanpa full page refresh.

---

## 6. Vue Architecture

- Menggunakan Vue 3 dengan syntax `<script setup>` dan TypeScript / JavaScript modern.
- Mengintegrasikan modul komponen UI terpusat (Tables, Forms, KPICard, AlertBox, Modal).
- Manajemen state lokal diprioritaskan, menggunakan Inertia Form Helper (`useForm`) untuk form submission & penanganan error validasi secara otomatis.

---

## 7. Service Layer & Transaction Integrity

Setiap perubahan anggaran (seperti persetujuan pengajuan, reservasi dana, dan realisasi final) WAJIB menggunakan **Database Transactions (`DB::transaction`)** untuk menjamin konsistensi data (ACID properties).

```php
DB::transaction(function () use ($submission) {
    // 1. Check Saldo Tersedia
    // 2. Buat Budget Reservation
    // 3. Update Status Submission menjadi APPROVED / RESERVED
    // 4. Catat Audit Log
});
```

---

## 8. Authorization & Data Isolation

Aplikasi mengimplementasikan pembatasan tingkat data (*Data-level Authorization*):
- User PTK Jurusan Elektro hanya dapat melihat dan mengajukan anggaran untuk Jurusan Elektro.
- Kepala Bagian Keuangan & Wakil Dekan dapat memonitor seluruh data fakultas.
- Pengecekan dilakukan menggunakan Laravel Policy `SubmissionPolicy`, `BudgetBucketPolicy`, dll.

---

## 9. File Storage & Upload Handling

- Upload dokumen pendukung pengajuan disimpan di direktori terisolasi (`storage/app/submissions/{submission_id}/`).
- File import XLSX/CSV diproses sementara di staging area sebelum dicommit ke basis data utama.

---

## 10. Import Pipeline

Pipeline import anggaran mendukung data berskala besar:
1. **Upload:** User mengunggah file XLSX/CSV.
2. **Staging & Parse:** Membaca file baris demi baris (*streaming/chunking*).
3. **Validation & Mapping:** Validasi struktur kolom, tipe data, serta ketersediaan master data.
4. **Preview:** Menampilkan ringkasan data yang valid dan invalid kepada user.
5. **Commit:** Menginsert data secara masal (*bulk insert*) ke dalam tabel `budget_buckets`.

---

## 11. Rule Engine Architecture

Engine EWS dieksekusi pada 2 pemicu utama:
- **Event-driven:** Saat pengajuan baru dibuat / diubah nominalnya.
- **Scheduled Job:** Routine check setiap jam/hari untuk mendeteksi perubahan tren penyerapan anggaran.

```text
[ Event: SubmissionCreated / BudgetUpdated ]
                    │
                    ▼
          [ RuleEngineService ]
                    │
       ┌────────────┴────────────┐
       ▼                         ▼
[ CriticalBalanceRule ]  [ HighBurnRateRule ]
       │                         │
       └────────────┬────────────┘
                    ▼
       [ EarlyWarning Generated / Resolved ]
```

---

## 12. Error Handling & Exception Management

- Exceptions ditangani secara terpusat di `bootstrap/app.php` / `app/Exceptions/Handler.php`.
- Menampilkan pesan kesalahan yang komunikatif pada UI (tidak mengembalikan 500 error mentah ke pengguna).
- Setiap kesalahan kritis dicatat pada log aplikasi (`storage/logs/laravel.log`).

---

## 13. Logging & Audit Trail

- Aktivitas penting (Perubahan anggaran, approval, login, perubahan konfigurasi rule) dicatat ke dalam tabel `audit_logs`.
- Audit Log merekam `user_id`, `action`, `model_type`, `model_id`, `old_values`, `new_values`, `ip_address`, dan `user_agent`.

---

## 14. Security Measures

- Cross-Site Request Forgery (CSRF) Protection pada seluruh endpoint.
- Sanitasi input dan proteksi terhadap SQL Injection (via Query Builder & PDO Prepared Statements).
- Rate Limiting pada endpoint sensitive (Login, Import, Export).

---

## 15. Deployment Strategy

- Kompatibel dengan lingkungan hosting internal / VPS berbasis Linux/Windows (Laragon/IIS/Nginx/Apache).
- Command deployment standar:
  ```bash
  composer install --no-dev --optimize-autoloader
  php artisan migrate --force
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  npm run build
  ```
