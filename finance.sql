-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2026 at 08:21 PM
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
-- Database: `finance`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `account_number_last4` varchar(4) DEFAULT NULL,
  `type` enum('checking','savings','mobile_wallet','credit_card','loan') NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `current_balance` decimal(15,4) DEFAULT 0.0000,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `user_id`, `institution_id`, `name`, `account_number_last4`, `type`, `currency`, `current_balance`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'TeleBirr', '8852', 'mobile_wallet', 'ETB', 0.0000, 1, '2026-07-30 20:16:18'),
(2, 1, 1, 'TeleBirr', '4487', 'mobile_wallet', 'ETB', 0.0000, 1, '2026-07-30 20:16:51'),
(3, 1, 2, 'CBE Account1', '0535', 'savings', 'ETB', 36000.0000, 1, '2026-07-30 20:17:23'),
(4, 1, 4, 'Dashen Bank1', '9011', 'savings', 'ETB', 543.6700, 1, '2026-07-30 20:19:00'),
(5, 1, 3, 'Apollo BOA', '2208', 'savings', 'ETB', 11196.4900, 1, '2026-07-30 20:20:13'),
(7, 1, 5, 'Addis Bank', '9001', 'checking', 'ETB', 34000.0000, 1, '2026-07-30 20:22:41'),
(8, 3, 2, 'CBE Main Account', '0535', 'checking', 'ETB', 1500.0000, 1, '2026-11-03 19:01:37'),
(9, 3, 5, 'Addis Bank Share Account', '9001', 'savings', 'ETB', 55.0000, 1, '2026-11-03 19:04:14'),
(10, 3, 3, 'BOA Apollo Saving', '2208', 'savings', 'ETB', 2334.3400, 1, '2026-11-03 19:05:41'),
(11, 3, 6, 'CBE Birr Wallet', '8852', 'checking', 'ETB', 226.0000, 1, '2026-11-03 19:06:29'),
(12, 3, 2, 'CBE Saving', '4359', 'savings', 'ETB', 695.2600, 1, '2026-11-03 19:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) DEFAULT NULL,
  `asset_class` enum('stock','etf','bond','real_estate','crypto','precious_metal','private_equity') NOT NULL,
  `quantity` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `purchase_price_per_unit` decimal(15,4) NOT NULL,
  `current_market_price` decimal(15,4) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `last_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_price_history`
--

CREATE TABLE `asset_price_history` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `price` decimal(15,4) NOT NULL,
  `recorded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'ACCOUNT_CREATED', 'Created account \'TeleBirr\' with starting balance of 0 ETB', '2026-07-30 20:16:18'),
(2, 1, 'ACCOUNT_CREATED', 'Created account \'TeleBirr\' with starting balance of 0 ETB', '2026-07-30 20:16:51'),
(3, 1, 'ACCOUNT_CREATED', 'Created account \'CBE Account1\' with starting balance of 8071 ETB', '2026-07-30 20:17:23'),
(4, 1, 'ACCOUNT_CREATED', 'Created account \'Dashen Bank1\' with starting balance of 543.67 ETB', '2026-07-30 20:19:00'),
(5, 1, 'ACCOUNT_CREATED', 'Created account \'Apollo BOA\' with starting balance of 196.49 ETB', '2026-07-30 20:20:13'),
(6, 1, 'ACCOUNT_CREATED', 'Created account \'Addis Bank\' with starting balance of 34000 ETB', '2026-07-30 20:21:44'),
(7, 1, 'ACCOUNT_CREATED', 'Created account \'Addis Bank\' with starting balance of 34000 ETB', '2026-07-30 20:22:41'),
(8, 3, 'ACCOUNT_CREATED', 'Created account \'CBE Main Account\' with starting balance of 1500 ETB', '2026-11-03 19:01:37'),
(9, 3, 'ACCOUNT_CREATED', 'Created account \'Addis Bank Share Account\' with starting balance of 55 ETB', '2026-11-03 19:04:14'),
(10, 3, 'ACCOUNT_CREATED', 'Created account \'BOA Apollo Saving\' with starting balance of 2334.34 ETB', '2026-11-03 19:05:41'),
(11, 3, 'ACCOUNT_CREATED', 'Created account \'CBE Birr Wallet\' with starting balance of 226 ETB', '2026-11-03 19:06:29'),
(12, 3, 'ACCOUNT_CREATED', 'Created account \'CBE Saving\' with starting balance of 695.26 ETB', '2026-11-03 19:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount_limit` decimal(15,4) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('income','expense','transfer') NOT NULL,
  `icon` varchar(50) DEFAULT 'bi-tag',
  `color` varchar(20) DEFAULT '#6c757d'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `parent_id`, `name`, `type`, `icon`, `color`) VALUES
