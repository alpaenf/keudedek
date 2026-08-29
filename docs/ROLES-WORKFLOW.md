# Role dan Alur Hak Akses Sistem (SIPEDA FT UNSOED)

Sistem informasi keuangan fakultas dirancang dengan pendekatan **Role-Based Access Control (RBAC)**, yaitu setiap pengguna memperoleh hak akses berdasarkan peran yang dimilikinya dalam proses bisnis. Pembagian role tidak hanya bertujuan membatasi akses terhadap fitur tertentu, tetapi juga untuk menjaga pemisahan tanggung jawab antara pihak yang melakukan input data, pihak yang melakukan verifikasi dan pengelolaan keuangan, pihak yang melakukan pemantauan dan pengambilan keputusan, serta pihak yang bertugas mengelola aplikasi. Dengan pendekatan tersebut, pengguna hanya dapat mengakses data dan melakukan tindakan yang sesuai dengan kewenangannya. Struktur role juga dirancang agar dapat dikembangkan apabila setelah proses validasi dengan pihak PTK dan pengelola keuangan ditemukan adanya struktur kewenangan yang lebih spesifik.

---

## 1. Super Admin

Super Admin merupakan role yang bertanggung jawab terhadap pengelolaan teknis dan konfigurasi sistem, bukan terhadap pengambilan keputusan keuangan. Super Admin memiliki akses terhadap konfigurasi aplikasi, pengelolaan akun pengguna, pengaturan role dan permission, serta pengelolaan master data yang dibutuhkan agar sistem dapat berjalan dengan baik. Pengelolaan tersebut dapat mencakup data organisasi seperti fakultas, jurusan, program studi atau unit kerja, serta konfigurasi lain seperti sumber dana, kategori transaksi, periode anggaran, dan parameter sistem.

Dalam alur penggunaan sistem, Super Admin terlebih dahulu memastikan struktur organisasi, akun pengguna, dan konfigurasi sistem telah tersedia. Setelah pengguna dibuat, Super Admin dapat memberikan role dan menghubungkan pengguna dengan unit tertentu apabila sistem menerapkan pembatasan akses berdasarkan unit. Sebagai contoh, seorang PTK dapat diberikan role `PTK_UNIT` dan dikaitkan dengan unit tertentu sehingga sistem mengetahui data mana yang boleh diakses oleh pengguna tersebut. Setelah konfigurasi selesai, aktivitas operasional keuangan dilakukan oleh pengguna sesuai role masing-masing.

Super Admin tidak menjadi pihak yang melakukan verifikasi pengajuan anggaran, menyetujui transaksi, menentukan kebijakan anggaran, atau mengubah hasil monitoring keuangan. Pembatasan tersebut diperlukan untuk menjaga pemisahan antara **administrator aplikasi** dan **pengelola proses keuangan**. Dengan demikian, perubahan konfigurasi teknis tidak bercampur dengan keputusan bisnis atau keputusan keuangan.

---

## 2. PTK Unit (Operator Unit)

PTK Unit merupakan pengguna yang berhubungan langsung dengan proses penginputan atau penyampaian informasi dari unit kerja ke dalam sistem. Role ini dirancang untuk merepresentasikan pengguna pada tingkat unit yang menjadi sumber data atau pengusul dalam proses perencanaan dan pengelolaan anggaran. 

Setelah login, PTK akan diarahkan ke dashboard unit yang menampilkan informasi yang relevan dengan unit yang menjadi tanggung jawabnya. Data yang ditampilkan dibatasi berdasarkan unit tersebut (`department_id`) sehingga PTK tidak secara otomatis memperoleh akses untuk mengubah data unit lain. PTK dapat melihat informasi anggaran, realisasi, pengajuan, status proses, serta peringatan yang berkaitan dengan unitnya.

Dalam proses perencanaan anggaran, PTK melakukan pengusulan data pengajuan dalam bentuk `DRAFT`. Pengguna dapat mengisi informasi program atau kegiatan beserta rincian item belanjanya, kemudian menyimpan data tersebut sebelum dikirimkan kepada bagian yang berwenang melakukan pemeriksaan. Setelah data dianggap lengkap, PTK melakukan proses submit sehingga status data berubah dari `DRAFT` menjadi `SUBMITTED`.

Setelah pengajuan dikirim, PTK tidak lagi memiliki kewenangan untuk mengubah data secara bebas. Apabila bagian keuangan mengembalikan pengajuan (`RETURNED`) karena terdapat kesalahan atau kekurangan informasi, PTK memperoleh kesempatan untuk melakukan perbaikan berdasarkan catatan yang diberikan. Setelah diperbaiki, data dapat dikirimkan kembali untuk proses verifikasi. Pola ini membentuk siklus yang jelas: **Input (Draft) → Submit → Review → Revisi (Returned) → Submit Kembali**.

---

## 3. Staff / KAJUR / PTU / Kabag Keuangan

