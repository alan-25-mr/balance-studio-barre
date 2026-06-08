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
-- Table structure for table `reservaciones`
--

CREATE TABLE `reservaciones` (
  `id_reserva` int NOT NULL,
  `id_clase` int DEFAULT NULL,
  `id_alumna` int DEFAULT NULL,
  `fecha_clase` date DEFAULT NULL,
  `estatus` enum('Confirmada','Cancelada') DEFAULT 'Confirmada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reservaciones`
--

INSERT INTO `reservaciones` (`id_reserva`, `id_clase`, `id_alumna`, `fecha_clase`, `estatus`) VALUES
(1, 1, 1, '2026-04-27', 'Confirmada'),
(2, 6, 1, '2026-04-27', 'Confirmada');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD PRIMARY KEY (`id_reserva`),
  ADD KEY `id_clase` (`id_clase`),
  ADD KEY `id_alumna` (`id_alumna`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reservaciones`
--
ALTER TABLE `reservaciones`
  MODIFY `id_reserva` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD CONSTRAINT `reservaciones_ibfk_1` FOREIGN KEY (`id_clase`) REFERENCES `horarios` (`id_clase`),
  ADD CONSTRAINT `reservaciones_ibfk_2` FOREIGN KEY (`id_alumna`) REFERENCES `alumnas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
