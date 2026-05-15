-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2026 at 09:22 PM
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
-- Database: `chickacc`
--

-- --------------------------------------------------------

--
-- Table structure for table `buyers`
--

CREATE TABLE `buyers` (
  `buyer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location_address` varchar(500) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `preferences` varchar(1000) DEFAULT NULL,
  `buyer_agent` text DEFAULT NULL,
  `consent_text` longtext DEFAULT NULL,
  `consent_timestamp` datetime DEFAULT NULL,
  `terms_accepted` int(11) DEFAULT 0,
  `accepted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_consent_logs`
--

CREATE TABLE `buyer_consent_logs` (
  `log_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `consent_text` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `given_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_inquiries`
--

CREATE TABLE `buyer_inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_id` int(11) DEFAULT NULL,
  `location_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `inquiry_status` enum('active','archived','contacted') DEFAULT 'active',
  `interested_at` datetime DEFAULT current_timestamp(),
  `last_interaction` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_profiles`
--

CREATE TABLE `buyer_profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `preferences` text DEFAULT NULL,
  `location_address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_seller_messages`
--

CREATE TABLE `buyer_seller_messages` (
  `message_id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `sender_type` enum('buyer','seller') NOT NULL,
  `message_content` longtext NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credentialss`
--

CREATE TABLE `credentialss` (
  `ids` int(11) NOT NULL,
  `User` varchar(255) NOT NULL,
  `Pass` varchar(255) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credentialss`
--

INSERT INTO `credentialss` (`ids`, `User`, `Pass`, `Email`, `is_admin`) VALUES
(5, 'Raymund', '$2y$10$hRxCGGMgqpMYATWGlGzgaOZDb20qZF.XjVcV2YgHl4IEdVNmdQvGi', NULL, 0),
(10, 'Edna Jane', '$2y$10$gRXHn3Wqsep4zs8On.jpgOyYUTWQMqBWO/ULg4U3REg47ZzPtruWO', NULL, 0),
(11, 'admin', '$2y$10$h7.WFXI5088Z7NK8mqBMhed4nBK.JFvc.wnLd6PQICBzLLjEMVQ0a', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `socmed` varchar(255) DEFAULT NULL,
  `number` varchar(20) DEFAULT NULL,
  `location_address` varchar(500) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo` longblob DEFAULT NULL,
  `photo_name` varchar(255) DEFAULT NULL,
  `consent_text` longtext DEFAULT NULL,
  `consent_timestamp` datetime DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `saved_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `views_count` int(11) DEFAULT 0,
  `interested_buyers` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_notifications`
--

CREATE TABLE `seller_notifications` (
  `notif_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `message_summary` varchar(255) DEFAULT NULL,
  `dismissed_by_seller` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buyers`
--
ALTER TABLE `buyers`
  ADD PRIMARY KEY (`buyer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_location` (`latitude`,`longitude`);

--
-- Indexes for table `buyer_consent_logs`
--
ALTER TABLE `buyer_consent_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `buyer_inquiries`
--
ALTER TABLE `buyer_inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD UNIQUE KEY `unique_inquiry` (`user_id`,`location_id`,`seller_id`),
  ADD KEY `fk_inquiry_location` (`location_id`),
  ADD KEY `fk_inquiry_seller` (`seller_id`);

--
-- Indexes for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  ADD PRIMARY KEY (`profile_id`);

--
-- Indexes for table `buyer_seller_messages`
--
ALTER TABLE `buyer_seller_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_message_inquiry` (`inquiry_id`);

--
-- Indexes for table `credentialss`
--
ALTER TABLE `credentialss`
  ADD PRIMARY KEY (`ids`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_saved_at` (`saved_at`),
  ADD KEY `idx_user_location` (`user_id`,`saved_at`),
  ADD KEY `idx_nearby_sellers` (`latitude`,`longitude`),
  ADD KEY `idx_active_locations` (`user_id`,`saved_at`);

--
-- Indexes for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `inquiry_id` (`inquiry_id`),
  ADD KEY `idx_buyer_id` (`buyer_id`),
  ADD KEY `fk_notif_seller` (`seller_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buyers`
--
ALTER TABLE `buyers`
  MODIFY `buyer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `buyer_consent_logs`
--
ALTER TABLE `buyer_consent_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `buyer_inquiries`
--
ALTER TABLE `buyer_inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `buyer_profiles`
--
ALTER TABLE `buyer_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `buyer_seller_messages`
--
ALTER TABLE `buyer_seller_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `credentialss`
--
ALTER TABLE `credentialss`
  MODIFY `ids` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyers`
--
ALTER TABLE `buyers`
  ADD CONSTRAINT `fk_buyer_user` FOREIGN KEY (`user_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_inquiries`
--
ALTER TABLE `buyer_inquiries`
  ADD CONSTRAINT `fk_inquiry_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inquiry_seller` FOREIGN KEY (`seller_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inquiry_user` FOREIGN KEY (`user_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_seller_messages`
--
ALTER TABLE `buyer_seller_messages`
  ADD CONSTRAINT `fk_message_inquiry` FOREIGN KEY (`inquiry_id`) REFERENCES `buyer_inquiries` (`inquiry_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE;

--
-- Constraints for table `seller_notifications`
--
ALTER TABLE `seller_notifications`
  ADD CONSTRAINT `fk_notif_seller` FOREIGN KEY (`seller_id`) REFERENCES `credentialss` (`ids`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_notifications_ibfk_1` FOREIGN KEY (`inquiry_id`) REFERENCES `buyer_inquiries` (`inquiry_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
