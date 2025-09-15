-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 08:59 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sql`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT 'fas fa-folder',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `color`, `icon`, `created_at`) VALUES
(1, 'Documents', 'General documents and files', '#007bff', 'fas fa-file-alt', '2025-06-15 08:32:05'),
(2, 'Images', 'Photos and graphics', '#28a745', 'fas fa-image', '2025-06-15 08:32:05'),
(3, 'Videos', 'Video files', '#dc3545', 'fas fa-video', '2025-06-15 08:32:05'),
(4, 'Audio', 'Music and audio files', '#ffc107', 'fas fa-music', '2025-06-15 08:32:05'),
(5, 'Archives', 'Compressed files', '#6f42c1', 'fas fa-file-archive', '2025-06-15 08:32:05'),
(6, 'Presentations', 'PowerPoint and presentations', '#fd7e14', 'fas fa-presentation', '2025-06-15 08:32:05'),
(7, 'Spreadsheets', 'Excel and data files', '#20c997', 'fas fa-table', '2025-06-15 08:32:05'),
(8, 'PDFs', 'PDF documents', '#e83e8c', 'fas fa-file-pdf', '2025-06-15 08:32:05');

-- --------------------------------------------------------

--
-- Table structure for table `department_documents`
--

CREATE TABLE `department_documents` (
  `id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `downloads` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `department_documents`
--

