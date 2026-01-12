-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 02:21 PM
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
-- Database: `gymfitdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `training_regime` enum('full_body','upper_body','lower_body','cardio','strength','flexibility','hiit','recovery') NOT NULL,
  `type` varchar(100) DEFAULT 'Personal Training',
  `notes` text DEFAULT NULL,
  `session_days` varchar(255) NOT NULL,
  `status` enum('pending','accepted','cancelled','declined','completed') DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT 0.00,
  `is_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `trainee_id`, `trainer_id`, `date`, `start_time`, `end_time`, `training_regime`, `type`, `notes`, `session_days`, `status`, `amount`, `is_paid`, `created_at`) VALUES
(1, 1, 3, '2025-10-12', '09:00:00', '10:00:00', 'full_body', 'Personal Training', 'Focus on strength and endurance', '', 'pending', 500.00, 1, '2025-10-10 20:38:09'),
(15, 3, 14, '2025-11-30', '07:00:00', '08:00:00', 'lower_body', 'Personal Training', '', '1', 'pending', 0.00, 0, '2025-11-29 12:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `zip_code` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`id`, `name`, `zip_code`) VALUES
(1, 'Atisan', 4000),
(2, 'Bagong Bayan II-A', 4000),
(3, 'Bagong Pook VI-C', 4000),
(4, 'Barangay I-A', 4000),
(5, 'Barangay I-B', 4000),
(6, 'Barangay II-A', 4000),
(7, 'Barangay II-B', 4000),
(8, 'Barangay II-C', 4000),
(9, 'Barangay II-D', 4000),
(10, 'Barangay II-E', 4000),
(11, 'Barangay II-F', 4000),
(12, 'Barangay III-A', 4000),
(13, 'Barangay III-B', 4000),
(14, 'Barangay III-C', 4000),
(15, 'Barangay III-D', 4000),
(16, 'Barangay III-E', 4000),
(17, 'Barangay III-F', 4000),
(18, 'Barangay IV-A', 4000),
(19, 'Barangay IV-R', 4000),
(20, 'Barangay IV-C', 4000),
(21, 'Barangay V-A', 4000),
(22, 'Barangay V-B', 4000),
(23, 'Barangay V-C', 4000),
(24, 'Barangay V-D', 4000),
(25, 'Barangay VI-A', 4000),
(26, 'Barangay VI-B', 4000),
(27, 'Barangay VI-D', 4000),
(28, 'Barangay VI-E', 4000),
(29, 'Barangay VII-A', 4000),
(30, 'Barangay VII-B', 4000),
(31, 'Barangay VII-C', 4000),
(32, 'Barangay VII-D', 4000),
(33, 'Barangay VII-E', 4000),
(34, 'Bautista', 4000),
(35, 'Concepcion', 4000),
(36, 'Del Remedio', 4000),
(37, 'Dolores', 4000),
(38, 'San Antonio 1', 4000),
(39, 'San Antonio 2', 4000),
(40, 'San Bartolome', 4000),
(41, 'San Buenaventura', 4000),
(42, 'San Crispin', 4000),
(43, 'San Cristobal', 4000),
(44, 'San Diego', 4000),
(45, 'San Francisco', 4000),
(46, 'San Gabriel', 4000),
(47, 'San Gregorio', 4000),
(48, 'San Ignacio', 4000),
(49, 'San Isidro', 4000),
(50, 'San Joaquin', 4000),
(51, 'San Jose', 4000),
(52, 'San Juan', 4000),
(53, 'San Lorenzo', 4000),
(54, 'San Lucas 1', 4000),
(55, 'San Lucas 2', 4000),
(56, 'San Marcos', 4000),
(57, 'San Mateo', 4000),
(58, 'San Miguel', 4000),
(59, 'San Nicolas', 4000),
(60, 'San Pedro', 4000),
(61, 'San Rafael', 4000),
(62, 'San Roque', 4000),
(63, 'San Vicente', 4000),
(64, 'Santa Ana', 4000),
(65, 'Santa Catalina', 4000),
(66, 'Santa Cruz', 4000),
(67, 'Santa Elena', 4000),
(68, 'Santa Felomina', 4000),
(69, 'Santa Isabel', 4000),
(70, 'Santa Maria', 4000),
(71, 'Santa Maria Magdalena', 4000),
(72, 'Santa Monica', 4000),
(73, 'Santa Veronica', 4000),
(74, 'Santiago I', 4000),
(75, 'Santiago II', 4000),
(76, 'Santisimo Rosario', 4000),
(77, 'Santo Angel', 4000),
(78, 'Santo Cristo', 4000),
(79, 'Santo Niño', 4000),
(80, 'Soledad', 4000);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Cardio', 'Cardiovascular exercise equipment'),
(2, 'Strength', 'Strength training equipment'),
(3, 'Machines', 'Other gym machines');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_by_user1` tinyint(1) DEFAULT 0,
  `deleted_by_user2` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `created_at`, `last_message_at`, `deleted_by_user1`, `deleted_by_user2`, `is_deleted`, `deleted_at`, `deleted_by`) VALUES
(2, 3, 1, '2025-10-10 20:50:39', '2025-11-25 07:01:43', 1, 0, 0, NULL, NULL),
(11, 3, 14, '2025-11-29 12:43:41', '2025-11-29 12:44:19', 1, 0, 0, NULL, NULL),
(12, 27, 14, '2025-12-01 05:15:18', '2025-12-01 05:15:18', 0, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users_archive`
--

CREATE TABLE `deleted_users_archive` (
  `id` int(11) NOT NULL,
  `original_id` int(11) DEFAULT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('admin','trainer','trainor','client') DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_users_archive`
--

INSERT INTO `deleted_users_archive` (`id`, `original_id`, `firstName`, `lastName`, `email`, `role`, `deleted_at`) VALUES
(1, 12, 'Mark', 'Comiso', 'jmykyap@gmail.com', '', '2025-11-25 10:05:38'),
(2, 15, 'anne', 'Smith', 'anne@gmail.com', '', '2025-11-29 11:59:12');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `status` enum('available','maintenance','out_of_order') DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `category_id`, `brand`, `model`, `serial_number`, `purchase_date`, `purchase_price`, `status`, `notes`, `last_maintenance`) VALUES
(1, 'Treadmill', 1, 'LifeFitness', 'T5', 'LF-T5-12345', '2023-01-15', 120000.00, 'available', 'Used for daily cardio sessions', '2023-09-01'),
(2, 'Bench Press', 2, 'Rogue', 'Flat Utility Bench', 'RG-FUB-67890', '2023-02-10', 15000.00, 'available', 'Standard flat bench', '2023-08-15'),
(3, 'Dumbbells Set', 2, 'Hammer Strength', 'Rubber Hex', 'HS-RH-54321', '2023-03-05', 25000.00, 'available', 'Set of 10 pairs', '2023-07-20'),
(4, 'Rowing Machine', 1, 'Concept2', 'Model D', 'C2-MD-98765', '2023-04-20', 80000.00, 'available', 'High-end rowing machine', '2023-09-05');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message`, `created_at`, `is_deleted`, `deleted_by`, `deleted_at`) VALUES
(3, 2, 3, 'Hello admin!', '2025-10-10 20:50:39', 0, NULL, NULL),
(16, 11, 3, 'hi', '2025-11-29 12:44:19', 0, NULL, NULL),
(17, 11, 3, 'hi', '2025-11-29 12:44:19', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `zone` varchar(50) NOT NULL,
  `region_name` varchar(100) NOT NULL,
  `zip_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `zone`, `region_name`, `zip_code`) VALUES
(1, 'Luzon', 'Ilocos Region (Region I)', '2900'),
(2, 'Luzon', 'Cagayan Valley (Region II)', '3500'),
(3, 'Luzon', 'Central Luzon (Region III)', '2000'),
(4, 'Luzon', 'CALABARZON (Region IV-A)', '4027'),
(5, 'Luzon', 'MIMAROPA (Region IV-B)', '5300'),
(6, 'Luzon', 'Bicol Region (Region V)', '4500'),
(7, 'Luzon', 'Cordillera Administrative Region (CAR)', '2600'),
(8, 'Luzon', 'National Capital Region (NCR / Metro Manila)', '1000'),
(9, 'Visayas', 'Western Visayas (Region VI)', '5000'),
(10, 'Visayas', 'Central Visayas (Region VII)', '6000'),
(11, 'Visayas', 'Eastern Visayas (Region VIII)', '6500'),
(12, 'Mindanao', 'Zamboanga Peninsula (Region IX)', '7000'),
(13, 'Mindanao', 'Northern Mindanao (Region X)', '9000'),
(14, 'Mindanao', 'Davao Region (Region XI)', '8000'),
(15, 'Mindanao', 'SOCCSKSARGEN (Region XII)', '9500'),
(16, 'Mindanao', 'Caraga (Region XIII)', '8600'),
(17, 'Mindanao', 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)', '9600');

-- --------------------------------------------------------

--
-- Table structure for table `signup_requests`
--

CREATE TABLE `signup_requests` (
  `id` int(11) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `middleName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('trainee','trainor') DEFAULT 'trainee',
  `birthday` date NOT NULL,
  `mobileNumber` varchar(20) NOT NULL,
  `street` varchar(100) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zipCode` varchar(10) DEFAULT NULL,
  `idImage` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `createdAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `middleName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact` int(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','trainer','trainor','client','trainee','owner') NOT NULL DEFAULT 'client',
  `full_name` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `idImage` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_code` varchar(10) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `active_status` enum('online','offline','busy') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `middleName`, `lastName`, `username`, `email`, `contact`, `password`, `role`, `full_name`, `avatar`, `idImage`, `status`, `created_at`, `reset_code`, `reset_expires`, `active_status`) VALUES
(1, 'admin', 'comiso', 'admin', 'admin', 'admin@gymfit.com', 2147483647, '482c811da5d5b4bc6d497ffa98491e38', 'admin', 'admin dsadas admin', '1761745841_69021bb173459.jpg', '', 'active', '2025-10-10 19:08:55', NULL, NULL, 'offline'),
(3, 'Min', '', 'Hoo', 'minmin', 'min@gmail.com', 0, '482c811da5d5b4bc6d497ffa98491e38', 'trainee', 'Min Hoo', '', '', 'active', '2025-10-10 19:08:55', '752924', '2025-11-25 11:14:10', 'offline'),
(14, 'mike', 'Smith', 'Hill', 'mike', 'mike@gmail.com', 2147483647, '$2y$10$K7cRta1xMCYdCFQ9W.q70OntiVCcdOHW.ST3xFuDCLdOYaz9cCECy', 'trainer', 'mike Smith Hill', 'uploads/1763807214_avatar_poeboston_2016_04_20_14_14_58.jpg', 'uploads/1763807214_id_Untitled 2.png', 'active', '2025-11-22 10:26:55', NULL, NULL, 'offline'),
(25, 'Mark', 'Cordon', 'Balmes', 'mark_01', 'jmykyap@gmail.com', 0, '6de9603c56986eb9595d0c8a974e32ea', 'trainee', NULL, NULL, '', 'active', '2025-11-29 14:19:26', NULL, NULL, 'offline'),
(27, 'Jaira', '', 'Balmes', 'jaira25', 'jai@gmail.com', 0, '482c811da5d5b4bc6d497ffa98491e38', 'trainee', NULL, NULL, '', 'active', '2025-12-01 05:14:40', NULL, NULL, 'offline');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user1_id` (`user1_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `deleted_users_archive`
--
ALTER TABLE `deleted_users_archive`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `signup_requests`
--
ALTER TABLE `signup_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `deleted_users_archive`
--
ALTER TABLE `deleted_users_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `signup_requests`
--
ALTER TABLE `signup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `equipment_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
