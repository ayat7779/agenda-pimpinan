-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Okt 2025 pada 11.31
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
-- Basis data: `db_agenda`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data untuk tabel `tb_agenda`
--

INSERT INTO `tb_agenda` (`id_agenda`, `tgl_agenda`, `waktu`, `nama_kegiatan`, `tempat_kegiatan`, `penanggungjawab_kegiatan`, `pakaian_kegiatan`, `pejabat`, `lampiran`, `id_status`, `hasil_agenda`) VALUES
(22, '2025-08-26', '11:59:00', 'Silaturahmi dan Perkenalan Forum Penyuluh Antikorupsi Provinsi Riau ', 'Inspektorat Daerah Provinsi Riau Jl. Cut Nyak Dien Pekanbaru', 'Drs. H. Eduar, M.Psa, M.Kom, CRMO', 'Menyesuaikan', '2', 'CamScanner 25-08-2025 1148_250826_113326.pdf', 4, 'Supaya dijadwalkan dengan Gubernur'),
(23, '2025-09-09', '13:00:00', 'Pembaca Berita pada Program Warta Berita Daerah Siang', 'Studio Pro1 99,1 MHz', 'Drs. Agung Prasatya Rosihan Umar', 'Menyesuaikan', '2', 'Sekretaris Daerah Prov Riau.pdf', 4, ''),
(24, '2025-09-16', '09:00:00', 'Focus Group Discussion (FGD) secara daring untuk mendukung digitalisasi sistem pengadaan pemerintah melalui INAPROC', 'Online via Zoom', 'Sutardi, B.Bus & B.Com (Hons) dan Budi Pramana Ginting', 'Menyesuaikan', '1', 'e1bb14fe5e6f37097482be18eca744de.pdf', 4, ''),
(25, '2025-09-02', '08:00:00', 'Gerakan Pangan Murah', 'Halaman Kantor Kelurahan Delima', 'Pemprov. Riau', 'PDH', '1', '', 4, ''),
(26, '2025-09-02', '08:00:00', 'Rapat Koordinasi terkait Arahan Mendagri ttg Perkembangan Situasi Terkirni dirangkaikan dengan Pengendalian Inflasi Thn 2025 secara Virtual', 'RCC Menara Lancang Kuning', 'Kemendagri (Imanuel 082118800730)', 'PDH Khaki', '2', '', 4, 'Meeting ID 677 057 3756\r\nPassword INFLASI'),
(27, '2025-09-02', '09:30:00', 'Rapat Koordinasi Terkait Kondisi & Dinamika Sosial Kemasyarakatan Beberapa Waktu ini serta dalam rangka Dukungan Stabilitas Sosial dan Politik di Daerah secara virtual', 'Ruang Rapat Sekda Kantor Gubernur ', 'Kemendagri (Fadel 082298520267)', 'PDH', '1', '', 4, 'Meeting ID : 822 2844 9306 \r\nPasscode : Polpum25'),
(28, '2025-09-02', '10:00:00', 'Pertemuan Gubernur dengan 5 Kepala Balai Kemen. PUPR Wilayah Riau', 'Kediaman Gubernur Riau', 'Pemprov. Riau', 'PDH', '2', '', 4, '');

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
  `id_agenda` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=armscii8 COLLATE=armscii8_general_ci;

--
-- Dumping data untuk tabel `tb_tindaklanjut`
--

INSERT INTO `tb_tindaklanjut` (`id_tindaklanjut`, `tgl_tindaklanjut`, `isi_tindaklanjut`, `penindaklanjut`, `lampiran`, `id_agenda`) VALUES
(1, '2025-09-07 14:30:43', 'Permohonan pertemuan sudah disampaikan dan Gubernur menjadwalkan tanggal 16 September 2025', 'Ajudan Gubernur', 'tindaklanjut_20250907143043_14c2d6ae273e3069762c4a410bb201d9.docx', 22),
(2, '2025-10-22 11:21:27', 'asdsadsadasd', 'asdasdsad', 'tindaklanjut_20251022112127_39ebd89c07fc14b5bdf1cd64ed372882.docx', 24);

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `tb_agenda`
--
ALTER TABLE `tb_agenda`
  ADD PRIMARY KEY (`id_agenda`);

--
-- Indeks untuk tabel `tb_pejabat`
--
ALTER TABLE `tb_pejabat`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id_agenda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `tb_pejabat`
--
ALTER TABLE `tb_pejabat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tb_status`
--
ALTER TABLE `tb_status`
  MODIFY `id_status` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tb_tindaklanjut`
--
ALTER TABLE `tb_tindaklanjut`
  MODIFY `id_tindaklanjut` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
