-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Des 2025 pada 16.34
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
-- Database: `helpdesk_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `entities`
--

CREATE TABLE `entities` (
  `id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(10) UNSIGNED NOT NULL,
  `nama_pengguna` varchar(150) DEFAULT NULL,
  `nama_entitas` varchar(200) NOT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `tipe_entitas` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `jumlah_ticket` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `entities`
--

INSERT INTO `entities` (`id`, `unit_id`, `nama_pengguna`, `nama_entitas`, `serial_number`, `tipe_entitas`, `brand`, `jumlah_ticket`, `created_at`, `updated_at`) VALUES
(1, 3, 'SARTANA RAMIREZ', 'NIP0001', 'LNV2025110001', 'pc', 'LENOVO', 1, '2025-11-21 06:58:50', '2025-11-26 05:28:00'),
(2, 3, 'ROMMY SANCHEZ', 'NIP0002', 'LNV2025110002', 'pc', 'LENOVO', 1, '2025-11-21 06:59:42', '2025-11-26 04:42:48'),
(3, 3, 'ARMANDO BONAPARTE', 'NIP0003', 'LNV2025110003', 'pc', 'LENOVO', 1, '2025-11-21 07:00:37', '2025-12-04 03:36:29'),
(4, 3, 'SARTANA RAMIREZ', 'NIP1001', 'LNV2025111001', 'monitor', 'LENOVO', 0, '2025-11-21 07:02:49', '2025-11-21 07:02:49'),
(5, 3, 'ROMMY SANCHEZ', 'NIP1002', 'LNV2025111002', 'monitor', 'LENOVO', 2, '2025-11-21 07:03:49', '2025-12-01 05:26:00'),
(6, 3, 'ARMANDO BONAPARTE', 'NIP1003', 'LNV2025111003', 'monitor', 'LENOVO', 1, '2025-11-21 07:04:23', '2025-11-26 05:30:16'),
(15, 3, 'SARTANA RAMIREZ', 'NIP2001', 'HP2025112001', 'laptop', 'HP', 1, '2025-11-24 05:27:21', '2025-11-26 04:35:49'),
(16, 3, 'ROMMY SANCHEZ', 'NIP2002', 'HP2025112002', 'laptop', 'HP', 0, '2025-11-24 05:28:01', '2025-11-24 05:28:01'),
(17, 3, 'ARMANDO BONAPARTE', 'NIP2003', 'HP2025112003', 'laptop', 'HP', 3, '2025-11-24 05:28:45', '2025-12-04 03:37:11'),
(18, 3, 'SARTANA RAMIREZ', 'NIP3001', 'EPS2025113001', 'printer', 'EPSON', 0, '2025-11-24 05:29:35', '2025-11-24 05:29:35'),
(19, 3, 'ROMMY SANCHEZ', 'NIP3002', 'EPS2025113002', 'printer', 'EPSON', 1, '2025-11-24 05:30:02', '2025-11-26 05:27:00'),
(20, 3, 'ARMANDO BONAPARTE', 'NIP3003', 'EPS2025113003', 'printer', 'EPSON', 2, '2025-11-24 05:43:24', '2025-12-01 05:24:32'),
(21, 4, 'KING CYAN PEREZ', 'NIP0004', 'LNV2025110004', 'pc', 'LENOVO', 1, '2025-11-24 06:29:09', '2025-11-27 06:54:43'),
(22, 4, 'FRINCE MIGUEL RAMIREZ', 'NIP0005', 'LNV2025110005', 'pc', 'LENOVO', 0, '2025-11-24 06:30:55', '2025-11-24 06:30:55'),
(23, 4, 'JANN KIRK SOLCRUZ GUTIERREZ', 'NIP0006', 'LNV2025110006', 'pc', 'LENOVO', 2, '2025-11-24 06:35:48', '2025-12-01 05:49:08'),
(24, 4, 'KING CYAN PEREZ', 'NIP1004', 'LNV2025111004', 'monitor', 'LENOVO', 1, '2025-11-24 06:37:05', '2025-12-01 05:14:21'),
(25, 4, 'FRINCE MIGUEL RAMIREZ', 'NIP1005', 'LNV2025111005', 'monitor', 'LENOVO', 0, '2025-11-24 06:38:05', '2025-11-24 06:38:05'),
(26, 4, 'JANN KIRK SOLCRUZ GUTIERREZ', 'NIP1006', 'LNV2025111006', 'monitor', 'LENOVO', 0, '2025-11-24 06:38:38', '2025-11-24 06:38:38'),
(27, 4, 'KING CYAN PEREZ', 'NIP2004', 'HP2025112004', 'laptop', 'HP', 0, '2025-11-24 06:48:11', '2025-11-24 06:48:11'),
(28, 4, 'FRINCE MIGUEL RAMIREZ', 'NIP2005', 'HP2025112005', 'laptop', 'HP', 1, '2025-11-24 06:48:36', '2025-12-01 05:15:11'),
(29, 4, 'JANN KIRK SOLCRUZ GUTIERREZ', 'NIP2006', 'HP2025112006', 'laptop', 'HP', 1, '2025-11-24 06:49:09', '2025-12-01 05:49:27'),
(30, 4, 'KING CYAN PEREZ', 'NIP3004', 'EPS2025113004', 'printer', 'EPSON', 0, '2025-11-24 06:50:11', '2025-11-24 06:50:11'),
(31, 4, 'FRINCE MIGUEL RAMIREZ', 'NIP3005', 'EPS2025113005', 'printer', 'EPSON', 0, '2025-11-24 06:50:57', '2025-11-24 06:50:57'),
(32, 4, 'JANN KIRK SOLCRUZ GUTIERREZ', 'NIP3006', 'EPS2025113006', 'printer', 'EPSON', 0, '2025-11-24 06:51:32', '2025-11-24 06:51:32'),
(33, 5, 'BORRIS JAMES PARRO', 'NIP0007', 'LNV2025110007', 'pc', 'LENOVO', 1, '2025-11-25 02:30:46', '2025-12-01 05:10:35'),
(34, 5, 'GRANT DUANE PILLAS', 'NIP0008', 'LNV2025110008', 'pc', 'LENOVO', 1, '2025-11-25 02:32:10', '2025-12-01 05:11:16'),
(35, 5, 'JAYLORD GONZALES', 'NIP0009', 'LNV2025110009', 'pc', 'LENOVO', 0, '2025-11-25 02:33:44', '2025-11-25 02:33:44'),
(36, 5, 'BORRIS JAMES PARRO', 'NIP1007', 'LNV2025111007', 'monitor', 'LENOVO', 0, '2025-11-25 02:34:10', '2025-11-25 02:34:10'),
(37, 5, 'GRANT DUANE PILLAS', 'NIP1008', 'LNV2025111008', 'monitor', 'LENOVO', 0, '2025-11-25 02:37:45', '2025-11-25 02:37:45'),
(38, 5, 'JAYLORD GONZALES', 'NIP1009', 'LNV2025111009', 'monitor', 'LENOVO', 0, '2025-11-25 02:38:14', '2025-11-25 02:38:14'),
(39, 5, 'BORRIS JAMES PARRO', 'NIP2007', 'HP2025112007', 'laptop', 'HP', 1, '2025-11-25 02:40:16', '2025-11-27 07:32:07'),
(40, 5, 'GRANT DUANE PILLAS', 'NIP2008', 'HP2025112008', 'laptop', 'HP', 0, '2025-11-25 02:40:51', '2025-11-25 02:40:51'),
(41, 5, 'JAYLORD GONZALES', 'NIP2009', 'HP2025112009', 'laptop', 'HP', 0, '2025-11-25 02:41:22', '2025-11-25 02:41:22'),
(42, 5, 'BORRIS JAMES PARRO', 'NIP3007', 'EPS25113007', 'printer', 'EPSON', 1, '2025-11-25 02:42:33', '2025-12-03 06:17:34'),
(43, 5, 'GRANT DUANE PILLAS', 'NIP3008', 'EPS25113008', 'printer', 'EPSON', 0, '2025-11-25 02:42:57', '2025-11-25 02:42:57'),
(44, 5, 'JAYLORD GONZALES', 'NIP3009', 'EPS25113009', 'printer', 'EPSON', 1, '2025-11-25 02:43:26', '2025-11-27 07:31:42'),
(45, 6, 'MICHAEL ANGELO SAYSON', 'NIP0010', 'LNV2025110010', 'pc', 'LENOVO', 0, '2025-11-25 02:48:51', '2025-11-25 02:48:51'),
(46, 6, 'DAVID CHARLES CANON', 'NIP0011', 'LNV2025110011', 'pc', 'LENOVO', 0, '2025-11-25 02:50:03', '2025-11-25 02:50:03'),
(47, 6, 'ANGELO KYLE ARCANGEL', 'NIP0012', 'LNV2025110012', 'pc', 'LENOVO', 0, '2025-11-25 02:52:08', '2025-11-25 02:52:08'),
(48, 6, 'MICHAEL ANGELO SAYSON', 'NIP1010', 'LNV2025111010', 'monitor', 'LENOVO', 1, '2025-11-25 02:53:12', '2025-11-28 03:37:34'),
(49, 6, 'DAVID CHARLES CANON', 'NIP1011', 'LNV2025111011', 'monitor', 'LENOVO', 1, '2025-11-25 02:54:12', '2025-12-03 06:18:34'),
(50, 6, 'ANGELO KYLE ARCANGEL', 'NIP1012', 'LNV2025111012', 'monitor', 'LENOVO', 0, '2025-11-25 02:54:39', '2025-11-25 02:54:39'),
(51, 6, 'MICHAEL ANGELO SAYSON', 'NIP2010', 'HP2025112010', 'laptop', 'HP', 0, '2025-11-25 02:56:53', '2025-11-25 02:56:53'),
(52, 6, 'DAVID CHARLES CANON', 'NIP2011', 'HP2025112011', 'laptop', 'HP', 0, '2025-11-25 02:57:42', '2025-11-25 02:57:42'),
(53, 6, 'ANGELO KYLE ARCANGEL', 'NIP2012', 'HP2025112012', 'laptop', 'HP', 1, '2025-11-25 02:58:20', '2025-12-01 05:17:51'),
(54, 6, 'MICHAEL ANGELO SAYSON', 'NIP3010', 'EPS25113010', 'printer', 'EPSON', 1, '2025-11-25 02:58:48', '2025-11-28 08:41:52'),
(55, 6, 'DAVID CHARLES CANON', 'NIP3011', 'EPS25113011', 'printer', 'EPSON', 0, '2025-11-25 02:59:55', '2025-11-25 02:59:55'),
(56, 6, 'ANGELO KYLE ARCANGEL', 'NIP3012', 'EPS25113012', 'printer', 'EPSON', 1, '2025-11-25 03:00:22', '2025-12-01 05:17:05'),
(57, 7, 'MARCO STEPHEN REQUITIANO', 'NIP0013', 'LNV2025110013', 'pc', 'LENOVO', 0, '2025-11-25 03:02:42', '2025-11-25 03:02:42'),
(58, 7, 'ROWGIEN STIMPSON UNIGO', 'NIP0014', 'LNV2025110014', 'pc', 'LENOVO', 1, '2025-11-25 03:04:23', '2025-12-04 03:34:16'),
(59, 7, 'VINCENT JOSEPH VILLONES UNIGO', 'NIP0015', 'LNV2025110015', 'pc', 'LENOVO', 1, '2025-11-25 03:07:07', '2025-12-01 05:05:59'),
(60, 7, 'MARCO STEPHEN REQUITIANO', 'NIP1013', 'LNV2025111013', 'monitor', 'LENOVO', 1, '2025-11-25 03:08:05', '2025-12-01 05:26:47'),
(61, 7, 'ROWGIEN STIMPSON UNIGO', 'NIP1014', 'LNV2025111014', 'monitor', 'LENOVO', 1, '2025-11-25 03:08:49', '2025-12-01 05:04:57'),
(62, 7, 'VINCENT JOSEPH VILLONES UNIGO', 'NIP1015', 'LNV2025111015', 'monitor', 'LENOVO', 0, '2025-11-25 03:09:18', '2025-11-25 03:09:18'),
(63, 7, 'MARCO STEPHEN REQUITIANO', 'NIP2013', 'HP2025112013', 'laptop', 'HP', 1, '2025-11-25 03:09:46', '2025-12-01 05:04:23'),
(64, 7, 'ROWGIEN STIMPSON UNIGO', 'NIP2014', 'HP2025112014', 'laptop', 'HP', 0, '2025-11-25 03:11:07', '2025-11-25 03:11:07'),
(65, 7, 'VINCENT JOSEPH VILLONES UNIGO', 'NIP2015', 'HP2025112015', 'laptop', 'HP', 1, '2025-11-25 03:11:53', '2025-12-04 03:35:14'),
(66, 7, 'MARCO STEPHEN REQUITIANO', 'NIP3013', 'EPS25113013', 'printer', 'EPSON', 1, '2025-11-25 03:12:26', '2025-12-01 05:06:55'),
(67, 7, 'ROWGIEN STIMPSON UNIGO', 'NIP3014', 'EPS25113014', 'printer', 'EPSON', 0, '2025-11-25 03:13:01', '2025-11-25 03:13:01'),
(68, 7, 'VINCENT JOSEPH VILLONES UNIGO', 'NIP3015', 'EPS25113015', 'printer', 'EPSON', 0, '2025-11-25 03:13:34', '2025-11-25 03:13:34');

