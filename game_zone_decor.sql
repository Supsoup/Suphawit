-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 10:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `game_zone_decor`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('SUPERADMIN','STAFF') DEFAULT 'STAFF',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `first_name`, `last_name`, `gender`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Suphawit Saetang', 'Suphawit', 'Saetang', 'MALE', 'suphawit.sa@ku.th', '123', 'SUPERADMIN', '2025-09-04 17:59:40');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'โต๊ะเกมมิ่ง', '2025-09-04 15:00:24'),
(2, 'เก้าอี้เกมมิ่ง', '2025-09-04 15:00:24'),
(3, 'ไฟ RGB', '2025-09-04 15:00:24'),
(4, 'ที่ตั้งหูฟัง', '2025-09-04 15:00:24'),
(5, 'ที่รองเมาส์', '2025-09-04 15:00:24');

-- --------------------------------------------------------

--
-- Table structure for table `forgot_tokens`
--

CREATE TABLE `forgot_tokens` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `ip` varchar(64) DEFAULT NULL,
  `ua` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `code`, `name`) VALUES
(1, 'FPS', 'FPS / ยิงมุมมองบุคคลที่หนึ่ง'),
(2, 'MMORPG', 'MMORPG / เก็บเลเวล'),
(3, 'MOBA', 'MOBA'),
(4, 'BR', 'Battle Royale'),
(5, 'RACING', 'Racing'),
(6, 'SIM', 'Simulation'),
(7, 'FIGHT', 'Fighting'),
(8, 'STRAT', 'Strategy');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('PENDING_PAYMENT','PAID_CHECKING','PAID_CONFIRMED','SHIPPING','COMPLETED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'PENDING_PAYMENT',
  `cancel_reason` varchar(255) DEFAULT NULL,
  `courier` varchar(100) DEFAULT NULL,
  `tracking_no` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `placed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `shipping_name` varchar(120) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_phone` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `cancel_reason`, `courier`, `tracking_no`, `total_amount`, `placed_at`, `expires_at`, `shipping_name`, `shipping_address`, `shipping_phone`) VALUES
