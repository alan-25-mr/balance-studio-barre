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
-- Table structure for table `alumnas`
--

CREATE TABLE `alumnas` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `clases_restantes` int NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `monto_pagado` decimal(10,2) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estatus` enum('Activa','Inactiva') DEFAULT 'Activa',
  `lesiones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `alumnas`
--

INSERT INTO `alumnas` (`id`, `nombre`, `clases_restantes`, `telefono`, `monto_pagado`, `fecha_vencimiento`, `estatus`, `lesiones`) VALUES
(1, 'Fany Salas', 0, '2821031529', 200.00, '2026-05-12', 'Activa', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumnas`
--
ALTER TABLE `alumnas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumnas`
--
ALTER TABLE `alumnas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