--
-- Trigger `entities`
--
DELIMITER $$
CREATE TRIGGER `trg_entities_after_delete` AFTER DELETE ON `entities` FOR EACH ROW BEGIN
  UPDATE units SET entity_count = GREATEST(0, entity_count - 1) WHERE id = OLD.unit_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_entities_after_insert` AFTER INSERT ON `entities` FOR EACH ROW BEGIN
  UPDATE units SET entity_count = entity_count + 1 WHERE id = NEW.unit_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_entities_after_update` AFTER UPDATE ON `entities` FOR EACH ROW BEGIN
  -- jika unit_id berubah, kurangi count di OLD.unit_id dan tambahkan di NEW.unit_id
  IF OLD.unit_id <> NEW.unit_id THEN
    UPDATE units SET entity_count = GREATEST(0, entity_count - 1) WHERE id = OLD.unit_id;
    UPDATE units SET entity_count = entity_count + 1 WHERE id = NEW.unit_id;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tickets`
--

CREATE TABLE `tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `ticket_no` varchar(40) NOT NULL,
  `reporter_id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(10) UNSIGNED NOT NULL,
  `entity_id` int(10) UNSIGNED NOT NULL,
  `problem_type` enum('software','hardware','internet','accessories') NOT NULL,
  `problem_detail` text NOT NULL,
  `phone_number` varchar(30) DEFAULT NULL,
  `status` enum('open','waiting','confirmed','closed','cancelled') NOT NULL DEFAULT 'open',
  `action_taken` text DEFAULT NULL,
  `close_remarks` text DEFAULT NULL,
  `close_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_no`, `reporter_id`, `unit_id`, `entity_id`, `problem_type`, `problem_detail`, `phone_number`, `status`, `action_taken`, `close_remarks`, `close_date`, `created_at`, `updated_at`) VALUES
(1, 'TKT/NIP/0001/25', 3, 3, 15, 'hardware', 'Laptop mengalami Blue Screen', '082251548898', 'closed', '[2025-11-26 08:15:43] EOS PALANGKA RAYA: Cleaning RAM dan SSD pada Laptop User', 'OK Solved. Cleaning RAM dan SSD pada Laptop User (Solved by EOS)', '2025-11-26 14:24:21', '2025-11-26 04:35:49', '2025-11-26 07:24:21'),
(2, 'TKT/NIP/0002/25', 3, 3, 17, 'hardware', 'Laptop mengalami Blue Screen', '082251548898', 'closed', '[2025-11-26 08:16:47] EOS PALANGKA RAYA: Cleaning RAM dan SSD pada Laptop User', 'OK Solved. Cleaning RAM dan SSD pada Laptop User (Solved by EOS)', '2025-11-26 14:21:42', '2025-11-26 04:39:25', '2025-11-26 07:21:42'),
(3, 'TKT/NIP/0003/25', 3, 3, 19, 'hardware', 'Printer Inbox Full : Error Notif', '082251548898', 'closed', '[2025-11-26 08:18:52] EOS PALANGKA RAYA: Reset pada Printer melalui Mode Maintenance', 'OK Solved. Reset pada Printer (Solved by EOS)', '2025-11-26 14:22:25', '2025-11-26 04:40:45', '2025-11-26 07:22:25'),
(4, 'TKT/NIP/0004/25', 3, 3, 20, 'software', 'Driver Printer Error', '082251548898', 'closed', '[2025-11-26 14:46:08] EOS PALANGKA RAYA: Install Ulang Driver Printer', 'Install Ulang Driver Printer (Solved by EOS)', '2025-11-26 14:50:35', '2025-11-26 04:41:29', '2025-11-26 07:50:35'),
(5, 'TKT/NIP/0005/25', 3, 3, 2, 'software', 'Reinstall Aplikasi WPS Office', '082251548898', 'closed', '[2025-11-26 14:46:37] EOS PALANGKA RAYA: Done Reinstall WPS Office pada PC User', 'Reinstall WPS Office (Solved by EOS)', '2025-11-26 14:49:45', '2025-11-26 04:42:48', '2025-11-26 07:49:45'),
(6, 'TKT/NIP/0006/25', 3, 3, 1, 'hardware', 'Upgrade RAM & SSD', '082251548898', 'closed', '[2025-11-26 14:48:17] EOS PALANGKA RAYA: Done Upgrade RAM dan SSD pada PC User. 64 GB/1 TB', 'OK Done Full Upgrade PC (Solved by EOS)', '2025-11-26 14:50:11', '2025-11-26 05:28:00', '2025-11-26 07:50:11'),
(7, 'TKT/NIP/0007/25', 3, 3, 5, 'hardware', 'Monitor mengalami Blank Screen', '082251548898', 'closed', '[2025-11-27 13:12:29] EOS PALANGKA RAYA: Replace Monitor dengan Unit Buffer', 'Replace Monitor Unit Buffer (Solved by EOS)', '2025-11-27 13:14:19', '2025-11-26 05:29:44', '2025-11-27 06:14:19'),
(8, 'TKT/NIP/0008/25', 3, 3, 6, 'hardware', 'Glitch hitam putih pada Layar Monitor', '082251548898', 'closed', '[2025-11-27 13:12:47] EOS PALANGKA RAYA: Replace dengan Monitor Buffer Unit', 'Replace dengan Monitor Buffer Unit (Solved by EOS)', '2025-11-27 13:14:45', '2025-11-26 05:30:16', '2025-11-27 06:14:45'),
(9, 'TKT/NIP/0009/25', 4, 4, 21, 'internet', 'LAN tidak terdeteksi', '081250504040', 'closed', '[2025-11-27 14:20:04] EOS BANJARMASIN: Reinstall Driver LAN dan Kabel LAN sudah di amankan (Solved)', 'Reinstall Driver & Secure LAN Cable (Solved by EOS)', '2025-11-27 14:33:30', '2025-11-27 06:54:43', '2025-11-27 07:33:30'),
(10, 'TKT/NIP/0010/25', 4, 4, 23, 'accessories', 'Keyboard dan Mouse tidak terdeteksi pada CPU', '081250504040', 'closed', '[2025-11-27 14:20:24] EOS BANJARMASIN: Reinstall Driver USB dan berhasil terdeteksi (Solved)', 'Reinstall Driver (Solved by EOS)', '2025-11-27 14:33:01', '2025-11-27 06:55:36', '2025-11-27 07:33:01'),
(13, 'TKT/NIP/0011/25', 5, 5, 44, 'software', 'Driver Printer Usang', '081315151616', 'closed', '[2025-11-27 15:11:50] EOS BUNTOK: Done Reinstall Driver Printer versi terbaru', 'Reinstall Driver Printer (Solved by EOS)', '2025-11-27 15:13:42', '2025-11-27 07:31:42', '2025-11-27 08:13:42'),
(14, 'TKT/NIP/0012/25', 5, 5, 39, 'hardware', 'Keyborad tidak berfungsi sama sekali', '081315151616', 'closed', '[2025-11-27 15:12:21] EOS BUNTOK: Laptop kita bawa ke HP Service Center dengan estimasi 3 Hari\r\n[2025-11-28 08:42:54] EOS BUNTOK: Done Keyboard sudah berfungsi Normal. Selesai Service pada HP SC', 'Service Keyboard pada HP SC (Solved by EOS)', '2025-11-28 08:48:31', '2025-11-27 07:32:07', '2025-11-28 01:48:31'),
(15, 'TKT/NIP/0013/25', 6, 6, 48, 'hardware', 'Monitor glitch hitam putih', '0815889090', 'closed', '[2025-11-28 10:37:52] EOS TAMIANG LAYANG: Bersihkan Kabel HDMI kemudian Normal kembali', 'Bersihkan Kabel HDMI. (Solved by EOS)', '2025-11-28 10:48:17', '2025-11-28 03:37:34', '2025-11-28 03:48:17'),
(16, 'TKT/NIP/0014/25', 6, 6, 54, 'software', 'Driver tidak terdeteksi', '0815889090', 'closed', '[2025-11-28 15:42:30] EOS TAMIANG LAYANG: Reinstall Driver Printer EPSON', 'Reinstall Driver Printer (Solved by EOS)', '2025-11-28 15:43:46', '2025-11-28 08:41:52', '2025-11-28 08:43:46'),
(17, 'TKT/NIP/0015/25', 7, 7, 63, 'internet', 'WiFi tidak terdeteksi sama sekali', '082150506060', 'closed', '[2025-12-01 12:42:26] EOS MUARA TEWEH: Reinstall Driver WiFi pada Laptop dan kembali normal', 'Reinstall Driver WiFi (Solved by EOS)', '2025-12-01 13:23:56', '2025-12-01 05:04:23', '2025-12-01 06:23:56'),
(18, 'TKT/NIP/0016/25', 7, 7, 61, 'accessories', 'Kabel HDMI putus', '082150506060', 'closed', '[2025-12-01 12:43:11] EOS MUARA TEWEH: Replace dengan Kabel VGA dan kembali aman', 'Replace Kabel VGA (Solved by EOS)', '2025-12-01 13:24:42', '2025-12-01 05:04:57', '2025-12-01 06:24:42'),
(19, 'TKT/NIP/0017/25', 7, 7, 59, 'accessories', 'Mouse tidak berfungsi dan USB tidak detect', '082150506060', 'closed', '[2025-12-01 12:43:50] EOS MUARA TEWEH: Reinstall Driver USB dan replace slot USB pada tempat lainnya (Solved)', 'Reinstall Driver USB pada PC (Solved by EOS)', '2025-12-01 13:29:03', '2025-12-01 05:05:59', '2025-12-01 06:29:03'),
(20, 'TKT/NIP/0018/25', 7, 7, 66, 'software', 'Opsi Menu Maintenance Printer pada Printer Preferences tidak muncul', '082150506060', 'closed', '[2025-12-01 12:44:08] EOS MUARA TEWEH: Reinstall Driver Printer EPSON', 'Reinstall Driver Printer EPSON', '2025-12-01 13:29:38', '2025-12-01 05:06:55', '2025-12-01 06:29:38'),
(21, 'TKT/NIP/0019/25', 5, 5, 33, 'internet', 'Internet tidak bisa tersambung padahal kondisi Kabel LAN aman', '081315151616', 'closed', '[2025-12-01 13:34:34] EOS BUNTOK: Konfigurasi Setting IPv4 Address pada PC User', 'Setting IPv4 Address pada PC User (Solved by EOS)', '2025-12-01 13:43:49', '2025-12-01 05:10:35', '2025-12-01 06:43:49'),
(22, 'TKT/NIP/0020/25', 5, 5, 34, 'accessories', 'Keyboard pada PC tidak berfungsi dan tidak detect USB', '081315151616', 'closed', '[2025-12-01 13:35:06] EOS BUNTOK: Ganti pada Slot USB lainnya dan ganti menggunakan USB Terminal Hub 3.0', 'Ganti USB Terminal Hub 3.0 (Solved by EOS)', '2025-12-01 13:44:23', '2025-12-01 05:11:16', '2025-12-01 06:44:23'),
(23, 'TKT/NIP/0021/25', 4, 4, 24, 'accessories', 'Kabel HDMI kotor boss', '0815889090', 'closed', '[2025-12-01 13:37:34] EOS BANJARMASIN: Done bersihkan Kabel HDMI', 'Bersihkan Kabel HDMI (Solved by EOS)', '2025-12-01 13:45:14', '2025-12-01 05:14:21', '2025-12-01 06:45:14'),
(24, 'TKT/NIP/0022/25', 4, 4, 28, 'accessories', 'Mouse pada Laptop tidak detect USB', '0815889090', 'closed', '[2025-12-01 13:37:55] EOS BANJARMASIN: Reinstall Driver USB pada PC User (Done)', 'Reinstall Driver USB (Solved by EOS)', '2025-12-03 16:21:00', '2025-12-01 05:15:11', '2025-12-03 09:21:00'),
(25, 'TKT/NIP/0023/25', 6, 6, 56, 'accessories', 'Kabel USB Printer tidak berfungsi', '082251548898', 'closed', '[2025-12-01 13:39:53] EOS TAMIANG LAYANG: Ganti Kabel USB Printer dan kembali normal', 'Ganti Kabel USB Printer (Solved by EOS)', '2025-12-01 13:45:33', '2025-12-01 05:17:05', '2025-12-01 06:45:33'),
(26, 'TKT/NIP/0024/25', 6, 6, 53, 'software', 'Ganti Aplikasi Office dari WPS ke Microsoft (User Request)', '082251548898', 'closed', '[2025-12-01 13:40:16] EOS TAMIANG LAYANG: Done Instalasi Aplikasi Microsoft Office 2024 LTSC', 'Instalasi Microsot Office 2024 LTSC (Solved by EOS)', '2025-12-01 13:46:04', '2025-12-01 05:17:51', '2025-12-01 06:46:04'),
(27, 'TKT/NIP/0025/25', 3, 3, 20, 'hardware', 'In Box Full - Printer Trouble Error Code', '082251548898', 'closed', '[2025-12-01 13:42:10] EOS PALANGKA RAYA: Reset Printer Epson pada Mode Maintenance', 'Reset Printer EPSON (Solved by EOS)', '2025-12-01 13:51:55', '2025-12-01 05:24:32', '2025-12-01 06:51:55'),
(28, 'TKT/NIP/0026/25', 3, 3, 17, 'software', 'Install Ulang Windows 11 25H2 Latest Version Updates', '082251548898', 'closed', '[2025-12-01 13:42:22] EOS PALANGKA RAYA: Done Install Ulang', 'Instal Ulang Windows (Solved by EOS)', '2025-12-01 13:52:30', '2025-12-01 05:25:17', '2025-12-01 06:52:30'),
(29, 'TKT/NIP/0027/25', 3, 3, 5, 'accessories', 'Kabel HDMI tidak berfungsi', '082251548898', 'closed', '[2025-12-01 13:42:37] EOS PALANGKA RAYA: Replace dengan Kabel HDMI lainnya dan berhasil', 'Replace Kabel HDMI (Solved by EOS)', '2025-12-01 13:52:48', '2025-12-01 05:26:00', '2025-12-01 06:52:48'),
(30, 'TKT/NIP/0028/25', 7, 7, 60, 'hardware', 'Layar Monitor Hitam Putih', '082150506060', 'closed', '[2025-12-01 13:41:09] EOS MUARA TEWEH: Replace dengan Monitor Buffer Unit', 'Replace dengan Buffer Unit (Solved by EOS)', '2025-12-01 13:53:12', '2025-12-01 05:26:47', '2025-12-01 06:53:12'),
(31, 'TKT/NIP/0029/25', 4, 4, 23, 'software', 'Install Ulang Windows 11 25H2 Latest Version Updates', '0815889090', 'closed', '[2025-12-01 13:38:07] EOS BANJARMASIN: Done Install Ulang', 'Install Ulang Windows (Solved by EOS)', '2025-12-01 13:53:32', '2025-12-01 05:49:08', '2025-12-01 06:53:32'),
(32, 'TKT/NIP/0030/25', 4, 4, 29, 'software', 'Install Ulang Windows 11 25H2 Latest Version Updates', '0815889090', 'closed', '[2025-12-01 13:38:18] EOS BANJARMASIN: Done Install Ulang', 'Install Ulang Windows (Solved by EOS)', '2025-12-01 13:53:42', '2025-12-01 05:49:27', '2025-12-01 06:53:42'),
(33, 'TKT/NIP/0031/25', 5, 5, 42, 'accessories', 'Kabel USB Printer putus', '082150506060', 'closed', '[2025-12-03 13:29:04] EOS BUNTOK: Replace Kabel USB Printer', 'Replace USB Kabel (Solved by EOS)', '2025-12-03 13:30:19', '2025-12-03 06:17:34', '2025-12-03 06:30:19'),
(34, 'TKT/NIP/0032/25', 6, 6, 49, 'hardware', 'Blank Screen pada Monitor', '0815889090', 'closed', '[2025-12-03 13:28:25] EOS TAMIANG LAYANG: Replace dengan Monitor Unit Buffer', 'Replace Unit Buffer (Solved by EOS)', '2025-12-04 09:19:42', '2025-12-03 06:18:34', '2025-12-04 03:33:32'),
(35, 'TKT/NIP/0033/25', 7, 7, 58, 'software', 'Install Ulang Windows 11 25H2', '081315151616', 'closed', '[2025-12-04 10:34:32] EOS MUARA TEWEH: Done Install Ulang Windows', 'Done Install Ulang Windows 11 25H2 (Solved by EOS)', '2025-12-04 10:47:13', '2025-12-04 03:34:16', '2025-12-04 03:47:13'),
(36, 'TKT/NIP/0034/25', 7, 7, 65, 'software', 'Install Ulang Windows 11 25H2', '081315151616', 'closed', '[2025-12-04 10:35:32] EOS MUARA TEWEH: Done Install Ulang Windows', 'Done Install Ulang Windows 11 25H2 (Solved by EOS)', '2025-12-04 10:46:54', '2025-12-04 03:35:14', '2025-12-04 03:46:54'),
(37, 'TKT/NIP/0035/25', 3, 3, 3, 'hardware', 'Upgrade RAM 64 GB dan SSD 1 TB', '082251548898', 'closed', '[2025-12-04 10:36:43] EOS PALANGKA RAYA: Done Full Upgrade Boss', 'Ok Done Full Upgrade (Solved by EOS)', '2025-12-04 10:46:31', '2025-12-04 03:36:29', '2025-12-04 03:46:31'),
(38, 'TKT/NIP/0036/25', 3, 3, 17, 'hardware', 'Upgrade RAM 64 GB dan SSD 1 TB', '082251548898', 'closed', '[2025-12-04 10:37:31] EOS PALANGKA RAYA: Done Laptop Full Upgrade juga Boss', 'Ok Done Full Upgrade (Solved by EOS)', '2025-12-04 10:46:10', '2025-12-04 03:37:11', '2025-12-04 03:46:10');

--
-- Trigger `tickets`
--
DELIMITER $$
CREATE TRIGGER `trg_tickets_after_delete` AFTER DELETE ON `tickets` FOR EACH ROW BEGIN
  UPDATE entities SET jumlah_ticket = GREATEST(0, jumlah_ticket - 1) WHERE id = OLD.entity_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tickets_after_insert` AFTER INSERT ON `tickets` FOR EACH ROW BEGIN
  UPDATE entities SET jumlah_ticket = jumlah_ticket + 1 WHERE id = NEW.entity_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tickets_after_update` AFTER UPDATE ON `tickets` FOR EACH ROW BEGIN
  -- jika entity_id berubah, adjust counts
  IF OLD.entity_id <> NEW.entity_id THEN
    UPDATE entities SET jumlah_ticket = GREATEST(0, jumlah_ticket - 1) WHERE id = OLD.entity_id;
    UPDATE entities SET jumlah_ticket = jumlah_ticket + 1 WHERE id = NEW.entity_id;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `units`
--

CREATE TABLE `units` (
  `id` int(10) UNSIGNED NOT NULL,
  `unit_id` varchar(50) NOT NULL,
  `nama_unit` varchar(200) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kab_kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `tat_target` int(10) UNSIGNED DEFAULT NULL,
  `entity_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `units`
