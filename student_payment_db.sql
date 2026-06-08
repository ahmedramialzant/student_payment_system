-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 11 أبريل 2026 الساعة 13:44
-- إصدار الخادم: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_payment_db`
--

-- --------------------------------------------------------

--
-- بنية الجدول `installments`
--

CREATE TABLE `installments` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `installment_no` int(11) NOT NULL,
  `amount_due` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid','overdue','partial') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `installments`
--

INSERT INTO `installments` (`id`, `student_id`, `installment_no`, `amount_due`, `due_date`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 5000.00, '2024-10-01', 'paid', NULL, '2026-04-10 23:59:23'),
(2, 1, 2, 5000.00, '2025-01-01', 'paid', NULL, '2026-04-10 23:59:23'),
(3, 1, 3, 5000.00, '2025-04-01', 'pending', NULL, '2026-04-10 23:59:23'),
(4, 2, 1, 4000.00, '2024-10-01', 'paid', NULL, '2026-04-10 23:59:23'),
(5, 2, 2, 4000.00, '2025-01-01', 'overdue', NULL, '2026-04-10 23:59:23'),
(6, 2, 3, 4000.00, '2025-04-01', 'pending', NULL, '2026-04-10 23:59:23'),
(7, 3, 1, 6000.00, '2024-10-01', 'paid', NULL, '2026-04-10 23:59:23'),
(8, 3, 2, 6000.00, '2025-01-01', 'overdue', NULL, '2026-04-10 23:59:23'),
(9, 3, 3, 6000.00, '2025-04-01', 'pending', NULL, '2026-04-10 23:59:23'),
(10, 4, 1, 166.67, '2026-05-11', 'pending', NULL, '2026-04-11 11:22:58'),
(11, 4, 2, 166.67, '2026-06-11', 'pending', NULL, '2026-04-11 11:22:58'),
(12, 4, 3, 166.67, '2026-07-11', 'pending', NULL, '2026-04-11 11:22:58'),
(13, 4, 4, 166.67, '2026-08-11', 'pending', NULL, '2026-04-11 11:22:58'),
(14, 4, 5, 166.67, '2026-09-11', 'pending', NULL, '2026-04-11 11:22:58'),
(15, 4, 6, 166.67, '2026-10-11', 'pending', NULL, '2026-04-11 11:22:58'),
(16, 4, 7, 166.67, '2026-11-11', 'pending', NULL, '2026-04-11 11:22:58'),
(17, 4, 8, 166.67, '2026-12-11', 'pending', NULL, '2026-04-11 11:22:58'),
(18, 4, 9, 166.67, '2027-01-11', 'pending', NULL, '2026-04-11 11:22:58');

-- --------------------------------------------------------

--
-- بنية الجدول `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `installment_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','check','online') NOT NULL DEFAULT 'cash',
  `reference_no` varchar(100) DEFAULT NULL,
  `received_by` int(10) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `installment_id`, `amount`, `payment_date`, `payment_method`, `reference_no`, `received_by`, `notes`, `created_at`) VALUES
(1, 1, 1, 5000.00, '2024-10-01', 'bank_transfer', 'REF-001', 1, NULL, '2026-04-10 23:59:23'),
(2, 1, 2, 5000.00, '2025-01-02', 'cash', 'REF-002', 1, NULL, '2026-04-10 23:59:23'),
(3, 2, 4, 4000.00, '2024-10-05', 'cash', 'REF-003', 1, NULL, '2026-04-10 23:59:23'),
(4, 3, 7, 6000.00, '2024-10-03', 'check', 'REF-004', 1, NULL, '2026-04-10 23:59:23');

-- --------------------------------------------------------

--
-- بنية الجدول `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_code` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `program` varchar(100) NOT NULL,
  `enrollment_date` date NOT NULL,
  `total_fees` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','graduated') NOT NULL DEFAULT 'active',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `students`
--

INSERT INTO `students` (`id`, `student_code`, `full_name`, `email`, `phone`, `program`, `enrollment_date`, `total_fees`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'STU-2024-001', 'Ahmed Hassan Ali', 'ahmed@example.com', '0501234567', 'Computer Science', '2024-09-01', 15000.00, NULL, 'active', 1, '2026-04-10 23:59:23', '2026-04-10 23:59:23'),
(2, 'STU-2024-002', 'Sara Mohammed Noor', 'sara@example.com', '0507654321', 'Business Administration', '2024-09-01', 12000.00, NULL, 'active', 1, '2026-04-10 23:59:23', '2026-04-10 23:59:23'),
(3, 'STU-2024-003', 'Khalid Abdulrahman', 'khalid@example.com', '0509876543', 'Electrical Engineering', '2024-09-01', 18000.00, NULL, 'active', 1, '2026-04-10 23:59:23', '2026-04-10 23:59:23'),
(4, 'STU-2026-001', 'Ibrahim Ramadan', 'ibrahim@ramadan.com', '0597939367', 'Computer Science', '2026-04-11', 1500.00, 'aaaaaaaaa', 'active', 1, '2026-04-11 11:22:58', '2026-04-11 11:22:58');

-- --------------------------------------------------------

--
-- بنية الجدول `transactions_log`
--

CREATE TABLE `transactions_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `transactions_log`
--

