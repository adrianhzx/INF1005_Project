-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2026 at 08:05 PM
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
-- Database: `ekea_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Living Room', 'Sofas, coffee tables, TV consoles and living room essentials'),
(2, 'Bedroom', 'Beds, mattresses, wardrobes and bedroom furniture'),
(3, 'Dining', 'Dining tables, chairs and dining room sets'),
(4, 'Office', 'Desks, office chairs and workspace solutions'),
(5, 'Storage', 'Shelving units, cabinets and storage organisers');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percent` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_percent`, `active`) VALUES
(1, 'SAVE10', 10, 1),
(2, 'SAVE20', 20, 1),
(3, 'EKEA50', 50, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'credit_card',
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `shipping_address`, `payment_method`, `coupon_code`, `discount`, `created_at`) VALUES
(1, 5, 95.00, 'delivered', '02-222, 11 NEW PUNGGOL ROAD SINGAPORE INSTITUTE OF TECHNOLOGY (CAMPUS HEART) SINGAPORE 828616, Singapore 828616', 'credit_card', NULL, 0.00, '2026-03-09 14:29:19'),
(2, 5, 95.00, 'pending', '82-284, 11 NEW PUNGGOL ROAD SINGAPORE INSTITUTE OF TECHNOLOGY SINGAPORE 828616, Singapore 828616', 'paypal', NULL, 0.00, '2026-03-09 15:02:52'),
(3, 5, 979.00, 'pending', '2323, 120 JURONG EAST STREET 13 IVORY HEIGHTS SINGAPORE 600120, Singapore 600120', 'credit_card', NULL, 0.00, '2026-03-27 21:35:58'),
(4, 5, 175.00, 'shipped', '23, 11 NEW PUNGGOL ROAD SINGAPORE INSTITUTE OF TECHNOLOGY (CAMPUS HEART) SINGAPORE 828616, Singapore 828616', 'credit_card', NULL, 0.00, '2026-03-28 12:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 15, 1, 80.00),
(2, 2, 15, 1, 80.00),
(3, 3, 15, 1, 80.00),
(4, 3, 1, 1, 899.00),
(5, 4, 15, 2, 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`, `created_at`) VALUES
(1, 1, 'SOLVIK Sofa', 'Modern 3-seater sofa in premium grey fabric with solid oak legs. Perfect for contemporary living spaces.', 899.00, 14, 'solvik_sofa.jpg', '2026-03-02 13:19:57'),
(2, 1, 'BJÖRK Coffee Table', 'Minimalist round coffee table in natural birch veneer with powder-coated steel legs.', 249.00, 30, 'bjork_coffee.jpg', '2026-03-02 13:19:57'),
(3, 1, 'STRÖM TV Console', 'Sleek TV bench in walnut finish with cable management and two drawers.', 399.00, 20, 'strom_tv.jpg', '2026-03-02 13:19:57'),
(4, 2, 'NORRA Bed Frame', 'King-size bed frame in solid pine with slatted base. Clean Scandinavian design.', 649.00, 10, 'norra_bed.jpg', '2026-03-02 13:19:57'),
(6, 2, 'LUGN Bedside Table', 'Compact bedside table with one drawer and open shelf. Available in oak finish.', 129.00, 40, 'lugn_bedside.jpg', '2026-03-02 13:19:57'),
(7, 3, 'VILA Dining Table', 'Extendable dining table seating 4-8 in solid oak. Built for family gatherings.', 599.00, 12, 'vila_dining.jpg', '2026-03-02 13:19:57'),
(8, 3, 'SKÅL Dining Chair', 'Ergonomic dining chair in moulded beech plywood with felt-padded feet.', 89.00, 50, 'skal_chair.jpg', '2026-03-02 13:19:57'),
(9, 4, 'TANKE Standing Desk', 'Height-adjustable standing desk with bamboo top and motorised legs.', 749.00, 18, 'tanke_desk.jpg', '2026-03-02 13:19:57'),
(10, 4, 'FOKUS Office Chair', 'Ergonomic mesh office chair with lumbar support and adjustable armrests.', 449.00, 25, 'fokus_chair.jpg', '2026-03-02 13:19:57'),
(11, 4, 'HYLLA Bookshelf', 'Open 5-tier bookshelf in powder-coated black steel with oak shelves.', 299.00, 22, 'hylla_bookshelf.jpg', '2026-03-02 13:19:57'),
(12, 5, 'ORDNA Storage Cabinet', 'Tall storage cabinet with 4 doors and internal adjustable shelves. White finish.', 349.00, 14, 'ordna_cabinet.jpg', '2026-03-02 13:19:57'),
(13, 5, 'PLATS Shelving Unit', 'Modular wall-mounted shelving system in natural pine. Customisable layout.', 199.00, 35, 'plats_shelving.jpg', '2026-03-02 13:19:57'),
(14, 5, 'RENSA Organisers Set', 'Set of 6 fabric storage boxes in assorted neutral colours. Fits most shelving units.', 49.00, 60, 'rensa_organisers.jpg', '2026-03-02 13:19:57'),
(15, 1, 'chair 1', 'chair', 80.00, 0, 'chair_1_1772459191.jpg', '2026-03-02 13:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 1, 5, 'Absolutely love this sofa! The fabric quality is outstanding and it is incredibly comfortable. Best purchase I have made this year.', '2026-03-02 13:19:57'),
(2, 4, 1, 4, 'Great sofa for the price. Assembly was straightforward. Took one star off because delivery was slightly delayed.', '2026-03-02 13:19:57'),
(3, 3, 4, 5, 'Solid bed frame with a beautiful finish. Slept wonderfully from the very first night.', '2026-03-02 13:19:57'),
(4, 4, 7, 4, 'Very sturdy dining table. The extendable feature is brilliant for hosting dinner parties.', '2026-03-02 13:19:57'),
(5, 3, 9, 5, 'Best standing desk I have ever used. The motorised height adjustment is smooth and quiet.', '2026-03-02 13:19:57'),
(6, 4, 10, 3, 'Good chair overall but the lumbar support could be better. Decent value for money though.', '2026-03-02 13:19:57'),
(7, 3, 12, 5, 'Perfect storage solution for our hallway. Looks sleek and holds a lot.', '2026-03-02 13:19:57'),
(8, 4, 14, 4, 'Great shelving unit. Easy to assemble and very versatile. Highly recommended.', '2026-03-02 13:19:57'),
(9, 5, 15, 5, 'very good!!!!!', '2026-03-09 14:31:22'),
(10, 5, 15, 4, 'testtttttt', '2026-03-27 21:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(249) NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `verified` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `resettable` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `roles_mask` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `registered` int(10) UNSIGNED NOT NULL,
  `last_login` int(10) UNSIGNED DEFAULT NULL,
  `force_logout` mediumint(8) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `username`, `status`, `verified`, `resettable`, `roles_mask`, `registered`, `last_login`, `force_logout`) VALUES
(12, 'test1@test.com', '$pa01$2y$10$DtutiGMKm/mvl/vphUEcWehJTdgO/7DRfnto/Wk/Y86mcYIJZJxky', NULL, 0, 1, 1, 0, 1774724289, 1774724357, 1),
(11, 'ekeaforsit@gmail.com', '$pa01$2y$10$tpUhU15qHlu7rmpWfQNVb.dh.wI0N8AxM3kbd58f7QB2cef2H9yUy', NULL, 0, 1, 1, 0, 1774722000, NULL, 0),
(4, 'test4@test.com', '$pa01$2y$10$M3t/U9oMQrau5f/gN5GVfOJnn2TP.LbUPlyCMctDYk/m3Lhh/rCxC', NULL, 0, 1, 1, 0, 1774637430, 1774644406, 0),
(5, 'adrianhzx_@hotmail.com', '$pa01$2y$10$UFETerk1aXLCtaTrd7VAYONVRFY.A3L/LVTyH/DminGRc5hTyduNO', NULL, 0, 1, 1, 1, 1774639076, 1774723821, 4),
(6, 'hozhixianadrian@gmail.com', '$pa01$2y$10$WC7Fxal4gL7zqXmPU0.S8.T3iYwzH.vBmbs/r./w4byayAUVYoeKy', NULL, 0, 1, 1, 0, 1774705562, 1774710517, 0),
(10, 'sitcooked@gmail.com', '$pa01$2y$10$iDyMBu1taVGLCs3fpLrgkObC.zE1gGk5uMuOHda2y61XuEIyCZu8O', NULL, 0, 1, 1, 0, 1774721940, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users_2fa`
--

CREATE TABLE `users_2fa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mechanism` tinyint(3) UNSIGNED NOT NULL,
  `seed` varchar(255) DEFAULT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  `expires_at` int(10) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_audit_log`
--

CREATE TABLE `users_audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event_at` int(10) UNSIGNED NOT NULL,
  `event_type` varchar(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details_json` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_audit_log`
--

INSERT INTO `users_audit_log` (`id`, `user_id`, `event_at`, `event_type`, `admin_id`, `ip_address`, `user_agent`, `details_json`) VALUES
(1, 1, 1774634584, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***1@t**t.com\",\"username\":null}'),
(2, 2, 1774635306, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***2@t**t.com\",\"username\":null}'),
(3, 3, 1774635833, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***3@t**t.com\",\"username\":null}'),
(4, 4, 1774637430, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***4@t**t.com\",\"username\":null}'),
(5, 4, 1774637649, 'confirmation.email.verify', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***4@t**t.com\"}'),
(6, 4, 1774637661, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***4@t**t.com\",\"username\":null}'),
(7, 4, 1774639027, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***4@t**t.com\",\"username\":null}'),
(8, 5, 1774639080, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(9, 5, 1774639108, 'confirmation.email.verify', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\"}'),
(10, 5, 1774639114, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(11, 5, 1774641055, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(12, 5, 1774642257, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(13, 5, 1774643987, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(14, 5, 1774644299, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(15, 4, 1774644406, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***4@t**t.com\",\"username\":null}'),
(16, 4, 1774644417, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(17, 5, 1774644486, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(18, 5, 1774644496, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(19, 5, 1774644642, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(20, 5, 1774646633, 'password.reconfirm', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(21, 5, 1774646633, 'password.change', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(22, 5, 1774646633, 'logout.remote', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(23, 5, 1774646639, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(24, 5, 1774646651, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(25, 5, 1774646677, 'password.reconfirm', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(26, 5, 1774646677, 'password.change', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(27, 5, 1774646677, 'logout.remote', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(28, 5, 1774647438, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(29, 5, 1774648417, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(30, 6, 1774689044, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(31, 5, 1774689054, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(32, 5, 1774689074, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(33, 5, 1774694668, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(34, 5, 1774694817, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(35, 5, 1774694823, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(36, 5, 1774695140, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(37, 5, 1774695911, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(38, 5, 1774696085, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(39, 5, 1774696090, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(40, 5, 1774698891, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(41, 5, 1774698902, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(42, 5, 1774700011, 'password.reconfirm', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(43, 5, 1774700011, 'password.change', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(44, 5, 1774700011, 'logout.remote', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(45, 5, 1774700013, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(46, 5, 1774700023, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(47, 5, 1774700542, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(48, 5, 1774705373, 'password.reset.start', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\"}'),
(49, 6, 1774705566, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"h***n@g***l.com\",\"username\":null}'),
(50, 7, 1774709541, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"s***d@g***l.com\",\"username\":null}'),
(51, 8, 1774709628, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"s***d@g***l.com\",\"username\":null}'),
(52, 9, 1774709954, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"s***d@g***l.com\",\"username\":null}'),
(53, 5, 1774710040, 'password.reset.start', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\"}'),
(54, 6, 1774710274, 'password.reset.start', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"h***n@g***l.com\"}'),
(55, 6, 1774710376, 'password.reset.start', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"h***n@g***l.com\"}'),
(56, 5, 1774710399, 'password.reset.start', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\"}'),
(57, 5, 1774710423, 'password.reset.finish', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(58, 5, 1774710435, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(59, 5, 1774710479, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(60, 6, 1774710517, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"h***n@g***l.com\",\"username\":null}'),
(61, 6, 1774710533, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(62, 10, 1774721947, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(63, 10, 1774721993, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(64, 11, 1774723314, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(65, 11, 1774723789, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(66, 11, 1774723812, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(67, 5, 1774723821, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"a***_@h***l.com\",\"username\":null}'),
(68, 5, 1774723826, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(69, 12, 1774724293, 'register', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***1@t**t.com\",\"username\":null}'),
(70, 12, 1774724320, 'confirmation.email.verify', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***1@t**t.com\"}'),
(71, 12, 1774724329, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***1@t**t.com\",\"username\":null}'),
(72, 12, 1774724346, 'password.reconfirm', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(73, 12, 1774724346, 'password.change', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(74, 12, 1774724346, 'logout.remote', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(75, 12, 1774724349, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL),
(76, 12, 1774724357, 'login', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', '{\"email\":\"t***1@t**t.com\",\"username\":null}'),
(77, 12, 1774724359, 'logout.local', NULL, '::/48', 'dSbADhg2e5IOCTtYxClz9+8XsGI/JCMhWd3xEPY/jL8=', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_confirmations`
--

CREATE TABLE `users_confirmations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(249) NOT NULL,
  `selector` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_confirmations`
--

INSERT INTO `users_confirmations` (`id`, `user_id`, `email`, `selector`, `token`, `expires`) VALUES
(1, 1, 'test1@test.com', 'KCSUtnBMkcEGABZG', '$2y$10$4PdR5lGmcuxYZ2kbH8uCfOUD9x5B6E9TVcWrEdoHD6MazqWPDKDG.', 1774720984),
(2, 2, 'test2@test.com', 'DIZcTsqgTgh9I7Iz', '$2y$10$WlDAiA7G7PjptqHR.PhScOeSLxr2QXupqMPyIMv/jhfYGELRItK8.', 1774721706),
(3, 3, 'test3@test.com', 'qvToEFoTtTrXPIuX', '$2y$10$g14SUgXraWpMeMK078WgqeKGHSuysKRowh1M6/0H38h7v1aEgnTXm', 1774722233),
(6, 6, 'hozhixianadrian@gmail.com', 'ShIvdxQ7XyjJ2yd1', '$2y$10$2Vrma3jwz4KKpYBku28kTuYIk8yvN2xKHUjYCJssDgg45p/IjWloy', 1774791962),
(7, 7, 'sitcooked@gmail.com', 'dgKVs7WDefCc4bLr', '$2y$10$.9JmVHMiBwXSuYt.gHF2GudSrVjgYIz6hBRwyOPBqVCJIqJwMMJmm', 1774795938),
(8, 8, 'sitcooked@gmail.com', 'gRY_GD-5ZNmm6iPa', '$2y$10$MdsiVXdDFhXoqor9LT7lFOY51NaFf4QFW2JJFcwuLERHhv3FL8QGu', 1774796024),
(9, 9, 'sitcooked@gmail.com', 'istbdf1AQbbZzDOt', '$2y$10$vkxtGFyGjwBfZOYsrgNAI.FRw/Cy8XQrSpB.ylzPQycrysgxj/fSi', 1774796351);

-- --------------------------------------------------------

--
-- Table structure for table `users_old`
--

CREATE TABLE `users_old` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_old`
--

INSERT INTO `users_old` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'Admin', 'EKEA', 'admin@ekea.com', '$2y$10$5lyznZryMas7h89mdyTC5O9dIsHcsG2uAOu.IGQ7sRm9KoPoEIS1y', '91234567', NULL, 'admin', '2026-03-02 13:19:57'),
(2, 'Manager', 'EKEA', 'manager@ekea.com', '$2y$10$oelC96OG0/l8nfMy/6YRtOMl/QXQZH4iwLlkJtJ0bfWIk5abGuomi', '98765432', NULL, 'admin', '2026-03-02 13:19:57'),
(3, 'John', 'Doe', 'john@example.com', '$2y$10$JGGmAIa9LMm2O7Q3nr/n5uwxazfO8miPMMZ2O8ki10QreQUo8TF0S', '81112222', '123 Main Street, Singapore 123456', 'user', '2026-03-02 13:19:57'),
(4, 'Jane', 'Smith', 'jane@example.com', '$2y$10$JGGmAIa9LMm2O7Q3nr/n5uwxazfO8miPMMZ2O8ki10QreQUo8TF0S', '83334444', '456 Oak Avenue, Singapore 654321', 'user', '2026-03-02 13:19:57'),
(5, 'test', 'test', 'test@test.com', '$2y$10$okBlizRN487cn4.Gh7VT9.rsrNq5Ji9TjdQWK0Dekx9TdROLxjKJi', '12312312', NULL, 'admin', '2026-03-02 13:40:55'),
(6, 'test', 'user', 'testuser@test.com', '$2y$10$1MFxbB5McpzvL4emnxUge.L74pAmvK.XfdgP47Q5JZAfNF2OBtnqe', '12312312', NULL, 'user', '2026-03-02 13:55:07'),
(7, 'adrian', 'ho', 'adrian@test.com', '$2y$10$gLSrmBGyCMVIM6UTSX6rEO1ZPelDSrYtM2BcdyWYcpjxSIje8hY1i', '11112222', NULL, 'user', '2026-03-09 13:26:39'),
(8, 'test1', 'test', 'test2@test.com', '$2y$10$GolSaMux3/z0f4OMvY9kxu4Wcg8Kq6/Rt0wGFcbh7ZHbwZHaGS1nm', '11112222', NULL, 'user', '2026-03-09 14:18:17');

-- --------------------------------------------------------

--
-- Table structure for table `users_otps`
--

CREATE TABLE `users_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mechanism` tinyint(3) UNSIGNED NOT NULL,
  `single_factor` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires_at` int(10) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_remembered`
--

CREATE TABLE `users_remembered` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_resets`
--

CREATE TABLE `users_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `selector` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_resets`
--

INSERT INTO `users_resets` (`id`, `user`, `selector`, `token`, `expires`) VALUES
(1, 5, 'C-_j3SZJAK0yR-u7Dq2-', '$2y$10$TgBGglmC9M8a.hgyxeycFuNlfcYzy82J.sj1gZQnxS1kEbqGWF8yC', 1774726973),
(2, 5, '4T1jFBoq4Igg7s7NgwT-', '$2y$10$DdR3kC9o0MBTwX2k.6hg2eej.HQOBV7QibJgGdT7.tl0At0C9HD/2', 1774731640),
(3, 6, 'FKd1bcNoNksSRL1Q9f-t', '$2y$10$WMwLkJDTESQDXfMsnLDL0.HJaPSEwTFQFPsWhXmc6KhxaUzAklpMW', 1774731874),
(4, 6, 'PRcdD6ZF9VP29ApZykJq', '$2y$10$g0QhQXdeA0kIk3L0EFXQ7ubLpIgZ.Wlra00X7ZT5J26CyVHIVmVli', 1774731976);

-- --------------------------------------------------------

--
-- Table structure for table `users_throttling`
--

CREATE TABLE `users_throttling` (
  `bucket` varchar(44) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `tokens` float NOT NULL,
  `replenished_at` int(10) UNSIGNED NOT NULL,
  `expires_at` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `auth_provider` varchar(50) DEFAULT 'local'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `address`, `created_at`, `auth_provider`) VALUES
(1, 3, 'test3', 'test3', '11112222', NULL, '2026-03-27 18:23:53', 'local'),
(2, 4, 'test4', 'test4', '11112222', NULL, '2026-03-27 18:50:30', 'local'),
(3, 5, 'adrian', 'ho', '12354151', 'test', '2026-03-27 19:18:00', 'local'),
(5, 6, 'adrian2', 'ho', '11112222', NULL, '2026-03-28 13:46:06', 'local'),
(6, 7, 'sit', 'cooked', '123123123', NULL, '2026-03-28 14:52:21', 'local'),
(7, 8, 'sit', 'cooked', '123123123123', NULL, '2026-03-28 14:53:48', 'local'),
(8, 9, 'sit', 'cooked', '12312312', NULL, '2026-03-28 14:59:14', 'local'),
(9, 10, 'sit', 'cooked', NULL, NULL, '2026-03-28 18:19:00', 'local'),
(10, 11, 'ekea', 'for sit', NULL, NULL, '2026-03-28 18:20:00', 'local'),
(11, 12, 'test1', 'test1', '123123123', '', '2026-03-28 18:58:13', 'local');

-- --------------------------------------------------------


-- --------------------------------------------------------

--
-- Table structure for table `session_data`
--

CREATE TABLE `session_data` (
  `session_id` varchar(128) NOT NULL,
  `hash` varchar(32) NOT NULL DEFAULT '',
  `session_data` blob NOT NULL,
  `session_expire` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_2fa`
--
ALTER TABLE `users_2fa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_mechanism` (`user_id`,`mechanism`);

--
-- Indexes for table `users_audit_log`
--
ALTER TABLE `users_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_at` (`event_at`),
  ADD KEY `user_id_event_at` (`user_id`,`event_at`),
  ADD KEY `user_id_event_type_event_at` (`user_id`,`event_type`,`event_at`);

--
-- Indexes for table `users_confirmations`
--
ALTER TABLE `users_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `email_expires` (`email`,`expires`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users_old`
--
ALTER TABLE `users_old`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_otps`
--
ALTER TABLE `users_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_mechanism` (`user_id`,`mechanism`),
  ADD KEY `selector_user_id` (`selector`,`user_id`);

--
-- Indexes for table `users_remembered`
--
ALTER TABLE `users_remembered`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user` (`user`);

--
-- Indexes for table `users_resets`
--
ALTER TABLE `users_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user_expires` (`user`,`expires`);

--
-- Indexes for table `users_throttling`
--
ALTER TABLE `users_throttling`
  ADD PRIMARY KEY (`bucket`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_index` (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users_2fa`
--
ALTER TABLE `users_2fa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_audit_log`
--
ALTER TABLE `users_audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `users_confirmations`
--
ALTER TABLE `users_confirmations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users_old`
--
ALTER TABLE `users_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users_otps`
--
ALTER TABLE `users_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_remembered`
--
ALTER TABLE `users_remembered`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_resets`
--
ALTER TABLE `users_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_old` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_old` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