INSERT INTO `department_documents` (`id`, `division_id`, `user_id`, `title`, `description`, `file_path`, `original_name`, `file_type`, `file_size`, `tags`, `is_public`, `downloads`, `created_at`, `updated_at`) VALUES
(1, 11, 1, 'Files', 'fweg', '../uploads/department/deptdoc_6880a548ad67b.jpg', 'viber_image_2025-07-18_10-25-05-419.jpg', 'jpg', 181021, '3whg3wh', 1, 0, '2025-07-23 17:03:04', '2025-07-23 17:37:36'),
(4, 11, 1, 'gwegwe', 'eggg', '../uploads/department/deptdoc_6880b13211270.pdf', 'DigitalOcean Invoice Preview 2025 Jul 1-22 (18738478-521341628).pdf', 'pdf', 40682, 'ggwg', 0, 0, '2025-07-23 17:53:54', '2025-07-23 17:53:54'),
(5, 11, 1, 'h5j', 'jrsj', '../uploads/department/deptdoc_6880b17155bd0.pdf', 'RFQ No. 2025-014 Supply and Delivery of Office Supplies.pdf', 'pdf', 685512, 'jrsjrsj', 0, 0, '2025-07-23 17:54:57', '2025-07-23 17:54:57'),
(7, 11, 4, 'h3qh3q', 'w4y43y', '../uploads/department/deptdoc_6880b48f12a80.pdf', 'RFQ No. 2025-014 Supply and Delivery of Office Supplies.pdf', 'pdf', 685512, '3h3qh', 0, 0, '2025-07-23 18:08:15', '2025-07-23 18:08:15'),
(8, 11, 18, 'WGEW', 'GWGE', '../uploads/department/deptdoc_6880ba446bba6.pdf', 'DigitalOcean Invoice Preview 2025 Jul 1-22 (18738478-521341628).pdf', 'pdf', 40682, 'GWG', 0, 0, '2025-07-23 18:32:36', '2025-07-23 18:32:36'),
(9, 4, 18, 'check', 'check', '../uploads/department/deptdoc_68bfb6e4b8bbd.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'check', 1, 0, '2025-09-09 13:11:00', '2025-09-09 13:11:00'),
(10, 3, 18, 'check1', 'adwqwd', '../uploads/department/deptdoc_68bfb7007b839.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'daw', 1, 0, '2025-09-09 13:11:28', '2025-09-09 13:11:28'),
(11, 16, 18, 'check2', 'sef', '../uploads/department/deptdoc_68bfb712c495f.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'fawf', 1, 0, '2025-09-09 13:11:46', '2025-09-09 13:11:46'),
(12, 2, 18, 'check3', 'awdqa', '../uploads/department/deptdoc_68bfb725c8fca.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'dawd', 1, 0, '2025-09-09 13:12:05', '2025-09-09 13:12:05'),
(13, 1, 18, 'check4', 'aefawd', '../uploads/department/deptdoc_68bfb73823951.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'awdaw', 1, 0, '2025-09-09 13:12:24', '2025-09-09 13:12:24'),
(14, 14, 18, 'check5', 'awd', '../uploads/department/deptdoc_68bfb74e03864.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'awdq2d', 1, 0, '2025-09-09 13:12:46', '2025-09-09 13:12:46'),
(15, 13, 18, 'check6', 'jvcibuj', '../uploads/department/deptdoc_68bfb761483c3.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'oboboboub', 1, 0, '2025-09-09 13:13:05', '2025-09-09 13:13:05'),
(16, 13, 18, 'check7', 'pougiovg', '../uploads/department/deptdoc_68bfb775407c8.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'iuvvob', 1, 0, '2025-09-09 13:13:25', '2025-09-09 13:13:25'),
(17, 5, 18, 'check8', 'uuclv', '../uploads/department/deptdoc_68bfb789caf6f.xlsx', 'alrx_dms.xlsx', 'xlsx', 17190, 'ljkvlvli', 1, 0, '2025-09-09 13:13:45', '2025-09-09 13:13:45'),
(22, 13, 3, 'check1', 'check1', '../uploads/department/deptdoc_68c1b0753f8b4.xlsx', 'alrx_dms.xlsx', 'xlsx', 18198, 'check1', 1, 0, '2025-09-11 01:08:05', '2025-09-11 01:08:05');

-- --------------------------------------------------------

--
-- Table structure for table `division`
--

CREATE TABLE `division` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `division`
--

INSERT INTO `division` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Finance', 'Manages the organization\'s financial planning, budgeting, reporting, and analysis. Ensures fiscal responsibility, compliance, and supports strategic decision-making.', '2025-07-10 11:34:40'),
(2, 'Engineering', 'Responsible for designing, developing, and maintaining technical systems, infrastructure, or products. Supports innovation, ensures safety compliance, and manages engineering projects.', '2025-07-10 11:36:03'),
(3, 'Corporate Planning', 'Oversees strategic planning, goal setting, and organizational development. Aligns long-term objectives with business performance, conducts analysis, and supports informed decision-making.', '2025-07-10 11:38:44'),
(4, 'Bid and Awards Committee', 'Responsible for overseeing the procurement process, ensuring fair and transparent bidding, evaluating proposals, and awarding contracts in accordance with policies and regulations.', '2025-07-10 11:39:59'),
(5, 'Project Management Office', 'Provides governance, standards, and support for managing projects. Ensures projects align with organizational goals, monitors progress, and promotes best practices for successful delivery.', '2025-07-10 11:41:03'),
(11, 'Information Technology Unit', 'All-around kami', '2025-07-15 06:17:10'),
(13, 'Office of the General Manager', 'GM', '2025-07-16 04:47:36'),
(14, 'News', 'Tagahatid ng Balita', '2025-07-16 07:28:14'),
(16, 'Demolition Team', 'jrrdkrykyryk', '2025-07-17 03:42:02'),
(17, 'check tester', 'testing', '2025-09-09 07:25:37');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(10) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `downloads` int(11) DEFAULT 0,
  `is_public` tinyint(1) DEFAULT 0,
  `tags` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `description`, `filename`, `original_name`, `file_size`, `file_type`, `mime_type`, `category_id`, `division_id`, `user_id`, `downloads`, `is_public`, `tags`, `created_at`, `updated_at`) VALUES
(232, 'alrx_dms', 'ug;b', '68c1bcbf14251_1757527231.xlsx', 'alrx_dms.xlsx', 18198, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 5, NULL, 1, 0, 1, '2025', '2025-09-10 18:00:31', '2025-09-10 18:00:31'),
(233, 'alrx_dms', 'ufuo', '68c1bf454fe07_1757527877.xlsx', 'alrx_dms.xlsx', 18198, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 5, NULL, 3, 0, 1, '2643', '2025-09-10 18:11:17', '2025-09-10 18:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`) VALUES
(1, 'loraine229820@gmail.com', '7af0dbe1f878b48720428e54db840d20288f6d2ab603d0f592731f0bc3b2dda1085d92238c936ec9a4e48acab1e83bcd48cb', '2025-09-02 23:47:05'),
(2, 'loraine229820@gmail.com', 'ffacefd508feb310f68d65a67feabf4f685455d3f616badc9ee39fd01b27202f943ac415df2b84acd08363cc8beec2bceb06', '2025-09-02 23:52:55'),
(3, 'loraine229820@gmail.com', '1a4ee7b320158e4d94be09a68f446cca1ee062f29a82faa8a47b7439e1d3fb9c7171263ae20610090256bff9cf02439665eb', '2025-09-02 23:53:23'),
(4, 'loraine229820@gmail.com', '49a49c9d66bd041d7f70f18dbcea293faf1cf1cefc196f64cf7cd1ea8855e22cb194f88e5314ff92a0e2ceae4d051a06c0bc', '2025-09-02 23:55:16'),
(5, 'loraine229820@gmail.com', '7335989a88e1bdfd7807da207efef7be9d88de0e630c0db359b314ab2de7c04a7b9f27d44f4a4127dea81e3b61505580c397', '2025-09-02 23:57:34'),
(6, 'loraine229820@gmail.com', '3b9dceeb84de0e915d099960c2cef82a8f3e6939841b4b61d56ec91ff4b1cd81e79227d52bfb3b70df0bd8d1d413982d92dc', '2025-09-02 23:57:56'),
(7, 'loraine229820@gmail.com', '943cee0f8738a312f7bb5e99f13596503e9403f7a0ab17dc36b3b76f5864e641053d4b3261ace8157dd7bfc3f993598a42f8', '2025-09-02 23:57:59'),
(8, 'loraine229820@gmail.com', 'fca40f5ad5e0a07c73ec45fefe90a23612698cee28e8b7765b3944c883bfaa75893260abf8906ac71b5802e19006456278d7', '2025-09-02 23:58:09'),
(9, 'loraine229820@gmail.com', '227eb4cbecfd023a033e4b85c23c0db561ebf9d1d06294b3e9d6476572f0efa9db2776fcb46adebb1f55c055e234ab12e29f', '2025-09-02 23:58:17'),
(10, 'loraine229820@gmail.com', 'c9418b19588700d745b4699a65a204e50023f77c2008a2573f81cf24339f66e8b88f5b9bc44ef64524cac9918e8bd4d2be28', '2025-09-02 23:58:24'),
(11, 'loraine229820@gmail.com', '081ce0b810401b0dfe56458f749296b766def6a3a2a021f4c54299ae4baec9ec', '2025-09-03 05:07:07'),
(13, 'loraine229820@gmail.com', '9a2ba4fefab950752492f6ca81675c88d162412999046b0ea52ae5359913a735', '2025-09-03 05:25:07'),
(14, 'lexpineda6@gmail.com', 'a187f62a75162d2171f5450a2f639b6bc51ed813579237f29a305f23b3b38471', '2025-09-03 05:57:05'),
(15, 'lexpineda6@gmail.com', 'a22916adbf30ba20571dbdfb7128ade765b5327d0874c157bf14f0ea5750deee', '2025-09-03 06:09:29'),
(16, 'loraine229820@gmail.com', '3d2173f91d5eb7addf63f31d4b7a4e2a23583246d957d2702313c2e64ef5b870', '2025-09-03 06:09:46'),
(17, 'loraine229820@gmail.com', '41f59423de7631142cfb92dd99624bdd1ecab547a2ab0a4cda0717d55990b654', '2025-09-03 06:11:16'),
(18, 'loraine229820@gmail.com', '342a1544c2d7ee384a7155ba827fcbb59e7286747abd8b49985f0920ea2e5130', '2025-09-03 06:12:06'),
(19, 'loraine229820@gmail.com', '69e6f2a9b28335538339dc0b746f78f88e1d3dd2fc1852eaca9d6ee22b8a908f', '2025-09-03 06:13:12'),
(20, 'loraine229820@gmail.com', '6efe2e8f6b1197202d49a375a149e680d2248e95abcde09d38338f4f4dd6afc5', '2025-09-03 06:15:39'),
(21, 'loraine229820@gmail.com', 'd19fc8f7ca6fc75ceeb594ffe42874c1f9458c5e690fc8276768d96db7a1f42b', '2025-09-03 06:50:21'),
(22, 'lexpineda6@gmail.com', '09d2ebcccce907e66bae03eca49cf1b347cef8e5fca7be01bb45f1b31d3af58b', '2025-09-03 06:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'PTNI Document Management Systems', '2025-06-25 06:01:41', '2025-06-30 03:28:07'),
(2, 'site_description', 'A secure platform for managing and sharing documents', '2025-06-25 06:01:41', '2025-06-25 06:03:02'),
(3, 'max_file_size', '40', '2025-06-25 06:01:41', '2025-06-25 06:02:22'),
(4, 'allowed_extensions', 'pdf,doc,docx,txt,jpg,jpeg,png,gif,zip,rar', '2025-06-25 06:01:42', '2025-06-25 06:01:42'),
(5, 'require_approval', '1', '2025-06-25 06:01:42', '2025-06-26 09:42:50'),
(6, 'allow_public_uploads', '1', '2025-06-25 06:01:42', '2025-06-25 06:39:11'),
(7, 'enable_downloads', '1', '2025-06-25 06:01:42', '2025-06-25 06:01:42'),
(8, 'maintenance_mode', '0', '2025-06-25 06:01:42', '2025-06-25 06:01:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('user','admin','superadmin') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_session_id` varchar(64) DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `last_login`, `avatar`, `created_at`, `updated_at`, `last_session_id`, `last_login_ip`) VALUES
(1, 'supad', 'sa@ptni.gov.ph', '$2y$10$dGcbE78mBBHyQpypSRuCWu6IapF6ErMAfVYEP5Fx4g6zjFL0/Alt.', 'superadmin', 'superadmin', 'active', '2025-09-10 17:53:32', NULL, '2025-09-09 14:27:57', '2025-09-10 17:53:32', NULL, NULL),
(2, 'admin', 'admin@ptni.gov.ph', '$2y$10$1rYmpsU.oqNDjPMSI4WaRuqolFH.JusAuO0xGu5vbHs6.9Ei0.ABG', 'admin', 'admin', 'active', '2025-09-10 18:22:41', NULL, '2025-09-09 14:28:18', '2025-09-10 18:22:41', NULL, NULL),
(3, 'user', 'user@ptni.gov.ph', '$2y$10$kngJFLOW9s.vBOOXIfynyOGDr0/XflD0CkciqU.Yw2n6GKdR98icW', 'user', 'user', 'active', '2025-09-10 16:09:39', NULL, '2025-09-09 14:28:38', '2025-09-10 16:09:39', NULL, NULL),
(4, 'check', 'checking01@ptni.gov.ph', '$2y$10$z1SDD0hC9C68vL5bc2Bu/u24egU01NCXbV3/1aa0GkGg4PE1ffMhq', 'check', 'user', 'active', NULL, NULL, '2025-09-09 17:15:50', '2025-09-10 03:45:07', NULL, NULL),
(5, 'test1', 'test1@ptni.gov.ph', '$2y$10$Tu5dIRloa.T/tHneKv.sU.PSd6YGq1Ek6KCZbDQ42U9e26Jco7t46', 'tester', 'user', 'active', NULL, NULL, '2025-09-09 18:01:57', '2025-09-10 03:45:07', NULL, NULL),
(6, 'testeringsss', 'test2edsef@ptni.gov.ph', '$2y$10$6ESqrND9RXiVe8PJmxhBX.Uz0nN0ZBKimUlzXTipibGprAnHXpkjW', 'test2222ssss', 'user', 'inactive', NULL, NULL, '2025-09-10 02:37:07', '2025-09-10 03:45:07', NULL, NULL),
(7, 'testers', 'test3@ptni.gov.ph', '$2y$10$kXSC12r5FJqTyTa1gJdxg.JYygTt4oMlncRIqWYCp46wW9Nlazjcq', 'test3', 'user', 'active', NULL, NULL, '2025-09-10 02:58:33', '2025-09-10 03:45:07', NULL, NULL),
(8, 'nix', 'nix02@ptni.gov.ph', '$2y$10$Oi0DolaQgJcHi5n/4snrmuVYoWVHO35GrZSx9pNzdHsNaLbYHLth2', 'nixie', 'admin', 'active', NULL, NULL, '2025-09-10 16:04:25', '2025-09-10 16:04:25', NULL, NULL),
(9, 'recenetly added', 'recenetlyadded@ptni.gov.ph', '$2y$10$3tXe8Vx3AdrUUgkeJM9nuOTgUpzEaX.5wAbN40YRUUXRFY8Xj16xm', 'ra', 'user', 'active', NULL, NULL, '2025-09-10 18:22:24', '2025-09-10 18:22:24', NULL, NULL),
(10, 'recenetly added1', 'recenetlyadded1@ptni.gov.ph', '$2y$10$Y9IKyHeQkJRpq5fz.OxDSOKr2nPnAEIxK9vsUXvJEuWFx1vifnlpy', 'rarecenetly added1', 'user', 'active', NULL, NULL, '2025-09-10 18:23:51', '2025-09-10 18:23:51', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_old`
--

CREATE TABLE `users_old` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('user','admin','superadmin') DEFAULT 'user',
  `division_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL COMMENT 'Last login timestamp for the user',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_divisions`
--

CREATE TABLE `user_divisions` (
  `user_id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_divisions`
--

INSERT INTO `user_divisions` (`user_id`, `division_id`, `created_at`) VALUES
(2, 11, '2025-09-09 19:06:51'),
(3, 11, '2025-09-10 02:50:11'),
(3, 13, '2025-09-10 02:50:11'),
(4, 3, '2025-09-10 02:50:07'),
(4, 11, '2025-09-10 02:50:06'),
(5, 11, '2025-09-10 03:49:12'),
(6, 2, '2025-09-10 02:49:39'),
(6, 11, '2025-09-10 02:49:39'),
(7, 4, '2025-09-10 03:07:39'),
(7, 13, '2025-09-10 03:07:39'),
(8, 17, '2025-09-10 16:04:25'),
(9, 4, '2025-09-10 18:22:24'),
(10, 11, '2025-09-10 18:23:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `target_user_id` (`target_user_id`);

--
-- Indexes for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_documents`
--
ALTER TABLE `department_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `division_id` (`division_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `division`
--
ALTER TABLE `division`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_file_type` (`file_type`),
  ADD KEY `idx_division_id` (`division_id`);
ALTER TABLE `documents` ADD FULLTEXT KEY `title` (`title`,`description`,`tags`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_old`
--
ALTER TABLE `users_old`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_division_id` (`division_id`);

--
-- Indexes for table `user_divisions`
--
ALTER TABLE `user_divisions`
  ADD PRIMARY KEY (`user_id`,`division_id`),
  ADD KEY `idx_division` (`division_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `department_documents`
--
ALTER TABLE `department_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `division`
--
ALTER TABLE `division`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users_old`
--
ALTER TABLE `users_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users_old` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_logs_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `users_old` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `department_documents`
--
ALTER TABLE `department_documents`
  ADD CONSTRAINT `department_documents_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_documents_division` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users_old`
--
ALTER TABLE `users_old`
  ADD CONSTRAINT `users_old_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_divisions`
--
ALTER TABLE `user_divisions`
  ADD CONSTRAINT `fk_ud_div` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ud_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