INSERT INTO `transactions_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGIN', 'users', 1, 'User admin logged in', '::1', '2026-04-11 00:15:07'),
(2, 1, 'LOGIN', 'users', 1, 'User admin logged in', '::1', '2026-04-11 11:17:18'),
(3, 1, 'LOGOUT', 'users', 1, 'User admin logged out', '::1', '2026-04-11 11:19:15'),
(4, 1, 'LOGIN', 'users', 1, 'User admin logged in', '::1', '2026-04-11 11:20:00'),
(5, 1, 'ADD_STUDENT', 'students', 4, 'Student \'Ibrahim Ramadan\' (Code: STU-2026-001) added with 9 installments', '::1', '2026-04-11 11:22:58'),
(6, 1, 'LOGOUT', 'users', 1, 'User admin logged out', '::1', '2026-04-11 11:23:33'),
(7, 1, 'LOGIN', 'users', 1, 'User admin logged in', '::1', '2026-04-11 11:23:35'),
(8, 1, 'LOGOUT', 'users', 1, 'User admin logged out', '::1', '2026-04-11 11:23:54'),
(9, 1, 'LOGIN', 'users', 1, 'User admin logged in', '::1', '2026-04-11 11:26:09'),
(10, 1, 'ADD_STUDENT', 'students', 5, 'Student \'Mohamed Ali\' (Code: STU-2026-002) added with 3 installments', '::1', '2026-04-11 11:27:03'),
(11, 1, 'DELETE_STUDENT', 'students', 5, 'Student \'Mohamed Ali\' deleted', '::1', '2026-04-11 11:27:21'),
(12, 1, 'LOGOUT', 'users', 1, 'User admin logged out', '::1', '2026-04-11 11:28:09');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$S2AVbopT9fINIQd8L05r2O0atTgg1dLqKw325w4vZS.SpwVrKUr3e', 'System Administrator', 'admin', '2026-04-10 23:59:23');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_overdue_installments`
-- (See below for the actual view)
--
CREATE TABLE `v_overdue_installments` (
`student_code` varchar(20)
,`full_name` varchar(100)
,`phone` varchar(20)
,`installment_no` int(11)
,`amount_due` decimal(12,2)
,`due_date` date
,`days_overdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_balance`
-- (See below for the actual view)
--
CREATE TABLE `v_student_balance` (
`id` int(10) unsigned
,`student_code` varchar(20)
,`full_name` varchar(100)
,`program` varchar(100)
,`total_fees` decimal(12,2)
,`status` enum('active','inactive','graduated')
,`total_paid` decimal(34,2)
,`remaining_balance` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_overdue_installments`
--
DROP TABLE IF EXISTS `v_overdue_installments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_overdue_installments`  AS SELECT `s`.`student_code` AS `student_code`, `s`.`full_name` AS `full_name`, `s`.`phone` AS `phone`, `i`.`installment_no` AS `installment_no`, `i`.`amount_due` AS `amount_due`, `i`.`due_date` AS `due_date`, to_days(curdate()) - to_days(`i`.`due_date`) AS `days_overdue` FROM (`installments` `i` join `students` `s` on(`s`.`id` = `i`.`student_id`)) WHERE `i`.`status` in ('pending','overdue') AND `i`.`due_date` < curdate() ORDER BY to_days(curdate()) - to_days(`i`.`due_date`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_balance`
--
DROP TABLE IF EXISTS `v_student_balance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_balance`  AS SELECT `s`.`id` AS `id`, `s`.`student_code` AS `student_code`, `s`.`full_name` AS `full_name`, `s`.`program` AS `program`, `s`.`total_fees` AS `total_fees`, `s`.`status` AS `status`, coalesce(sum(`p`.`amount`),0) AS `total_paid`, `s`.`total_fees`- coalesce(sum(`p`.`amount`),0) AS `remaining_balance` FROM (`students` `s` left join `payments` `p` on(`p`.`student_id` = `s`.`id`)) GROUP BY `s`.`id`, `s`.`student_code`, `s`.`full_name`, `s`.`program`, `s`.`total_fees`, `s`.`status` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `installments`
--
ALTER TABLE `installments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_installment` (`student_id`,`installment_no`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `installment_id` (`installment_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `transactions_log`
--
ALTER TABLE `transactions_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions_log`
--
ALTER TABLE `transactions_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- قيود الجداول المُلقاة.
--

--
-- قيود الجداول `installments`
--
ALTER TABLE `installments`
  ADD CONSTRAINT `installments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`installment_id`) REFERENCES `installments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- قيود الجداول `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- قيود الجداول `transactions_log`
--
ALTER TABLE `transactions_log`
  ADD CONSTRAINT `transactions_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
