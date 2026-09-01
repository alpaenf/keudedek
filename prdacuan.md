# PRODUCT REQUIREMENTS DOCUMENT

## Sistem Monitoring & Pengendalian Realisasi Anggaran Fakultas

### Rule-Based Budget Control + Early Warning System

| Status | Draft untuk validasi PTK dan pembimbing |
| --- | --- |
| Versi | 1.0 |
| Fokus MVP | Rupiah Murni (RM) • monitoring realisasi per jurusan |
| Design language | Modern professional • white-first • blue secondary • Poppins |
| Tanggal | 28 Agustus 2026 |

> **Working scope** — Sistem ini adalah lapisan monitoring dan pengendalian internal fakultas. MVP tidak menggantikan SAKTI, ELFINA, maupun aplikasi Pak Arwan dan tidak melakukan pencairan dana resmi.

# 01 — PRODUCT OVERVIEW

## Ringkasan Produk

Dokumen ini menerjemahkan hasil diskusi Kabag Keuangan dan dokumen anggaran yang tersedia menjadi kebutuhan produk yang siap dipakai untuk desain, prototyping, dan validasi stakeholder.

## 1.1 Problem statement

Fakultas membutuhkan informasi realisasi anggaran yang dapat ditelusuri sampai tingkat jurusan. Pada kondisi saat ini, data lintas PTK dan sumber dana dapat terkonsolidasi pada level yang belum cukup granular sehingga kontrol pagu jurusan membutuhkan pengecekan manual. Risiko yang ingin dikurangi adalah pengajuan melampaui pagu, penggunaan saldo yang secara internal dialokasikan untuk unit lain, keterlambatan deteksi realisasi, dan pencatatan berulang.

## 1.2 Product proposition

> **Value proposition** — Satu aplikasi internal yang menerima data anggaran definitif, mengaitkannya dengan jurusan, mencatat lifecycle pengajuan PTK, melakukan reservasi pagu otomatis, menolak/menandai overbudget, melepas reservasi saat pengajuan dibatalkan/dikembalikan, dan memberi early warning kepada pengelola.

## 1.3 Fokus penelitian

| Komponen | Keputusan PRD |
| --- | --- |
| Problem domain | Monitoring dan pengendalian realisasi anggaran sampai tingkat jurusan. |
| Sumber dana MVP | Rupiah Murni (RM). |
| Algoritma utama | Rule-Based Budget Control. |
| Decision support | Rule-Based Early Warning System. |
| Data awal | Import struktur anggaran / spreadsheet sesuai template yang tersedia. |
| Output utama | Status pengajuan, pagu tersedia, realisasi final, reserved budget, warning, dashboard, laporan. |
| Integrasi eksternal | Di luar MVP; disiapkan sebagai future integration. |

## 1.4 Sasaran produk

- Mencegah pengajuan internal melebihi saldo anggaran yang tersedia pada budget bucket yang dikontrol.
- Memberikan visibilitas realisasi dan pengajuan aktif per jurusan tanpa menunggu rekap manual.
- Mengurangi risiko double-counting dengan membedakan pagu, reserved/committed, realisasi final, dan saldo tersedia.
- Memberi peringatan terstruktur untuk saldo kritis, serapan rendah, pengajuan terlalu lama, atau potensi overbudget.
- Menjaga histori revisi anggaran dan audit trail sehingga perubahan dapat ditelusuri.

## 1.5 Non-goals MVP

- Tidak melakukan pencairan dana ke KPPN atau menggantikan fungsi SAKTI.
- Tidak menggantikan ELFINA sebagai layanan perbendaharaan BLU/PNBP.
- Tidak melakukan alokasi/ranking jurusan dengan SAW.
- Tidak melakukan forecasting berbasis machine learning pada MVP.
- Tidak mengandalkan API SAKTI/ELFINA/Pak Arwan untuk MVP.

# 02 — REQUIREMENT BASIS

## Requirement yang sudah tervalidasi vs asumsi

Semua keputusan yang belum dikonfirmasi PTK ditandai sebagai configurable atau open question, bukan dianggap proses resmi.

| Status | Requirement / asumsi |
| --- | --- |
| VALIDATED | Monitoring realisasi dibutuhkan sampai tingkat jurusan. |
| VALIDATED | MVP difokuskan pada Rupiah Murni. |
| VALIDATED | Pengajuan awal perlu mengurangi pagu tersedia secara internal agar pengajuan lain tidak memakai saldo yang sama. |
| VALIDATED | Pengajuan yang dibatalkan/dikembalikan harus dapat mengembalikan pagu yang sebelumnya di-reserve. |
| VALIDATED | Data mentah perlu membawa identitas jurusan/kode jurusan. |
| VALIDATED | Kontrol terlalu detail per baris RAB berisiko terlalu rigid; level kontrol perlu mengikuti bucket/akun yang disepakati. |
| CONFIGURABLE | Urutan approval dan nama status final masih harus dikonfirmasi PTK. |
| CONFIGURABLE | Threshold early warning belum ditentukan stakeholder. |
| OPEN | Dokumen/field berbeda untuk LS, UP, TUP dan variasi transaksi lainnya. |
| OPEN | Ketersediaan data historis yang layak untuk fitur analitik lanjutan. |

# 03 — PRODUCT GOALS

## Tujuan, indikator keberhasilan, dan batasan

## 3.1 Success metrics MVP

| Metric | Target awal | Cara ukur |
| --- | --- | --- |
| Overbudget prevention | 100% pengajuan di atas saldo tersedia terblokir/terflag sebelum submit final internal | Uji skenario + audit rule execution |
| Budget consistency | Tidak ada saldo tersedia negatif tanpa override berotorisasi | Rekonsiliasi data dan assertion database |
| Traceability | 100% perubahan status dan perubahan pagu terekam | Audit log |
| Jurusan visibility | Setiap user jurusan hanya melihat budget scope yang diotorisasi | Role-based access test |
| Import reliability | Baris valid terimpor tanpa duplikasi; baris invalid masuk validation queue | Import report |
| Warning usefulness | Setiap warning memiliki alasan, severity, object, dan tindakan yang dapat dilakukan | UAT stakeholder |

## 3.2 Product principles

- White-first interface: layar terlihat bersih, fokus pada data dan status, bukan dekorasi.
- Blue only as structural accent: CTA, active navigation, link, highlight, selected state.
- Financial clarity: nominal selalu memakai pemisah ribuan, label sumber dana, tahun anggaran, dan versi/revisi yang jelas.
- Explainable rules: setiap blok/warning harus menjelaskan rule yang memicu dan nilai yang dibandingkan.
- No silent mutation: revisi, pembatalan, approval, dan release reservation harus menghasilkan audit record.

# 04 — USERS & ROLES

## Peran pengguna dan scope akses

