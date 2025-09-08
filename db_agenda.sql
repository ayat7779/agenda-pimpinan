-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Waktu pembuatan: 08 Sep 2025 pada 01.46
-- Versi server: 10.4.19-MariaDB
-- Versi PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_agenda`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_agenda`
--

CREATE TABLE `tb_agenda` (
  `id_agenda` int(11) NOT NULL,
  `tgl_agenda` date NOT NULL,
  `waktu` time NOT NULL,
  `nama_kegiatan` varchar(3065) NOT NULL,
  `tempat_kegiatan` varchar(3065) NOT NULL,
  `penanggungjawab_kegiatan` varchar(3065) NOT NULL,
  `pakaian_kegiatan` varchar(3065) NOT NULL,
  `pejabat` varchar(3065) NOT NULL,
  `lampiran` text DEFAULT NULL,
  `id_status` int(11) NOT NULL,
  `hasil_agenda` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data untuk tabel `tb_agenda`
--

INSERT INTO `tb_agenda` (`id_agenda`, `tgl_agenda`, `waktu`, `nama_kegiatan`, `tempat_kegiatan`, `penanggungjawab_kegiatan`, `pakaian_kegiatan`, `pejabat`, `lampiran`, `id_status`, `hasil_agenda`) VALUES
(22, '2025-08-26', '11:59:00', 'Silaturahmi dan Perkenalan Forum Penyuluh Antikorupsi Provinsi Riau ', 'Inspektorat Daerah Provinsi Riau Jl. Cut Nyak Dien Pekanbaru', 'Drs. H. Eduar, M.Psa, M.Kom, CRMO', 'Menyesuaikan', 'Sekda', 'CamScanner 25-08-2025 1148_250826_113326.pdf', 4, 'Supaya dijadwalkan dengan Gubernur'),
(23, '2025-09-09', '13:00:00', 'Pembaca Berita pada Program Warta Berita Daerah Siang', 'Studio Pro1 99,1 MHz', 'Drs. Agung Prasatya Rosihan Umar', 'Menyesuaikan', 'Sekda', 'Sekretaris Daerah Prov Riau.pdf', 4, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_status`
--

CREATE TABLE `tb_status` (
  `id_status` int(11) NOT NULL,
  `nama_status` varchar(255) NOT NULL,
  `akronim` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `id_agenda` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=armscii8;

--
-- Dumping data untuk tabel `tb_tindaklanjut`
--

INSERT INTO `tb_tindaklanjut` (`id_tindaklanjut`, `tgl_tindaklanjut`, `isi_tindaklanjut`, `penindaklanjut`, `lampiran`, `id_agenda`) VALUES
(1, '2025-09-07 14:30:43', 'Permohonan pertemuan sudah disampaikan dan Gubernur menjadwalkan tanggal 16 September 2025', 'Ajudan Gubernur', 'tindaklanjut_20250907143043_14c2d6ae273e3069762c4a410bb201d9.docx', 22);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `tb_agenda`
--
ALTER TABLE `tb_agenda`
  ADD PRIMARY KEY (`id_agenda`);

--
-- Indeks untuk tabel `tb_status`
--
ALTER TABLE `tb_status`
  ADD PRIMARY KEY (`id_status`);

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
  MODIFY `id_agenda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `tb_status`
--
ALTER TABLE `tb_status`
  MODIFY `id_status` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tb_tindaklanjut`
--
ALTER TABLE `tb_tindaklanjut`
  MODIFY `id_tindaklanjut` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