(1, 1, NULL, 'Gift', 'income', 'bi-gift', '#0d6efd'),
(2, 1, NULL, 'Upwork Freelance', 'income', 'bi-piggy-bank', '#0d6efd'),
(3, 1, NULL, 'Grocery', 'expense', 'bi-cart', '#fd0d19'),
(4, 1, NULL, 'House Rental', 'expense', 'bi-lightning', '#0d6efd'),
(5, 1, NULL, 'Bills', 'expense', 'bi-lightning', '#0d6efd'),
(6, 1, NULL, 'Other Expence', 'expense', 'bi-arrow-down-up', '#0d6efd'),
(7, 1, NULL, 'Hospital Expence', 'expense', 'bi-heart-pulse', '#0d6efd');

-- --------------------------------------------------------

--
-- Table structure for table `debts`
--

CREATE TABLE `debts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `person_or_entity` varchar(100) NOT NULL,
  `type` enum('lent','borrowed') NOT NULL,
  `total_amount` decimal(15,4) NOT NULL,
  `paid_amount` decimal(15,4) DEFAULT 0.0000,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','partially_paid','settled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institutions`
--

CREATE TABLE `institutions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('bank','mobile_wallet','brokerage','crypto_exchange','other') NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutions`
--

INSERT INTO `institutions` (`id`, `name`, `type`, `logo_url`) VALUES
(1, 'TeleBirr', 'mobile_wallet', NULL),
(2, 'Commercial Bank Of Ethiopia', 'bank', NULL),
(3, 'Bank Of Abyssinia', 'bank', NULL),
(4, 'Dashen Bank', 'bank', NULL),
(5, 'Addis Bank', 'bank', NULL),
(6, 'CBEBirr', 'mobile_wallet', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recurring_transactions`
--

CREATE TABLE `recurring_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(15,4) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
  `next_due_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_account_id` int(11) DEFAULT NULL,
  `to_account_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(15,4) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `type` enum('income','expense','transfer') NOT NULL,
  `transaction_date` datetime NOT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `from_account_id`, `to_account_id`, `category_id`, `amount`, `currency`, `type`, `transaction_date`, `description`, `reference_number`, `created_at`) VALUES
(1, 1, 3, NULL, 3, 200.0000, 'USD', 'expense', '2026-08-16 00:00:00', 'Grocery shopping', NULL, '2026-08-16 20:36:36'),
(2, 1, 3, NULL, 2, 80000.0000, 'USD', 'income', '2026-08-21 00:00:00', '', NULL, '2026-08-23 09:07:35'),
(3, 1, 3, NULL, 6, 10000.0000, 'USD', 'expense', '2026-08-22 00:00:00', 'Transfer for ribka', NULL, '2026-08-27 13:44:14'),
(4, 1, 3, NULL, 4, 13000.0000, 'USD', 'expense', '2026-08-27 00:00:00', 'House Rental Payment', NULL, '2026-08-27 13:44:37'),
(5, 1, 3, NULL, 5, 2000.0000, 'USD', 'expense', '2026-08-21 00:00:00', 'Wifi Bill', NULL, '2026-08-27 13:45:15'),
(6, 1, 3, NULL, 6, 11000.0000, 'USD', 'expense', '2026-08-27 00:00:00', '', NULL, '2026-08-27 13:45:42'),
(7, 1, 3, 5, NULL, 11000.0000, 'USD', 'transfer', '2026-08-27 00:00:00', 'Internal Transfer', NULL, '2026-08-27 13:46:51'),
(8, 1, 3, NULL, 3, 6000.0000, 'USD', 'expense', '2026-08-27 00:00:00', 'Grocery', NULL, '2026-08-27 13:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `base_currency` varchar(3) DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `base_currency`, `created_at`) VALUES
(1, 'Abel Mesay', 'abelmesay49@gmail.com', '$2y$10$/EnpKEwwXbbX7lIRdYdFlOW8obYhYdErbTL6Pq7jQa9KlK1YL7xG2', 'USD', '2026-07-30 20:06:22'),
(2, 'Abel Mesay', 'abelmesa830@gmail.com', '$2y$10$5NqLEAqLlc7xWuBCRK4L0es9wWbfNBkem.FkNIg.vMu31QSEOEgaW', 'USD', '2026-11-03 18:59:45'),
(3, 'Abel Mesay', 'abelmesay830@gmail.com', '$2y$10$vtgdF/Zf1eETdTObgwMk7uTVohQrBYLWcdT86299jdNukYD74FEui', 'USD', '2026-11-03 19:00:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `institution_id` (`institution_id`),
  ADD KEY `idx_user_account` (`user_id`,`is_active`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `institution_id` (`institution_id`);

--
-- Indexes for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `debts`
--
ALTER TABLE `debts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_account_id` (`from_account_id`),
  ADD KEY `to_account_id` (`to_account_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_user_tx` (`user_id`,`transaction_date`);

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
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `debts`
--
ALTER TABLE `debts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institutions`
--
ALTER TABLE `institutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `accounts_ibfk_2` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_price_history`
--
ALTER TABLE `asset_price_history`
  ADD CONSTRAINT `asset_price_history_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `debts`
--
ALTER TABLE `debts`
  ADD CONSTRAINT `debts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recurring_transactions`
--
ALTER TABLE `recurring_transactions`
  ADD CONSTRAINT `recurring_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recurring_transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`from_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