| Role konseptual | Kebutuhan utama | Scope data | Aksi utama |
| --- | --- | --- | --- |
| PTK / Operator | Membuat pengajuan, melihat saldo, memantau status | Jurusan/penugasan sendiri | Create draft, submit, upload dokumen, revise, cancel bila diizinkan |
| Kajur / Penanggung Jawab | Mengetahui penggunaan pagu jurusan dan memberi approval internal | Jurusan sendiri | Review, approve/return, lihat warning jurusan |
| PTU / Keuangan | Verifikasi kesesuaian dan kelengkapan pengajuan | Lintas jurusan sesuai kewenangan | Verify, return, flag issue, lihat queue |
| Kabag Keuangan | Monitoring operasional fakultas | Seluruh fakultas | Monitor, resolve exception, laporan, konfigurasi terbatas |
| WD Keuangan / Dekan | Monitoring eksekutif | Seluruh fakultas | Dashboard, exception view, laporan; approval jika workflow membutuhkan |
| Admin Sistem | Kelola master, user, role, rule configuration | Seluruh data administratif | Master data, user, permission, threshold, audit support |

> **Catatan validasi** — Nama role dan urutan approval harus dipetakan ke struktur operasional nyata setelah diskusi PTK. PRD sengaja membuat workflow configurable agar prototype dapat disesuaikan tanpa redesign besar.

# 05 — UI/UX DESIGN SYSTEM

## Modern professional — white primary, blue secondary, Poppins

Tampilan harus terasa seperti financial operations dashboard modern: padat informasi namun tenang, minim ornamen, dan konsisten.

## 5.1 Visual direction

| Token | Nilai | Penggunaan |
| --- | --- | --- |
| Primary canvas | #FFFFFF | Card, table, panel, modal, form surface |
| Secondary / Blue 600 | #2563EB | Primary CTA, active nav, selected state, link |
| Blue 700 | #1D4ED8 | Hover/pressed state, heading accent |
| Blue 50 | #EFF6FF | Informational highlight, selected row soft background |
| App background | #F8FAFC | Main page background di luar card |
| Text primary | #0F172A | Judul dan angka penting |
| Text body | #1E293B | Isi utama |
| Text muted | #64748B | Caption, metadata, helper text |
| Border | #E2E8F0 | Divider, card border, table grid |
| Success | #16A34A | Approved/final/healthy |
| Warning | #D97706 | At risk / needs attention |
| Danger | #DC2626 | Blocked / overbudget / rejected |
| Info | #0284C7 | Processing / informational |

## 5.2 Typography

| Style | Font | Weight | Desktop size | Usage |
| --- | --- | --- | --- | --- |
| Display | Poppins | 700 | 28–32 px | Page title besar / cover |
| H1 | Poppins | 600 | 22–24 px | Judul halaman |
| H2 | Poppins | 600 | 16–18 px | Judul section/card |
| Body | Poppins | 400 | 14 px | Form, table, description |
| Body medium | Poppins | 500 | 14 px | Label dan highlight |
| Caption | Poppins | 400 | 12 px | Helper, timestamp, metadata |
| Financial KPI | Poppins | 600–700 | 24–30 px | Nominal dashboard |

## 5.3 Layout

- Left sidebar 240–256 px, background putih, logo/product title di atas, active item dengan blue-50 + blue text.
- Topbar 64 px: breadcrumb, tahun anggaran/revisi aktif, notification bell, user menu.
- Main background #F8FAFC; card putih dengan radius 12–14 px, border tipis #E2E8F0, shadow sangat halus.
- Content spacing 24 px desktop; 16 px tablet; 12–16 px mobile.
- Tabel menggunakan sticky header untuk data panjang; nominal rata kanan; status menggunakan pill badge.
- Primary button solid blue; secondary button white + blue border; destructive action red hanya pada action destruktif.

## 5.4 Shared components

| Component | Specification |
| --- | --- |
| KPI card | Label kecil, nominal besar, delta/info kecil, optional icon linear 18 px. |
| Status badge | Draft=gray, Submitted=blue, Processing=info, Returned=warning, Approved/Final=green, Rejected/Blocked=red. |
| Budget progress | Horizontal progress dengan marker 70% / 85% / 95% sesuai threshold config. |
| Rule alert | Severity icon + title + penjelasan rule + current value vs threshold + CTA. |
| Data table | Search, filters, sort, pagination, column visibility, export current view. |
| Drawer detail | Untuk quick inspect tanpa kehilangan posisi tabel. |
| Confirmation modal | Wajib untuk cancel, reject, release manual, import commit, dan perubahan rule. |
| Empty state | Ikon outline, 1 kalimat konteks, satu CTA yang relevan. |
| Skeleton/loading | Card dan table skeleton; hindari spinner besar untuk halaman data. |
| Toast | Success/info untuk aksi ringan; validation error tetap tampil dekat field. |

# 06 — INFORMATION ARCHITECTURE

## Navigasi aplikasi berdasarkan role

| Menu | PTK | Kajur | PTU/Kabag | WD/Dekan | Admin |
| --- | --- | --- | --- | --- | --- |
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Anggaran | View scope | View jurusan | View semua | View semua | Manage master/import |
| Pengajuan | Create + own | Review jurusan | Verification queue | Monitor/approval conditional | View/support |
| Early Warning | Own scope | Jurusan | Semua | Executive summary | Manage threshold |
| Laporan | Own/jurusan | Jurusan | Semua | Semua | Semua |
| Import & Revisi | — | — | Conditional | — | ✓ |
| Master Data | — | — | — | — | ✓ |
| User & Role | — | — | — | — | ✓ |
| Audit Log | Own action | Jurusan | Semua | Read summary | ✓ |

# 07 — CORE DATA MODEL

## Objek produk dan relasi konseptual

| Entity | Field kunci | Catatan |
| --- | --- | --- |
| FiscalYear | id, year, is_active | Satu tahun dapat memiliki beberapa revision/version. |
| BudgetVersion | id, fiscal_year_id, revision_no, status, effective_at | Tidak overwrite versi lama; versi aktif ditandai jelas. |
| Department | id, code, name, unit/subunit mapping | Kode unik; tidak memakai text bebas pada transaksi. |
| BudgetStructure | program, activity, KRO, RO, component, subcomponent, account | Master struktur mengikuti data sumber tahun berjalan. |
| BudgetAllocation | version, department, budget bucket, source_fund, amount | Pagu yang dapat dikontrol aplikasi. |
| Submission | number, department, PTK, method, amount, status, dates | Object transaksi/pengajuan internal. |
| SubmissionLine | submission, budget_bucket, amount | Memungkinkan satu pengajuan terdiri dari satu/lebih bucket jika proses nyata mengizinkan. |
| BudgetReservation | submission_line, amount, state, released_at | Mengurangi available budget sebelum final realization. |
| Realization | submission_line, realized_amount, finalized_at | Terbentuk/di-update ketika status final internal tercapai. |
| Warning | rule_id, object_id, severity, state, triggered_at | Warning harus explainable dan dapat ditutup/resolved. |
| RuleConfig | rule_code, threshold, enabled, effective_version | Configurable; perubahan dicatat. |
| AuditLog | actor, action, entity, before, after, timestamp | Immutable dari UI normal. |

