# ROLES & PERMISSIONS SPECIFICATION

# SIPEDA - Role-Based Access Control (RBAC)
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. Role Definitions

Sistem mengidentifikasi 6 peran (*roles*) utama dalam pengelolaan anggaran internal Fakultas Teknik:

1. **PTK (Pelaksana Tugas Kegiatan / Operator Unit)**
   - Bertanggung jawab atas pembuatan pengajuan, penyusunan item belanja, pengunggahan dokumen pendukung, serta revisi pengajuan yang dikembalikan.
   - **Scope Data:** Terbatas pada Unit/Jurusan tempat PTK ditugaskan.

2. **KAJUR (Ketua Jurusan / Penanggung Jawab Unit)**
   - Memonitor ketersediaan anggaran unit, menyetujui pengajuan unit, memantau indikator EWS, serta mengkaji laporan unit.
   - **Scope Data:** Terbatas pada Unit/Jurusan kewenangannya.

3. **PTU (Pelaksana Tugas Umum / Reviewer Keuangan)**
   - Memeriksa kelengkapan administrasi & keabsahan dokumen pengajuan dari seluruh unit.
   - **Scope Data:** Seluruh Fakultas Teknik (Fokus pada antrean review pengajuan).

4. **KABAG (Kepala Bagian Keuangan)**
   - Memonitor seluruh kondisi anggaran fakultas, mengelola revisi pagu, menangani *exception*, dan mengonfirmasi *warning* EWS ber-severity tinggi.
   - **Scope Data:** Seluruh Fakultas Teknik.

5. **WD (Wakil Dekan Bidang Keuangan / Perencanaan)**
   - Memantau indikator makro penyerapan anggaran, laporan perbandingan unit, serta pendukung keputusan strategis.
   - **Scope Data:** Seluruh Fakultas Teknik (Read-Only / Monitoring Executive View).

6. **ADMIN (System Administrator)**
   - Mengelola akun pengguna, role, master data organisasi, konfigurasi rule engine EWS, dan import anggaran awal.
   - **Scope Data:** Akses Penuh (System-Wide Management).

---

## 2. Permission Matrix

| Modul / Fitur | PTK | KAJUR | PTU | KABAG | WD | ADMIN |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Dashboard Executive** | - | Unit | All | All | All | All |
| **Buat Pengajuan** | ✅ | - | - | - | - | - |
| **Edit Pengajuan (Draft/Returned)** | ✅ | - | - | - | - | - |
| **Verifikasi Pengajuan (Review)** | - | - | ✅ | - | - | - |
| **Persetujuan Pengajuan (Approve)** | - | ✅ | - | ✅ | - | - |
| **Import Data Anggaran** | - | - | - | - | - | ✅ |
| **Kelola Revisi Anggaran** | - | - | - | ✅ | - | ✅ |
| **Acknowledge EWS Warning** | - | ✅ | - | ✅ | - | ✅ |
| **Konfigurasi Rule EWS** | - | - | - | - | - | ✅ |
| **Export Laporan (PDF/XLSX)** | Unit | Unit | All | All | All | All |
| **Lihat Audit Log** | - | - | - | ✅ | ✅ | ✅ |
| **Kelola User & Master Data** | - | - | - | - | - | ✅ |

---

## 3. Data Scoping Implementation

Pengecekan otorisasi tingkat baris (*Row-Level Security*) diimplementasikan pada Laravel Policies:

```php
// Contoh pada SubmissionPolicy.php
public function view(User $user, Submission $submission): bool
{
    if ($user->hasRole(['ADMIN', 'KABAG', 'WD', 'PTU'])) {
        return true;
    }

    return $user->department_id === $submission->department_id;
}
```
