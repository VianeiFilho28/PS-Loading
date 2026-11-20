-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 20, 2026 at 06:03 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `solicitacao`
--

-- --------------------------------------------------------

--
-- Table structure for table `historico_solicitacoes`
--

DROP TABLE IF EXISTS `historico_solicitacoes`;
CREATE TABLE IF NOT EXISTS `historico_solicitacoes` (
  `id_hist` int NOT NULL AUTO_INCREMENT,
  `cod_solicitacao` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_registro` datetime NOT NULL,
  PRIMARY KEY (`id_hist`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `solicitacoes`
--

DROP TABLE IF EXISTS `solicitacoes`;
CREATE TABLE IF NOT EXISTS `solicitacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prioridade` int NOT NULL,
  `status` int NOT NULL,
  `cod` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_registro` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
