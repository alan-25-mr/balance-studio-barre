-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 28, 2026 at 01:05 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `balance_final`
--

-- --------------------------------------------------------

--
-- Table structure for table `horarios`
--

CREATE TABLE `horarios` (
  `id_clase` int NOT NULL,
  `dia_semana` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `coach` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `horarios`
--

INSERT INTO `horarios` (`id_clase`, `dia_semana`, `hora_inicio`, `coach`) VALUES
(1, 'Lunes', '07:00:00', 'Coach Fany'),
(2, 'Martes', '07:00:00', 'Coach Fany'),
(3, 'Miércoles', '07:00:00', 'Coach Fany'),
(4, 'Jueves', '07:00:00', 'Coach Fany'),
(5, 'Viernes', '07:00:00', 'Coach Fany'),
(6, 'Lunes', '08:00:00', 'Coach Fati'),
(7, 'Miércoles', '08:00:00', 'Coach Fati'),
(8, 'Viernes', '08:00:00', 'Coach Fati'),
(9, 'Lunes', '17:00:00', 'Coach Fany'),
(10, 'Martes', '17:00:00', 'Coach Fany'),
(11, 'Miércoles', '17:00:00', 'Coach Fany'),
(12, 'Jueves', '17:00:00', 'Coach Fany'),
(13, 'Viernes', '17:00:00', 'Coach Fany'),
(14, 'Lunes', '18:00:00', 'Coach Fany'),
(15, 'Martes', '18:00:00', 'Coach Fany'),
(16, 'Miércoles', '18:00:00', 'Coach Fany'),
(17, 'Jueves', '18:00:00', 'Coach Fany'),
(18, 'Viernes', '18:00:00', 'Coach Fany'),
(19, 'Lunes', '19:00:00', 'Coach Fany'),
(20, 'Martes', '19:00:00', 'Coach Fany'),
(21, 'Miércoles', '19:00:00', 'Coach Fany'),
(22, 'Jueves', '19:00:00', 'Coach Fany'),
(23, 'Viernes', '19:00:00', 'Coach Fany'),
(24, 'Lunes', '20:00:00', 'Coach Fany'),
(25, 'Martes', '20:00:00', 'Coach Fany'),
(26, 'Miércoles', '20:00:00', 'Coach Fany'),
(27, 'Jueves', '20:00:00', 'Coach Fany'),
(28, 'Viernes', '20:00:00', 'Coach Fany');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id_clase`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id_clase` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
