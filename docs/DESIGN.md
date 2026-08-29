# DESIGN SYSTEM
## SIPEDA - Sistem Monitoring dan Pengendalian Anggaran
### Fakultas Teknik Universitas Jenderal Soedirman

Version: 0.1
Status: Draft

---

# 1. Design Direction

Sistem menggunakan visual direction:

> Modern Professional Institutional Financial Dashboard

Karakteristik:

- profesional;
- bersih;
- informatif;
- mudah dipindai;
- tidak dekoratif berlebihan;
- berorientasi pada data;
- cocok digunakan dalam lingkungan institusi pendidikan tinggi.

Sistem tidak menggunakan visual "AI futuristic" atau elemen dekoratif yang tidak memberikan nilai informasi.

---

# 2. Design Principles

## 2.1 Information First

Informasi keuangan harus lebih dominan daripada dekorasi.

## 2.2 Clear Financial Context

Setiap angka keuangan harus memiliki konteks yang jelas.

Contoh:

BAD:

Rp500.000.000

GOOD:

Pagu Aktif  
Rp500.000.000

## 2.3 Explainable Warning

Setiap warning harus menjelaskan:

- apa yang terjadi;
- nilai aktual;
- threshold;
- alasan;
- tindakan yang disarankan.

## 2.4 Progressive Disclosure

Informasi ringkas ditampilkan terlebih dahulu.

Detail dapat dibuka melalui:

- detail page;
- drawer;
- modal;
- expandable row.

---

# 3. Typography

Font utama:

Poppins

Hierarchy:

H1:
32px / 700

H2:
24px / 700

H3:
20px / 600

Body:
14px / 400

Small:
12px / 400

Financial Number:
24-32px / 600-700

---

# 4. Color System

## Primary

Blue

Digunakan untuk:

- primary button;
- active navigation;
- links;
- selected state;
- primary chart;
- focus state.

## Neutral

White:
#FFFFFF

Background:
#F8FAFC

Text:
#0F172A

Secondary text:
#64748B

Border:
#E2E8F0

## Semantic

Success:
Green

Warning:
Amber

Critical:
Red

Information:
Blue

Semantic colors tidak boleh menjadi satu-satunya indikator status.
Gunakan pula icon, label, atau text.

---

# 5. Layout

Desktop:

Sidebar
+
Topbar
+
Main Content

Mobile:

Topbar
+
Drawer Navigation
+
Main Content

---

# 6. Navigation

## Workspace

- Dashboard
- Pengajuan
- Early Warning

## Anggaran

- Anggaran
- Revisi & Versi
- Import Anggaran

## Laporan

- Reports

## Administration

- Master Data
- User & Role
- Rule Settings
- Audit Log

Menu ditampilkan berdasarkan permission.

---

# 7. Dashboard

Dashboard harus menjawab tiga pertanyaan utama:

1. Bagaimana kondisi anggaran sekarang?
2. Apa yang membutuhkan perhatian?
3. Apa yang harus dilakukan pengguna?

---

## KPI Cards

Pagu Aktif
Reserved
Realisasi
Saldo Tersedia
Serapan
Warning Aktif

---

## Budget Overview

Menampilkan:

- Pagu;
- Reserved;
- Realisasi;
- Available Balance.

---

## Department Comparison

Table:

| Jurusan | Pagu | Reserved | Realisasi | Saldo | Serapan | Warning |
|---|---:|---:|---:|---:|---:|---:|

---

# 8. Early Warning

Warning menggunakan severity:

CRITICAL
HIGH
MEDIUM
LOW
INFO

Setiap warning menampilkan:

- severity;
- rule;
- object;
- current value;
- threshold;
- explanation;
- recommended action.

---

# 9. Table

Table harus memiliki:

- column header;
- sorting jika diperlukan;
- filtering;
- pagination;
- empty state;
- loading state;
- error state.

Untuk data finansial:

- nominal rata kanan;
- gunakan format Rupiah;
- angka harus konsisten.

---

# 10. Forms

Form menggunakan:

- label;
- helper text;
- required indicator;
- validation message;
- disabled state;
- loading state.

Validation dilakukan server-side dan client-side bila diperlukan.

---

# 11. Financial Display

Format:

Rp 125.000.000

Persentase:

75,40%

Negative:

-Rp 5.000.000

Tidak menggunakan format yang ambigu.

---

# 12. Warning Card

Structure:

[ICON] [SEVERITY]

Judul Warning

Deskripsi singkat.

Current:
8%

Threshold:
10%

Recommended Action:
Periksa kondisi anggaran.

[View Detail]

---

# 13. Empty State

Contoh:

Belum ada pengajuan

Belum terdapat pengajuan pada periode yang dipilih.

[+ Buat Pengajuan]

---

# 14. Loading State

Gunakan skeleton/loading indicator.

Hindari halaman kosong ketika data sedang dimuat.

---

# 15. Error State

Error harus menjelaskan:

- masalah;
- dampak;
- tindakan yang dapat dilakukan.

Contoh:

"Gagal memuat data anggaran."

[Try Again]

---

# 16. Responsive

Breakpoint harus mempertimbangkan:

- desktop;
- tablet;
- mobile.

Data table pada mobile dapat menggunakan:

- horizontal scroll;
- responsive card;
- detail drawer.

---

# 17. Accessibility

- contrast cukup;
- keyboard navigation;
- focus state;
- semantic HTML;
- label form;
- tidak mengandalkan warna saja;
- icon memiliki konteks yang jelas.

---

# 18. Design Rule

Jangan menambahkan komponen UI baru hanya untuk dekorasi.

Setiap komponen harus memiliki fungsi terhadap:

- informasi;
- navigasi;
- input;
- monitoring;
- pengambilan tindakan.
