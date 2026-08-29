-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 29, 2026 at 03:32 PM
-- Server version: 8.0.30
-- PHP Version: 8.3.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sipeda`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint UNSIGNED DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `model_type`, `model_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 1, 'SEED_INITIAL_DATA', 'System', 1, NULL, '{\"status\": \"SUCCESS\"}', '127.0.0.1', 'Symfony', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(2, 5, 'RESERVE_BUDGET', 'App\\Models\\BudgetBucket', 1, NULL, '{\"amount\": 10000000, \"reserved_budget\": \"55000000.00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-28 23:11:16', '2026-08-28 23:11:16'),
(3, 5, 'TRANSITION_SUBMISSION_STATUS', 'App\\Models\\Submission', 4, '{\"status\": \"DRAFT\"}', '{\"notes\": \"gada\", \"status\": \"APPROVED\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-28 23:11:16', '2026-08-28 23:11:16'),
(4, 1, 'FINALIZE_REALIZATION', 'App\\Models\\BudgetBucket', 1, NULL, '{\"amount\": 10000000, \"realized_budget\": \"185000000.00\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-28 23:12:16', '2026-08-28 23:12:16'),
(5, 1, 'TRANSITION_SUBMISSION_STATUS', 'App\\Models\\Submission', 4, '{\"status\": \"APPROVED\"}', '{\"notes\": \"gada\", \"status\": \"COMPLETED\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-28 23:12:16', '2026-08-28 23:12:16'),
(6, 1, 'TRANSITION_SUBMISSION_STATUS', 'App\\Models\\Submission', 4, '{\"status\": \"COMPLETED\"}', '{\"notes\": \"gada\", \"status\": \"COMPLETED\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', '2026-08-28 23:50:43', '2026-08-28 23:50:43');

-- --------------------------------------------------------

--
-- Table structure for table `budget_buckets`
--