## 7.1 Import field baseline

Template data anggaran yang sudah diterima memiliki field: No, Tahun, Revisi ke, Nama Unit, Nama Subunit, Kode Unit, Kode Subunit, Program, Kegiatan, output, suboutput, komponen, Subkomponen, Kode Sasaran, Kode Indikator, Kode Akun, Header, Uraian, Volume, Satuan, Harga Satuan, Jumlah. PRD menambahkan mapping internal kode_jurusan dan budget_control_key agar baris dapat dikonsolidasikan ke bucket kontrol.

> **Design decision** — Kode program/kegiatan tidak boleh di-hardcode. Master harus diimport/dikelola per tahun dan revisi karena nomenklatur dapat berubah sementara pola struktur tetap serupa.

# 08 — RULE-BASED BUDGET CONTROL

## Algoritma inti, invariants, dan lifecycle budget

Semua rule bersifat explainable. Sistem selalu dapat menunjukkan nilai input, threshold, dan dampak rule.

## 8.1 Perhitungan saldo tersedia

> **Core formula** — Available Budget = Current Approved Budget − Final Realization − Active Reservation

Current Approved Budget berasal dari BudgetVersion aktif. Active Reservation berasal dari pengajuan yang sudah mencapai titik “commit/reserve” internal tetapi belum final. Final Realization adalah nominal yang sudah dinyatakan final sesuai workflow internal yang disetujui stakeholder.

## 8.2 Rule catalog — MVP

| Rule | Trigger | Action sistem | Severity |
| --- | --- | --- | --- |
| RBC-001 Overbudget | requested_amount > available_budget | Block submit/approval; tampilkan kekurangan nominal dan saldo saat ini | Critical |
| RBC-002 Reserve on commit | status masuk state reservasi | Buat reservation atomik; available budget langsung berkurang | Info |
| RBC-003 Release on cancel/return | status kembali ke state yang tidak mengunci dana atau cancelled | Release reservation sesuai business rule; saldo tersedia naik kembali | Info |
| RBC-004 Finalize realization | status menjadi final internal | Pindahkan nilai dari reservation ke realization tanpa double-count | Success |
| RBC-005 Revision guard | budget version berubah saat ada active reservation | Recalculate; flag jika reservation tidak lagi covered | High |
| RBC-006 Duplicate protection | submission fingerprint/number sudah ada | Block duplicate atau minta merge/review | High |
| RBC-007 Jurisdiction guard | user mengakses department di luar scope | Deny action dan log security event | Critical |
| RBC-008 Data integrity | amount <= 0 / invalid account / unmapped department | Block save/submit sesuai jenis error | High |

## 8.3 Atomicity requirement

Budget check dan pembuatan reservation harus dilakukan dalam satu transaksi database. Sistem tidak boleh hanya memeriksa saldo di frontend karena dua PTK dapat submit pada waktu hampir bersamaan. Server harus mengunci/menjamin konsistensi saldo sebelum reservation dibuat.

## 8.4 Override

MVP sebaiknya tidak menyediakan override overbudget untuk user biasa. Jika stakeholder meminta override, fitur harus terbatas pada role tertentu, mewajibkan alasan, bukti, dan menghasilkan audit log serta warning khusus. Default PRD: tidak ada override sampai divalidasi.

# 09 — EARLY WARNING SYSTEM

## Peringatan berbasis rule untuk membantu monitoring

| Rule | Contoh kondisi | Output | Konfigurasi |
| --- | --- | --- | --- |
| EWS-001 Critical balance | available / current_budget ≤ X% | Warning saldo kritis per jurusan/bucket | X ditetapkan stakeholder |
| EWS-002 High utilization | realization + reservation ≥ Y% sebelum periode tertentu | Warning penggunaan cepat | Y + periode configurable |
| EWS-003 Low absorption | realisasi kumulatif < expected threshold pada bulan M | Warning serapan rendah | M dan threshold configurable |
| EWS-004 Stale submission | pengajuan tidak berubah status > N hari | Warning proses tertahan | N configurable per status |
| EWS-005 Returned repeatedly | pengajuan dikembalikan ≥ K kali | Warning kualitas/kelengkapan data | K configurable |
| EWS-006 Revision impact | revisi menurunkan pagu di bawah committed amount | Critical exception | Otomatis |
| EWS-007 Unmapped data | import berisi department/account tidak dikenali | Data quality warning | Otomatis |

## 9.1 Warning lifecycle

State warning: OPEN → ACKNOWLEDGED → RESOLVED. Warning otomatis dapat resolved ketika kondisi sudah tidak terpenuhi; warning yang memerlukan tindakan manual menyimpan actor, catatan penyelesaian, dan timestamp.

## 9.2 Severity

| Severity | Warna UI | Makna |
| --- | --- | --- |
| Info | Blue | Informasi proses; tidak membutuhkan tindakan segera. |
| Warning | Amber | Perlu perhatian/monitoring. |
| High | Orange/amber dark | Risiko operasional; harus ditindaklanjuti. |
| Critical | Red | Aksi diblokir atau risiko saldo/integritas tinggi. |

# 10 — SUBMISSION WORKFLOW

## State machine konseptual yang harus divalidasi PTK

| State | Makna | Budget effect | Aksi tersedia |
| --- | --- | --- | --- |
| DRAFT | Pengajuan sedang disusun | Tidak reserve | Edit, delete draft, preview |
| SUBMITTED | Dikirim untuk proses internal | Reserve jika titik commit disepakati di sini | Withdraw bila policy mengizinkan |
| UNDER_REVIEW | Sedang diverifikasi/approval | Reservation aktif | Approve, return, reject |
| RETURNED | Dikembalikan untuk perbaikan | Default: reservation dilepas; configurable setelah validasi | Edit, resubmit, cancel |
| APPROVED_INTERNAL | Disetujui internal / siap proses lanjut | Reservation aktif | Forward/mark processing |
| PROCESSING | Sedang proses pencairan di luar sistem | Reservation aktif | Update status/reference |
| FINAL | Realisasi final | Reservation dikonversi ke realization | Read-only; correction by controlled flow |
| CANCELLED | Dibatalkan | Reservation dilepas | Read-only + reason |
| REJECTED | Ditolak | Reservation dilepas | Read-only + reason |

