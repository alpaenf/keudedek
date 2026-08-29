# AUDIT TRAIL & SECURITY SPECIFICATION

# SIPEDA - Keamanan & Audit Trail System
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Audit Trail Requirements

Sesuai dengan PRD Poin 5.1 (J) & Bab 7, **SIPEDA** wajib mencatat setiap aktivitas krusial (*auditable events*) yang memengaruhi status data, konfigurasi, dan posisi keuangan secara transparan dan *non-repudiable* (tidak dapat disangkal).

---

## 2. Auditable Events Catalog

Aktivitas berikut **WAJIB** mencatatkan *log entry* ke tabel `audit_logs`:

1. **Authentication:** Login pengguna, logout, dan percobaan login gagal.
2. **Budget Management:** Inisialisasi pagu, perubahan pagu, dan approval revisi anggaran.
3. **Submissions Workflow:** Pembuatan pengajuan, perubahan status (Review, Returned, Approved, Completed, Rejected), dan perubahan nominal item.
4. **EWS Configuration:** Perubahan threshold rule EWS dan *acknowledgement* warning oleh KAJUR/KABAG.
5. **Data Import:** Peluncuran data dari staging area ke basis data utama.

---

## 3. Audit Log Data Schema & Payload Structure

Setiap record `audit_logs` menyimpan struktur payload JSON berikut:

```json
{
  "event": "SUBMISSION_APPROVED",
  "user": {
    "id": 12,
    "name": "Budi Santoso",
    "role": "KAJUR"
  },
  "target": {
    "model": "App\\Models\\Submission",
    "id": 45
  },
  "old_values": {
    "status": "REVIEW",
    "reserved_amount": 0
  },
  "new_values": {
    "status": "APPROVED",
    "reserved_amount": 25000000
  },
  "network": {
    "ip_address": "10.10.2.14",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
  },
  "timestamp": "2026-08-29T12:30:00+07:00"
}
```

---

## 4. Application Security Controls

### 4.1 Data Isolation & Authorizations
- Setiap *query* data keuangan otomatis dibatasi oleh `department_id` pengguna yang sedang aktif (kecuali role Fakultas seperti KABAG, WD, Admin).

### 4.2 Protection Against Financial Data Tampering
- Nilai `available_balance` tidak boleh diperbarui secara manual melalui query mentah (*raw UPDATE*), melainkan harus dikalkulasi otomatis oleh Service Layer yang terisolasi dalam `DB::transaction()`.

### 4.3 Input Sanitization & Proteksi Vulnerability
- Seluruh input form disanitasi dari potensi *Cross-Site Scripting* (XSS).
- Seluruh query database menggunakan Eloquent Query Builder / PDO Prepared Statements untuk mencegah *SQL Injection*.
- Proteksi CSRF diaktifkan pada seluruh request stateful via Inertia/Laravel middleware.
