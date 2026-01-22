-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2026 at 03:38 AM
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
-- Database: `osas_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrow_lists`
--

CREATE TABLE `borrow_lists` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `borrow_status` varchar(50) NOT NULL,
  `due_date` date DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `borrower_name` varchar(100) NOT NULL,
  `borrower_course` varchar(100) DEFAULT NULL,
  `borrower_id` varchar(50) NOT NULL,
  `borrower_year` int(11) DEFAULT NULL,
  `borrower_department` varchar(100) DEFAULT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `release_by` varchar(100) DEFAULT NULL,
  `deposit_money` decimal(10,2) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_lists`
--

INSERT INTO `borrow_lists` (`id`, `item_id`, `user_id`, `borrow_status`, `due_date`, `deleted_at`, `borrower_name`, `borrower_course`, `borrower_id`, `borrower_year`, `borrower_department`, `item_description`, `contact_number`, `quantity`, `release_by`, `deposit_money`, `semester`, `school_year`, `created_at`) VALUES
(1, 4, 1, 'Approved', '2026-01-15', '2026-01-13 01:02:53', 'ian', 'BSIT', '2022-010166', 4, 'CCS', 'Protective Gear - dasdsa', '09514696308', 9, 'Diane Therese Bahala', 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(2, 4, 1, 'Returned', '2026-01-15', '2026-01-13 01:08:25', 'ian', 'BSIT', '2021-010166', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, 'Diane Therese Bahala', 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(3, 4, 1, 'Approved', '2026-01-15', '2026-01-13 01:57:17', 'ian', 'BSIT', '2021-010166', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, 'Diane Therese Bahala', 300.00, NULL, NULL, '2026-01-13 20:06:21'),
(4, 4, 1, 'Rejected', '2026-01-14', '2026-01-13 01:59:05', 'ian', 'BSIT', '2021-010166', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, NULL, 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(5, 4, 1, 'Returned', '2026-01-15', '2026-01-13 02:26:17', 'ian', 'BSIT', '2021-010166', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, 'Diane Therese Bahala', 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(6, 4, 1, 'Returned', '2026-01-15', '2026-01-13 03:12:02', 'ian', 'BSIT', '2021-010166', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, 'Diane Therese Bahala', 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(7, 4, 1, 'Approved', '2026-01-22', '2026-01-13 12:21:47', 'ian', 'BSIT', '2021-010166', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, 'Diane Therese Bahala', 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(8, 4, 1, 'Returned', '2026-01-15', NULL, 'ian', 'BSIT', '2021-010166', 2, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 5, 'Diane Therese Bahala', 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(9, 3, 1, 'Pending', '2026-01-15', '2026-01-13 07:34:53', 'jesper', 'BSIT', '2021-010166', 3, 'College of Computer Studies', 'Net - dsada', '09514696308', 2, NULL, 500.00, NULL, NULL, '2026-01-13 20:06:21'),
(10, 4, 1, 'Rejected', '2026-01-15', NULL, 'barila', 'BSIT', '22222', 4, 'College of Computer Studies', 'Protective Gear - dasdsa', '09514696308', 2, 'Diane Therese Bahala', 500.00, '2nd Semester', NULL, '2026-01-13 20:06:21'),
(11, 3, 1, 'Approved', '2026-01-15', NULL, 'jordan', 'BSIT', '22222', 3, 'College of Art and Sciences', 'Net - dsada', '1232321312', 2, 'Diane Therese Bahala', 500.00, 'Summer', NULL, '2026-01-13 20:06:21'),
(12, 2, 1, 'Pending', '2026-01-16', '2026-01-14 01:31:04', 'jordans', 'BSIT', '3333', 3, 'College of Education', 'Ball - sinaw', '09514696308', 1, NULL, 500.00, '2nd Semester', '2025-2026', '2026-01-14 01:26:15'),
(13, 2, 1, 'Approved', '2026-01-16', '2026-01-14 01:35:10', 'ian', 'BSIT', '2021-010166', 3, 'College of Engineering', 'Ball - sinaw', '09514696308', 2, '', 500.00, '2nd Semester', '2025-2026', '2026-01-14 01:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `cabinets`
--

CREATE TABLE `cabinets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'Cabinet',
  `position` int(11) DEFAULT NULL COMMENT 'Order/position of cabinet',
  `description` text DEFAULT NULL COMMENT 'Short description of the cabinet',
  `status` enum('active','pending','archived') DEFAULT 'active',
  `added_by` varchar(100) DEFAULT NULL COMMENT 'Username or name of person who added',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cabinets`
--

INSERT INTO `cabinets` (`id`, `user_id`, `name`, `position`, `description`, `status`, `added_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cabinet 1', NULL, 'jordan cabinetdadadasdasdas', 'active', 'Admin', '2026-01-14 09:02:30', '2026-01-15 01:16:37'),
(2, 1, 'Cabinet 2', NULL, 'dasda', 'archived', 'Admin', '2026-01-14 09:03:48', '2026-01-15 01:52:48'),
(3, 1, 'Cabinet 3', NULL, 'dasdasdas', 'pending', 'Admin', '2026-01-14 09:03:56', '2026-01-14 23:43:46'),
(4, 1, 'Cabinet 4', NULL, 'dsadsadas', 'active', 'Admin', '2026-01-14 22:35:34', '2026-01-14 23:56:55'),
(5, 1, 'Cabinet 5', NULL, 'ksadkasdkas', 'pending', 'Admin', '2026-01-14 22:35:49', '2026-01-14 23:56:56'),
(6, 1, 'Cabinet 1', NULL, 'dasdasdas', 'active', 'Admin', '2026-01-14 22:50:31', '2026-01-14 23:56:52'),
(7, 1, 'Cabinet 2', NULL, NULL, 'pending', 'Admin', '2026-01-14 23:43:00', '2026-01-14 23:43:49');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `cabinet_id` int(11) NOT NULL,
  `cabinet_number` varchar(20) NOT NULL COMMENT 'Format: C1.1, C1.2, etc. (per cabinet numbering)',
  `filename` varchar(255) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Short description of the file',
  `category` varchar(50) DEFAULT 'Documents' COMMENT 'Category: Documents, Sports, Objects, etc.',
  `status` enum('available','borrowed','archived') DEFAULT 'available',
  `added_by` varchar(100) DEFAULT NULL COMMENT 'Username or name of person who added',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete timestamp',
  `borrow_by` varchar(100) DEFAULT NULL COMMENT 'Username or name of person who borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `cabinet_id`, `cabinet_number`, `filename`, `description`, `category`, `status`, `added_by`, `created_at`, `updated_at`, `deleted_at`, `borrow_by`) VALUES