--

INSERT INTO `units` (`id`, `unit_id`, `nama_unit`, `alamat`, `kab_kota`, `provinsi`, `tat_target`, `entity_count`, `created_at`, `updated_at`) VALUES
(3, '0001', 'KANTOR CABANG PALANGKA RAYA', 'Jl. RTA Milono Km. 5 No. 10', 'PALANGKA RAYA', 'KALIMANTAN TENGAH', 2, 12, '2025-11-21 02:47:45', '2025-11-24 05:43:24'),
(4, '0002', 'KANTOR CABANG BANJARMASIN', 'Jl. SULTAN HASSANUDIN No. 15', 'BANJARMASIN', 'KALIMANTAN SELATAN', 3, 12, '2025-11-21 03:18:41', '2025-11-24 06:51:32'),
(5, '0003', 'KANTOR CABANG BUNTOK', 'Jl. PANGLIMA BATUR Gg. KEPASTURAN No. 2', 'BARITO SELATAN', 'KALIMANTAN TENGAH', 5, 12, '2025-11-21 04:11:04', '2025-11-25 02:43:26'),
(6, '0004', 'KANTOR CABANG TAMIANG LAYANG', 'Jl. NANSARUNAI No. 25', 'BARITO TIMUR', 'KALIMANTAN TENGAH', 7, 12, '2025-11-21 05:07:34', '2025-11-25 03:00:22'),
(7, '0005', 'KANTOR CABANG MUARA TEWEH', 'Jl. PANGERAN DIPONEGORO No. 55', 'BARITO UTARA', 'KALIMANTAN TENGAH', 10, 12, '2025-11-21 05:12:26', '2025-12-03 06:44:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','project','engineer') NOT NULL DEFAULT 'engineer',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TERTU AKIKKUTI JORDAN', 'tertu', '$2y$10$PXoHlwwRWAWydbHsyjN9GOkpMR5NI5n0pAlrKc4/ATp9XFZN3RHsq', 'admin', 1, '2025-11-20 04:37:35', '2025-11-20 04:37:35'),
(2, 'FRINCE MIGUEL RAMIREZ', 'frince', '$2y$10$duoLjpu.DjHtE85CrcC/.O7rTRQzI3CK9TRumnZsVkvHTPZgbR83y', 'project', 1, '2025-11-20 06:50:53', '2025-11-20 06:50:53'),
(3, 'EOS PALANGKA RAYA', 'eosplk', '$2y$10$pUs.nVLwywCuKXdZ8I3EKewIEZN.dX55EmMyQGeE7Ul50i3gLpU4W', 'engineer', 1, '2025-11-25 03:36:42', '2025-11-25 03:36:42'),
(4, 'EOS BANJARMASIN', 'eosbjm', '$2y$10$6R.QdSDvUn.QVqMTDADUO.oOsmojV2hAyCrdJuGhhMsikDnze5DlW', 'engineer', 1, '2025-11-25 05:59:32', '2025-11-25 05:59:32'),
(5, 'EOS BUNTOK', 'eosbtk', '$2y$10$LCwAipwcYFbfu6lMBwQJlOWg9JfAL7TS3737ql8hNHg6so46RPMFu', 'engineer', 1, '2025-11-25 06:00:10', '2025-11-25 06:00:10'),
(6, 'EOS TAMIANG LAYANG', 'eostml', '$2y$10$jnYq.LNVLU2tBVweMrlqtuexsAdQBW8ip3WD3N.TsmfQUpQgbkPzG', 'engineer', 1, '2025-11-25 06:00:44', '2025-11-25 06:00:44'),
(7, 'EOS MUARA TEWEH', 'eosmth', '$2y$10$q2hUqdWPYlyLWR/ULQrIC.1.k03VrIkJZh9ypyV/JbYfDyj4frCiK', 'engineer', 1, '2025-11-25 06:01:20', '2025-11-25 06:01:20'),
(12, 'LOORAND SPOOFY', 'loorand', '$2y$10$t2B4YIn9xRJP8mo7TXRYKOOgScqdnYPU0FQPDMkXQysU8aL3bmrHm', 'project', 1, '2025-12-01 05:50:38', '2025-12-01 05:50:38');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `vw_ticket_summary`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `vw_ticket_summary` (
`total_tickets` bigint(21)
,`tickets_open` bigint(21)
,`tickets_closed` bigint(21)
,`total_engineers` bigint(21)
,`total_units` bigint(21)
,`total_entities` bigint(21)
);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `entities`
--
ALTER TABLE `entities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entities_unit` (`unit_id`);