> **Needs PTK validation** — Titik mulai reservation, definisi “final”, dan perlakuan status RETURNED belum boleh dianggap sebagai SOP resmi sampai dikonfirmasi PTK.

# 11 — DETAILED PAGE SPECIFICATIONS

## Rincian halaman, konten, interaksi, validasi, dan state

Bagian ini adalah blueprint UI untuk desain dan implementasi. Nama menu dapat disederhanakan kemudian tanpa mengubah kebutuhan data.

## P01 — Login & Access

Autentikasi aman dan masuk ke scope role yang benar.

| Area | Detail |
| --- | --- |
| Primary users | Semua pengguna |
| Page content | • Brand panel sederhana: nama sistem, tagline “Monitoring anggaran yang terukur dan terkendali”.<br>• Form: NIP/username/email, password, show/hide password, remember me opsional.<br>• CTA utama “Masuk”; link “Lupa password” jika mekanisme reset tersedia.<br>• Informasi singkat bahwa akses mengikuti kewenangan dan aktivitas dicatat.<br>• Error state generik untuk kredensial salah; jangan membocorkan apakah user terdaftar. |
| Primary actions | Masuk • Lupa password |
| Key rules / states | • Rate limit login<br>• Session timeout configurable<br>• Redirect ke dashboard sesuai role |

## P02 — Dashboard Fakultas — Executive

Memberi snapshot kondisi anggaran seluruh fakultas dan exception yang perlu tindakan.

| Area | Detail |
| --- | --- |
| Primary users | Kabag, WD Keuangan, Dekan |
| Page content | • Filter global: Tahun Anggaran, Revisi aktif, Sumber Dana (default RM), periode/bulan.<br>• KPI cards: Pagu Aktif, Reserved, Realisasi Final, Available Budget, Serapan %, Open Warnings.<br>• Chart tren bulanan: realisasi final vs reservation vs pagu berjalan.<br>• Tabel “Kondisi per Jurusan”: pagu, reserved, realisasi, available, utilization %, warning severity.<br>• Panel “Top Attention”: saldo kritis, low absorption, stale submission, revision impact.<br>• Panel “Recent Activity”: pengajuan baru, return, finalization, import/revision.<br>• Klik jurusan membuka drill-down dashboard jurusan; klik warning membuka detail reason. |
| Primary actions | Drill-down jurusan • Export snapshot • Buka warning • Buka laporan |
| Key rules / states | • Jangan tampilkan ranking “baik/buruk”; urutkan default berdasarkan severity atau nama<br>• Nilai harus mengikuti BudgetVersion aktif |

## P03 — Dashboard Jurusan

Memberi Kajur/penanggung jawab gambaran pagu dan realisasi jurusannya sendiri.

| Area | Detail |
| --- | --- |
| Primary users | Kajur, PTK tertentu |
| Page content | • Header: nama jurusan, tahun/revisi, sumber dana.<br>• KPI: pagu jurusan, committed/reserved, final realization, available budget, utilization.<br>• Budget bucket list: account/subcomponent yang dikontrol, nominal dan progress.<br>• Pengajuan aktif: nomor, PTK, nominal, status, umur proses.<br>• Early warning jurusan dengan severity dan recommended action.<br>• Tren bulanan jurusan; optional comparison terhadap plan jika data rencana bulanan tersedia. |
| Primary actions | Buka budget detail • Buka pengajuan • Review approval (Kajur) • Export jurusan |
| Key rules / states | • User tidak dapat berpindah ke jurusan lain tanpa permission |

## P04 — PTK Workbench

Halaman kerja utama PTK untuk mengetahui saldo dan pengajuan yang harus ditindaklanjuti.

| Area | Detail |
| --- | --- |
| Primary users | PTK / Operator |
| Page content | • Banner saldo ringkas sesuai jurusan dan sumber dana aktif.<br>• Quick action “Buat Pengajuan”.<br>• Tab: Draft, Perlu Perbaikan, Diproses, Selesai.<br>• Table pengajuan: nomor, tanggal, kegiatan, budget bucket, nominal, status, current owner, last update.<br>• Card “Perlu Tindakan”: returned request, dokumen kurang, warning terkait submission.<br>• Mini activity timeline untuk 5 aktivitas terakhir PTK. |
| Primary actions | Buat pengajuan • Lanjutkan draft • Perbaiki returned • Buka detail |
| Key rules / states | • Nominal ditampilkan bersama available budget agar PTK tidak bekerja buta |

## P05 — Anggaran — Allocation List

Menelusuri seluruh pagu yang diimpor dan telah dipetakan ke jurusan/bucket kontrol.

| Area | Detail |
| --- | --- |
| Primary users | Keuangan, Admin; read-only role lain sesuai scope |
| Page content | • Filter: tahun, revision, sumber dana, jurusan, program, kegiatan, KRO, RO, subkomponen, akun, status mapping.<br>• Search kode/nama/uraian.<br>• Columns minimum: Jurusan, Program/Kegiatan ringkas, Subkomponen, Akun, Pagu, Reserved, Final, Available, Utilization, Warning.<br>• Row dapat di-expand untuk melihat detail struktur tanpa membuka halaman lain.<br>• Column visibility dan saved filter opsional. |
| Primary actions | Buka detail • Export current view • Lihat source row |
| Key rules / states | • Nominal available dihitung, bukan field manual<br>• Data versi nonaktif diberi label archived |

## P06 — Anggaran — Budget Detail

Menampilkan satu budget bucket secara lengkap beserta komponen pembentuk dan transaksi yang memakai pagu.

| Area | Detail |
| --- | --- |
| Primary users | PTK/Kajur sesuai scope, Keuangan, Admin |
| Page content | • Breadcrumb struktur anggaran sampai akun/bucket.<br>• Summary: pagu awal, penyesuaian revisi, current budget, reserved, final, available.<br>• Progress bar penggunaan.<br>• Tab “Source Lines”: baris impor yang membentuk bucket.<br>• Tab “Transactions”: pengajuan aktif dan final yang mengurangi bucket.<br>• Tab “Revision History”: perubahan nominal antar versi.<br>• Tab “Warnings”: warning terkait bucket.<br>• Panel rule explanation menampilkan formula current state. |
| Primary actions | Buka pengajuan terkait • Buka source version • Export detail |
| Key rules / states | • Tidak ada edit nominal langsung di halaman detail; perubahan via import/revision flow |

## P07 — Import Anggaran — Upload

Memasukkan data anggaran dari file dengan proses staging, validation, preview, lalu commit.

