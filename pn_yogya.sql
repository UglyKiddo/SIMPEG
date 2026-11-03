-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 03 Nov 2026 pada 07.13
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pn_yogya`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `jabatan`
--

CREATE TABLE `jabatan` (
  `id_jabatan` int(10) UNSIGNED NOT NULL,
  `nama_jabatan` varchar(150) NOT NULL,
  `kategori` enum('Hakim','Pegawai','PPNPN') NOT NULL DEFAULT 'Pegawai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jabatan`
--

INSERT INTO `jabatan` (`id_jabatan`, `nama_jabatan`, `kategori`, `created_at`) VALUES
(1, 'Ketua', 'Hakim', '2026-10-15 04:06:22'),
(2, 'Wakil Ketua', 'Hakim', '2026-10-15 04:06:22'),
(3, 'Hakim Utama Muda / Pertama', 'Hakim', '2026-10-15 04:06:22'),
(4, 'Hakim Madya Utama / Muda', 'Hakim', '2026-10-15 04:06:22'),
(5, 'Panitera', 'Pegawai', '2026-10-15 04:06:22'),
(6, 'Panitera Pengganti', 'Pegawai', '2026-10-15 04:06:22'),
(7, 'Juru Sita', 'Pegawai', '2026-10-15 04:06:22'),
(8, 'Juru Sita Pengganti', 'Pegawai', '2026-10-15 04:06:22'),
(9, 'Panitera Muda Perdata', 'Pegawai', '2026-10-15 04:06:22'),
(10, 'Panitera Muda Pidana', 'Pegawai', '2026-10-15 04:06:22'),
(11, 'Panitera Muda Hukum', 'Pegawai', '2026-10-15 04:06:22'),
(12, 'Panitera Muda Khusus Tindak Pidana Korupsi (Tipikor)', 'Pegawai', '2026-10-15 04:06:22'),
(13, 'Panitera Muda Khusus Penyelesaian Perselisihan Hubungan Industrial', 'Pegawai', '2026-10-15 04:06:22'),
(14, 'Panitera Muda Khusus Hak Asasi Manusia', 'Pegawai', '2026-10-15 04:06:22'),
(15, 'Sekretaris', 'Pegawai', '2026-10-15 04:06:22'),
(16, 'Jabatan Fungsional (JF) Analis Pengelolaan Keuangan APBN Ahli Muda', 'Pegawai', '2026-10-15 04:06:22'),
(17, 'Jabatan Fungsional (JF) Pranata Komputer Ahli Pertama', 'Pegawai', '2026-10-15 04:06:22'),
(18, 'Jabatan Fungsional (JF) Arsiparis Terampil', 'Pegawai', '2026-10-15 04:06:22'),
(19, 'Kepala Sub Bagian Perencanaan, Teknologi Informasi, dan Pelaporan', 'Pegawai', '2026-10-15 04:06:22'),
(20, 'Kepala Sub Bagian Kepegawaian, Organisasi, dan Tata Laksana', 'Pegawai', '2026-10-15 04:06:22'),
(21, 'Plt. Kepala Sub Bagian Umum dan Keuangan', 'Pegawai', '2026-10-15 04:06:22'),
(22, 'Klerek - Analis Perkara Peradilan', 'Pegawai', '2026-10-15 04:06:22'),
(23, 'Klerek - Pengelola Penanganan Perkara', 'Pegawai', '2026-10-15 04:06:22'),
(24, 'Klerek - Dokumentalis Hukum', 'Pegawai', '2026-10-15 04:06:22'),
(25, 'Klerek - Pengadministrasi Perkantoran', 'Pegawai', '2026-10-15 04:06:22'),
(26, 'Operator Layanan Operasional', 'Pegawai', '2026-10-15 04:06:22'),
(27, 'Operator - Penata Layanan Operasional', 'Pegawai', '2026-10-15 04:06:22'),
(28, 'Hakim Ad Hoc PHI', 'Hakim', '2026-10-15 04:06:22'),
(29, 'Hakim Ad Hoc Tipikor', 'Hakim', '2026-10-15 04:06:22'),
(30, 'Petugas Keamanan', 'PPNPN', '2026-10-15 04:06:22'),
(31, 'Pengelola Data dan Informasi', 'Pegawai', '2026-10-15 09:45:15'),
(32, 'Teknisi Sarana dan Prasarana-Umum', 'Pegawai', '2026-10-15 09:45:15'),
(33, 'Pengelola Umum Operasional', 'Pegawai', '2026-10-15 09:45:15'),
(35, 'sekretatis', 'Pegawai', '2026-11-03 03:17:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lampiran_transaksi`
--

CREATE TABLE `lampiran_transaksi` (
  `id_lampiran` int(10) UNSIGNED NOT NULL,
  `jenis_transaksi` enum('promosi','mutasi','lainnya') NOT NULL DEFAULT 'promosi',
  `tipe_transaksi` varchar(50) DEFAULT NULL COMMENT 'Tipe transaksi lampiran (promosi/mutasi/dll)',
  `id_transaksi` int(10) UNSIGNED NOT NULL COMMENT 'ID dari riwayat_promosi atau riwayat_mutasi',
  `id_pegawai` int(10) UNSIGNED DEFAULT NULL,
  `nama_asli` varchar(255) DEFAULT NULL COMMENT 'Nama berkas asli saat diunggah',
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `tipe_file` varchar(50) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `ukuran_file` int(11) DEFAULT NULL,
  `ukuran` int(11) DEFAULT NULL COMMENT 'Ukuran berkas dalam bytes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lampiran_transaksi`
--

INSERT INTO `lampiran_transaksi` (`id_lampiran`, `jenis_transaksi`, `tipe_transaksi`, `id_transaksi`, `id_pegawai`, `nama_asli`, `nama_file`, `file_path`, `tipe_file`, `mime_type`, `ukuran_file`, `ukuran`, `created_at`) VALUES
(1, 'promosi', 'promosi', 0, NULL, 'WhatsApp Image 2026-10-15 at 13.42.27 (1).jpeg', '04da3583a8eb83a537bc29795f21b9ed.jpg', 'uploads/sk/04da3583a8eb83a537bc29795f21b9ed.jpg', 'jpeg', 'image/jpeg', 296426, 296426, '2026-10-15 09:07:51'),
(2, 'promosi', 'promosi', 6, 47, 'WhatsApp Image 2026-10-15 at 13.42.27 (1).jpeg', '491922fe61547e27c3acb59367699ac7.jpg', 'private/lampiran-transaksi/491922fe61547e27c3acb59367699ac7.jpg', 'jpg', 'image/jpeg', 296426, 296426, '2026-10-15 09:17:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pangkat_golongan`
--

CREATE TABLE `pangkat_golongan` (
  `id_pangkat` int(10) UNSIGNED NOT NULL,
  `nama_pangkat` varchar(50) NOT NULL,
  `golongan` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pangkat_golongan`
--

INSERT INTO `pangkat_golongan` (`id_pangkat`, `nama_pangkat`, `golongan`) VALUES
(1, 'Pengatur', 'II/c'),
(2, 'Pengatur Tk. I', 'II/d'),
(3, 'Penata Muda', 'III/a'),
(4, 'Penata Muda Tk. I', 'III/b'),
(5, 'Penata', 'III/c'),
(6, 'Penata Tk. I', 'III/d'),
(7, 'Pembina', 'IV/a'),
(8, 'Pembina Tk. I', 'IV/b'),
(9, 'Pembina Utama Muda', 'IV/c'),
(10, 'Pembina Utama Madya', 'IV/d'),
(11, 'PPPK Golongan IX', 'IX'),
(12, 'PPPK Golongan V', 'V'),
(13, 'PPPK Golongan I', 'I'),
(14, 'PPPK Paruh Waktu', 'Paruh Wakt'),
(15, 'Non-Golongan / Ad Hoc', '-');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id_pegawai` int(10) UNSIGNED NOT NULL,
  `nip` varchar(30) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `id_jabatan` int(10) UNSIGNED NOT NULL,
  `id_pangkat` int(10) UNSIGNED DEFAULT NULL,
  `pendidikan_terakhir` enum('S3','S2','S1','D3','SMA','SMP','SD','Lainnya') DEFAULT 'S1',
  `status_aktif` enum('Aktif','Non-Aktif') NOT NULL DEFAULT 'Aktif',
  `tmt` date DEFAULT NULL,
  `dibuat_oleh` int(10) UNSIGNED DEFAULT NULL COMMENT 'users.id_user (role Admin) yang menginput data ini',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`id_pegawai`, `nip`, `nama_lengkap`, `id_jabatan`, `id_pangkat`, `pendidikan_terakhir`, `status_aktif`, `tmt`, `dibuat_oleh`, `created_at`, `updated_at`) VALUES
(2, '196804141996031002', 'SYAFRIZAL, S.H.', 1, 10, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-11-02 18:05:19'),
(4, '196905311996031003', 'SUNARYANTO, SH.,MH', 3, 10, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-11-02 18:03:36'),
(5, '197501272000032003', 'NI LUH SUKMARINI, SH., MH', 4, 9, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:16:11'),
(6, '197204271993031003', 'KARSENA, S.H.,M.H.', 4, 9, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:16:11'),
(7, '196908101990031006', 'JAMUJI, SH.,MH', 4, 9, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:16:11'),
(8, '197510282000122002', 'FITRI RAMADHAN, SH', 4, 9, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:14:31'),
(9, '197701312001121002', 'SURYA LAKSEMANA, SH', 4, 9, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:14:31'),
(11, '197801122002121002', 'GABRIEL SIALLAGAN, SH.,MH', 4, 8, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(12, '197505162002122001', 'PATYARINI MEININGSIH R, SH.,M.Hum', 4, 8, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(13, '197610092002121004', 'MUHAMMAD ISMAIL HAMID, SH.,MH', 4, 8, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(14, '197709242002122003', 'SRI SULASTUTI, SH', 4, 8, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(15, '197912282002122001', 'ERNI KUSUMAWATI, SH., MH', 4, 8, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(16, '197703192002122003', 'SRI WIJAYANTI TANJUNG, SH', 4, 8, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(17, '197701192002121004', 'REZA TYRAMA, SH', 4, 8, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(18, '197807152003121002', 'PURNOMO WIBOWO, SH.,MH', 4, 8, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(19, '197901272003121001', 'DJOKO WIRYONO BUDHI S, SH', 5, 8, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:14:31'),
(20, '197303151992032001', 'JOHANA CAROLINA LEKBILA, S.IP.,SH', 5, 8, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(21, '197807082006042001', 'DIAN UMAWATI,SH., MH', 13, 7, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:33:17'),
(23, '198010092008051002', 'ANDANG CATUR PRASETYA, SH., MH', 11, 7, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(24, '198204152011011005', 'DARU BUANA SEJATI, SH', 12, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(25, '196805141990032005', 'HENY SURYANI, SH', 12, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:33:17'),
(26, '197801272002122003', 'THESIANA MAYA FITRIA A, SH.,MH', 6, 7, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(27, '198310262008012008', 'OCTAVIA MARIANA WIJAYANTI, SH.,MH', 6, 7, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(28, '196710201993032005', 'RR.DINAWATI, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(29, '196907141994032005', 'Rr. SRI WINASTUTI,SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(30, '196606021999032003', 'ANNA HENY W,SH', 12, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(31, '196911151992032004', 'MARIA LUSIATI,SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(32, '196908051992031004', 'KUWAT WAHYU MURDANA,SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(33, '197001191992032002', 'YANI WIDIYANTI, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(34, '197006101992032002', 'SRI SUWANTI, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(35, '197509052001122001', 'NURI MAHAR KESTRI,SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(36, '196604091990032003', 'NUNUNG DIAH RETNO SAPTINING, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(37, '197111102006041001', 'ANTONIUS ANDI SUSANTO, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(38, '196911161993031002', 'YUDI SUHENDRO, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(39, '196605181988031001', 'SURYONO NUGROHO,SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(40, '197907092009042004', 'RULLIANA YUDAWATI, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(41, '197606152006042002', 'YUDHA AYU TIMORNIYATI, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(42, '197706072000122002', 'RR. WORO HAPSARI D,SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(43, '198508052009122005', 'RIKE SIMBALAGO, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(44, '198309172011011008', 'SEPTIAN ADI SASTRIA, S.H.', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(45, '198207062011012009', 'NAFISATUN ANA FITRIA UTAMI, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(46, '198711122006041001', 'FRANGKY ANTONI P, SH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(47, '197111061993031002', 'AGUS RIYANTO, SH', 35, 5, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-11-03 03:17:33'),
(48, '198212192006041002', 'RIMBANG KRISDIANTO, SH', 6, 5, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(49, '198803252015032001', 'SHEILA POSITA, SH.,MH', 6, 5, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(50, '199006132009042001', 'YUNITA NILA KRISNA, SH', 6, 5, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(51, '198409262009041004', 'BARATA MUHARAMIN, SH', 6, 5, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(52, '197306261994031003', 'HERI PRASETYA, SH', 7, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(53, '197508252006042003', 'LUSI RACHMAYANI,SE.SH', 7, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(54, '198007072008051001', 'ARLYO PERDANA PUTRA,SH', 7, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(55, '197305252006041004', 'NANANG SUPRIYADI, SE.,SH.,M.Kn', 7, 6, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(56, '197210041993031005', 'SALASA AGUS EKOYADI, SH', 7, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(57, '198001012008052002', 'NURMAYA REZEKY AR, SH', 7, 5, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(58, '198209222009042008', 'JEANNE PAMELA,S.Kom,MT', 8, 7, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(59, '197001171990032001', 'WARSIYATI', 8, 4, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(60, '197601011995101001', 'DOMINGOS DOUTEL', 8, 4, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(61, '196812211990031002', 'MOHAMAD SAID IDUL FITRI', 8, 4, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(62, '197308161994031001', 'TASIMAN, SH.,MH', 15, 8, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:16:11'),
(63, '198404102009042016', 'YENNY VIKKY EFFENDY,ST.SH.M.Eng', 20, 7, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:40:03'),
(65, '198607242011011005', 'KUNCORO SETYA R,SE.,MM', 16, 6, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:33:17'),
(66, '199102032019031005', 'NUGRAHA ABDILLAH, S.Kom', 17, 3, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:33:17'),
(67, '199509072020121004', 'MUHAMMAD NUR FIRDAUS S, A.Md', 18, 2, 'D3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:33:17'),
(68, '198208182010121002', 'HARIS HERMAWAN EFFENDI, SS.,MM', 27, 6, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:43'),
(69, '198510182015031001', 'ARDI WICAKSONO, ST', 19, 6, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(70, '199510162020122006', 'OKTA EMILIA LARASATI, SH', 22, 4, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:42'),
(71, '199511142020122005', 'NADYA PRIMAASHA BRAHMANA, SH', 22, 4, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:42'),
(72, '198911262015032003', 'NOVITA DIASTUTI, S.Kom', 22, 4, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(73, '199711132020121003', 'DWI NOVIANDARU, S. Tr. Kom', 22, 4, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(74, '199603252020122003', 'NADYA MAULANI MELYANA, A.Md. A.P', 23, 2, 'D3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:43'),
(75, '199911232024052001', 'DINA TRI LESTARI, SH', 22, 3, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:42'),
(76, '200010122024052002', 'PUTRI AZZAHRA, SH', 22, 3, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:42'),
(77, '200007192024051002', 'RYAN ADE SAPUTRO, SH', 23, 3, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:14:31'),
(78, '197212161993031001', 'MOH. RUSDIANTO', 25, 2, 'SMP', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(79, '197806192014081004', 'NINDYA YOSDALU PUTRA', 31, 1, 'D3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(80, '199904292022032021', 'INDAH MELINDA, A.Md.A.B.', 31, 2, 'D3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(81, '199412122022032011', 'TESA MONICA BR GULTOM, A.Md', 32, 1, 'D3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(84, '199310022025062003', 'FRANCISCA WESTRI INDASARI, SH', 22, 3, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:42'),
(85, '199703042025062014', 'ANIS HANIFAH, A.Md.Kom', 33, 1, 'D3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(86, '1471110904700001', 'HERI PURNOMO, S.Si.,SH.,MH', 28, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(87, '3471085612690001', 'SITI UMI AKHIROKH, SH.,MH', 28, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(88, '3216072404760003', 'AJI, S.H.', 28, 15, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(89, '7106096210740001', 'MAYA RIESKE J RUMAMBI, SH.,MH', 28, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:40:03'),
(90, '3578080611690005', 'SOEBEKTI, S.H.', 29, 15, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(91, '1271071003660004', 'ELIAS HAMONANGAN, SE.,SH.,MH', 29, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:40:03'),
(92, '3401070601700001', 'WARSONO, SH.,MH', 29, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(93, '3322195308730001', 'ATUN BUDI ASTUTI, SH', 29, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(94, '3217022704690001', 'KUSMAT TIRTA SASMITA, SH', 29, 15, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(95, '3313092802720002', 'YULIUS EKA SETIAWAN, SH.,MH', 29, 15, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:36:00'),
(96, '198107302025212017', 'DIAH SUKORINI,SH', 27, 11, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 06:37:43'),
(97, '198708282025211042', 'FAHMI HIDAYAT, SH', 27, 11, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:05'),
(98, '198412272025211029', 'DONNY SURIPTO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(99, '197312172025211016', 'TUNJUNG SULAKSANA P', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(100, '198701202025211030', 'KEMAS INDARTO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(101, '197308302025211014', 'WIRID WINOTO', 25, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(102, '198508312025211035', 'ARIF PRIHENDARTO', 25, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(103, '196906272025212006', 'BARIYAH', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(104, '197305292025211010', 'BUDI PRASETYO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(105, '197511092025211023', 'ANDIK SULISTYO', 25, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(106, '197608072025211028', 'DENY DWI SUSILO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(107, '198902142025211034', 'PEBRIANTO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(108, '198107172025211034', 'BAMBANG NUGROHO A MARTANTYO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(109, '198307232025211038', 'DWI RIYANTO', 26, 12, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:36:04'),
(110, '198109292025211037', 'SUDARMADI', 33, 13, 'SMA', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-20 06:43:12'),
(111, '197801252025211018', 'NGADIYO', 33, 13, 'SD', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-20 06:45:18'),
(112, '198412312025211083', 'EDI SISWANTO', 33, 13, 'SD', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-20 06:44:38'),
(113, '199204082025211067', 'ANGGA PERDANA PUTRA, SH', 27, 14, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 08:40:03'),
(114, '197304212005021003', 'HERI KUSMANTO, S.H.', 4, 7, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-20 06:49:52'),
(115, '197109011991031002', 'NURHADI, S.H.', 9, 7, 'S1', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(116, '197603112001121001', 'MULYADI ARIBOWO, S.H., M.H.', 4, 9, 'S2', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(117, '198702182009042004', 'LUTHFININGRUM NUR AFIYAH, S.E., M.B.A.', 21, 7, 'S3', 'Aktif', NULL, 1, '2026-09-15 01:53:35', '2026-10-15 09:45:15'),
(118, '198602192009121003', 'DANU ARMAN, SH.,MH', 6, 6, 'S1', 'Aktif', NULL, 1, '2026-09-22 08:00:00', '2026-10-15 09:45:15'),
(119, 'PPNPN-119', 'NOVIA IKE DEVITA, S.Kom.', 47, 15, 'S1', 'Aktif', NULL, 1, '2026-09-22 08:00:00', '2026-10-15 06:36:00'),
(120, '197105151999032003', 'ASNI MERIYANTI, S.H., M.H', 4, 9, 'S2', 'Aktif', NULL, 1, '2026-10-15 08:40:03', '2026-11-02 18:02:10'),
(121, '197812222001122001', 'DEWI KURNIASARI, S.H.', 4, 8, 'S2', 'Aktif', NULL, 1, '2026-10-15 08:40:03', '2026-10-15 09:45:15'),
(122, '197201082005022002', 'HARTATI ARI SURYAWATI, SH', 4, 8, 'S1', 'Aktif', NULL, 1, '2026-10-15 08:40:03', '2026-10-15 09:45:15'),
(123, '197606112007041001', 'M HASANUDDIN HEFNI, S.H., M.H', 4, 7, 'S2', 'Aktif', NULL, 1, '2026-10-15 08:40:03', '2026-10-15 09:45:15'),
(124, '197609172001122003', 'SETYANINGSIH, SH', 4, 9, 'S2', 'Aktif', NULL, NULL, '2026-10-15 09:45:15', '2026-10-15 09:45:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_edit_pegawai`
--

CREATE TABLE `riwayat_edit_pegawai` (
  `id_riwayat` bigint(20) UNSIGNED NOT NULL,
  `id_pegawai` int(11) NOT NULL,
  `nip` varchar(30) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `id_jabatan` int(11) NOT NULL,
  `id_pangkat` int(11) DEFAULT NULL,
  `pendidikan_terakhir` varchar(100) DEFAULT NULL,
  `tmt` date DEFAULT NULL,
  `status_aktif` varchar(30) NOT NULL,
  `tanggal_perubahan` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_edit_pegawai`
--

INSERT INTO `riwayat_edit_pegawai` (`id_riwayat`, `id_pegawai`, `nip`, `nama_lengkap`, `id_jabatan`, `id_pangkat`, `pendidikan_terakhir`, `tmt`, `status_aktif`, `tanggal_perubahan`, `created_at`) VALUES
(1, 4, '196905311996031003', 'SUNARYANTO, SH.,MH', 3, 10, 'S2', NULL, 'Aktif', '2026-11-03', '2026-11-02 18:00:45'),
(2, 4, '196905311996031003', 'SUNARYANTO, SH.,MH', 3, 10, 'S1', NULL, 'Aktif', '2026-11-03', '2026-11-02 18:01:20'),
(3, 4, '196905311996031003', 'SUNARYANTO, SH.,MH', 3, 10, 'S3', NULL, 'Aktif', '2026-11-03', '2026-11-02 18:01:57'),
(4, 120, '197105151999032003', 'ASNI MERIYANTI, S.H., M.H', 4, 9, 'S2', NULL, 'Aktif', '2026-11-03', '2026-11-02 18:02:04'),
(5, 120, '197105151999032003', 'ASNI MERIYANTI, S.H., M.H', 4, 9, 'S1', NULL, 'Aktif', '2026-11-03', '2026-11-02 18:02:10'),
(6, 4, '196905311996031003', 'SUNARYANTO, SH.,MH', 3, 10, 'S2', NULL, 'Aktif', '2026-11-03', '2026-11-02 18:03:29'),
(7, 4, '196905311996031003', 'SUNARYANTO, SH.,MH', 3, 10, 'SD', NULL, 'Aktif', '2026-11-01', '2026-10-31 18:03:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_mutasi`
--

CREATE TABLE `riwayat_mutasi` (
  `id_mutasi` int(10) UNSIGNED NOT NULL,
  `id_pegawai` int(10) UNSIGNED NOT NULL,
  `nama_pegawai` varchar(150) DEFAULT NULL,
  `nip_pegawai` varchar(30) DEFAULT NULL,
  `no_sk` varchar(100) NOT NULL,
  `file_sk` varchar(255) DEFAULT NULL COMMENT 'Nama file PDF / dokumen SK Mutasi yang diupload',
  `tanggal_sk` date NOT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `jenis_mutasi` enum('Masuk','Keluar','Internal') NOT NULL DEFAULT 'Keluar',
  `id_jabatan_saat_mutasi` int(10) UNSIGNED DEFAULT NULL,
  `id_pangkat_saat_mutasi` int(10) UNSIGNED DEFAULT NULL,
  `tujuan` varchar(255) NOT NULL COMMENT 'Instansi/Pengadilan Tujuan atau Asal Mutasi',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_promosi`
--

CREATE TABLE `riwayat_promosi` (
  `id_promosi` int(10) UNSIGNED NOT NULL,
  `id_pegawai` int(10) UNSIGNED NOT NULL,
  `nama_pegawai` varchar(150) DEFAULT NULL,
  `nip_pegawai` varchar(30) DEFAULT NULL,
  `jenis_promosi` enum('Naik Jabatan','Keluar') NOT NULL DEFAULT 'Naik Jabatan',
  `no_sk` varchar(100) NOT NULL,
  `file_sk` varchar(255) DEFAULT NULL COMMENT 'Nama file PDF / dokumen SK Promosi yang diupload',
  `tanggal_sk` date NOT NULL,
  `tanggal_melaksanakan` date DEFAULT NULL,
  `tanggal_keluar` date DEFAULT NULL,
  `id_jabatan_lama` int(10) UNSIGNED DEFAULT NULL,
  `id_jabatan_baru` int(10) UNSIGNED NOT NULL,
  `id_pangkat_lama` int(10) UNSIGNED DEFAULT NULL,
  `id_pangkat_baru` int(10) UNSIGNED DEFAULT NULL,
  `tujuan` varchar(255) NOT NULL COMMENT 'Unit/Jabatan/Lokasi Tujuan Promosi',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `riwayat_promosi`
--

INSERT INTO `riwayat_promosi` (`id_promosi`, `id_pegawai`, `nama_pegawai`, `nip_pegawai`, `jenis_promosi`, `no_sk`, `file_sk`, `tanggal_sk`, `tanggal_melaksanakan`, `tanggal_keluar`, `id_jabatan_lama`, `id_jabatan_baru`, `id_pangkat_lama`, `id_pangkat_baru`, `tujuan`, `keterangan`, `created_at`) VALUES
(8, 47, 'AGUS RIYANTO, SH', '197111061993031002', 'Naik Jabatan', '11111', NULL, '2026-11-03', '2026-11-03', NULL, 6, 35, 5, 5, 'pn yogya', 'a', '2026-11-03 03:17:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `role` enum('Admin','Pegawai','Super Admin') NOT NULL DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `nip`, `password`, `nama_user`, `role`, `created_at`, `updated_at`) VALUES
(2, '198911262015032003', '198911262015032003', '$2y$10$CnWX4S2I6150dWf0QuCC3.UOStdoFm32LUQbVNL.2s7ytSdrAYhKG', 'admin', 'Pegawai', '2026-10-15 08:45:57', '2026-11-03 02:24:25');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id_jabatan`);

--
-- Indeks untuk tabel `lampiran_transaksi`
--
ALTER TABLE `lampiran_transaksi`
  ADD PRIMARY KEY (`id_lampiran`),
  ADD KEY `idx_transaksi` (`jenis_transaksi`,`id_transaksi`),
  ADD KEY `fk_lampiran_pegawai` (`id_pegawai`),
  ADD KEY `idx_lampiran_tipe_transaksi` (`tipe_transaksi`,`id_transaksi`);

--
-- Indeks untuk tabel `pangkat_golongan`
--
ALTER TABLE `pangkat_golongan`
  ADD PRIMARY KEY (`id_pangkat`),
  ADD UNIQUE KEY `uk_golongan` (`golongan`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id_pegawai`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `fk_pegawai_jabatan` (`id_jabatan`),
  ADD KEY `fk_pegawai_dibuat_oleh` (`dibuat_oleh`),
  ADD KEY `fk_pegawai_pangkat` (`id_pangkat`);

--
-- Indeks untuk tabel `riwayat_edit_pegawai`
--
ALTER TABLE `riwayat_edit_pegawai`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `idx_riwayat_edit_pegawai_tanggal` (`id_pegawai`,`tanggal_perubahan`,`id_riwayat`);

--
-- Indeks untuk tabel `riwayat_mutasi`
--
ALTER TABLE `riwayat_mutasi`
  ADD PRIMARY KEY (`id_mutasi`),
  ADD KEY `fk_mutasi_pegawai` (`id_pegawai`),
  ADD KEY `fk_mutasi_jabatan` (`id_jabatan_saat_mutasi`),
  ADD KEY `fk_mutasi_pangkat` (`id_pangkat_saat_mutasi`);

--
-- Indeks untuk tabel `riwayat_promosi`
--
ALTER TABLE `riwayat_promosi`
  ADD PRIMARY KEY (`id_promosi`),
  ADD KEY `fk_promosi_pegawai` (`id_pegawai`),
  ADD KEY `fk_promosi_pangkat_lama` (`id_pangkat_lama`),
  ADD KEY `fk_promosi_pangkat_baru` (`id_pangkat_baru`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD UNIQUE KEY `uk_nip_users` (`nip`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id_jabatan` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `lampiran_transaksi`
--
ALTER TABLE `lampiran_transaksi`
  MODIFY `id_lampiran` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pangkat_golongan`
--
ALTER TABLE `pangkat_golongan`
  MODIFY `id_pangkat` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id_pegawai` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT untuk tabel `riwayat_edit_pegawai`
--
ALTER TABLE `riwayat_edit_pegawai`
  MODIFY `id_riwayat` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `riwayat_mutasi`
--
ALTER TABLE `riwayat_mutasi`
  MODIFY `id_mutasi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `riwayat_promosi`
--
ALTER TABLE `riwayat_promosi`
  MODIFY `id_promosi` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `lampiran_transaksi`
--
ALTER TABLE `lampiran_transaksi`
  ADD CONSTRAINT `fk_lampiran_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `fk_pegawai_pangkat` FOREIGN KEY (`id_pangkat`) REFERENCES `pangkat_golongan` (`id_pangkat`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_mutasi`
--
ALTER TABLE `riwayat_mutasi`
  ADD CONSTRAINT `fk_mutasi_jabatan` FOREIGN KEY (`id_jabatan_saat_mutasi`) REFERENCES `jabatan` (`id_jabatan`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mutasi_pangkat` FOREIGN KEY (`id_pangkat_saat_mutasi`) REFERENCES `pangkat_golongan` (`id_pangkat`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `riwayat_promosi`
--
ALTER TABLE `riwayat_promosi`
  ADD CONSTRAINT `fk_promosi_pangkat_baru` FOREIGN KEY (`id_pangkat_baru`) REFERENCES `pangkat_golongan` (`id_pangkat`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_promosi_pangkat_lama` FOREIGN KEY (`id_pangkat_lama`) REFERENCES `pangkat_golongan` (`id_pangkat`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
