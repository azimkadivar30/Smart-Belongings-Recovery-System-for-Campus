-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2026 at 05:13 PM
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
-- Database: `smart_belonging_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_name` varchar(150) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `colour` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_hash` varchar(64) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `qr_image` varchar(255) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` timestamp NULL DEFAULT NULL,
  `otp_verified_at` timestamp NULL DEFAULT NULL,
  `device_status` enum('active','lost','recovered') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `user_id`, `device_name`, `brand`, `model`, `serial_number`, `colour`, `description`, `image`, `image_hash`, `qr_token`, `qr_image`, `otp`, `otp_expiry`, `otp_verified_at`, `device_status`, `created_at`) VALUES
(1, 2, 'Asus Strix G17', 'Asus', 'Strix 17', 'SN0147852369', 'Black', 'RGB LIGTHING', NULL, NULL, 'baabbf85ab91dd7c2b5de2a4c1ce3cf8', 'uploads/qr_devices/qr_baabbf85ab91dd7c2b5de2a4c1ce3cf8.png', NULL, NULL, '2026-08-07 06:15:10', 'recovered', '2026-08-07 06:09:16'),
(3, 2, 'Asus Strix G17', 'Asus', 'G17', 'SN1478529630', 'MAT BLACK', 'xcvb', NULL, NULL, '0863f079463580354fd0fc6a59f64a68', 'uploads/qr_devices/qr_0863f079463580354fd0fc6a59f64a68.png', NULL, NULL, NULL, 'active', '2026-08-08 04:20:42');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(150) NOT NULL,
  `item_date` date NOT NULL,
  `report_type` enum('lost','found') NOT NULL DEFAULT 'lost',
  `status` enum('pending','found','not_found','collected') NOT NULL DEFAULT 'pending',
  `recovery_status` enum('pending','recovered') NOT NULL DEFAULT 'pending',
  `qr_code` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_hash` varchar(64) DEFAULT NULL,
  `collection_details` text DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `owner_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `user_id`, `device_id`, `item_name`, `category`, `description`, `location`, `item_date`, `report_type`, `status`, `recovery_status`, `qr_code`, `image_path`, `image_hash`, `collection_details`, `verification_code`, `owner_verified_at`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'water bottle', 'Bottle / Lunchbox', 'ax water bottle', 'mb101', '2026-08-07', 'lost', 'collected', 'recovered', NULL, NULL, NULL, '', '627755', '2026-08-07 06:03:07', '2026-08-07 05:59:34', '2026-08-07 06:03:26'),