| Area | Detail |
| --- | --- |
| Primary users | Admin/Keuangan berotorisasi |
| Page content | • Step 1 Upload: pilih tahun, revision target, sumber dana, file .xlsx/.csv sesuai policy.<br>• Download template jika dibutuhkan.<br>• Checksum/file metadata dicatat untuk audit.<br>• Setelah upload, sistem tidak langsung menulis ke tabel aktif; masuk staging.<br>• Tampilkan jumlah row dan preview awal. |
| Primary actions | Upload • Download template • Lanjut validasi • Batalkan staging |
| Key rules / states | • Block format invalid<br>• Block tahun/revisi tidak konsisten<br>• Jangan commit sebagian tanpa summary |

## P08 — Import Anggaran — Validation & Mapping

Membersihkan masalah data sebelum menjadi pagu aktif.

| Area | Detail |
| --- | --- |
| Primary users | Admin/Keuangan berotorisasi |
| Page content | • Summary cards: total rows, valid, warning, error, unmapped department, unmapped account, duplicate candidate.<br>• Validation table dengan row number, field bermasalah, nilai file, message, suggested action.<br>• Mapping panel: map Kode Unit/Subunit/teks ke master Jurusan dan budget control key.<br>• Bulk mapping untuk pola yang sama; simpan mapping reusable bila disetujui.<br>• Preview aggregate per jurusan dan akun sebelum commit.<br>• Confirmation screen membandingkan revision sebelumnya vs target baru. |
| Primary actions | Map • Ignore warning • Fix value di staging jika policy mengizinkan • Commit revision • Download error report |
| Key rules / states | • Error wajib 0 sebelum commit<br>• Warning boleh commit hanya dengan acknowledgement<br>• Commit membuat immutable import batch |

## P09 — Budget Version & Revision

Mengelola histori anggaran tanpa overwrite dan melihat dampak revisi pada transaksi berjalan.

| Area | Detail |
| --- | --- |
| Primary users | Keuangan/Admin; read-only eksekutif |
| Page content | • Timeline/version list: revision no, effective date, source file, imported by, status Draft/Active/Archived.<br>• Diff summary: total pagu naik/turun, jumlah bucket berubah, jurusan terdampak.<br>• Exception list: active reservation yang tidak lagi covered setelah revisi.<br>• Action “Activate Revision” hanya jika validation pass dan exception ditangani sesuai policy.<br>• Rollback bukan delete; aktivasi ulang versi lama hanya melalui controlled action dan audit. |
| Primary actions | Compare versions • Activate revision • View import batch • Resolve exception |
| Key rules / states | • Satu revision aktif per fiscal year/source fund scope<br>• Activation harus transactional |

## P10 — Pengajuan Baru — Wizard

Memandu PTK membuat pengajuan dengan data minimal namun lengkap untuk budget control.

| Area | Detail |
| --- | --- |
| Primary users | PTK |
| Page content | • Step 1 Context: jurusan otomatis dari user; pilih tahun/sumber dana; pilih metode transaksi jika sudah divalidasi (LS/UP/TUP/other).<br>• Step 2 Budget: pilih budget bucket dari daftar yang hanya menampilkan scope jurusan dan saldo tersedia.<br>• Step 3 Detail: uraian, kegiatan, nominal, tanggal kebutuhan, pihak/penerima bila relevan, reference internal.<br>• Step 4 Document: upload/generate dokumen yang benar-benar dibutuhkan setelah PTK memvalidasi jenisnya.<br>• Step 5 Review: tampilkan current available, requested amount, projected available after submit, rule checks.<br>• CTA “Simpan Draft” dan “Submit”. |
| Primary actions | Simpan draft • Submit • Tambah line budget jika proses mengizinkan • Preview |
| Key rules / states | • Realtime soft check di frontend + authoritative check di server<br>• Block jika request > available<br>• Block unmapped/invalid budget |

## P11 — Pengajuan — Detail & Timeline

Menjadi single source of truth satu pengajuan dari draft hingga final/cancelled.

| Area | Detail |
| --- | --- |
| Primary users | Semua role sesuai scope |
| Page content | • Header: nomor pengajuan, status badge, jurusan, PTK, total nominal.<br>• Budget impact card: source bucket, available before, reserved, final realized, current effect.<br>• Section detail pengajuan: metode, kegiatan, uraian, tanggal, penerima/reference.<br>• Document checklist dan attachment list.<br>• Timeline: created, submitted, reviewed, returned, resubmitted, approved, processing, final/cancelled.<br>• Comments/notes antar reviewer dengan timestamp.<br>• Rule execution log ringkas: rule, result, value, threshold.<br>• Action bar berubah sesuai role/status. |
| Primary actions | Edit draft/returned • Submit/resubmit • Approve • Return • Reject • Cancel • Mark next status sesuai permission |
| Key rules / states | • Semua status transition divalidasi server<br>• Return/reject/cancel wajib alasan<br>• Action destruktif confirmation |

## P12 — Verification / Approval Queue

Memungkinkan reviewer memproses banyak pengajuan dengan prioritas yang jelas.

| Area | Detail |
| --- | --- |
| Primary users | Kajur, PTU, Keuangan, approver lain |
| Page content | • Tabs by stage: Perlu Review Saya, Menunggu Unit Lain, Returned, High Risk.<br>• Filter jurusan, nominal, metode, age, warning severity, account.<br>• Columns: submission, jurusan, PTK, nominal, available after reservation, age, documents, warning.<br>• Quick preview drawer memperlihatkan budget impact dan checklist tanpa pindah halaman.<br>• Bulk action hanya untuk action aman; approval finansial sebaiknya satu per satu pada MVP. |
| Primary actions | Open review • Approve • Return • Reject • Assign/forward jika workflow mendukung |
| Key rules / states | • Reviewer tidak boleh approve record yang sudah berubah version/amount tanpa refresh/recheck |

## P13 — Early Warning Center

Satu tempat untuk semua kondisi yang membutuhkan perhatian.

| Area | Detail |
| --- | --- |
| Primary users | Kajur sesuai scope, Keuangan, Eksekutif, Admin |
| Page content | • Summary by severity: Critical, High, Warning, Info.<br>• Filter rule, jurusan, budget bucket, submission, status warning, date.<br>• Warning row: code, title, object, current value, threshold, first triggered, age, owner.<br>• Detail drawer: explanation, formula, history condition, linked objects, suggested action.<br>• Acknowledge dan resolve dengan catatan.<br>• Rule performance analytics opsional: warning count per rule, resolved time. |
| Primary actions | Acknowledge • Resolve • Open linked object • Export warnings |
| Key rules / states | • Auto-resolve bila condition hilang untuk rule yang deterministik<br>• Manual resolve tidak mengubah data keuangan |

## P14 — Reports & Export

Menyediakan laporan operasional yang bisa dipakai rapat/rekonsiliasi tanpa menyusun ulang spreadsheet.

