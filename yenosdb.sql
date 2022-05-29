-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2022 at 12:16 PM
-- Server version: 10.4.16-MariaDB
-- PHP Version: 7.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `yenosdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_profile` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(60) NOT NULL,
  `level` enum('admin','user','','') NOT NULL,
  `activation` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Stand-in structure for view `account_details`
-- (See below for the actual view)
--
CREATE TABLE `account_details` (
`id` int(10) unsigned
,`username` varchar(50)
,`name` varchar(255)
,`phone` varchar(13)
,`email` varchar(255)
,`level` enum('admin','user','','')
,`activation` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `cover` varchar(300) NOT NULL,
  `description` text NOT NULL,
  `category` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`category`)),
  `datetime_added` datetime NOT NULL DEFAULT current_timestamp(),
  `datetime_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `article_logs`
--

CREATE TABLE `article_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_article` int(10) UNSIGNED NOT NULL,
  `id_account` int(10) UNSIGNED NOT NULL,
  `action_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `action_type` enum('add','edit','active','non-active') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  `id_article` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_article` int(10) UNSIGNED DEFAULT NULL,
  `id_account` int(10) UNSIGNED DEFAULT NULL,
  `content` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_account` int(10) UNSIGNED NOT NULL,
  `login_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('login','logout','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(13) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_account` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `action_type` enum('activation','forgetpass','','') NOT NULL,
  `action_datetime` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure for view `account_details`
--
DROP TABLE IF EXISTS `account_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `account_details`  AS SELECT `accounts`.`id` AS `id`, `accounts`.`username` AS `username`, `profiles`.`name` AS `name`, `profiles`.`phone` AS `phone`, `profiles`.`email` AS `email`, `accounts`.`level` AS `level`, `accounts`.`activation` AS `activation` FROM (`accounts` join `profiles` on(`accounts`.`id` = `profiles`.`id`)) GROUP BY `accounts`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_profile_accounts` (`id_profile`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_logs`
--
ALTER TABLE `article_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_account_article_logs` (`id_account`),
  ADD KEY `FK_id_article_article_logs` (`id_article`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_article_categories` (`id_article`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_article_comments` (`id_article`),
  ADD KEY `FK_id_account_comments` (`id_account`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_account_login_logs` (`id_account`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_id_account_user_tokens` (`id_account`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `article_logs`
--
ALTER TABLE `article_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `FK_id_profile_accounts` FOREIGN KEY (`id_profile`) REFERENCES `profiles` (`id`);

--
-- Constraints for table `article_logs`
--
ALTER TABLE `article_logs`
  ADD CONSTRAINT `FK_id_account_article_logs` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `FK_id_article_article_logs` FOREIGN KEY (`id_article`) REFERENCES `articles` (`id`);

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `FK_id_article_categories` FOREIGN KEY (`id_article`) REFERENCES `articles` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `FK_id_account_comments` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `FK_id_article_comments` FOREIGN KEY (`id_article`) REFERENCES `articles` (`id`);

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `FK_id_account_login_logs` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `FK_id_account_user_tokens` FOREIGN KEY (`id_account`) REFERENCES `accounts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
