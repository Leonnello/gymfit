-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2026 at 01:50 PM
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
-- Database: `gymfitdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `trainee_id` int(11) DEFAULT NULL,
  `trainer_id` int(11) DEFAULT NULL,
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
(2, 2, 4, '2025-10-13', '14:00:00', '15:30:00', 'cardio', 'Personal Training', 'Improve stamina', '', 'cancelled', 400.00, 1, '2025-10-10 20:38:09'),
(4, 3, 2, '2025-10-11', '08:00:00', '10:00:00', 'cardio', 'Personal Training', 'dasdas', '', 'accepted', 0.00, 1, '2025-10-10 20:43:20'),
(5, 3, 2, '2025-10-25', '10:00:00', '12:00:00', 'flexibility', 'Personal Training', 'qwerty1223', '3', 'completed', 1500.00, 1, '2025-10-24 10:45:30'),
(6, 3, 2, '2025-10-31', '10:30:00', '12:30:00', 'flexibility', 'Personal Training', 'dasdasdas', '5', 'completed', 2000.00, 1, '2025-10-29 11:31:39'),
(8, NULL, 2, '2025-12-05', '07:30:00', '08:00:00', 'cardio', 'Personal Training', '', '2', 'cancelled', 300.00, 1, '2025-12-05 00:59:59'),
(9, 1, 2, '2025-12-05', '07:00:00', '07:30:00', 'upper_body', 'Personal Training', '', '1', 'accepted', 0.00, 1, '2025-12-05 02:06:17'),
(10, NULL, 2, '2025-12-19', '07:00:00', '09:00:00', 'strength', 'Personal Training', '', '5', 'cancelled', 200.00, 1, '2025-12-05 02:12:59'),
(11, 2, 2, '2025-12-05', '15:30:00', '16:30:00', 'full_body', 'Personal Training', '', '2', 'cancelled', 200.00, 1, '2025-12-05 02:25:35'),
(13, 2, 2, '2025-12-08', '08:30:00', '11:30:00', 'flexibility', 'Personal Training', '', '3', 'cancelled', 0.00, 1, '2025-12-05 02:33:27'),
(14, 2, 2, '2025-12-06', '07:00:00', '08:30:00', 'full_body', 'Personal Training', '', '2', 'cancelled', 200.00, 1, '2025-12-05 02:38:39'),
(15, 1, 2, '2025-12-10', '07:00:00', '08:30:00', 'full_body', 'Personal Training', '', '2', 'cancelled', 200.00, 1, '2025-12-05 02:43:04'),
(16, NULL, 2, '2025-12-20', '07:00:00', '08:30:00', 'full_body', 'Personal Training', '', '2', 'cancelled', 200.00, 1, '2025-12-06 12:14:15'),
(18, 18, 2, '2025-12-08', '07:30:00', '09:00:00', 'lower_body', 'Personal Training', '', '2', 'accepted', 200.00, 1, '2025-12-08 09:30:30');

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
(1, 'Atisan', 4023),
(2, 'Bagong Bayan II-A', 4023),
(3, 'Bagong Pook VI-C', 4023),
(4, 'Barangay I-A', 4023),
(5, 'Barangay I-B', 4023),
(6, 'Barangay II-A', 4023),
(7, 'Barangay II-B', 4023),
(8, 'Barangay II-C', 4023),
(9, 'Barangay II-D', 4023),
(10, 'Barangay II-E', 4023),
(11, 'Barangay II-F', 4023),
(12, 'Barangay III-A', 4023),
(13, 'Barangay III-B', 4023),
(14, 'Barangay III-C', 4023),
(15, 'Barangay III-D', 4023),
(16, 'Barangay III-E', 4023),
(17, 'Barangay III-F', 4023),
(18, 'Barangay IV-A', 4023),
(19, 'Barangay IV-R', 4023),
(20, 'Barangay IV-C', 4023),
(21, 'Barangay V-A', 4023),
(22, 'Barangay V-B', 4023),
(23, 'Barangay V-C', 4023),
(24, 'Barangay V-D', 4023),
(25, 'Barangay VI-A', 4023),
(26, 'Barangay VI-B', 4023),
(27, 'Barangay VI-D', 4023),
(28, 'Barangay VI-E', 4023),
(29, 'Barangay VII-A', 4023),
(30, 'Barangay VII-B', 4023),
(31, 'Barangay VII-C', 4023),
(32, 'Barangay VII-D', 4023),
(33, 'Barangay VII-E', 4023),
(34, 'Bautista', 4023),
(35, 'Concepcion', 4023),
(36, 'Del Remedio', 4023),
(37, 'Dolores', 4023),
(38, 'San Antonio 1', 4023),
(39, 'San Antonio 2', 4023),
(40, 'San Bartolome', 4023),
(41, 'San Buenaventura', 4023),
(42, 'San Crispin', 4023),
(43, 'San Cristobal', 4023),
(44, 'San Diego', 4023),
(45, 'San Francisco', 4023),
(46, 'San Gabriel', 4023),
(47, 'San Gregorio', 4023),
(48, 'San Ignacio', 4023),
(49, 'San Isidro', 4023),
(50, 'San Joaquin', 4023),
(51, 'San Jose', 4023),
(52, 'San Juan', 4023),
(53, 'San Lorenzo', 4023),
(54, 'San Lucas 1', 4023),
(55, 'San Lucas 2', 4023),
(56, 'San Marcos', 4023),
(57, 'San Mateo', 4023),
(58, 'San Miguel', 4023),
(59, 'San Nicolas', 4023),
(60, 'San Pedro', 4023),
(61, 'San Rafael', 4023),
(62, 'San Roque', 4023),
(63, 'San Vicente', 4023),
(64, 'Santa Ana', 4023),
(65, 'Santa Catalina', 4023),
(66, 'Santa Cruz', 4023),
(67, 'Santa Elena', 4023),
(68, 'Santa Felomina', 4023),
(69, 'Santa Isabel', 4023),
(70, 'Santa Maria', 4023),
(71, 'Santa Maria Magdalena', 4023),
(72, 'Santa Monica', 4023),
(73, 'Santa Veronica', 4023),
(74, 'Santiago I', 4023),
(75, 'Santiago II', 4023),
(76, 'Santisimo Rosario', 4023),
(77, 'Santo Angel', 4023),
(78, 'Santo Cristo', 4023),
(79, 'Santo Niño', 4023),
(80, 'Soledad', 4023);

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
  `user1_id` int(11) DEFAULT NULL,
  `user2_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message` text DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user1_id`, `user2_id`, `created_at`, `last_message`, `last_message_at`) VALUES