| Area | Detail |
| --- | --- |
| Primary users | Semua role sesuai scope |
| Page content | • Report templates: Realisasi per Jurusan, Pagu vs Reserved vs Final, Serapan per Akun, Pengajuan per Status, Aging Pengajuan, Early Warning Summary, Revision Comparison.<br>• Filter report tersimpan sementara.<br>• Preview sebelum export.<br>• Export XLSX/PDF/CSV sesuai kebutuhan; metadata laporan memuat tahun, revision, source fund, generated at, generated by.<br>• Tidak menyembunyikan data filter aktif dari header laporan. |
| Primary actions | Generate • Export • Save filter preset opsional |
| Key rules / states | • Nominal dan total harus reconcile dengan dashboard pada filter yang sama |

## P15 — Notification Center

Mengurangi ketergantungan pada pengecekan manual status dan warning.

| Area | Detail |
| --- | --- |
| Primary users | Semua pengguna |
| Page content | • Bell topbar dengan unread count.<br>• Notification categories: Status, Approval Task, Warning, Revision/System.<br>• Item menampilkan title, context, timestamp, severity dan deep link.<br>• Mark as read / mark all read.<br>• Preference channel (in-app wajib; email opsional future). |
| Primary actions | Open • Mark read • Filter category |
| Key rules / states | • Notification tidak menggantikan audit log |

## P16 — Master Data

Mengelola referensi yang dibutuhkan agar transaksi tidak memakai text bebas.

| Area | Detail |
| --- | --- |
| Primary users | Admin |
| Page content | • Submenu: Jurusan, Unit/Subunit mapping, Sumber Dana, Struktur Anggaran, Account, Transaction Method, Status/Workflow config (jika configurable).<br>• Setiap master memiliki code, name, active state, effective year/version bila relevan.<br>• Import/export master tertentu.<br>• Delete fisik dihindari; gunakan deactivate jika sudah dipakai transaksi. |
| Primary actions | Create • Edit • Deactivate • Import master |
| Key rules / states | • Unique code enforcement<br>• Referential integrity |

## P17 — User & Role Management

Mengatur siapa dapat melihat dan bertindak pada scope tertentu.

| Area | Detail |
| --- | --- |
| Primary users | Admin |
| Page content | • User list: name, identifier, role, department scope, active, last login.<br>• User detail: role assignment, department/unit scope, permission overrides bila benar-benar diperlukan.<br>• Role matrix read-only overview untuk audit.<br>• Reset access/session revoke.<br>• Tidak menampilkan password atau secret. |
| Primary actions | Create/activate/deactivate user • Assign role • Change scope • Revoke sessions |
| Key rules / states | • Least privilege<br>• Critical permission change logged<br>• Self-privilege escalation blocked |

## P18 — Audit Log

Menyediakan jejak aktivitas yang dapat ditelusuri untuk perubahan finansial dan konfigurasi.

| Area | Detail |
| --- | --- |
| Primary users | Admin, Kabag; read-only eksekutif bila perlu |
| Page content | • Filter: actor, action, entity, jurusan, date, severity, correlation/request ID.<br>• Columns: timestamp, actor, action, entity, object ID, summary, IP/device metadata jika kebijakan mengizinkan.<br>• Detail view menampilkan before/after untuk perubahan field sensitif.<br>• Export audit untuk kebutuhan pemeriksaan internal. |
| Primary actions | Open detail • Export |
| Key rules / states | • Audit record immutable dari UI<br>• Log import, status transition, budget revision, rule config, permission change |

## P19 — Rule & Threshold Settings

Memungkinkan early warning disesuaikan stakeholder tanpa mengubah kode aplikasi.

| Area | Detail |
| --- | --- |
| Primary users | Admin + approval konfigurasi jika dibutuhkan |
| Page content | • Rule list: code, name, enabled, severity, scope, current threshold, effective date.<br>• Rule detail: description, formula/pseudocode read-only, configurable params, notification target.<br>• Simulation panel: masukkan sample/current data untuk melihat apakah rule trigger.<br>• Version history rule config.<br>• Critical budget-control rule tidak boleh dinonaktifkan sembarang role. |
| Primary actions | Edit threshold • Enable/disable warning rule • Simulate • Publish config |
| Key rules / states | • Perubahan rule menghasilkan version + audit<br>• Budget control invariants locked by system |

# 12 — CRITICAL FORMS & FIELD-LEVEL REQUIREMENTS

## Field yang paling penting untuk menghindari ambiguity saat implementasi

## 12.1 Submission form — baseline fields

| Field | Type | Required | Source/behavior |
| --- | --- | --- | --- |
| Submission No | System-generated | Yes | Unique, readable, immutable after created |
| Fiscal Year | Select | Yes | Default active year |
| Budget Revision | Read-only contextual | Yes | Snapshot version used at submit |
| Source Fund | Select/read-only | Yes | MVP default RM |
| Department | Read-only from user scope | Yes | Cannot manually switch without permission |
| PTK | Read-only current user / assignment | Yes | Owner/creator |
| Transaction Method | Select | TBD | LS/UP/TUP/other after PTK validation |
| Budget Bucket | Search select | Yes | Only active/mapped bucket with available value shown |
| Request Amount | Currency | Yes | >0; authoritative budget check server-side |
| Description | Textarea | Yes | Plain text; max length configured |
| Need Date | Date | TBD | Validate against fiscal period if required |
| Recipient/Third Party | Text/entity | Conditional | Only if required by method |
| Attachments | Files | Conditional | Type/size/checklist per method after validation |
| Notes | Textarea | No | Internal note |

## 12.2 Budget list — minimum columns

- Jurusan
- Kode + nama akun/budget bucket
- Program/kegiatan ringkas
- Pagu aktif
- Reserved
- Realisasi final
- Available
- Utilization %
- Revision
- Warning severity
- Last updated

## 12.3 Monetary presentation

- Currency IDR, pemisah ribuan konsisten; desimal disembunyikan jika tidak digunakan.
- Nominal negatif hanya boleh muncul pada delta/perubahan, bukan available budget normal.
- Semua KPI menampilkan tooltip definisi Reserved, Final, Available.
- Export mempertahankan numeric type, bukan hanya formatted text.

# 13 — DASHBOARD DATA SEMANTICS

## Definisi KPI agar angka tidak berbeda antarhalaman

| KPI | Definition |
| --- | --- |
| Pagu Aktif | Current Approved Budget pada BudgetVersion aktif untuk filter scope. |
| Reserved / Committed | Total active reservation yang belum final/dilepas. |
| Realisasi Final | Total realization yang statusnya final internal sesuai workflow. |
| Available Budget | Pagu Aktif − Reserved − Realisasi Final. |
| Utilization % | (Reserved + Realisasi Final) / Pagu Aktif × 100. |
| Realization % | Realisasi Final / Pagu Aktif × 100. |
| Open Warning | Warning OPEN/ACKNOWLEDGED yang belum resolved. |
| Aging | Hari kalender/kerja sejak last meaningful transition; definisi harus dipilih stakeholder. |