CREATE TABLE `budget_buckets` (
  `id` bigint UNSIGNED NOT NULL,
  `fiscal_year_id` bigint UNSIGNED NOT NULL,
  `department_id` bigint UNSIGNED NOT NULL,
  `funding_source_id` bigint UNSIGNED NOT NULL,
  `account_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `initial_budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `allocated_budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reserved_budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `realized_budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `available_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_buckets`
--

INSERT INTO `budget_buckets` (`id`, `fiscal_year_id`, `department_id`, `funding_source_id`, `account_code`, `account_name`, `initial_budget`, `allocated_budget`, `reserved_budget`, `realized_budget`, `available_balance`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, '521111', 'Belanja Bahan & Operasional Laboratorium Komputer', '250000000.00', '250000000.00', '45000000.00', '185000000.00', '20000000.00', '2026-08-28 22:39:07', '2026-08-28 23:12:16'),
(2, 1, 2, 2, '522112', 'Belanja Jasa Riset & Seminar Internasional', '180000000.00', '180000000.00', '20000000.00', '80000000.00', '80000000.00', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(3, 1, 3, 1, '521111', 'Belanja Peralatan Uji Bahan Struktur Sipil', '300000000.00', '300000000.00', '35000000.00', '150000000.00', '115000000.00', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(4, 1, 4, 3, '524111', 'Belanja Perjalanan Dinas Praktikum Lapangan Elektro', '120000000.00', '120000000.00', '10000000.00', '105000000.00', '5000000.00', '2026-08-28 22:39:07', '2026-08-28 22:39:07');

-- --------------------------------------------------------

--
-- Table structure for table `budget_revisions`
--

CREATE TABLE `budget_revisions` (
  `id` bigint UNSIGNED NOT NULL,
  `revision_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_bucket_id` bigint UNSIGNED NOT NULL,
  `previous_amount` decimal(15,2) NOT NULL,
  `revised_amount` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `code`, `name`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'FT-UNSOED', 'Fakultas Teknik Universitas Jenderal Soedirman', NULL, 1, '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(2, 'JTIF', 'Jurusan Teknik Informatika', 1, 1, '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(3, 'JTS', 'Jurusan Teknik Sipil', 1, 1, '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(4, 'JTE', 'Jurusan Teknik Elektro', 1, 1, '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(5, 'JTG', 'Jurusan Teknik Geologi', 1, 1, '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(6, 'JTI', 'Jurusan Teknik Industri', 1, 1, '2026-08-28 22:39:05', '2026-08-28 22:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `early_warnings`
--

CREATE TABLE `early_warnings` (
  `id` bigint UNSIGNED NOT NULL,
  `rule_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `budget_bucket_id` bigint UNSIGNED DEFAULT NULL,
  `current_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `threshold_value` decimal(15,2) NOT NULL DEFAULT '0.00',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `acknowledged_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `early_warnings`
--

INSERT INTO `early_warnings` (`id`, `rule_code`, `severity`, `department_id`, `budget_bucket_id`, `current_value`, `threshold_value`, `message`, `status`, `acknowledged_by`, `created_at`, `updated_at`) VALUES
(1, 'EWS-001', 'HIGH', 2, 1, '20000000.00', '37500000.00', 'Sisa saldo ketersediaan anggaran pada pos 521111 - Belanja Bahan & Operasional Laboratorium Komputer berada di bawah threshold 8.0% (Rp 20.000.000).', 'ACTIVE', NULL, '2026-08-28 22:39:07', '2026-08-28 23:11:16'),
(2, 'EWS-001', 'CRITICAL', 4, 4, '5000000.00', '18000000.00', 'Sisa saldo ketersediaan anggaran pada pos 524111 - Belanja Perjalanan Dinas Praktikum Lapangan Elektro berada di bawah threshold 4.2% (Rp 5.000.000).', 'ACTIVE', NULL, '2026-08-28 22:39:07', '2026-08-28 22:39:07');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_years`
--

CREATE TABLE `fiscal_years` (
  `id` bigint UNSIGNED NOT NULL,
  `year` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fiscal_years`
--

INSERT INTO `fiscal_years` (`id`, `year`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 2026, 'ACTIVE', '2026-01-01', '2026-12-31', '2026-08-28 22:39:05', '2026-08-28 22:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `funding_sources`
--

CREATE TABLE `funding_sources` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `funding_sources`
--

INSERT INTO `funding_sources` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'UKT', 'Uang Kuliah Tunggal (BPN)', 'Dana operasional pendidikan bersumber dari UKT mahasiswa.', '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(2, 'BOPTN', 'Bantuan Operasional PTN', 'Dana bantuan dari Kementerian untuk riset dan sarana pendukung.', '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(3, 'RM', 'Rupiah Murni', 'Dana anggaran pemerintah pusat.', '2026-08-28 22:39:05', '2026-08-28 22:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_29_000001_create_departments_table', 1),
(5, '2026_08_29_000002_add_department_and_role_to_users_table', 1),
(6, '2026_08_29_000003_create_fiscal_years_table', 1),
(7, '2026_08_29_000004_create_funding_sources_table', 1),
(8, '2026_08_29_000005_create_budget_buckets_table', 1),
(9, '2026_08_29_000006_create_submissions_table', 1),
(10, '2026_08_29_000007_create_submission_items_table', 1),
(11, '2026_08_29_000008_create_early_warnings_table', 1),
(12, '2026_08_29_000009_create_budget_revisions_table', 1),
(13, '2026_08_29_000010_create_audit_logs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('gSW1BvWacx4IKv6WPsJbWmlMkKm2dYJjOWemZDoL', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'eyJfdG9rZW4iOiJqcnhwSFhSaWltekJCRWd2cXdsOW5Mdk1vTHNIaXc4VDdjU1N5Zm00IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=', 1787992066),
('MqszezMFuUwC7sYZa9BmO092ljH4ZfVyDF32PrbN', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'eyJfdG9rZW4iOiIya3Jyb1N3NlRONmF0aDZqRkNsME1heXd2aTM0YWl3dTJUcUZaU25VIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDAiLCJyb3V0ZSI6ImxhbmRpbmcifX0=', 1788016196),
('RTNJOczSv68xMYGrdW0KGyFZC4HjIGHtgnfBPgZd', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'eyJfdG9rZW4iOiI3TjJNWjZ6ZXd3R2hPcUZvWTFpdzhoUE5ESUV2d2xsUGkzUzZDaW1YIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9kYXNoYm9hcmQiLCJyb3V0ZSI6ImRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1787999379);

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` bigint UNSIGNED NOT NULL,
  `submission_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint UNSIGNED NOT NULL,
  `fiscal_year_id` bigint UNSIGNED NOT NULL,
  `budget_bucket_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint UNSIGNED NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `submission_number`, `title`, `department_id`, `fiscal_year_id`, `budget_bucket_id`, `amount`, `status`, `created_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SUB/2026/08/001', 'Pengadaan Lisensi Software Simulasi Jaringan & Cloud Server', 2, 1, 1, '45000000.00', 'RESERVED', 6, 'Pengajuan telah diverifikasi PTU dan disetujui KAJUR. Anggaran di-reserve.', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(2, 'SUB/2026/08/002', 'Penyelenggaraan Workshop International Conference on IT (ICIT)', 2, 1, 2, '20000000.00', 'REVIEW', 6, 'Sedang diverifikasi kelengkapan dokumen oleh PTU.', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(3, 'SUB/2026/08/003', 'Pengadaan Bahan Uji Kuat Tekan Beton & Aspal Lab Sipil', 3, 1, 3, '35000000.00', 'COMPLETED', 7, 'Pencairan selesai dan SPJ telah diverifikasi.', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(4, 'SUB/2026/08/638', 'cafe', 2, 1, 1, '10000000.00', 'COMPLETED', 5, 'gada', '2026-08-28 23:08:50', '2026-08-28 23:12:16');

-- --------------------------------------------------------

--
-- Table structure for table `submission_items`
--

CREATE TABLE `submission_items` (
  `id` bigint UNSIGNED NOT NULL,
  `submission_id` bigint UNSIGNED NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submission_items`
--

INSERT INTO `submission_items` (`id`, `submission_id`, `item_name`, `quantity`, `unit_price`, `total_price`, `created_at`, `updated_at`) VALUES
(1, 1, 'Lisensi Software Cisco Packet Tracer & Lab Server', 15, '2000000.00', '30000000.00', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(2, 1, 'Kredit AWS Educational Cloud Server 1 Tahun', 1, '15000000.00', '15000000.00', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(3, 2, 'Honorarium Keynote Speaker Internasional', 2, '10000000.00', '20000000.00', '2026-08-28 22:39:07', '2026-08-28 22:39:07'),
(4, 4, 'kopi', 1, '10000000.00', '10000000.00', '2026-08-28 23:08:50', '2026-08-28 23:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint UNSIGNED DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PTK',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `department_id`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin Keuangan FT', 'admin@ft.unsoed.ac.id', NULL, '$2y$12$LWj3f/AndpiGYvNJ1h1rnuPqZiQPgC.2qlVvZXfLMro.BVNH1aXHi', 1, 'ADMIN', NULL, '2026-08-28 22:39:05', '2026-08-28 22:39:05'),
(2, 'Dr. Ir. Wakil Dekan II', 'wd2@ft.unsoed.ac.id', NULL, '$2y$12$oax9FW9R5H4NUWvL09JH/uknO/5w9OsfPvNQdZP6qlONoTkuUBl5.', 1, 'WD', NULL, '2026-08-28 22:39:06', '2026-08-28 22:39:06'),
(3, 'Bapak Kepala Bagian Keuangan', 'kabag@ft.unsoed.ac.id', NULL, '$2y$12$AXAPxjosljEtyQ51sQVFnu5MBVid/cHQUOz3dFlkvwnP0TtiBuyKe', 1, 'KABAG', NULL, '2026-08-28 22:39:06', '2026-08-28 22:39:06'),
(4, 'Ibu Reviewer PTU', 'ptu@ft.unsoed.ac.id', NULL, '$2y$12$VZpmH9MOgPAOFd3K6k4Erebn3obA59l9JwzvpZmlThWDd1U1zPosC', 1, 'PTU', NULL, '2026-08-28 22:39:06', '2026-08-28 22:39:06'),
(5, 'Ketua Jurusan Informatika', 'kajur.if@ft.unsoed.ac.id', NULL, '$2y$12$5nl5Xaredvr8rCn.bfyey.dGHc4ajgwECF8Zb0itnjd7lpY2mRzqS', 2, 'KAJUR', NULL, '2026-08-28 22:39:06', '2026-08-28 22:39:06'),
(6, 'Operator PTK Informatika', 'ptk.if@ft.unsoed.ac.id', NULL, '$2y$12$fUCs0BlTA8ulDZaD/vTkb.R/9Tw79vydvnJ8/Ue6QblTNcnlrRi7O', 2, 'PTK', NULL, '2026-08-28 22:39:06', '2026-08-28 22:39:06'),
(7, 'Operator PTK Sipil', 'ptk.ts@ft.unsoed.ac.id', NULL, '$2y$12$q5tcVBP3QAIcQZHXZ/xe9./1L9Wm3.rAJS/ZLNzRg4peYQeaXLlHG', 3, 'PTK', NULL, '2026-08-28 22:39:07', '2026-08-28 22:39:07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `budget_buckets`
--
ALTER TABLE `budget_buckets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bucket_fiscal_dept_code_unique` (`fiscal_year_id`,`department_id`,`account_code`),
  ADD KEY `budget_buckets_department_id_foreign` (`department_id`),
  ADD KEY `budget_buckets_funding_source_id_foreign` (`funding_source_id`);

--
-- Indexes for table `budget_revisions`
--
ALTER TABLE `budget_revisions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `budget_revisions_revision_number_unique` (`revision_number`),
  ADD KEY `budget_revisions_budget_bucket_id_foreign` (`budget_bucket_id`),
  ADD KEY `budget_revisions_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `departments_code_unique` (`code`),
  ADD KEY `departments_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `early_warnings`
--
ALTER TABLE `early_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `early_warnings_department_id_foreign` (`department_id`),
  ADD KEY `early_warnings_budget_bucket_id_foreign` (`budget_bucket_id`),
  ADD KEY `early_warnings_acknowledged_by_foreign` (`acknowledged_by`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `fiscal_years`
--
ALTER TABLE `fiscal_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fiscal_years_year_unique` (`year`);

--
-- Indexes for table `funding_sources`
--
ALTER TABLE `funding_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `funding_sources_code_unique` (`code`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `submissions_submission_number_unique` (`submission_number`),
  ADD KEY `submissions_department_id_foreign` (`department_id`),
  ADD KEY `submissions_fiscal_year_id_foreign` (`fiscal_year_id`),
  ADD KEY `submissions_budget_bucket_id_foreign` (`budget_bucket_id`),
  ADD KEY `submissions_created_by_foreign` (`created_by`);

--
-- Indexes for table `submission_items`
--
ALTER TABLE `submission_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_items_submission_id_foreign` (`submission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_department_id_foreign` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `budget_buckets`
--
ALTER TABLE `budget_buckets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `budget_revisions`
--
ALTER TABLE `budget_revisions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `early_warnings`
--
ALTER TABLE `early_warnings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fiscal_years`
--
ALTER TABLE `fiscal_years`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `funding_sources`
--
ALTER TABLE `funding_sources`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `submission_items`
--
ALTER TABLE `submission_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `budget_buckets`
--
ALTER TABLE `budget_buckets`
  ADD CONSTRAINT `budget_buckets_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_buckets_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_buckets_funding_source_id_foreign` FOREIGN KEY (`funding_source_id`) REFERENCES `funding_sources` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_revisions`
--
ALTER TABLE `budget_revisions`
  ADD CONSTRAINT `budget_revisions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_revisions_budget_bucket_id_foreign` FOREIGN KEY (`budget_bucket_id`) REFERENCES `budget_buckets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `early_warnings`
--
ALTER TABLE `early_warnings`
  ADD CONSTRAINT `early_warnings_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `early_warnings_budget_bucket_id_foreign` FOREIGN KEY (`budget_bucket_id`) REFERENCES `budget_buckets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `early_warnings_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_budget_bucket_id_foreign` FOREIGN KEY (`budget_bucket_id`) REFERENCES `budget_buckets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_fiscal_year_id_foreign` FOREIGN KEY (`fiscal_year_id`) REFERENCES `fiscal_years` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submission_items`
--
ALTER TABLE `submission_items`
  ADD CONSTRAINT `submission_items_submission_id_foreign` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
