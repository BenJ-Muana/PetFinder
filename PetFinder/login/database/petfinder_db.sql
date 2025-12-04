-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 01:28 PM
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
-- Database: `petfinder_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `species` varchar(50) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `age` decimal(4,1) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `location` varchar(200) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('available','pending','adopted') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `user_id`, `name`, `species`, `breed`, `age`, `gender`, `description`, `location`, `contact_email`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(11, 9, 'Ben', 'Dog', 'Husky', 2.0, 'Female', 'Cute', 'Urgellio', 'jamesandrie@gmail.com', 'uploads/pets/pet_69313bcd6f0051.71868720.jpg', 'adopted', '2025-12-04 07:44:13', '2025-12-04 07:58:29'),
(14, 9, 'Maxtech', 'Cat', 'Pug', 2.0, 'Male', 'fS', 'Urgellio', 'jamesandrie@gmail.com', 'uploads/pets/pet_69314003c64163.26177397.jpg', 'available', '2025-12-04 08:02:11', '2025-12-04 08:02:11'),
(15, 9, 'reD', 'Cat', 'Cat', 2.0, 'Male', 'cAT', 'Urgellio', 'jamesandrie@gmail.com', '', 'available', '2025-12-04 08:02:54', '2025-12-04 08:02:54'),
(16, 9, 'MING ', 'Cat', 'Pug', 2.0, 'Female', 'FER', 'Urgellio', 'jamesandrie@gmail.com', 'uploads/pets/pet_69314070625ab0.18049133.jpg', 'available', '2025-12-04 08:04:00', '2025-12-04 08:04:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`) VALUES
(1, 'James Andrie', 'Tabanao', 'james@gmai.com', '$2y$10$34gsVaTKbHhwvrdrNMlqd.auUM4IX/44PjsIpgK42fG5Py/NfEZ7G'),
(9, 'Ben', 'nedict', 'ben@gmail.com', '$2y$10$BnHbm149S7NT4HAT9ZVvpOZCDTJALqVosOMu8M9dMF4VkAH9tag1S'),
(11, 'Ben', 'Josh', '123@gmail.com', '$2y$10$mWkBuAzhiW8dkL.wVTyYKOWhtmKGU0xEgokZwpY64oF3UXiPNY7XS');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_species` (`species`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`);

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
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
