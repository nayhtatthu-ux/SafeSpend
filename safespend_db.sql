-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 12:40 PM
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
-- Database: `safespend_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username_attempt` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `username_attempt`, `created_at`) VALUES
(1, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 10:40:40'),
(2, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 10:40:52'),
(3, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 10:43:18'),
(4, 1, 'DELETE_TRANSACTION', 'Deleted transaction #1 (Singha).', '::1', NULL, '2026-03-25 10:44:28'),
(5, 1, 'UPDATE_TRANSACTION', 'Updated transaction #6 for category Transportation.', '::1', NULL, '2026-03-25 10:45:57'),
(6, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 11:02:28'),
(7, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 11:18:41'),
(8, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 12:00:44'),
(9, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 12:03:49'),
(10, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 12:11:38'),
(11, 5, 'REGISTER', 'New account created.', '::1', NULL, '2026-03-25 12:12:31'),
(12, 5, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 12:13:01'),
(13, 6, 'REGISTER', 'New account created.', '::1', NULL, '2026-03-25 12:13:29'),
(14, 6, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 12:14:00'),
(15, 6, 'PASSWORD_RESET_REQUEST', 'Password reset requested by email.', '::1', 'heinpyaesonephyo2004@gmail.com', '2026-03-25 12:16:24'),
(16, 1, 'PASSWORD_RESET_REQUEST', 'Password reset requested by email.', '::1', 'hetthunay@gmail.com', '2026-03-25 12:16:37'),
(17, 1, 'PASSWORD_RESET_REQUEST', 'Password reset requested by email.', '::1', 'hetthunay@gmail.com', '2026-03-25 12:17:16'),
(18, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 12:25:51'),
(19, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 12:26:12'),
(20, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 12:26:18'),
(21, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 12:26:24'),
(22, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 12:26:29'),
(23, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 12:26:34'),
(24, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 12:26:42'),
(25, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 12:28:21'),
(26, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 14:09:43'),
(27, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 14:09:49'),
(28, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 14:09:53'),
(29, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 14:09:57'),
(30, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 14:10:01'),
(31, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 14:10:07'),
(32, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 14:12:01'),
(33, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 14:29:35'),
(34, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 14:29:46'),
(35, 1, 'CHANGE_PASSWORD', 'Password changed from profile page.', '::1', NULL, '2026-03-25 14:56:32'),
(36, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 14:56:35'),
(37, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-25 14:56:41'),
(38, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 14:56:49'),
(39, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 15:13:10'),
(40, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 15:13:23'),
(41, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Drinks.', '::1', NULL, '2026-03-25 15:24:12'),
(42, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 15:40:53'),
(43, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 15:41:00'),
(44, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 15:41:27'),
(45, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 15:42:41'),
(46, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 17:48:28'),
(47, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 17:49:31'),
(48, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 17:51:07'),
(49, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 17:51:19'),
(50, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 19:26:53'),
(51, 7, 'REGISTER', 'New account created.', '::1', NULL, '2026-03-25 19:27:18'),
(52, 7, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 19:27:23'),
(53, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-25 19:29:56'),
(54, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-25 19:30:01'),
(55, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'noah', '2026-03-25 19:30:11'),
(56, 1, 'GOAL_CONTRIBUTION', 'Added a contribution to goal #2.', '::1', NULL, '2026-03-25 19:32:36'),
(57, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 10:19:47'),
(58, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'noah', '2026-03-26 10:19:56'),
(59, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Drinks.', '::1', NULL, '2026-03-26 12:38:14'),
(60, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 12:38:39'),
(61, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'noah', '2026-03-26 12:47:36'),
(62, 1, 'CHANGE_PASSWORD', 'Password changed from profile edit page.', '::1', NULL, '2026-03-26 13:34:02'),
(63, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 13:34:08'),
(64, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 13:34:13'),
(65, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-26 13:34:21'),
(66, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 17:07:27'),
(67, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-26 17:07:36'),
(68, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 19:02:26'),
(69, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-26 19:24:35'),
(70, 1, 'CREATE_CATEGORY', 'Created category Meal.', '::1', NULL, '2026-03-26 19:26:22'),
(71, 1, 'CREATE_CATEGORY', 'Created category Suscription.', '::1', NULL, '2026-03-26 19:26:36'),
(72, 1, 'CREATE_CATEGORY', 'Created category Gift.', '::1', NULL, '2026-03-26 19:26:44'),
(73, 1, 'ADD_TRANSACTION', 'Added Income transaction for category Gift.', '::1', NULL, '2026-03-26 19:27:28'),
(74, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Meal.', '::1', NULL, '2026-03-26 19:28:00'),
(75, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Transportation.', '::1', NULL, '2026-03-26 19:29:06'),
(76, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Groceries.', '::1', NULL, '2026-03-26 19:45:18'),
(77, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 20:40:30'),
(78, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 20:44:48'),
(79, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 20:45:15'),
(80, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 20:45:22'),
(81, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 20:45:27'),
(82, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 20:45:34'),
(83, NULL, 'LOGIN_FAIL', 'Invalid login credentials.', '::1', 'Noah', '2026-03-26 20:48:39'),
(84, 8, 'REGISTER', 'New account created.', '::1', NULL, '2026-03-26 20:49:50'),
(85, 8, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-26 20:53:45'),
(86, 1, 'LOGIN_SUCCESS', 'User signed in successfully.', '::1', 'Noah', '2026-03-26 20:53:53'),
(87, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Suscription.', '::1', NULL, '2026-03-26 20:58:42'),
(88, 1, 'UPDATE_TRANSACTION', 'Updated transaction #10 for category Drinks.', '::1', NULL, '2026-03-26 20:59:58'),
(89, 1, 'DELETE_TRANSACTION', 'Deleted transaction #10 (Tiger).', '::1', NULL, '2026-03-26 21:00:15'),
(90, 1, 'UPDATE_TRANSACTION', 'Updated transaction #15 for category Groceries.', '::1', NULL, '2026-03-26 21:04:25'),
(91, 1, 'DELETE_BUDGET', 'Deleted budget #2.', '::1', NULL, '2026-03-26 21:06:41'),
(92, 1, 'SAVE_BUDGET', 'Saved annual budget for category Drinks in 2026.', '::1', NULL, '2026-03-26 21:06:55'),
(93, 1, 'CREATE_GOAL', 'Created savings goal MAC BOOK.', '::1', NULL, '2026-03-27 05:34:50'),
(94, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Groceries.', '::1', NULL, '2026-03-27 05:36:59'),
(95, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Meal.', '::1', NULL, '2026-03-27 05:37:39'),
(96, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Suscription.', '::1', NULL, '2026-03-27 05:37:59'),
(97, 1, 'ADD_TRANSACTION', 'Added Expense transaction for category Drinks.', '::1', NULL, '2026-03-27 05:38:44'),
(98, 1, 'UPDATE_TRANSACTION', 'Updated transaction #9 for category Concert.', '::1', NULL, '2026-03-27 05:39:24'),
(99, 1, 'CHANGE_PASSWORD', 'Password changed from profile edit page.', '::1', NULL, '2026-03-27 05:41:38'),
(100, 1, 'CHANGE_PASSWORD', 'Password changed from profile edit page.', '::1', NULL, '2026-03-27 11:16:42'),
(101, 1, 'LOGOUT', 'User signed out.', '::1', NULL, '2026-03-27 11:32:30');

-- --------------------------------------------------------

--
-- Table structure for table `bankbalance`
--

CREATE TABLE `bankbalance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `confirmed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bankbalance`
--

INSERT INTO `bankbalance` (`id`, `user_id`, `balance`, `confirmed`, `created_at`) VALUES
(10, 1, 53.01, 1, '2026-03-23 19:48:59'),
(11, 2, 0.00, 1, '2026-03-24 14:22:29'),
(12, 3, 0.00, 1, '2026-03-25 10:50:31'),
(13, 4, 0.00, 1, '2026-03-25 11:16:12'),
(14, 5, 0.00, 1, '2026-03-25 12:12:31'),
(15, 6, 0.00, 1, '2026-03-25 12:13:29'),
(16, 7, 0.00, 1, '2026-03-25 19:27:18'),
(17, 8, 0.00, 1, '2026-03-26 20:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `budget_month` tinyint(4) NOT NULL,
  `budget_year` smallint(6) NOT NULL,
  `emoji` varchar(16) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `user_id`, `category_id`, `amount`, `budget_month`, `budget_year`, `emoji`, `created_at`) VALUES
(6, 1, 2, 20.00, 1, 2026, NULL, '2026-03-24 17:19:10'),
(7, 1, 2, 20.00, 2, 2026, NULL, '2026-03-24 17:19:10'),
(8, 1, 2, 20.00, 4, 2026, NULL, '2026-03-24 17:19:10'),
(9, 1, 2, 20.00, 5, 2026, NULL, '2026-03-24 17:19:10'),
(10, 1, 2, 20.00, 6, 2026, NULL, '2026-03-24 17:19:10'),
(11, 1, 2, 20.00, 7, 2026, NULL, '2026-03-24 17:19:10'),
(12, 1, 2, 20.00, 8, 2026, NULL, '2026-03-24 17:19:10'),
(13, 1, 2, 20.00, 9, 2026, NULL, '2026-03-24 17:19:10'),
(14, 1, 2, 20.00, 10, 2026, NULL, '2026-03-24 17:19:10'),
(15, 1, 2, 20.00, 11, 2026, NULL, '2026-03-24 17:19:10'),
(16, 1, 2, 20.00, 12, 2026, NULL, '2026-03-24 17:19:10'),
(17, 1, 3, 500.00, 1, 2026, NULL, '2026-03-24 17:20:41'),
(18, 1, 3, 500.00, 2, 2026, NULL, '2026-03-24 17:20:41'),
(19, 1, 3, 500.00, 3, 2026, NULL, '2026-03-24 17:20:41'),
(20, 1, 3, 500.00, 4, 2026, NULL, '2026-03-24 17:20:41'),
(21, 1, 3, 500.00, 5, 2026, NULL, '2026-03-24 17:20:41'),
(22, 1, 3, 500.00, 6, 2026, NULL, '2026-03-24 17:20:41'),
(23, 1, 3, 500.00, 7, 2026, NULL, '2026-03-24 17:20:41'),
(24, 1, 3, 500.00, 8, 2026, NULL, '2026-03-24 17:20:41'),
(25, 1, 3, 500.00, 9, 2026, NULL, '2026-03-24 17:20:41'),
(26, 1, 3, 500.00, 10, 2026, NULL, '2026-03-24 17:20:41'),
(27, 1, 3, 500.00, 11, 2026, NULL, '2026-03-24 17:20:41'),
(28, 1, 3, 500.00, 12, 2026, NULL, '2026-03-24 17:20:41'),
(31, 1, 2, 20.00, 3, 2026, NULL, '2026-03-26 21:06:55');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Saving','Expense') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `created_at`) VALUES
(1, 1, 'Salary', 'Saving', '2026-03-23 19:54:58'),
(2, 1, 'Drinks', 'Expense', '2026-03-23 19:55:05'),
(3, 1, 'Groceries', 'Expense', '2026-03-24 11:31:53'),
(4, 1, 'Transportation', 'Expense', '2026-03-24 23:23:50'),
(5, 1, 'Concert', 'Expense', '2026-03-25 10:57:21'),
(6, 1, 'Meal', 'Expense', '2026-03-26 19:26:22'),
(7, 1, 'Suscription', 'Expense', '2026-03-26 19:26:36'),
(8, 1, 'Gift', 'Saving', '2026-03-26 19:26:44');

-- --------------------------------------------------------

--
-- Table structure for table `saving_goals`
--

CREATE TABLE `saving_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal_name` varchar(120) NOT NULL,
  `target_amount` decimal(12,2) NOT NULL,
  `current_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saving_goals`
--

INSERT INTO `saving_goals` (`id`, `user_id`, `goal_name`, `target_amount`, `current_amount`, `deadline`, `created_at`) VALUES
(1, 1, 'For Hoodie', 1000.00, 100.00, '2026-03-31', '2026-03-24 19:42:09'),
(2, 1, 'New Phone', 1000.00, 100.00, '2026-04-04', '2026-03-24 23:10:32'),
(3, 1, 'MAC BOOK', 1000.00, 0.00, '2026-04-27', '2026-03-27 05:34:50');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `type` enum('Saving','Expense') NOT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `description`, `amount`, `type`, `category_id`, `user_id`, `date`, `created_at`) VALUES
(2, 'Lyods', 22.00, 'Saving', 1, 1, '2026-03-23', '2026-03-23 21:19:45'),
(3, 'New', 50.00, 'Saving', 1, 1, '2026-03-22', '2026-03-23 21:22:00'),
(5, 'Salary', 200.00, 'Saving', 1, 1, '2026-02-28', '2026-03-24 17:07:43'),
(6, 'To Work', 10.00, 'Expense', 4, 1, '2026-03-25', '2026-03-24 23:24:10'),
(7, 'To work', 12.00, 'Expense', 4, 1, '2026-03-24', '2026-03-24 23:32:07'),
(8, 'To Work', 13.00, 'Expense', 4, 1, '2026-03-23', '2026-03-24 23:32:35'),
(9, 'The 1975', 60.00, 'Expense', 5, 1, '2026-02-25', '2026-03-25 10:57:57'),
(11, 'Change', 11.00, 'Expense', 2, 1, '2026-02-26', '2026-03-26 12:38:14'),
(12, 'Suzz Gyi', 10.00, 'Saving', 8, 1, '2026-03-01', '2026-03-26 19:27:28'),
(13, 'Moe\'s', 9.99, 'Expense', 6, 1, '2026-03-01', '2026-03-26 19:28:00'),
(14, 'To Work', 3.00, 'Expense', 4, 1, '2026-03-02', '2026-03-26 19:29:06'),
(15, 'ALDI', 40.00, 'Expense', 3, 1, '2026-03-19', '2026-03-26 19:45:18'),
(16, 'Netflix', 6.00, 'Expense', 7, 1, '2026-03-17', '2026-03-26 20:58:42'),
(17, 'ALDI', 22.00, 'Expense', 3, 1, '2026-02-14', '2026-03-27 05:36:59'),
(18, 'Wagamama', 22.00, 'Expense', 6, 1, '2026-02-12', '2026-03-27 05:37:39'),
(19, 'Spotify', 5.00, 'Expense', 7, 1, '2026-02-10', '2026-03-27 05:37:59'),
(20, 'Hatfield Tap', 15.00, 'Expense', 2, 1, '2026-03-27', '2026-03-27 05:38:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `profile_photo`, `created_at`) VALUES
(1, 'Noah', 'hetthunay@gmail.com', '$2y$10$BKsYhwwnCuS86U92V9KZaOUySmqaX5LvFNzzc4/EAZZ.nwv.SpGd2', NULL, '2026-03-23 19:48:59'),
(2, 'Hexon', 'hex@gmail.com', '$2y$10$uUkGUwXhdcCGo4xcBjMX4eNVwWtmsdexgl7YzdNgRjXUeC75G3JSC', NULL, '2026-03-24 14:22:29'),
(3, 'lol', 'lol@gmail.com', '$2y$10$ecbU8DmU85swIWOUPAQPmeVnpIlGAaGdD8XgzbAVuwL.pTu8pARsO', NULL, '2026-03-25 10:50:31'),
(4, '123131', '123@gmail.com', '$2y$10$nJgRX4gBuDxk9Jms.Xn7re9ozzaXQbXukd2pT2Asc6K2YdJzl5cI6', NULL, '2026-03-25 11:16:12'),
(5, 'Hein', 'heinpyaesonephyo4@gmail.com', '$2y$10$7haIzJ2wDO6yKYpy0zOWye0vXdUI098NDKZ/pPRt6GyGGaS2QK56a', NULL, '2026-03-25 12:12:31'),
(6, 'Phyo', 'heinpyaesonephyo2004@gmail.com', '$2y$10$za7kBKbsUPsMsi99w3BZIOA7M.ObKcUIHQOIhcNnIgE/ZnDihuqnu', NULL, '2026-03-25 12:13:29'),
(7, 'Thaw', 'thaw@gmail.com', '$2y$10$0vxNIbVkqEVzJxQzHOlSAewl/9QHC.Jl.O7vH6DC2EyuTe1u5znIq', NULL, '2026-03-25 19:27:18'),
(8, 'suzgyi', 'suzgyi@gmail.com', '$2y$10$3oes1zLfe2xYTRd8znBzCetDDibK.FQwL1d7vtrOv.ok/Pdqb2Mb2', NULL, '2026-03-26 20:49:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_action_created` (`action`,`created_at`),
  ADD KEY `idx_audit_ip_user` (`ip_address`,`username_attempt`,`created_at`),
  ADD KEY `fk_audit_logs_user` (`user_id`);

--
-- Indexes for table `bankbalance`
--
ALTER TABLE `bankbalance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bankbalance_user` (`user_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_budget_period` (`user_id`,`category_id`,`budget_month`,`budget_year`),
  ADD KEY `fk_budgets_category` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_category` (`user_id`,`name`,`type`);

--
-- Indexes for table `saving_goals`
--
ALTER TABLE `saving_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_goals_user` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transactions_user_date` (`user_id`,`date`),
  ADD KEY `fk_transactions_category` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `bankbalance`
--
ALTER TABLE `bankbalance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `saving_goals`
--
ALTER TABLE `saving_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bankbalance`
--
ALTER TABLE `bankbalance`
  ADD CONSTRAINT `fk_bankbalance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budgets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budgets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saving_goals`
--
ALTER TABLE `saving_goals`
  ADD CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
