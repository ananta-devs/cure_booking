-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 13, 2025 at 05:02 AM
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
-- Database: `cure_booking`
--

-- --------------------------------------------------------

----------------------------------------------------------
--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `clinic_id` int(11) NOT NULL,
  `clinic_name` varchar(255) NOT NULL,
  `clinic_email` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `location` text NOT NULL,
  `available_timing` varchar(100) NOT NULL,
  `clinic_pass` varchar(255) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`clinic_id`, `clinic_name`, `clinic_email`, `contact_number`, `location`, `available_timing`, `clinic_pass`, `profile_image`, `about`, `status`, `created_at`, `updated_at`) VALUES
(1, 'City Health Center', 'info@cityhealthcenter.com', '+1234567890', '123 Main Street, Downtown', '08:00 AM - 06:00 PM', 'password123', NULL, 'A modern healthcare facility providing comprehensive medical services to the community.', 'active', '2025-06-13 01:48:49', '2025-06-13 01:48:49'),
(2, 'Sunrise Medical Clinic', 'contact@sunriseclinic.com', '+1234567891', '456 Oak Avenue, Uptown', '09:00 AM - 09:00 PM', '$2y$10$example_hashed_password2', NULL, 'Specialized in family medicine and preventive care with experienced doctors.', 'active', '2025-06-13 01:48:49', '2025-06-13 01:48:49'),
(3, 'Valley Care Clinic', 'hello@valleycare.com', '+1234567892', '789 Pine Road, Valley District', '10:00 AM - 08:00 PM', '$2y$10$example_hashed_password3', NULL, 'Providing quality healthcare services with state-of-the-art medical equipment.', 'active', '2025-06-13 01:48:49', '2025-06-13 01:48:49');

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `doc_id` int(11) NOT NULL,
  `doc_name` varchar(255) NOT NULL,
  `doc_specia` varchar(255) NOT NULL,
  `doc_email` varchar(255) DEFAULT NULL,
  `fees` decimal(10,2) NOT NULL,
  `doc_img` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `doc_pass` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`doc_id`, `doc_name`, `doc_specia`, `doc_email`, `fees`, `doc_img`, `gender`, `experience`, `location`, `education`, `bio`, `doc_pass`, `created_at`) VALUES
(3, 'Raj Das', 'Neurologist', 'rajdas@gmail.com', 1500.00, '684b89a7e670a.jpg', 'male', 6, 'Kolkata', 'MD', 'Nothing', '$2y$10$b4j7HIidsnsDLE.KRp1bO.eTKsTY1L3nSGirvB9bsqIpEXGA4vq3i', '2025-06-13 02:15:04'),
(5, 'Amit Samanta', 'Dentist', 'amitrana@yahoo.com', 2000.00, '684b8a7924bf6.png', 'male', 2, 'Burdwan', 'MBBS, MD', 'Nothing', '$2y$10$iHC9tl21yKDNhWVaNXe1je29BR/s/lYH/I/dKTmLkKeXiLMeEX.Em', '2025-06-13 02:18:33'),
(6, 'Anik Pan', 'Dermatologist', 'anikpan@gmail.com', 1500.00, '684b8bd91daa9.jpg', 'male', 4, 'Jamalpur, Memari', 'MBBS', 'nothing', '$2y$10$EH5iQQjx.LdMLO3ebpiD9eZaETeVD47nwv9V4CinKFB5jNrnxX3jq', '2025-06-13 02:24:25');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_clinic_assignments`
--

CREATE TABLE `doctor_clinic_assignments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `availability_schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`availability_schedule`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_clinic_assignments`
--

INSERT INTO `doctor_clinic_assignments` (`id`, `doctor_id`, `clinic_id`, `availability_schedule`, `created_at`) VALUES
(5, 3, 1, '{\"monday\":{\"11:00-13:00\":true,\"14:00-16:00\":true,\"17:00-19:00\":false},\"tuesday\":{\"11:00-13:00\":false,\"14:00-16:00\":true,\"17:00-19:00\":true},\"wednesday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"thursday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"friday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"saturday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"sunday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false}}', '2025-06-13 02:15:04'),
(6, 3, 2, '{\"monday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":true},\"tuesday\":{\"11:00-13:00\":true,\"14:00-16:00\":false,\"17:00-19:00\":false},\"wednesday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"thursday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"friday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"saturday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"sunday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false}}', '2025-06-13 02:15:04'),
(9, 5, 1, '{\"monday\":{\"11:00-13:00\":true,\"14:00-16:00\":true,\"17:00-19:00\":false},\"tuesday\":{\"11:00-13:00\":false,\"14:00-16:00\":true,\"17:00-19:00\":true},\"wednesday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"thursday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"friday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"saturday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"sunday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false}}', '2025-06-13 02:18:33'),
(10, 5, 2, '{\"monday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":true},\"tuesday\":{\"11:00-13:00\":true,\"14:00-16:00\":false,\"17:00-19:00\":false},\"wednesday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"thursday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"friday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"saturday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"sunday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false}}', '2025-06-13 02:18:33'),
(11, 6, 1, '{\"monday\":{\"11:00-13:00\":false,\"14:00-16:00\":true,\"17:00-19:00\":true},\"tuesday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":true},\"wednesday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"thursday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"friday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"saturday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false},\"sunday\":{\"11:00-13:00\":false,\"14:00-16:00\":false,\"17:00-19:00\":false}}', '2025-06-13 02:24:25');

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `mobile`, `password`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 'Sudipta Samanta', 'samantasudipta2022@gmail.com', '', '$2y$10$QktQWSWPM7JfBQCPKePJmuRQOqN0451aNsakaS.tasody6DCOeJli', 1, '2025-06-11 14:59:13', '2025-06-11 14:59:13'),
(9, 'Ananta Hazra', 'anantahazra.bdn@gmail.com', '', '$2y$10$rOxF8hAzOwgtC1tYYctiluEBzDKvVesWe8MDg5IT5l.T5UO3jq1oG', 1, '2025-06-11 16:08:41', '2025-06-11 16:08:41'),
(12, 'Anik Santra', 'aniksantra1969@gmail.com', '', '$2y$10$LprGq5PRh57jVkP9DQF9de1TtLuvavkeYfleCB3LgkzI0MbMtn4De', 1, '2025-06-12 16:28:32', '2025-06-12 16:28:32');

--
-- Indexes for dumped tables
--



--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`),
  ADD UNIQUE KEY `clinic_email` (`clinic_email`),
  ADD KEY `idx_clinic_email` (`clinic_email`),
  ADD KEY `idx_clinic_status` (`status`),
  ADD KEY `idx_clinic_location` (`location`(100));

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `doctor_clinic_assignments`
--
ALTER TABLE `doctor_clinic_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--


--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `doctor_clinic_assignments`
--
ALTER TABLE `doctor_clinic_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--

-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `doctor_clinic_assignments`
--
ALTER TABLE `doctor_clinic_assignments`
  ADD CONSTRAINT `doctor_clinic_assignments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctor` (`doc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_clinic_assignments_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE CASCADE;

--

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