--
-- Indeks untuk tabel `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_no` (`ticket_no`),
  ADD KEY `idx_tickets_reporter` (`reporter_id`),
  ADD KEY `idx_tickets_unit` (`unit_id`),
  ADD KEY `idx_tickets_entity` (`entity_id`),
  ADD KEY `idx_tickets_status` (`status`),
  ADD KEY `idx_tickets_created_at` (`created_at`);

--
-- Indeks untuk tabel `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_unit_nama` (`nama_unit`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `entities`
--
ALTER TABLE `entities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT untuk tabel `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `units`
--
ALTER TABLE `units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

-- --------------------------------------------------------

--
-- Struktur untuk view `vw_ticket_summary`
--
DROP TABLE IF EXISTS `vw_ticket_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_ticket_summary`  AS SELECT (select count(0) from `tickets`) AS `total_tickets`, (select count(0) from `tickets` where `tickets`.`status` = 'open') AS `tickets_open`, (select count(0) from `tickets` where `tickets`.`status` = 'closed') AS `tickets_closed`, (select count(0) from `users` where `users`.`role` = 'engineer') AS `total_engineers`, (select count(0) from `units`) AS `total_units`, (select count(0) from `entities`) AS `total_entities` ;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `entities`
--
ALTER TABLE `entities`
  ADD CONSTRAINT `fk_entities_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_entity` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tickets_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
