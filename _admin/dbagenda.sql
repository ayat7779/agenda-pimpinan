-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Des 2025 pada 06.49
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbagenda`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_agenda`
--

CREATE TABLE `tb_agenda` (
  `id_agenda` int(10) UNSIGNED NOT NULL,
  `tgl_agenda` date NOT NULL,
  `waktu` time NOT NULL,
  `nama_kegiatan` varchar(3065) NOT NULL,
  `tempat_kegiatan` varchar(3065) NOT NULL,
  `penanggungjawab_kegiatan` varchar(3065) NOT NULL,
  `pakaian_kegiatan` varchar(3065) NOT NULL,
  `pejabat` varchar(3065) NOT NULL,
  `lampiran` text DEFAULT NULL,
  `id_status` int(11) NOT NULL,
  `hasil_agenda` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `tb_agenda`
--

INSERT INTO `tb_agenda` (`id_agenda`, `tgl_agenda`, `waktu`, `nama_kegiatan`, `tempat_kegiatan`, `penanggungjawab_kegiatan`, `pakaian_kegiatan`, `pejabat`, `lampiran`, `id_status`, `hasil_agenda`, `created_at`, `updated_at`) VALUES
(35, '2025-12-08', '21:44:00', 'Rapat Kerja bersama Banggar', 'Ruang Medium DPRD Riau', 'Sekretaris DRPD', 'PDL', '1', '1765205098_PermenpanRBNomor32Tahun2020.pdf', 4, 'hadiri', '2025-12-08 14:44:58', '2025-12-08 14:45:19'),
(36, '2025-12-08', '14:00:00', 'Coffe Morning Staf Sekretariat Daerah', 'Kantor Gubernur Riau vvvvvv', 'Protokol', 'Menyesuaikan', '2', '1765205900_PermendagriNomor12Tahun2021.pdf', 4, 'hadiri', '2025-12-08 14:58:20', '2025-12-08 14:59:50'),
(37, '2025-12-09', '12:14:00', 'Pertemuan dengan panglima TNI', 'Kantor Gubernur Riau', 'Sekretaris DRPD', 'PDU', '2', '1765257273_PermenpanRBNomor32Tahun2020.pdf', 4, 'effefefefefefe', '2025-12-09 05:14:33', '2025-12-09 05:16:51'),
(38, '2025-12-09', '18:40:00', 'Rapat Kerja bersama Banggar', 'Ruang Gubernur', 'Sekretaris DRPD RIAU', 'PDU', '1', '1765258680_PermenpanRBNomor32Tahun2020.pdf', 4, 'laporkan dan dokumentasikan', '2025-12-09 05:38:00', '2025-12-09 05:38:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_notification_log`
--

CREATE TABLE `tb_notification_log` (
  `id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pejabat`
--

CREATE TABLE `tb_pejabat` (
  `id` int(11) NOT NULL,
  `kode_pejabat` varchar(10) NOT NULL,
  `nama_jabatan` varchar(1024) NOT NULL,
  `nama_pejabat` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pejabat`
--

INSERT INTO `tb_pejabat` (`id`, `kode_pejabat`, `nama_jabatan`, `nama_pejabat`) VALUES
(1, '001', 'Sekretaris Daerah', 'SYAHRIAL ABDI'),
(2, '002', 'Kepala Diskominfotik', 'TEZA');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_status`
--

CREATE TABLE `tb_status` (
  `id_status` int(11) NOT NULL,
  `nama_status` varchar(255) NOT NULL,
  `akronim` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `tb_status`
--

INSERT INTO `tb_status` (`id_status`, `nama_status`, `akronim`) VALUES
(3, 'Tindaklanjuti', 'ttl'),
(4, 'Selesai', 'sls'),
(5, 'Ditunda', 'ttd'),
(6, 'Belum Mulai', 'blm');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_tindaklanjut`
--

CREATE TABLE `tb_tindaklanjut` (
  `id_tindaklanjut` int(11) NOT NULL,
  `tgl_tindaklanjut` datetime NOT NULL,
  `isi_tindaklanjut` text NOT NULL,
  `penindaklanjut` varchar(255) NOT NULL,
  `lampiran` text DEFAULT NULL,
  `id_agenda` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_tindaklanjut`
--

INSERT INTO `tb_tindaklanjut` (`id_tindaklanjut`, `tgl_tindaklanjut`, `isi_tindaklanjut`, `penindaklanjut`, `lampiran`, `id_agenda`, `created_at`, `updated_at`) VALUES
(8, '2025-12-08 21:45:19', 'sudah', 'Raden Rorod', 'tindaklanjut_20251208214519_910803f6a09297e747fb83c745c2f433.jpg', 35, '2025-12-08 14:45:19', '2025-12-08 14:45:19'),
(10, '2025-12-08 21:58:36', 'ewrwerwerwer', 'Yayat', 'tindaklanjut_20251208215836_a8716b9aadc68d9f6bebd50b3bc2e8a1.jpg', 36, '2025-12-08 14:58:36', '2025-12-08 14:58:36'),
(12, '2025-12-09 12:36:53', '0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000', '1111111111111111111111111111111', 'tindaklanjut_20251209121539_3ac2e44f650c1d4d8ce9b25aa4ae985d.jpg', 37, '2025-12-09 05:15:39', '2025-12-09 05:36:53'),
(14, '2025-12-09 12:38:34', 'lapor komandan sudah selesai', 'Raden Rorod', 'tindaklanjut_20251209123834_2cd7ce24adff3cc3030730cdb7669b51.jpg', 38, '2025-12-09 05:38:34', '2025-12-09 05:38:34');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_agenda`
--
ALTER TABLE `tb_agenda`
  ADD PRIMARY KEY (`id_agenda`);

--
-- Indeks untuk tabel `tb_notification_log`
--
ALTER TABLE `tb_notification_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tb_tindaklanjut`
--
ALTER TABLE `tb_tindaklanjut`
  ADD PRIMARY KEY (`id_tindaklanjut`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `tb_agenda`
--
ALTER TABLE `tb_agenda`
  MODIFY `id_agenda` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `tb_notification_log`
--
ALTER TABLE `tb_notification_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tb_tindaklanjut`
--
ALTER TABLE `tb_tindaklanjut`
  MODIFY `id_tindaklanjut` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
