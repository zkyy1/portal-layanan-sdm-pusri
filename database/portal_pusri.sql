-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Okt 2026 pada 06.41
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `portal_pusri`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `applications`
--

CREATE TABLE `applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `applications`
--

INSERT INTO `applications` (`id`, `name`, `description`, `url`, `category_id`, `icon`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Sistem Kepegawaian', 'Aplikasi untuk mengelola data kepegawaian.', 'https://facebook.com', 1, 'applications/icons/icon_19e966974394b06c0c99141b.png', 1, 1, '2026-08-19 07:06:02', '2026-08-19 07:06:02'),
(3, 'Sistem Produksi', 'Aplikasi untuk mendukung proses produksi.', '#', 3, NULL, 1, 3, '2026-08-19 07:06:02', '2026-08-19 07:06:02'),
(12, 'Test MinIO CRUD 23', 'Data ini sudah diperbarui untuk pengujian fitur edit.', 'http://localhost/demo-aplikasi/', 2, 'applications/icons/img_f8871db07fda9505fa6640249ce45236.png', 1, 5, '2026-09-21 04:03:10', '2026-09-21 04:03:10'),
(15, 'contoj', 'lkjhgvfghjk', 'https://contoh.com', 2, 'applications/icons/img_d9a5ca3e6779888711571874e16d8255.png', 1, 2, '2026-09-23 02:36:38', '2026-09-23 02:36:38'),
(17, 'contoh aplikasi 1', 'contoh', 'https://facebook.com', 2, 'applications/icons/img_1f4ed7f2628f505071dcf5ec4efb8790.png', 1, 4, '2026-09-28 09:08:52', '2026-09-28 09:08:52'),
(18, 'contoh aplikasi 2', 'contoh', 'https://facebook.com', 2, 'applications/icons/img_d76b1785e3fe30cde21f061bdef51a7c.webp', 1, 5, '2026-09-28 09:10:03', '2026-09-28 09:10:03'),
(19, 'contoh aplikasi 3', 'contoh', 'https://facebook.com', 1, 'applications/icons/img_5bc52beb4b52b3c25defd597374a3c4e.png', 1, 6, '2026-09-28 09:10:44', '2026-09-28 09:10:44'),
(20, 'contoh aplikasi 4', 'contoh', 'https://facebook.com', 2, 'applications/icons/img_97ff6bef3feb2acee12cc56da7d1570e.png', 1, 7, '2026-09-28 09:11:25', '2026-09-28 09:11:25'),
(21, 'contoh aplikasi 5', 'contoh', 'https://facebook.com', 2, 'applications/icons/img_1bcd771bdacceaa9673db9fbdf423919.png', 1, 8, '2026-09-28 09:12:02', '2026-09-28 09:12:02'),
(23, 'sistem contoh', 'contoh', 'https://contoh.com', 1, 'applications/icons/img_29e3c0c2883c79e0dac94df4ea65e1a4.png', 1, 11, '2026-10-05 07:07:46', '2026-10-05 07:07:46'),
(24, 'sistem contoh 1', 'contoh', 'https://facebook.com', 2, 'applications/icons/img_ebdb2ba3cfbb76faaacd3977b31c9d25.png', 1, 0, '2026-10-26 05:52:24', '2026-10-26 05:52:24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `banners`
--

CREATE TABLE `banners` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `banners`
--

INSERT INTO `banners` (`id`, `title`, `subtitle`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Apapun tanamannya, PUSRI pupuknya', 'Pusri akan selalu mencari inovasi - inovasi dan terobosan - terobosan baru untuk mencapai pertumbuhan berkelanjutan yang sanggup memenuhi kebutuhan masa depan.', 'banners/icon_75b1d2d412587d0b29573d4b.jpg', 1, 3, '2026-08-26 01:50:33', '2026-08-31 06:53:22'),
(2, 'Bangun Kemandirian Pangan', '\"Bangkitkan Sinergi Bangun Kemajuan Negeri\"', 'banners/icon_67af71e01ff3a8227a91a2a9.jpg', 1, 2, '2026-08-28 02:48:30', '2026-08-31 06:53:38'),
(3, 'Giat Menghadirkan Solusi', 'Produktif Berinovasi Hasilkan Solusi Bermutu Tinggi', 'banners/icon_8b6295085cbf0d95fe648ac0.jpg', 1, 1, '2026-08-28 02:49:07', '2026-10-05 07:09:34'),
(4, 'ㅤ', '', 'banners/icon_478b070de0d28c2968c9381b.jpg', 1, 4, '2026-08-31 06:54:38', '2026-09-03 07:14:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Human Resource', 'Aplikasi yang berkaitan dengan sumber daya manusia', '2026-08-19 07:03:44', '2026-08-19 07:03:44'),
(2, 'Finance', 'Aplikasi yang berkaitan dengan keuangan', '2026-08-19 07:03:44', '2026-08-19 07:03:44'),
(3, 'Produksi', 'Aplikasi yang berkaitan dengan produksi', '2026-08-19 07:03:44', '2026-08-19 07:03:44'),
(8, 'KEUANGAN', 'contoh', '2026-10-05 07:08:58', '2026-10-05 07:08:58');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_app_category` (`category_id`);

--
-- Indeks untuk tabel `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_app_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