(1, 3, 2, '2025-10-10 20:50:39', 'jgdjsgds', '2025-12-08 14:28:30'),
(2, 3, 1, '2025-10-10 20:50:39', NULL, NULL),
(4, 2, 18, '2025-12-08 11:42:34', 'hsgd', '2025-12-08 21:18:20');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users_archive`
--

CREATE TABLE `deleted_users_archive` (
  `archive_id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_users_archive`
--

INSERT INTO `deleted_users_archive` (`archive_id`, `original_id`, `firstName`, `lastName`, `email`, `role`, `deleted_at`) VALUES
(1, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:29:05'),
(2, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:29:13'),
(3, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:29:23'),
(4, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:30:12'),
(5, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:30:21'),
(6, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:34:35'),
(7, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:34:43'),
(8, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:36:10'),
(9, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:37:34'),
(10, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:37:41'),
(11, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:38:35'),
(12, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:44:37'),
(13, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:44:45'),
(14, 9, 'ley anne', 'Flores', 'leyanneflores19@gmail.com', 'trainee', '2025-12-08 08:53:13'),
(15, 7, 'Lea Grace', 'Arjona', 'leagracearjona0729@gmail.com', 'trainee', '2025-12-08 08:53:26'),
(16, 17, 'nics', 'pam', 'annamariejabian@gmail.com', 'trainee', '2025-12-08 09:27:34');

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
(4, 'Rowing Machine', 1, 'Concept2', 'Model D', 'C2-MD-98765', '2023-04-20', 80000.00, 'available', 'High-end rowing machine', '2023-09-05'),
(5, 'Leg Press Machine', 3, 'Cybex', 'Eagle', 'CY-EG-24680', '2023-05-15', 90000.00, 'out_of_order', 'Requires repair on footplate', '2023-06-10');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `message`, `created_at`) VALUES
(1, 1, 3, 'Hello trainer!', '2025-10-10 20:50:39'),
(2, 1, 2, 'Hi! How can I assist your training today?', '2025-10-10 20:50:39'),
(3, 2, 3, 'Hello admin!', '2025-10-10 20:50:39'),
(6, 1, 3, 'hi', '2025-10-10 20:58:04'),
(7, 1, 2, 'hello', '2025-10-10 21:24:05'),
(8, 1, 3, 'hi', '2025-11-20 08:59:43'),
(9, 1, 3, 'heloo', '2025-11-20 09:00:04'),
(10, 2, 1, 'hello', '2025-12-05 02:09:14'),
(11, 2, 1, 'hello', '2025-12-05 02:09:14'),
(12, 1, 2, 'nicole', '2025-12-08 11:42:45'),
(13, 1, 2, 'niwdhebd', '2025-12-08 04:56:31'),
(14, 4, 18, 'hello', '2025-12-08 12:09:23'),
(15, 4, 18, 'hello', '2025-12-08 12:09:23'),
(16, 4, 18, 'in', '2025-12-08 12:09:47'),
(17, 4, 18, 'in', '2025-12-08 12:09:47'),
(18, 4, 18, 'hello', '2025-12-08 12:23:33'),
(19, 4, 18, 'in', '2025-12-08 12:48:10'),
(20, 4, 18, 'ofc', '2025-12-08 12:51:58'),
(21, 4, 18, 'ofc', '2025-12-08 12:52:02'),
(22, 4, 18, 'in', '2025-12-08 13:01:42'),
(23, 4, 18, 'hin', '2025-12-08 13:01:51'),
(24, 4, 18, 'hellooooo', '2025-12-08 13:02:07'),
(25, 4, 18, 'hsjsdkwd', '2025-12-08 13:02:11'),
(26, 4, 18, 'hsgd', '2025-12-08 13:02:47'),
(27, 1, 2, 'bjbsd', '2025-12-08 06:03:41'),
(28, 4, 18, 'hi', '2025-12-08 13:18:20'),
(29, 4, 18, 'hi', '2025-12-08 13:18:20'),
(30, 1, 2, 'nigh', '2025-12-08 06:20:44'),
(31, 1, 2, 'hsid', '2025-12-08 06:28:20'),
(32, 1, 2, 'jgdjsgds', '2025-12-08 06:28:30');

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

--
-- Dumping data for table `signup_requests`
--

INSERT INTO `signup_requests` (`id`, `firstName`, `middleName`, `lastName`, `username`, `email`, `password`, `role`, `birthday`, `mobileNumber`, `street`, `barangay`, `city`, `province`, `zipCode`, `idImage`, `status`, `createdAt`) VALUES
(3, 'qwerty', 'weewq', 'vxcz', 'qwerty123', 'qwerty@gmail.com', '$2y$10$IdC5IIwu5OgOlTB725MiN..LdIvaimxKy2xRm37MfwFvCJthbxkSC', 'trainee', '1998-07-16', '0924631275', 'Mangga St', 'Barangay III-E', NULL, '8', '4023', 'uploads/1761736107_552652076_1540569024027797_2116362053324498621_n.jpg', 'pending', '2025-10-29 19:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `typing_status`
--

CREATE TABLE `typing_status` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_typing` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typing_status`
--

INSERT INTO `typing_status` (`conversation_id`, `user_id`, `is_typing`) VALUES
(4, 18, 0);

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
  `active_status` enum('online','offline','busy') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `middleName`, `lastName`, `username`, `email`, `contact`, `password`, `role`, `full_name`, `avatar`, `idImage`, `status`, `created_at`, `active_status`) VALUES
(1, 'admin', 'dsadas', 'admin', 'admin', 'admin@gymfit.com', 0, '482c811da5d5b4bc6d497ffa98491e38', 'admin', 'admin dsadas admin', '1761745841_69021bb173459.jpg', '', 'active', '2025-10-10 19:08:55', 'offline'),
(2, 'trainer1', 'dsadas', 'trainer1', 'trainer1', 'trainer@gymfit.com', 0, '482c811da5d5b4bc6d497ffa98491e38', 'trainer', 'trainer1 dsadas trainer1', '1761744175_6902152f510dc.jpeg', '1761744175_6902152f51269.jpeg', 'active', '2025-10-10 19:08:55', 'offline'),
(3, 'client1', 'fdsad', 'client1', 'client1', 'client@gymfit.com', 0, '482c811da5d5b4bc6d497ffa98491e38', 'client', 'client1 fdsad client1', '1761744068_690214c403782.jpeg', '1761744068_690214c403940.jpeg', 'active', '2025-10-10 19:08:55', 'offline'),
(4, 'owner1', 'owner1', 'owner1', 'owner1', 'owner@gymfit.com', 0, '482c811da5d5b4bc6d497ffa98491e38', 'owner', 'Owner One', NULL, '', 'active', '2025-10-10 19:08:55', 'offline'),
(18, 'Jelyn', 'Abistado', 'Dela Cruz', 'Jude', 'test@gmail.com', 0, '$2y$10$8pBizjcqI7xC2uFI6KaE2epiqqKKbqgmISth6sj.5BZ3M170jzU8S', 'trainee', NULL, NULL, '', 'active', '2025-12-08 09:29:40', 'offline');

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
  ADD PRIMARY KEY (`archive_id`);

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
-- Indexes for table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`conversation_id`,`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deleted_users_archive`
--
ALTER TABLE `deleted_users_archive`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `signup_requests`
--
ALTER TABLE `signup_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