(5, 1, 'C1.1', 'jordan', 'jordans file', 'Sports', 'archived', 'Admin', '2026-01-14 12:23:34', '2026-01-15 01:52:59', NULL, 'jordam'),
(6, 1, 'C1.2', 'jordan', 'dadada', 'Documents', 'borrowed', 'Admin', '2026-01-14 12:23:48', '2026-01-15 01:47:37', NULL, 'val'),
(7, 1, 'C1.3', 'jordan', 'dasdas', 'Objects', 'available', 'Admin', '2026-01-14 23:44:37', '2026-01-15 01:47:42', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `file_uses`
--

CREATE TABLE `file_uses` (
  `id` int(11) NOT NULL,
  `cabinet_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `uses_by` varchar(100) DEFAULT NULL COMMENT 'Username or name of person who used',
  `borrow_by` varchar(100) DEFAULT NULL COMMENT 'Username or name of person who borrowed',
  `archived_by` varchar(100) DEFAULT NULL COMMENT 'Username or name of person who archived',
  `status` enum('available','borrowed','archived') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `unique_id` varchar(255) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `price` int(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `number_has_been_uses` varchar(255) NOT NULL,
  `damage_at` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `item_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL,
  `size` varchar(255) DEFAULT NULL,
  `sport` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `unique_id`, `quantity`, `price`, `status`, `number_has_been_uses`, `damage_at`, `image`, `created_at`, `item_name`, `category`, `semester`, `description`, `brand`, `color`, `size`, `sport`) VALUES
(2, '1', '5', 499, 'Damaged', '0', '', 'item_1768201407_69649cbf5b10a.jpg', '2026-01-12 06:39:24', 'BOLA', 'Ball', '2nd Semester', 'sinaw', 'samsung', 'orange', 'M', 'Basketball'),
(3, '2', '3', 500, 'Unavailable', '0', '', 'item_1768201438_69649cde62feb.jpg', '2026-01-12 06:46:28', 'NET', 'Net', '2nd Semester', 'dsada', 'dsada', 'black', 'L', 'Volleyball'),
(4, '3', '10', 200, 'Available', '0', '', 'item_1768205808_6964adf018d16.jpg', '2026-01-12 08:16:48', 'CONES', 'Protective Gear', '1st Semester', 'dasdsa', 'dasda', 'White', '', 'Football');

-- --------------------------------------------------------

--
-- Table structure for table `return_lists`
--

CREATE TABLE `return_lists` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `borrow_list_id` int(11) NOT NULL,
  `receive_by` varchar(100) DEFAULT NULL,
  `item_return` date DEFAULT NULL,
  `item_status` varchar(50) DEFAULT NULL,
  `deposit_money` decimal(10,2) DEFAULT NULL,
  `item_quantity_return` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `penalty` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_lists`
--

INSERT INTO `return_lists` (`id`, `item_id`, `user_id`, `borrow_list_id`, `receive_by`, `item_return`, `item_status`, `deposit_money`, `item_quantity_return`, `status`, `penalty`) VALUES
(1, 4, 1, 10, 'Diane Therese Bahala', '2026-01-16', 'Good Condition', 500.00, 2, 'Returned', 500.00),
(2, 4, 1, 8, 'Diane Therese Bahala', '2026-01-16', 'Good Condition', 500.00, 5, 'Returned', 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `lastname`, `firstname`, `image`, `gender`, `position`, `age`) VALUES
(1, 'admin@gmail.com', '$2y$10$AKRwb3yqo1w2WrGfbmRoE.AFlAKrsf.VdNjvs/mGewm1GZszDW5ee', 'Bahala', 'Diane Therese', 'user_1_1768370113.jpg', '', 'Administrator', '');

-- --------------------------------------------------------

--
-- Table structure for table `user_history_saved`
--

CREATE TABLE `user_history_saved` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `borrow_list_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_history_saved`
--

INSERT INTO `user_history_saved` (`id`, `user_id`, `borrow_list_id`, `saved_at`) VALUES
(1, 1, 11, '2026-01-13 12:54:49'),
(3, 1, 10, '2026-01-13 13:06:41'),
(4, 1, 8, '2026-01-13 13:11:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrow_lists`
--
ALTER TABLE `borrow_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_borrow_product` (`item_id`),
  ADD KEY `fk_borrow_user` (`user_id`);

--
-- Indexes for table `cabinets`
--
ALTER TABLE `cabinets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cabinet_id` (`cabinet_id`),
  ADD KEY `idx_cabinet_number` (`cabinet_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `file_uses`
--
ALTER TABLE `file_uses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cabinet_id` (`cabinet_id`),
  ADD KEY `idx_file_id` (`file_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `return_lists`
--
ALTER TABLE `return_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_return_item` (`item_id`),
  ADD KEY `fk_return_user` (`user_id`),
  ADD KEY `fk_return_borrow` (`borrow_list_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_history_saved`
--
ALTER TABLE `user_history_saved`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_borrow` (`user_id`,`borrow_list_id`),
  ADD KEY `borrow_list_id` (`borrow_list_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrow_lists`
--
ALTER TABLE `borrow_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cabinets`
--
ALTER TABLE `cabinets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `file_uses`
--
ALTER TABLE `file_uses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `return_lists`
--
ALTER TABLE `return_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_history_saved`
--
ALTER TABLE `user_history_saved`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_lists`
--
ALTER TABLE `borrow_lists`
  ADD CONSTRAINT `fk_borrow_product` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `fk_borrow_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `cabinets`
--
ALTER TABLE `cabinets`
  ADD CONSTRAINT `cabinets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_uses`
--
ALTER TABLE `file_uses`
  ADD CONSTRAINT `file_uses_ibfk_1` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_uses_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_lists`
--
ALTER TABLE `return_lists`
  ADD CONSTRAINT `fk_return_borrow` FOREIGN KEY (`borrow_list_id`) REFERENCES `borrow_lists` (`id`),
  ADD CONSTRAINT `fk_return_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `fk_return_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `user_history_saved`
--
ALTER TABLE `user_history_saved`
  ADD CONSTRAINT `user_history_saved_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_history_saved_ibfk_2` FOREIGN KEY (`borrow_list_id`) REFERENCES `borrow_lists` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
