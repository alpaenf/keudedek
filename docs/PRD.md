# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# Sistem Monitoring dan Pengendalian Anggaran
## Fakultas Teknik Universitas Jenderal Soedirman

**Nama kerja sistem:** SIPEDA  
**Versi dokumen:** 0.1  
**Status:** Draft / Working Specification  
**Tanggal:** 29 Agustus 2026  
**Platform:** Web Application  
**Target:** Internal Fakultas Teknik Universitas Jenderal Soedirman  

---

# 1. Ringkasan Produk

Sistem Monitoring dan Pengendalian Anggaran merupakan aplikasi web internal yang dirancang untuk membantu Fakultas Teknik Universitas Jenderal Soedirman dalam melakukan pencatatan, monitoring, pengendalian, dan pelaporan penggunaan anggaran secara lebih terstruktur dan granular.

Sistem difokuskan pada pengelolaan informasi anggaran tingkat fakultas hingga unit/jurusan, dengan perhatian khusus pada:

- pagu anggaran;
- sumber dana;
- struktur anggaran;
- pengajuan;
- komitmen/reservasi anggaran;
- realisasi;
- saldo tersedia;
- revisi anggaran;
- monitoring antar unit;
- early warning;
- rule-based budget control;
- laporan;
- audit trail.

Sistem bukan merupakan pengganti sistem keuangan resmi universitas seperti ELFINA maupun sistem pemerintah yang digunakan untuk proses keuangan resmi.

Sistem berfungsi sebagai **sistem monitoring dan pengendalian internal tingkat fakultas** yang menyediakan informasi lebih terstruktur untuk membantu pengguna dalam menjalankan proses operasional dan pengambilan keputusan.

---

# 2. Latar Belakang Produk

Pengelolaan anggaran fakultas melibatkan berbagai struktur program, kegiatan, subkegiatan, akun, unit, sumber dana, pengajuan, dan realisasi.

Informasi tersebut perlu dapat dipantau secara lebih terstruktur agar pihak pengelola keuangan dapat mengetahui:

1. berapa pagu yang tersedia;
2. berapa anggaran yang telah digunakan;
3. berapa anggaran yang sedang berkomitmen/reserved;
4. berapa saldo yang masih tersedia;
5. unit mana yang membutuhkan perhatian;
6. pengajuan apa saja yang sedang berjalan;
7. apakah suatu pengajuan berpotensi menyebabkan penggunaan anggaran melebihi saldo tersedia;
8. bagaimana perubahan/revisi anggaran memengaruhi kondisi anggaran;
9. bagaimana kondisi anggaran dibandingkan antar unit;
10. dan bagaimana histori perubahan data dapat ditelusuri.

Sistem dirancang untuk memberikan visibilitas tersebut dalam satu aplikasi.

---

# 3. Tujuan Produk

## 3.1 Tujuan Utama

Membangun sistem web yang membantu pengelola keuangan Fakultas Teknik melakukan monitoring dan pengendalian anggaran secara terstruktur, transparan, dan dapat ditelusuri.

## 3.2 Tujuan Khusus

Sistem diharapkan mampu:

- mengelola master data organisasi;
- mengelola struktur anggaran;
- menyimpan informasi pagu;
- mengelola sumber dana;
- mencatat pengajuan;
- melakukan pengecekan ketersediaan anggaran;
- melakukan reservasi/commitment terhadap anggaran;
- mencatat realisasi;
- menghitung saldo tersedia;
- memantau penggunaan anggaran;
- mengelola revisi anggaran;
- memberikan early warning berbasis aturan;
- menyediakan dashboard;
- menyediakan laporan;
- menyimpan audit trail aktivitas penting.

---

# 4. Sasaran Pengguna

Sistem dirancang untuk beberapa kelompok pengguna.

## 4.1 PTK / Operator

Tanggung jawab utama:

- membuat pengajuan;
- mengisi informasi transaksi/pengajuan;
- mengunggah dokumen;
- melihat status pengajuan;
- memperbaiki pengajuan yang dikembalikan;
- melihat kondisi anggaran unit yang menjadi kewenangannya.

## 4.2 Ketua Jurusan / Penanggung Jawab Unit

Tanggung jawab:

- memonitor kondisi anggaran unit;
- memonitor pengajuan;
- melihat early warning;
- melihat laporan unit;
- melakukan tindakan sesuai kewenangan yang ditetapkan.

## 4.3 PTU / Reviewer Keuangan

Tanggung jawab:

- melakukan pemeriksaan pengajuan;
- memeriksa dokumen;
- melakukan verifikasi;
- mengembalikan pengajuan apabila terdapat kekurangan;
- meneruskan pengajuan sesuai workflow.

## 4.4 Kepala Bagian Keuangan

Tanggung jawab:

- memonitor kondisi anggaran fakultas;
- memonitor kondisi antar unit;
- menangani exception;
- melihat laporan;
- memonitor early warning;
- memantau perubahan/revisi anggaran.

## 4.5 Wakil Dekan

Tanggung jawab:

- melihat kondisi keuangan fakultas;
- memonitor pagu dan realisasi;
- memonitor early warning;
- memperoleh ringkasan kondisi anggaran untuk mendukung pengambilan keputusan.

## 4.6 Administrator

Tanggung jawab:

- mengelola pengguna;
- mengelola role;
- mengelola master data;
- melakukan import data;
- mengelola konfigurasi tertentu;
- mengelola rule sesuai kewenangan.

> Role final wajib dikonfirmasi kembali dengan pihak keuangan sebelum implementasi final.

---

# 5. Ruang Lingkup Produk

## 5.1 In Scope

### A. Authentication & Authorization

- login;
- logout;
- role-based access control;
- pembatasan akses berdasarkan unit;
- session management.

### B. Master Data

- jurusan;
- program studi;
- unit/subunit;
- sumber dana;
- tahun anggaran;
- struktur anggaran;
- akun;
- kategori;
- metode transaksi;
- konfigurasi workflow.

### C. Budget Management

- pagu anggaran;
- anggaran aktif;
- struktur budget bucket;
- reserved/committed budget;
- final realization;
- available balance;
- histori perubahan anggaran.

### D. Budget Import

- upload XLSX/CSV;
- staging;
- validation;
- mapping;
- preview;
- commit;
- import history.

### E. Pengajuan

- pembuatan pengajuan;
- pemilihan budget bucket;
- nominal;
- informasi kegiatan;
- dokumen;
- validasi anggaran;
- workflow status;
- timeline.

### F. Budget Control

- pengecekan saldo;
- overbudget prevention;
- reservation;
- release reservation;
- finalization;
- pengecekan dampak revisi.

### G. Early Warning

- rule-based warning;
- severity;
- threshold;
- affected object;
- explanation;
- recommended action;
- acknowledgement;
- resolution;
- warning history.

### H. Dashboard

- dashboard fakultas;
- dashboard jurusan;
- dashboard PTK;
- KPI;
- tren realisasi;
- kondisi anggaran;
- warning.

### I. Reporting

- realisasi;
- pagu;
- reserved;
- saldo;
- pengajuan;
- early warning;
- revision comparison;
- export XLSX/PDF/CSV.

### J. Audit Log

- aktivitas pengguna;
- perubahan data;
- perubahan status;
- import;
- perubahan konfigurasi;
- perubahan rule;
- aktivasi revisi.

---

# 6. Out of Scope

Fitur berikut tidak menjadi bagian dari MVP:

- menggantikan ELFINA;
- menggantikan SAKTI;
- integrasi langsung dengan sistem keuangan pemerintah;
- integrasi perbankan;
- pembayaran otomatis;
- internet banking;
- otomatisasi pencairan;
- tanda tangan elektronik;
- OCR dokumen;
- machine learning;
- forecasting;
- generative AI;
- chatbot;
- automatic budget allocation;
- automatic financial decision making;
- integrasi eksternal yang belum memiliki API resmi.

Fitur di luar scope dapat dipertimbangkan sebagai future development.

---

# 7. Prinsip Sistem

## 7.1 Sistem sebagai Monitoring dan Control Layer

Sistem tidak menggantikan sistem keuangan resmi.

Sistem bertindak sebagai lapisan:

```text
Data Anggaran
      ↓
Monitoring
      ↓
Budget Control
      ↓
Early Warning
      ↓
Reporting / Decision Support
```