(4, 2, 1, 'Asus Strix G17', 'Electronics (Phone / Laptop)', 'Model: Strix 17\nSerial No.: SN0147852369\nColour: Black\nRGB LIGTHING', 'MB101', '2026-08-07', 'lost', 'found', 'recovered', NULL, NULL, NULL, '', '981924', NULL, '2026-08-07 06:11:37', '2026-08-08 04:31:10');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `type` enum('found','review','pickup','system','closed','found_alert') NOT NULL DEFAULT 'system',
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `item_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 2, NULL, 'system', 'Welcome to Smart Belonging System, safik Sherasiya! Verify your email to get started.', 0, '2026-08-07 05:57:07'),
(2, 2, 1, 'review', 'Your report for \"water bottle\" has been submitted and is under review.', 0, '2026-08-07 05:59:34'),
(3, 2, 1, 'found', 'Good news — your item \"water bottle\" has been found! Check the collection details and verify ownership with the code we emailed you before you can collect it.', 0, '2026-08-07 06:00:17'),
(4, 2, 1, 'system', 'Your item \"water bottle\" has been marked as collected. Case closed.', 0, '2026-08-07 06:03:26'),
(7, 2, NULL, 'system', 'Your device \"Asus Strix G17\" has been registered and tagged with a QR code.', 0, '2026-08-07 06:09:16'),
(8, 2, 4, 'review', 'Your device \"Asus Strix G17\" has been reported lost and is under review.', 0, '2026-08-07 06:11:37'),
(9, 2, NULL, 'found_alert', 'Someone scanned your \"Asus Strix G17\" tag and reported finding it. The admin team is verifying — you\'ll be notified once it\'s confirmed.', 0, '2026-08-07 06:12:50'),
(10, 2, NULL, 'system', 'Your device \"Asus Strix G17\" is now marked active.', 0, '2026-08-07 06:14:13'),
(11, 2, NULL, 'system', 'Good news — your device \"Asus Strix G17\" has been verified and marked as recovered.', 0, '2026-08-07 06:15:10'),
(12, 2, NULL, 'system', 'Good news — your device \"Asus Strix G17\" has been marked as recovered.', 0, '2026-08-07 06:16:08'),
(13, 2, 4, 'found', 'Good news — your item \"Asus Strix G17\" has been found! Check the collection details and verify ownership with the code we emailed you before you can collect it.', 0, '2026-08-07 06:18:11'),
(14, 2, 4, 'system', 'Your item \"Asus Strix G17\" has been marked as collected. Case closed.', 0, '2026-08-07 06:19:40'),
(25, 4, NULL, 'system', 'Welcome to Smart Belonging System, Nisar Badi! Verify your email to get started.', 0, '2026-08-07 17:31:39'),
(26, 2, NULL, 'system', 'Your device \"Asus Strix G17\" has been registered and tagged with a QR code.', 0, '2026-08-08 04:20:42'),
(27, 5, NULL, 'system', 'Welcome to Smart Belonging System, jigar sor! Verify your email to get started.', 0, '2026-08-08 04:26:33'),
(28, 6, NULL, 'system', 'Welcome to Smart Belonging System, Azim Kadivar! Verify your email to get started.', 0, '2026-08-08 04:28:36'),
(29, 2, 4, 'found', 'Good news — your item \"Asus Strix G17\" has been found! Check the collection details and verify ownership with the code we emailed you before you can collect it.', 0, '2026-08-08 04:30:25'),
(30, 2, 4, 'found', 'Good news — your item \"Asus Strix G17\" has been found! Check the collection details and verify ownership with the code we emailed you before you can collect it.', 0, '2026-08-08 04:30:36');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL,
  `type` enum('complaint','found_alert') NOT NULL DEFAULT 'complaint',
  `finder_name` varchar(150) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `finder_contact` varchar(150) DEFAULT NULL,
  `finder_email` varchar(150) DEFAULT NULL,
  `found_location` varchar(255) DEFAULT NULL,
  `status` enum('open','resolved','escalated') NOT NULL DEFAULT 'open',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `item_id`, `device_id`, `type`, `finder_name`, `subject`, `message`, `finder_contact`, `finder_email`, `found_location`, `status`, `resolved_at`, `created_at`) VALUES
(1, NULL, NULL, 1, 'found_alert', 'abbas kadivar', 'Possible match found: \"Asus Strix G17\" (device)', '(no additional message from finder)', '951022222', 'abbas@gmail.com', 'MB101', 'resolved', '2026-08-07 06:14:13', '2026-08-07 06:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `enrollment_no` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL DEFAULT 'student',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `enrollment_no`, `department`, `password`, `role`, `email_verified`, `verification_token`, `token_expires_at`, `profile_pic`, `created_at`) VALUES
(1, 'Campus Admin', 'smartbelongingsystemadmin@gmail.com', NULL, NULL, 'Administration', '$2b$10$8Sz22aH60V60L9uTlv185OdC.RRDB1jYXmCDE64/ezhgWkfmErJZO', 'admin', 1, NULL, NULL, NULL, '2026-08-07 05:55:42'),
(2, 'safik Sherasiya', 'mohamadsafik.sherasiya126417@marwadiuniversity.ac.in', '9510218598', '9241010328', 'Computer Engineering', '$2y$10$9OG1bJFeBFU8Ptu8uW3tu.Y2fKqXeDixkOdDOCQ3MvxY2Mg2FWWAm', 'student', 1, NULL, NULL, NULL, '2026-08-07 05:56:26'),
(4, 'Nisar Badi', 'nisarahmed.badi126418@marwadiuniversity.ac.in', '9313175056', '92410103029', 'Computer Engineering', '$2y$10$/4OSUXswZGR60qN9HbQP1elzqKYgO8QIHm6j6ABvvIWLeMBHRpy.O', 'student', 0, '44a9f148ded85858db7597e653931b670d3e917cdd281776b0a0064525d56529', '2026-08-08 14:01:32', NULL, '2026-08-07 17:31:32'),
(5, 'jigar sor', 'aswathy.nair134208@marwadiuniversity.ac.in', '7410852963', '92410103029', 'Computer Engineering', '$2y$10$KyvVKVvZdoZUCqQ6eK0iIewm7q/lYtfk9ZQDPVCPeblKbHXhpH6xy', 'student', 0, 'd60aa41a85e7dab9965d48d00957c7e3905941741b7c3d94ca4f120440d6bc2d', '2026-08-09 00:56:28', NULL, '2026-08-08 04:26:28'),
(6, 'Azim Kadivar', 'azim.kadivar126420@marwadiuniversity.ac.in', '9510218598', '92410103030', 'Computer Engineering', '$2y$10$VPN5Fm1ZZA1QOUeOhH1L7.z3difW2ehij2C58C1a46k7JJWoh9jIi', 'student', 0, '5d3443de0b4f28a17fc3edfa769564019125ca7ba570453296ae7d158ed0fb19', '2026-08-09 00:58:30', NULL, '2026-08-08 04:28:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_devices_qr_token` (`qr_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