(1, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 2190.00, '2025-09-04 13:33:39', '2025-09-04 20:43:39', 'focus', 'socute', '0863320431'),
(2, 2, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 2190.00, '2025-09-04 13:36:29', '2025-09-04 20:46:29', 'ssssasd', 'asd', '0863320431'),
(3, 1, 'COMPLETED', NULL, NULL, NULL, 2190.00, '2025-09-04 13:50:29', '2025-09-04 21:00:29', 'ddd', 'sss', '0863320431'),
(4, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1290.00, '2025-09-15 04:41:27', '2025-09-15 11:51:27', 'asdasdasd', 'asdasdadsasdads', '0863320431'),
(5, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 890.00, '2025-10-02 15:34:42', '2025-10-02 22:44:42', 'focus', 'ฟหกกกกกกกก', '0863320431'),
(6, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1290.00, '2025-10-02 15:36:51', '2025-10-02 22:46:51', 'ฟหก', 'ฟหก', 'ฟหก'),
(7, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 349.00, '2025-10-02 15:38:53', '2025-10-02 22:48:53', 'focus', 'ฟหก', '0863320431'),
(8, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 2190.00, '2025-10-02 15:47:54', '2025-10-02 22:57:54', 'focus', 'กฟหหฟก', '0863320431'),
(9, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1290.00, '2025-10-02 15:50:01', '2025-10-02 23:00:01', 'focus', 'ภ-ถ-ภถ', '0863320431'),
(10, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1190.00, '2025-10-02 21:05:48', '2025-10-03 04:15:48', 'Focus', 'กกก', '0863329431'),
(11, 3, 'CANCELLED', 'INVALID_PAYMENT', NULL, NULL, 690.00, '2025-10-02 21:08:09', '2025-10-03 04:18:09', 'Focus', 'dd', '0863329431'),
(12, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 2990.00, '2025-10-02 21:10:26', '2025-10-03 04:20:26', 'Focus', 'ภ', '0863329431'),
(13, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 2990.00, '2025-10-02 21:21:17', '2025-10-03 04:31:17', 'Focus', 'กก', '0863329431'),
(14, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 890.00, '2025-10-03 13:20:39', '2025-10-03 20:30:39', 'Focus', '43124', '0863329431'),
(15, 3, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 890.00, '2025-10-03 13:23:40', '2025-10-03 20:33:40', 'Focus', '434', '0863329431'),
(16, 3, 'SHIPPING', NULL, 'J&T', '123', 890.00, '2025-10-03 13:34:27', '2025-10-03 20:44:27', 'Focus', '4234', '0863329431'),
(17, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1.00, '2025-10-03 13:48:06', '2025-10-03 20:58:06', 'Focus2', 'asdasd', '0863329431'),
(18, 1, 'PAID_CHECKING', NULL, NULL, NULL, 1.00, '2025-10-03 13:49:24', '2025-10-03 20:59:24', 'Focus2', 'asdasd', '0863329431'),
(19, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 890.00, '2025-10-08 19:34:06', '2025-10-09 02:44:06', 'Focus2', 'กดดกหกหกด', '0863329431'),
(20, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-08 21:23:15', '2025-10-09 04:33:15', 'Focus2', 'asd', '0863329431'),
(21, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-08 21:24:34', '2025-10-09 04:34:34', 'Focus2', 'asd', '0863329431'),
(22, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 890.00, '2025-10-08 21:41:47', '2025-10-09 04:51:47', 'Focus2', 'ฟหกฟหก', '0863329431'),
(23, 1, 'CANCELLED', NULL, NULL, NULL, 1190.00, '2025-10-08 22:07:42', '2025-10-09 05:17:42', 'Focus2', 'asdasd', '0863320431'),
(24, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1190.00, '2025-10-08 22:08:07', '2025-10-09 05:18:07', 'Focus2', 'asdasd', '0863320431'),
(25, 1, 'CANCELLED', NULL, NULL, NULL, 1190.00, '2025-10-08 22:19:13', '2025-10-09 05:29:13', 'Focus2', 'asdasd', '0863320431'),
(26, 1, 'CANCELLED', NULL, NULL, NULL, 349.00, '2025-10-08 22:19:43', '2025-10-09 05:29:43', 'Focus2', 'asdasd', '0863320431'),
(27, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1190.00, '2025-10-08 22:20:13', '2025-10-09 05:30:13', 'Focus2', 'asdasd', '0863320431'),
(28, 1, 'COMPLETED', NULL, NULL, NULL, 1190.00, '2025-10-08 22:46:14', '2025-10-09 05:56:14', 'Focus2', 'asdasd', '0863320431'),
(29, 1, 'CANCELLED', NULL, NULL, NULL, 120.00, '2025-10-09 03:58:07', '2025-10-09 11:08:07', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(30, 1, 'COMPLETED', NULL, NULL, NULL, 120.00, '2025-10-09 04:02:29', '2025-10-09 11:12:29', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(31, 1, 'PAID_CHECKING', NULL, NULL, NULL, 120.00, '2025-10-09 06:56:11', '2025-10-09 14:06:11', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(32, 1, 'CANCELLED', NULL, NULL, NULL, 120.00, '2025-10-09 06:59:30', '2025-10-09 14:09:30', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(33, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 890.00, '2025-10-09 06:59:42', '2025-10-09 14:09:42', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(34, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-15 19:39:59', '2025-10-16 02:49:59', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(35, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-15 19:44:36', '2025-10-16 02:54:36', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(36, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-15 19:52:14', '2025-10-16 03:02:14', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(37, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-15 19:56:42', '2025-10-16 03:06:42', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(38, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-15 20:31:03', '2025-10-16 03:41:03', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(39, 1, 'SHIPPING', NULL, NULL, NULL, 890.00, '2025-10-15 20:49:11', '2025-10-16 03:59:11', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(40, 1, 'PAID_CHECKING', NULL, NULL, NULL, 120.00, '2025-10-15 21:02:14', '2025-10-16 04:12:14', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(41, 1, 'CANCELLED', NULL, NULL, NULL, 890.00, '2025-10-15 21:02:36', '2025-10-16 04:12:36', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(42, 1, 'CANCELLED', NULL, NULL, NULL, 120.00, '2025-10-15 21:03:01', '2025-10-16 04:13:01', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(43, 1, 'PAID_CHECKING', NULL, NULL, NULL, 890.00, '2025-10-15 21:07:44', '2025-10-16 04:17:44', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(44, 4, 'CANCELLED', NULL, NULL, NULL, 360.00, '2025-10-16 06:16:58', '2025-10-16 13:26:58', 'Focus1', 'awdawdwadwadasdd', '0863320431'),
(45, 4, 'PAID_CONFIRMED', NULL, NULL, NULL, 360.00, '2025-10-16 06:17:40', '2025-10-16 13:27:40', 'Focus1', 'awdawdwadwadasdd', '0863320431'),
(46, 4, 'PAID_CHECKING', NULL, NULL, NULL, 120.00, '2025-10-16 06:25:39', '2025-10-16 13:35:39', 'Focus1', 'awdawdwadwadasdd', '0863320431'),
(47, 4, 'CANCELLED', NULL, NULL, NULL, 120.00, '2025-10-16 06:26:52', '2025-10-16 13:36:52', 'Focus1', 'awdawdwadwadasdd', '0863320431'),
(48, 4, '', NULL, NULL, NULL, 47840.00, '2025-10-16 06:50:44', '2025-10-16 14:00:44', 'Focus1', 'awdawdwadwadasdd', '0863320431'),
(49, 4, 'CANCELLED', NULL, NULL, NULL, 690.00, '2025-10-16 06:52:03', '2025-10-16 14:02:03', 'Focus1', 'awdawdwadwadasdd', '0863320431'),
(50, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 120.00, '2025-10-29 04:45:51', '2025-10-29 11:55:51', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(51, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 360.00, '2025-10-29 04:59:52', '2025-10-29 12:09:52', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(52, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 240.00, '2025-10-29 05:07:38', '2025-10-29 12:17:38', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(53, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 6180.00, '2025-10-29 05:13:41', '2025-10-29 12:23:41', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(54, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 4380.00, '2025-10-29 05:44:07', '2025-10-29 12:54:07', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(55, 1, 'CANCELLED', NULL, NULL, NULL, 2190.00, '2025-10-29 05:50:42', '2025-10-29 13:00:42', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(56, 1, 'PAID_CONFIRMED', NULL, NULL, NULL, 4380.00, '2025-10-29 05:51:32', '2025-10-29 13:01:32', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(57, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 4380.00, '2025-10-29 06:00:09', '2025-10-29 13:10:09', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(58, 1, 'CANCELLED', NULL, NULL, NULL, 8970.00, '2025-10-29 06:19:43', '2025-10-29 13:29:43', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(59, 1, 'EXPIRED', 'TIMEOUT_BY_SYSTEM', NULL, NULL, 1047.00, '2025-10-29 06:22:47', '2025-10-29 13:32:47', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(60, 1, 'CANCELLED', NULL, NULL, NULL, 2670.00, '2025-10-29 06:29:06', '2025-10-29 13:39:06', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(61, 1, 'CANCELLED', NULL, NULL, NULL, 360.00, '2025-10-29 15:27:01', '2025-10-29 22:37:01', 'ศุภวิชญ์ โฟกัส', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(62, 1, 'CANCELLED', NULL, NULL, NULL, 3990.00, '2025-11-06 07:39:13', '2025-11-06 14:49:13', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(63, 1, 'PAID_CONFIRMED', NULL, NULL, NULL, 3990.00, '2025-11-06 07:40:21', '2025-11-06 14:50:21', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431'),
(64, 1, 'CANCELLED', NULL, NULL, NULL, 120.00, '2025-11-06 08:28:31', '2025-11-06 15:38:31', 'ศุภวิชญ์ แซ่ตั้ง', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '0863320431');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `price`, `unit_price`, `line_total`) VALUES
(1, 1, 2, 1, 2190.00, 2190.00, 2190.00),
(2, 2, 2, 1, 2190.00, 2190.00, 2190.00),
(3, 3, 2, 1, 2190.00, 2190.00, 2190.00),
(4, 4, 5, 1, 1290.00, 1290.00, 1290.00),
(5, 5, 14, 1, 890.00, 890.00, 890.00),
(6, 6, 5, 1, 1290.00, 1290.00, 1290.00),
(7, 7, 4, 1, 349.00, 349.00, 349.00),
(8, 8, 2, 1, 2190.00, 2190.00, 2190.00),
(9, 9, 5, 1, 1290.00, 1290.00, 1290.00),
(10, 10, 10, 1, 1190.00, 1190.00, 1190.00),
(11, 11, 11, 1, 690.00, 690.00, 690.00),
(12, 12, 6, 1, 2990.00, 2990.00, 2990.00),
(13, 13, 6, 1, 2990.00, 2990.00, 2990.00),
(14, 14, 14, 1, 890.00, 890.00, 890.00),
(15, 15, 14, 1, 890.00, 890.00, 890.00),
(17, 17, 15, 1, 120.00, 1.00, 1.00),
(18, 18, 15, 1, 120.00, 1.00, 1.00),
(19, 19, 14, 1, 890.00, 890.00, 890.00),
(20, 20, 14, 1, 890.00, 890.00, 890.00),
(21, 21, 14, 1, 890.00, 890.00, 890.00),
(22, 22, 14, 1, 890.00, 890.00, 890.00),
(23, 23, 10, 1, 1190.00, 1190.00, 1190.00),
(24, 24, 10, 1, 1190.00, 1190.00, 1190.00),
(25, 25, 10, 1, 1190.00, 1190.00, 1190.00),
(26, 26, 4, 1, 349.00, 349.00, 349.00),
(27, 27, 10, 1, 1190.00, 1190.00, 1190.00),
(28, 28, 10, 1, 1190.00, 1190.00, 1190.00),
(29, 29, 15, 1, 120.00, 120.00, 120.00),
(30, 30, 15, 1, 120.00, 120.00, 120.00),
(31, 31, 15, 1, 0.00, 120.00, 120.00),
(32, 32, 15, 1, 0.00, 120.00, 120.00),
(33, 33, 14, 1, 0.00, 890.00, 890.00),
(34, 34, 14, 1, 0.00, 890.00, 890.00),
(35, 35, 14, 1, 0.00, 890.00, 890.00),
(36, 36, 14, 1, 0.00, 890.00, 890.00),
(37, 37, 14, 1, 0.00, 890.00, 890.00),
(38, 38, 14, 1, 0.00, 890.00, 890.00),
(39, 39, 14, 1, 0.00, 890.00, 890.00),
(40, 40, 15, 1, 0.00, 120.00, 120.00),
(41, 41, 14, 1, 0.00, 890.00, 890.00),
(42, 42, 15, 1, 0.00, 120.00, 120.00),
(43, 43, 14, 1, 0.00, 890.00, 890.00),
(44, 44, 15, 3, 0.00, 120.00, 360.00),
(45, 45, 15, 3, 0.00, 120.00, 360.00),
(46, 46, 15, 1, 0.00, 120.00, 120.00),
(47, 47, 15, 1, 0.00, 120.00, 120.00),
(48, 48, 6, 16, 0.00, 2990.00, 47840.00),
(49, 49, 11, 1, 0.00, 690.00, 690.00),
(50, 50, 15, 1, 0.00, 120.00, 120.00),
(51, 51, 15, 3, 0.00, 120.00, 360.00),
(52, 52, 15, 2, 0.00, 120.00, 240.00),
(53, 53, 1, 2, 0.00, 3090.00, 6180.00),
(54, 54, 2, 2, 0.00, 2190.00, 4380.00),
(55, 55, 2, 1, 0.00, 2190.00, 2190.00),
(56, 56, 2, 2, 0.00, 2190.00, 4380.00),
(57, 57, 2, 2, 0.00, 2190.00, 4380.00),
(58, 58, 6, 3, 0.00, 2990.00, 8970.00),
(59, 59, 4, 3, 0.00, 349.00, 1047.00),
(60, 60, 9, 3, 0.00, 890.00, 2670.00),
(61, 61, 15, 3, 0.00, 120.00, 360.00),
(62, 62, 7, 1, 0.00, 3990.00, 3990.00),
(63, 63, 7, 1, 0.00, 3990.00, 3990.00),
(64, 64, 15, 1, 0.00, 120.00, 120.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `otp_code` char(6) NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `otp_code`, `token`, `expires_at`, `used_at`, `created_at`, `ip`) VALUES
(12, 1, '', '825282', '', '2025-10-09 20:47:21', NULL, '2025-10-10 01:37:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `slip_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `note` text DEFAULT NULL,
  `verified_by_admin` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `slip_path`, `uploaded_at`, `paid_at`, `note`, `verified_by_admin`, `verified_at`) VALUES
(1, 10, 'uploads/slips/SLIP_10_1759439159_6718.jpg', '2025-10-08 22:51:48', '2025-10-02 21:05:59', NULL, 1, '2025-10-02 21:06:18'),
(2, 11, 'uploads/slips/SLIP_11_1759439303_7415.jpg', '2025-10-08 22:51:48', '2025-10-02 21:08:23', NULL, NULL, NULL),
(3, 16, 'uploads/slips/SLIP_16_1759498475_5846.jpg', '2025-10-08 22:51:48', '2025-10-03 13:34:35', NULL, 1, '2025-10-03 13:34:54'),
(4, 18, 'uploads/slips/SLIP_18_1759499370_6471.jpg', '2025-10-08 22:51:48', '2025-10-03 13:49:30', NULL, NULL, NULL),
(5, 28, 'uploads/slips/order_28_1759963912.jpg', '2025-10-08 22:51:52', '2025-10-08 22:51:52', NULL, NULL, NULL),
(6, 30, 'uploads/slips/order_30_1759982578.jpg', '2025-10-09 04:02:58', '2025-10-09 04:02:58', NULL, NULL, NULL),
(7, 31, 'uploads/slips/order_31_1759993048.jpg', '2025-10-09 06:57:28', '2025-10-09 06:57:28', NULL, NULL, NULL),
(8, 39, 'uploads/slips/order_39_1760561355.jpg', '2025-10-15 20:49:15', '2025-10-15 20:49:15', NULL, NULL, NULL),
(9, 40, 'uploads/slips/order_40_1760562144.jpg', '2025-10-15 21:02:24', '2025-10-15 21:02:24', NULL, NULL, NULL),
(10, 43, 'uploads/slips/order_43_1760562726.jpg', '2025-10-15 21:12:06', '2025-10-15 21:12:06', NULL, NULL, NULL),
(11, 45, 'uploads/slips/order_45_1760595680.jpg', '2025-10-16 06:21:20', '2025-10-16 06:21:20', NULL, NULL, NULL),
(12, 46, 'uploads/slips/order_46_1760595946.jpg', '2025-10-16 06:25:46', '2025-10-16 06:25:46', NULL, NULL, NULL),
(13, 48, 'uploads/slips/order_48_1760597659.jpg', '2025-10-16 06:54:19', '2025-10-16 06:54:19', NULL, NULL, NULL),
(14, 56, 'uploads/slips/order_56_1761717357.jpg', '2025-10-29 05:55:57', '2025-10-29 05:55:57', NULL, NULL, NULL),
(15, 63, 'uploads/slips/order_63_1762414828.jpg', '2025-11-06 07:40:28', '2025-11-06 07:40:28', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand` varchar(80) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand`, `name`, `description`, `price`, `is_active`, `deleted_at`, `stock`, `image_url`, `created_at`) VALUES
(1, 2, 'Nubwo', 'Nubwo X107+ Gaming Chair Pink', 'เก้าอี้เกมมิ่งหนัง PU', 3090.00, 1, NULL, 10, 'assets/sample/nubwox107.jpg', '2025-09-04 17:38:38'),
(2, 1, 'Tengu', 'Tengu ODA Gaming Desk', 'โต๊ะ 120x60 ซม.', 2190.00, 1, NULL, 7, 'assets/sample/Tengu.jpg', '2025-09-04 17:38:38'),
(4, 3, 'EGA', 'TYPE ML1 Monitor Light Bar', 'ประเภท\r\nไฟแขวนจอคอม (lightbar)\r\n\r\nปรับอุณหภูมิแสงได้\r\nปรับได้\r\n\r\nขนาด(กว้าง x ยาว x สูง cm)\r\n8.7 x 3.2 x 50\r\n\r\nน้ำหนัก\r\n154.00 g', 349.00, 1, NULL, 11, 'uploads/products/IMG_1757013308_7158.jpg', '2025-09-04 19:15:08'),
(5, 5, 'Artisan', 'Artisan Zero XL', 'แผ่นรองเมาส์สายบาลานซ์ คุมหยุดดี เหมาะกับคอมโบ/ลากเมาส์ยาว', 1290.00, 1, NULL, 24, 'uploads/products/IMG_1757607669_9006.jpg', '2025-09-11 16:06:55'),
(6, 4, 'Razer', 'Base Station V2 Chroma', 'ที่ตั้งหูฟังมี USB Hub ช่วยจัดโต๊ะและชาร์จอุปกรณ์', 2990.00, 1, NULL, 20, 'uploads/products/IMG_1757607550_5680.jpg', '2025-09-11 16:06:55'),
(7, 2, 'Nubwo', 'Nubwo X118 Gaming Chair', 'พนักพิงปรับเอนได้ รองรับเล่นนาน ลดเมื่อยนั่งยาว', 3990.00, 1, NULL, 9, 'uploads/products/IMG_1757607503_7077.jpg', '2025-09-11 16:06:55'),
(9, 3, 'Yeelight', 'Yeelight Lightstrip 5m (MOBA)', 'แถบไฟ RGB ปรับซีนเพิ่มบรรยากาศห้องเกม', 890.00, 1, NULL, 30, 'uploads/products/IMG_1757607377_5660.jpg', '2025-09-11 16:06:55'),
(10, 5, 'SteelSeries', 'QcK Heavy XXL', 'แผ่นผ้าหนานุ่ม คุมหยุดดี เหมาะยิงแบบ micro-adjust', 1190.00, 1, NULL, 24, 'uploads/products/IMG_1757607312_6525.jpg', '2025-09-11 16:06:55'),
(11, 4, 'NZXT', 'Puck Headset Mount', 'ที่แขวนหูฟังแม่เหล็ก ประหยัดพื้นที่โต๊ะ', 690.00, 1, NULL, 25, 'uploads/products/IMG_1757607263_3011.jpg', '2025-09-11 16:06:55'),
(14, 3, 'Zengset', 'Game Wall Light', 'ไฟผนังทรงจอย เพิ่มโฟกัสและบรรยากาศสไตล์ eSports', 890.00, 1, NULL, 25, 'uploads/products/IMG_1757606995_3029.jpg', '2025-09-11 16:06:55'),
(15, 4, 'Signo', 'PINKKER HS-800P Headphone Stand Pink', 'วัสดุ : พลาสติก\r\n\r\nประเภท : ตั้งโต๊ะ\r\n\r\nน้ำหนัก : 170.00 g', 120.00, 1, NULL, 13, 'uploads/products/IMG_1759979735_7860.jpg', '2025-10-03 13:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `product_genres`
--

CREATE TABLE `product_genres` (
  `product_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_genres`
--

INSERT INTO `product_genres` (`product_id`, `genre_id`) VALUES
(1, 1),
(1, 3),
(2, 3),
(5, 3),
(6, 3),
(7, 3),
(9, 3),
(10, 1),
(11, 1),
(14, 1),
(15, 1),
(15, 2),
(15, 3),
(15, 4),
(15, 5),
(15, 6),
(15, 7),
(15, 8);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `gender` enum('MALE','FEMALE','OTHER') DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `first_name`, `last_name`, `gender`, `phone`, `email`, `password_hash`, `address`, `created_at`) VALUES
(1, 'ศุภวิชญ์ แซ่ตั้ง', 'focus', 'socute', 'MALE', '0863320431', 'chiro2546@gmail.com', 'fff123', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '2025-09-04 18:29:38'),
(2, 'กัส โฟ', '', '', NULL, '0863320431', 'focushandsome@gmail.com', '$2y$10$Gr0xYVLk53BF3w95Bp0/keXu8zgSCA3aBcOxuR/3fGEDXV3A0MfPq', 'asd', '2025-09-04 18:32:50'),
(3, 'Suphawit Saetang', '', '', 'FEMALE', '0863329431', 'furuyamans@gmail.com', '$2y$10$POS38wZpLsknCH8dVOkdBeO04tYefIREDKmMX1iMIZFCyZi.LFdKe', 'หอพักพีพีโฮม หน้ามอ  ห้องที่ 14\r\nซอยครัวเชียงเครือ\r\n510 หมู่ 1 ต.เชียงเครือ อ.เมือง จ.สกลนคร\r\n47000', '2025-10-02 13:13:10'),
(4, 'Focus1', '', '', 'MALE', '0863320431', 'chiro2025@gmail.com', '$2y$10$rFnR1EZeSdZqSZsAOby9X.BdYbfcdxZS7rihM3fS4bHmqoAJU6Oz.', 'awdawdwadwadasdd', '2025-10-16 06:10:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `forgot_tokens`
--
ALTER TABLE `forgot_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `fk_resets_user` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `verified_by_admin` (`verified_by_admin`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_genres`
--
ALTER TABLE `product_genres`
  ADD PRIMARY KEY (`product_id`,`genre_id`),
  ADD KEY `genre_id` (`genre_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `forgot_tokens`
--
ALTER TABLE `forgot_tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`verified_by_admin`) REFERENCES `admins` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