# 14 — EMPTY, LOADING, ERROR, AND EXCEPTION STATES

## State UX harus diperlakukan sebagai requirement, bukan detail kosmetik

| Scenario | Expected UX |
| --- | --- |
| No budget imported | Dashboard menampilkan empty state + CTA ke import untuk role berwenang; PTK mendapat pesan “anggaran belum tersedia”. |
| No mapping jurusan | Baris masuk data quality error; tidak boleh digunakan untuk pengajuan. |
| No available budget | Budget bucket tetap terlihat namun disabled/label saldo habis; PTK tidak dapat submit nominal >0. |
| Concurrent submit | Salah satu request yang kehilangan saldo mendapatkan server response overbudget + nilai saldo terbaru. |
| Revision activated mid-session | Pada submit, server memaksa re-check revision; user diberi prompt refresh/review. |
| Returned request | Status warning jelas; alasan return sticky di bagian atas detail; field yang perlu diperbaiki ditandai. |
| Network/server error | Form draft tersimpan bila memungkinkan; retry aman dan tidak membuat duplikasi. |
| Unauthorized access | 403 page minimal; log security event; tidak bocorkan data object. |
| Import partial failure | Tidak ada commit parsial yang tidak terlihat; tampilkan batch status dan error report. |

# 15 — SECURITY, AUDIT & NON-FUNCTIONAL REQUIREMENTS

## Karena aplikasi menangani data finansial internal, kontrol akses dan konsistensi lebih penting daripada fitur dekoratif.

## 15.1 Security

- Role-based + scope-based authorization pada server untuk setiap query dan mutation.
- Password disimpan dengan hashing kuat; session cookie secure/httpOnly; CSRF protection bila web session.
- Input validation dan file upload allowlist; attachment tidak dieksekusi sebagai file aktif.
- Sensitive actions: re-auth/confirmation dapat diterapkan untuk aktivasi revisi, perubahan role, dan publish rule.
- Audit log untuk login penting, import, revision activation, status transition, cancel/reject, rule changes, permission changes.
- Backup database dan attachment terjadwal sesuai kebijakan deploy.

## 15.2 Performance

| Requirement | Target awal |
| --- | --- |
| Dashboard load | ≤ 3 detik untuk dataset fakultas normal pada jaringan kampus yang layak |
| Table interaction | Filter/pagination ≤ 2 detik pada data terindeks |
| Budget submit check | ≤ 1 detik untuk validasi budget normal |
| Import | Asynchronous/progress untuk file besar; UI tidak freeze |
| Export | Background job bila > threshold row |
| Availability | Target deployment mengikuti kebutuhan kampus; MVP minimal stabil selama jam kerja |

## 15.3 Data integrity

- Financial mutation wajib transactional.
- Foreign key untuk jurusan/master kritis.
- No hard delete untuk financial transaction.
- BudgetVersion immutable setelah active kecuali melalui revision baru.
- Idempotency key disarankan untuk submit/finalization endpoint.
- Server clock/timezone konsisten dan timestamp disimpan dengan timezone yang jelas.

## 15.4 Accessibility & responsive

- Kontras teks dan status minimal memenuhi WCAG AA secara praktis.
- Status tidak dibedakan hanya dengan warna; selalu ada label/icon.
- Keyboard focus jelas pada form dan modal.
- Desktop adalah target utama; tablet harus usable; mobile fokus view/approval ringan, bukan import massal.

# 16 — SYSTEM BOUNDARIES & INTEGRATION STRATEGY

## Batas sistem agar skripsi tetap dapat diselesaikan

| External system | MVP relation | Future possibility |
| --- | --- | --- |
| SAKTI | Tidak terintegrasi; sistem internal tidak menggantikan pencairan resmi. | Data exchange/import bila akses dan kebijakan memungkinkan. |
| ELFINA | Di luar fokus RM MVP. | PNBP/BLU dapat ditambahkan sebagai sumber data/monitoring terpisah. |
| Aplikasi Pak Arwan | Dipelajari sebagai existing tool; bukan dependency MVP. | Import/bridge jika format dan ownership memungkinkan. |
| SIMAPAN/Spreadsheet anggaran | File import sebagai baseline input anggaran. | Direct export/import automation jika source stabil. |
| SSO kampus | Tidak diasumsikan tersedia. | Integrasi bila endpoint dan policy ada. |

> **Scope protection** — MVP harus tetap berfungsi penuh dengan file import + database internal. Integrasi eksternal tidak boleh menjadi critical path penyelesaian skripsi.

# 17 — ACCEPTANCE CRITERIA MVP

## Kondisi minimum agar prototype dapat dianggap memenuhi requirement utama

| ID | Acceptance criterion |
| --- | --- |
| AC-01 | Admin dapat mengimpor budget version dan melihat validation result sebelum activation. |
| AC-02 | Setiap alokasi yang dipakai sistem memiliki department mapping dan budget control key. |
| AC-03 | PTK hanya dapat memilih anggaran dalam scope jurusannya. |
| AC-04 | Submit dengan nominal > available budget ditolak oleh server. |
| AC-05 | Submit valid menghasilkan reservation sesuai titik workflow yang disepakati. |
| AC-06 | Dua submit bersamaan tidak dapat membuat available budget negatif. |
| AC-07 | Cancel/return/reject melepaskan reservation sesuai konfigurasi workflow. |
| AC-08 | Finalization memindahkan nilai dari reserved ke final tanpa double-count. |
| AC-09 | Dashboard menampilkan Pagu, Reserved, Final, Available yang reconcile dengan transaksi. |
| AC-10 | Early warning dapat trigger, di-acknowledge, dan di-resolve dengan explanation. |
| AC-11 | Revision history tidak overwrite data lama dan activation menghasilkan audit log. |
| AC-12 | Setiap status transition kritis memiliki actor, timestamp, dan reason jika dibutuhkan. |
| AC-13 | Laporan per jurusan dapat diexport dan total sesuai dashboard filter yang sama. |
| AC-14 | Unauthorized user tidak dapat membuka data jurusan lain hanya dengan mengubah URL/request. |

# 18 — CORE TEST SCENARIOS

## Skenario yang wajib diuji karena langsung berhubungan dengan budget control

