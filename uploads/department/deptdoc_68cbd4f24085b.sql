-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 10:28 AM
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
-- Database: `ptni4new`
--

-- --------------------------------------------------------

--
-- Table structure for table `shared_documents`
--

CREATE TABLE `shared_documents` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `shared_to` int(11) NOT NULL,
  `shared_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shared_documents`
--

INSERT INTO `shared_documents` (`id`, `document_id`, `shared_by`, `shared_to`, `shared_at`) VALUES
(1, 239, 3, 2, '2025-09-17 07:01:47'),
(2, 239, 3, 12, '2025-09-17 07:03:01'),
(3, 239, 3, 12, '2025-09-17 07:12:00'),
(4, 240, 3, 13, '2025-09-18 01:59:21'),
(5, 240, 3, 2, '2025-09-18 02:47:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `shared_documents`
--
ALTER TABLE `shared_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `shared_by` (`shared_by`),
  ADD KEY `shared_to` (`shared_to`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `shared_documents`
--
ALTER TABLE `shared_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `shared_documents`
--
ALTER TABLE `shared_documents`
  ADD CONSTRAINT `shared_documents_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`),
  ADD CONSTRAINT `shared_documents_ibfk_2` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `shared_documents_ibfk_3` FOREIGN KEY (`shared_to`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