Role Keuangan merupakan role utama dalam proses pengelolaan dan pengendalian informasi keuangan pada tingkat fakultas dan jurusan. Role ini memiliki kewenangan yang lebih luas dibandingkan PTK karena bertugas melakukan pemeriksaan, pengelolaan, monitoring, serta penyusunan informasi keuangan yang diperlukan oleh organisasi.

- **KAJUR (Ketua Jurusan):** Memverifikasi dan menyetujui pengajuan unit jurusan sebelum diteruskan ke tingkat fakultas.
- **PTU (Reviewer Keuangan):** Memeriksa kelengkapan SPJ, rincian biaya, dan kepatuhan akun belanja (`REVIEW`).
- **KABAG Keuangan:** Melakukan approval tingkat fakultas (`APPROVED`), mengunci komitmen reservasi dana (`RESERVED`), mengeksekusi pencairan (`COMPLETED`), serta mengelola revisi pagu anggaran (*Budget Revision*).

Role Keuangan juga menjadi pengguna utama fitur **Budget Control dan Early Warning (EWS)**. Sistem akan mengolah data anggaran dan realisasi untuk mendeteksi kondisi threshold (EWS-001 < 15% saldo ketersediaan, EWS-002 blokir overbudget).

---

## 4. Pimpinan / Executive (WD II)

Role Pimpinan (Wakil Dekan II / Dekan) ditujukan untuk pengguna yang membutuhkan informasi keuangan pada tingkat pengambilan keputusan, bukan untuk melakukan pekerjaan administratif harian. Pimpinan memperoleh informasi yang telah dirangkum oleh sistem sehingga dapat melihat kondisi anggaran fakultas secara keseluruhan maupun berdasarkan unit tertentu.

Setelah login, Pimpinan dapat melihat **Executive Dashboard** yang menampilkan indikator utama seperti total pagu aktif, total realisasi, komitmen reserved, sisa saldo tersedia, persentase penyerapan, kondisi anggaran berdasarkan unit, serta daftar early warning yang membutuhkan perhatian.

---

## 5. Auditor / Viewer

Role Auditor atau Viewer dirancang untuk kebutuhan pemantauan dan penelusuran informasi tanpa memberikan kewenangan untuk mengubah data. Pengguna dengan role ini dapat memperoleh akses baca terhadap data yang diperlukan, seperti laporan realisasi anggaran (LRA), histori transaksi, status pengajuan, serta **Audit Trail Log**.

Tujuan utama role ini adalah memberikan transparansi dan kemampuan penelusuran (*traceability*) terhadap aktivitas yang terjadi di dalam sistem. Auditor atau Viewer tidak diperbolehkan melakukan input, perubahan, penghapusan, verifikasi, maupun approval terhadap data keuangan.

---

## 6. Alur Antar-Role Secara Keseluruhan

```text
PTK UNIT (Operator)
   │
   │ Input / Pengajuan Belanja
   ▼
┌──────────────────────┐
│       SISTEM         │
│  Draft → Submitted   │
└──────────┬───────────┘
           │
           ▼
    KAJUR / PTU (Verifikasi)
           │
     ┌─────┴─────┐
     │           │
     ▼           ▼
  RETURNED    VERIFIED (Review)
     │           │
     │           ▼
     │     KABAG KEUANGAN
     │     (Approved & Reserved)
     │           │
     │           ▼
     │     Budget Control & EWS
     │     (Normal / Warning / Critical)
     │           │
     └──────► PTK
                 │
                 ▼
            PIMPINAN (WD II)
                 │
                 ▼
           Executive LRA /
          Decision Support
                 │
                 ▼
          AUDITOR / VIEWER
          (Audit Trail Log)
```

---

## 7. Hubungan Role dengan Budget Control dan Early Warning

Modul Rule-Based Budget Control bertindak sebagai lapisan analitik di atas data anggaran dan realisasi:

```text
Data Pagu + Data Realisasi + Data Reservasi
                     │
                     ▼
       ┌──────────────────────────┐
       │   RULE ENGINE (EWS)      │
       │ Evaluasi Ketersediaan    │
       │ Sisa Saldo (< 15%)       │
       └────────────┬─────────────┘
                    │
                    ▼
          Early Warning Alerts
            (Active / Ack)
                    │
            ┌───────┴───────┐
            ▼               ▼
        KEUANGAN        PIMPINAN
```

**Rule-Based System tidak mengambil alih keputusan manusia**, melainkan memberikan identifikasi kondisi berdasarkan aturan yang telah ditetapkan, kemudian pengguna yang berwenang melakukan interpretasi dan tindakan lebih lanjut.

---

## 8. Prinsip Struktur RBAC pada Laravel

Implementasi RBAC pada Laravel 12 + Inertia.js + Vue 3 menggunakan struktur relasi:

```text
User
 ├── Role (ADMIN, PTK, KAJUR, PTU, KABAG, WD)
 └── Department (Jurusan / Unit Kerja)
```

Pengecekan otorisasi tidak hanya berdasarkan nama role, tetapi juga memperhitungkan unit kerja asal user (`department_id`). Dengan demikian, penambahan unit baru tidak memerlukan pembuatan role baru di database.
