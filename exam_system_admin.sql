-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 11:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exam_system_admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `backend_config`
--

CREATE TABLE `backend_config` (
  `id` int(11) NOT NULL,
  `api_base_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backend_config`
--

INSERT INTO `backend_config` (`id`, `api_base_url`, `created_at`, `updated_at`) VALUES
(1, 'http://localhost:8059/api/public', '2025-11-10 21:12:24', '2025-11-10 21:13:13'),
(2, 'http://localhost:8059/api/public', '2025-11-10 21:16:26', '2025-11-10 21:16:26'),
(3, 'http://localhost:8054/api/public', '2025-11-10 21:18:56', '2025-11-10 21:18:56'),
(4, 'http://localhost:8054/api/public', '2025-11-10 21:19:02', '2025-11-10 21:19:02'),
(5, 'http://localhost:8054/api/public/', '2025-11-10 23:29:57', '2025-11-10 23:29:57'),
(6, 'http://localhost:8054/api/public', '2025-11-10 23:30:03', '2025-11-10 23:30:03');

-- --------------------------------------------------------

--
-- Table structure for table `combined_analyses`
--

CREATE TABLE `combined_analyses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `exam_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`exam_ids`)),
  `labels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`labels`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_lists`
--

CREATE TABLE `exam_lists` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_list_items`
--

CREATE TABLE `exam_list_items` (
  `id` int(11) NOT NULL,
  `exam_list_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `exam_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_exam_list_assignments`
--

CREATE TABLE `student_exam_list_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `exam_list_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backend_config`
--
ALTER TABLE `backend_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `combined_analyses`
--
ALTER TABLE `combined_analyses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_lists`
--
ALTER TABLE `exam_lists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_list_items`
--
ALTER TABLE `exam_list_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_list_id` (`exam_list_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_exam_list_assignments`
--
ALTER TABLE `student_exam_list_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_list_id` (`exam_list_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backend_config`
--
ALTER TABLE `backend_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `combined_analyses`
--
ALTER TABLE `combined_analyses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_lists`
--
ALTER TABLE `exam_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_list_items`
--
ALTER TABLE `exam_list_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_exam_list_assignments`
--
ALTER TABLE `student_exam_list_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `exam_list_items`
--
ALTER TABLE `exam_list_items`
  ADD CONSTRAINT `exam_list_items_ibfk_1` FOREIGN KEY (`exam_list_id`) REFERENCES `exam_lists` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_exam_list_assignments`
--
ALTER TABLE `student_exam_list_assignments`
  ADD CONSTRAINT `student_exam_list_assignments_ibfk_1` FOREIGN KEY (`exam_list_id`) REFERENCES `exam_lists` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