| Scenario | Setup | Expected result |
| --- | --- | --- |
| Normal submit | Pagu 10 jt, final 2 jt, reserved 1 jt; request 3 jt | Submit berhasil; reserved menjadi 4 jt; available menjadi 4 jt. |
| Overbudget | Pagu 10 jt, final 6 jt, reserved 3 jt; request 2 jt | Blocked; tersedia 1 jt; tidak ada reservation baru. |
| Concurrent requests | Available 5 jt; dua request masing-masing 4 jt submit bersamaan | Hanya satu berhasil; satu ditolak setelah re-check server. |
| Cancel | Request 3 jt sudah reserved lalu cancelled | Reservation 3 jt released; available bertambah 3 jt. |
| Finalize | Reserved 3 jt masuk final | Reserved turun 3 jt, final naik 3 jt, available tidak berubah karena tidak double-count. |
| Revision decrease | Budget turun hingga committed > new budget | Revision exception critical; aktivasi diblokir/ditandai sesuai policy. |
| Unauthorized scope | PTK A membuka URL budget jurusan B | 403/deny; event dicatat. |
| Stale warning | Pengajuan berada di review > N hari | EWS-004 muncul dengan owner dan age. |
| Low absorption | Bulan threshold tercapai, final realization di bawah target | EWS-003 muncul sesuai config. |
| Import duplicate | File sama/row fingerprint sama di revision target | Duplicate candidate ditandai; commit memerlukan resolution. |

# 19 — MVP DELIVERY PLAN

## Urutan implementasi yang meminimalkan rework

| Phase | Deliverable | Dependency |
| --- | --- | --- |
| 0. Domain validation | Validasi PTK: workflow, dokumen, metode RM, titik reserve/final, level bucket | Meeting PTK |
| 1. Foundation | Auth, user, role, department, fiscal year, source fund | None |
| 2. Budget backbone | Import staging, mapping, BudgetVersion, allocation, budget detail | Template anggaran |
| 3. Transaction core | Submission, status timeline, reservation, realization, audit | Workflow baseline |
| 4. Budget control | RBC-001 s.d. RBC-008, concurrency protection | Transaction core |
| 5. Monitoring | Role dashboards, allocation table, drill-down | Budget calculations |
| 6. Early warning | EWS rules + center + notification | Threshold validation |
| 7. Reporting | Export dan reconciliation reports | Stable calculations |
| 8. UAT hardening | Security, edge cases, performance, polish | Stakeholder feedback |

# 20 — PTK VALIDATION CHECKLIST

## Pertanyaan yang harus dijawab sebelum mengunci workflow produksi

| Topic | Question |
| --- | --- |
| Payment flow | Untuk LS, UP, TUP: apa input PTK, dokumen apa, siapa review/approve, dan sistem eksternal mana yang dipakai? |
| Reservation point | Pada event mana nominal harus dianggap mengurangi saldo tersedia? Saat draft, submit, approval, atau tahap lain? |
| Return behavior | Saat berkas dikembalikan untuk perbaikan, apakah anggaran tetap di-reserve atau langsung dilepas? |
| Finalization | Event apa yang secara operasional menandakan realisasi final? Siapa yang mengubah status itu? |
| Control grain | Bucket kontrol harus pada akun 6 digit, akun internal 8 digit, subkomponen+akun, atau kombinasi lain? |
| Multi-line request | Satu pengajuan dapat memakai lebih dari satu akun/budget bucket atau selalu satu? |
| Documents | Dokumen minimum per metode; field mana yang dapat digenerate sistem? |
| Approval chain | Apakah Kajur selalu approve? Kapan PTU/Kabag/WD masuk? Adakah nominal threshold? |
| Cancellation | Siapa boleh cancel dan sampai state apa? |
| Revision | Apa yang dilakukan terhadap pengajuan aktif jika pagu/struktur berubah? |
| Existing tool | Data apa yang sudah dicatat aplikasi Pak Arwan dan apakah dapat diexport? |
| Historical data | Apakah ada data realisasi bulanan per jurusan untuk validasi warning/analitik? |
| Warning threshold | Batas saldo kritis, low absorption, dan stale request yang dianggap relevan? |
| Reports | Laporan apa yang paling sering diminta saat rapat monitoring? |

# 21 — POST-MVP OPPORTUNITIES

## Fitur lanjutan yang tidak menjadi dependency MVP

- Import/bridge data PNBP/BLU dari ELFINA atau sumber lain jika akses tersedia.
- Import data aplikasi Pak Arwan untuk menghindari entry ulang.
- Forecasting penyerapan akhir tahun bila tersedia histori bulanan yang cukup dan definisi target jelas.
- Anomaly detection sebagai secondary control, bukan pengganti deterministic budget rules.
- Document generation untuk TOR/KAK/form tertentu setelah format PTK tervalidasi.
- Notifikasi email/WhatsApp/SSO bila kebijakan institusi dan akses integrasi memungkinkan.
- One-data reporting lintas sumber dana dengan semantic layer yang sama.

# APPENDIX A

## Source basis untuk requirement

Daftar ini digunakan sebagai basis domain PRD; bukan daftar pustaka akademik final.

| Source | Bagian yang menjadi dasar PRD |
| --- | --- |
| hasildiskusikabagkeuanganfakultas.docx | Gap monitoring per jurusan; fokus RM; risiko minus/overload; dokumen awal sebagai acuan realisasi; status process/return/final; kode jurusan; import; role; revisi; aplikasi Pak Arwan. |
| Panduan Penyusunan RPKA Unit BLU Unsoed TA 2024 | Struktur Program → Kegiatan → KRO → RO → Komponen → Subkomponen; siklus penyusunan; master unit/subunit/akun. |
| RBA Definitif TA 2026 / Ringkasan RBA 2026 | Struktur pejabat pengelola BLU; peran PTK/PTU; sumber dana dan konteks anggaran 2026. |
| DIPA 2026 | Sumber dana dan posisi DIPA sebagai dasar pelaksanaan/pencairan. |
| RKAKL 2026 | Struktur kertas kerja dan nomenklatur 2026 yang menunjukkan kode dapat berubah dari panduan 2024. |
| Tabel detail program anggaran / export SIMAPAN | Baseline field untuk import dan master budget structure. |

# APPENDIX B

## Recommended sidebar structure

| Group | Menu items |
| --- | --- |
| Workspace | Dashboard • Pengajuan • Early Warning |
| Budget | Anggaran • Revisi/Versi • Import |
| Insights | Laporan • Audit Log |
| Administration | Master Data • User & Role • Rule Settings |

# APPENDIX C

## Definition of Done — design handoff

- Semua halaman P01–P19 memiliki desktop wireframe/high-fidelity design yang konsisten dengan token UI.
- Semua status badge, warning severity, empty/error/loading state telah didesain.
- Design menyertakan responsive behavior untuk dashboard, table, form wizard, drawer, dan modal.
- Semua nominal dan label budget semantics konsisten dengan PRD.
- Designer tidak menambahkan AI/ranking/rekomendasi alokasi di luar scope tanpa perubahan PRD.
- Prototype interaction minimal mencakup: login → dashboard → create submission → overbudget block → valid submit → review/return → resubmit → final → dashboard updated.
