# WORKFLOW SPECIFICATION

# SIPEDA - Submission & Budget Reservation Workflow
## Fakultas Teknik Universitas Jenderal Soedirman

---

## 1. State Diagram

Berikut adalah alur perubahan status pengajuan anggaran pada sistem SIPEDA:

```text
       [ DRAFT ] (Operator / PTK)
           │
           ▼ (Submit)
     [ SUBMITTED ]
           │
           ▼ (Verifikasi / Reviewer)
      [ REVIEW ]
       ├───► [ RETURNED ] ───► (Revisi oleh PTK) ───► [ DRAFT ]
       │        (Dikembalikan)
       │
       └───► [ APPROVED ]
                │
                ▼ (Sistem melakukan Reservasi Anggaran)
           [ RESERVED ]
                │
                ▼ (Proses Pencairan / Operasional)
          [ PROCESSING ]
                │
                ▼ (SPJ Diverifikasi & Selesai)
           [ COMPLETED ]
```

---

## 2. Description of States

| Status | Pengelola | Dampak pada Anggaran | Deskripsi |
|---|---|---|---|
| **DRAFT** | PTK / Operator | Saldo tidak berubah | Pengajuan sedang disusun oleh operator unit. |
| **SUBMITTED** | PTK / Operator | Saldo tidak berubah | Pengajuan telah dikirim dan menunggu verifikasi awal. |
| **REVIEW** | PTU / Reviewer | Saldo tidak berubah | Pengajuan sedang diverifikasi oleh reviewer keuangan. |
| **RETURNED** | PTU / Reviewer | Saldo tidak berubah | Pengajuan dikembalikan ke PTK karena berkas/nominal perlu diperbaiki. |
| **APPROVED** | KAJUR / KABAG | Transit ke Reserved | Pengajuan disetujui secara prosedural. |
| **RESERVED** | Sistem | `reserved_budget` + Nominal<br>`available_balance` - Nominal | Anggaran dikunci/di-reservasi sehingga tidak bisa dipakai pengajuan lain. |
| **PROCESSING** | Pihak Keuangan | Saldo terikat pada komitmen | Kegiatan sedang berlangsung dan berkas pertanggungjawaban diproses. |
| **COMPLETED** | Pihak Keuangan | `reserved_budget` - Nominal<br>`realized_budget` + Nominal | Kegiatan selesai, realisasi dicatat secara permanen. |
| **REJECTED** | Reviewer / KABAG | Reservasi dilepas (jika ada) | Pengajuan ditolak sepenuhnya dan tidak dapat dilanjutkan. |

---

## 3. Budget Reservation Mechanism

1. **Pengecekan Saldo (Available Balance Check):**
   - Sebelum status berubah menjadi `APPROVED`/`RESERVED`, sistem memeriksa `available_balance >= submission_amount`.
2. **Penguncian Anggaran (Budget Locking):**
   - Nominal pengajuan ditambahkan ke `reserved_budget`.
   - `available_balance` otomatis berkurang.
3. **Penyelesaian / Realisasi Final:**
   - Saat status menjadi `COMPLETED`, dana dari `reserved_budget` dipindahkan ke `realized_budget`. Jika realisasi aktual berbeda dengan reservasi, penyesuaian (*adjustment*) otomatis dihitung oleh sistem.
