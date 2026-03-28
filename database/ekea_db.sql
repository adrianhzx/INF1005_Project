
-- ============================================================
-- EKEA Furniture E-Commerce Database
-- Run this script in MySQL Workbench to create and seed the DB
-- ============================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ekea_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ekea_db;

-- -----------------------------------------------------------
-- Users Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Categories Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Products Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Reviews Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Orders Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'credit_card',
    payment_intent_id VARCHAR(100) DEFAULT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Order Items Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Coupons Table
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percent INT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin accounts (passwords hashed with password_hash)
-- Admin@123
INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES
('Admin', 'EKEA', 'admin@ekea.com', '$2y$10$5lyznZryMas7h89mdyTC5O9dIsHcsG2uAOu.IGQ7sRm9KoPoEIS1y', '91234567', 'admin'),
('Manager', 'EKEA', 'manager@ekea.com', '$2y$10$oelC96OG0/l8nfMy/6YRtOMl/QXQZH4iwLlkJtJ0bfWIk5abGuomi', '98765432', 'admin');

-- User accounts
-- User@123
INSERT INTO users (first_name, last_name, email, password, phone, address, role) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$JGGmAIa9LMm2O7Q3nr/n5uwxazfO8miPMMZ2O8ki10QreQUo8TF0S', '81112222', '123 Main Street, Singapore 123456', 'user'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$JGGmAIa9LMm2O7Q3nr/n5uwxazfO8miPMMZ2O8ki10QreQUo8TF0S', '83334444', '456 Oak Avenue, Singapore 654321', 'user');

-- Categories
INSERT INTO categories (name, description) VALUES
('Living Room', 'Sofas, coffee tables, TV consoles and living room essentials'),
('Bedroom', 'Beds, mattresses, wardrobes and bedroom furniture'),
('Dining', 'Dining tables, chairs and dining room sets'),
('Office', 'Desks, office chairs and workspace solutions'),
('Storage', 'Shelving units, cabinets and storage organisers');

-- Products
INSERT INTO products (category_id, name, description, price, stock, image_url) VALUES
-- Living Room
(1, 'SOLVIK Sofa', 'Modern 3-seater sofa in premium grey fabric with solid oak legs. Perfect for contemporary living spaces.', 899.00, 15, 'solvik_sofa.jpg'),
(1, 'BJÖRK Coffee Table', 'Minimalist round coffee table in natural birch veneer with powder-coated steel legs.', 249.00, 30, 'bjork_coffee.jpg'),
(1, 'STRÖM TV Console', 'Sleek TV bench in walnut finish with cable management and two drawers.', 399.00, 20, 'strom_tv.jpg'),

-- Bedroom
(2, 'NORRA Bed Frame', 'King-size bed frame in solid pine with slatted base. Clean Scandinavian design.', 649.00, 10, 'norra_bed.jpg'),
(2, 'FJÄLL Wardrobe', 'Spacious 3-door wardrobe in white with mirror panel and adjustable shelves.', 799.00, 8, 'fjall_wardrobe.jpg'),
(2, 'LUGN Bedside Table', 'Compact bedside table with one drawer and open shelf. Available in oak finish.', 129.00, 40, 'lugn_bedside.jpg'),

-- Dining
(3, 'VILA Dining Table', 'Extendable dining table seating 4-8 in solid oak. Built for family gatherings.', 599.00, 12, 'vila_dining.jpg'),
(3, 'SKÅL Dining Chair', 'Ergonomic dining chair in moulded beech plywood with felt-padded feet.', 89.00, 50, 'skal_chair.jpg'),

-- Office
(4, 'TANKE Standing Desk', 'Height-adjustable standing desk with bamboo top and motorised legs.', 749.00, 18, 'tanke_desk.jpg'),
(4, 'FOKUS Office Chair', 'Ergonomic mesh office chair with lumbar support and adjustable armrests.', 449.00, 25, 'fokus_chair.jpg'),
(4, 'HYLLA Bookshelf', 'Open 5-tier bookshelf in powder-coated black steel with oak shelves.', 299.00, 22, 'hylla_bookshelf.jpg'),

-- Storage
(5, 'ORDNA Storage Cabinet', 'Tall storage cabinet with 4 doors and internal adjustable shelves. White finish.', 349.00, 14, 'ordna_cabinet.jpg'),
(5, 'PLATS Shelving Unit', 'Modular wall-mounted shelving system in natural pine. Customisable layout.', 199.00, 35, 'plats_shelving.jpg'),
(5, 'RENSA Organisers Set', 'Set of 6 fabric storage boxes in assorted neutral colours. Fits most shelving units.', 49.00, 60, 'rensa_organisers.jpg');

-- Sample Reviews
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(3, 1, 5, 'Absolutely love this sofa! The fabric quality is outstanding and it is incredibly comfortable. Best purchase I have made this year.'),
(4, 1, 4, 'Great sofa for the price. Assembly was straightforward. Took one star off because delivery was slightly delayed.'),
(3, 4, 5, 'Solid bed frame with a beautiful finish. Slept wonderfully from the very first night.'),
(4, 7, 4, 'Very sturdy dining table. The extendable feature is brilliant for hosting dinner parties.'),
(3, 9, 5, 'Best standing desk I have ever used. The motorised height adjustment is smooth and quiet.'),
(4, 10, 3, 'Good chair overall but the lumbar support could be better. Decent value for money though.'),
(3, 12, 5, 'Perfect storage solution for our hallway. Looks sleek and holds a lot.'),
(4, 14, 4, 'Great shelving unit. Easy to assemble and very versatile. Highly recommended.');

-- Coupons
INSERT INTO coupons (code, discount_percent, active) VALUES
('SAVE10', 10, 1),
('SAVE20', 20, 1),
('EKEA50', 50, 1);
=======
-- ============================================================
-- EKEA — Master Database Setup Script (delight-im/auth Edition)
-- 
-- COMPATIBLE WITH: delight-im/auth v8.x
-- 
-- USAGE (XAMPP):
--   1. Open phpMyAdmin (http://localhost/phpmyadmin)
--   2. Create a new database named 'ekea_db' (utf8mb4_general_ci)
--   3. Select the database → Import → Choose this file → Execute
--
-- This script is IDEMPOTENT — safe to re-run without data loss.
--
-- NOTE ON AUTHENTICATION:
--   The 'users' table is managed by delight-im/auth.
--   The library also auto-creates these tables on first use:
--     users_confirmations, users_remembered, users_resets,
--     users_throttling, users_2fa, users_audit_log, users_otps
--   Those tables are included here for completeness, but if
--   they already exist (from the library), they will be skipped.
--
-- CREATING AN ADMIN ACCOUNT:
--   1. Register a new user through the website's Register page
--   2. Verify the email (check logs/ekea.log for the verification URL)
--   3. Run this SQL in phpMyAdmin to promote to admin:
--      UPDATE users SET roles_mask = 1 WHERE email = 'your-email@example.com';
--   (roles_mask = 1 maps to \Delight\Auth\Role::ADMIN)
-- ============================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ekea_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ekea_db;

-- ============================================================
-- 1. USERS TABLE (delight-im/auth — DO NOT MODIFY STRUCTURE)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` varchar(249) NOT NULL,
    `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `username` varchar(100) DEFAULT NULL,
    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
    `verified` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
    `resettable` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
    `roles_mask` int(10) UNSIGNED NOT NULL DEFAULT 0,
    `registered` int(10) UNSIGNED NOT NULL,
    `last_login` int(10) UNSIGNED DEFAULT NULL,
    `force_logout` mediumint(8) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 2. AUTH LIBRARY SUPPORT TABLES
--    (auto-created by the library, included here for completeness)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users_confirmations` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `email` varchar(249) NOT NULL,
    `selector` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `expires` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `selector` (`selector`),
    KEY `email_expires` (`email`, `expires`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users_remembered` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user` int(10) UNSIGNED NOT NULL,
    `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `expires` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `selector` (`selector`),
    KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users_resets` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user` int(10) UNSIGNED NOT NULL,
    `selector` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `expires` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `selector` (`selector`),
    KEY `user_expires` (`user`, `expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users_throttling` (
    `bucket` varchar(44) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `tokens` float NOT NULL,
    `replenished_at` int(10) UNSIGNED NOT NULL,
    `expires_at` int(10) UNSIGNED NOT NULL,
    PRIMARY KEY (`bucket`),
    KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users_2fa` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `mechanism` tinyint(3) UNSIGNED NOT NULL,
    `seed` varchar(255) DEFAULT NULL,
    `created_at` int(10) UNSIGNED NOT NULL,
    `expires_at` int(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id_mechanism` (`user_id`, `mechanism`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users_otps` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `mechanism` tinyint(3) UNSIGNED NOT NULL,
    `single_factor` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
    `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
    `expires_at` int(10) UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id_mechanism` (`user_id`, `mechanism`),
    KEY `selector_user_id` (`selector`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users_audit_log` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED DEFAULT NULL,
    `event_at` int(10) UNSIGNED NOT NULL,
    `event_type` varchar(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `admin_id` int(10) UNSIGNED DEFAULT NULL,
    `ip_address` varchar(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `details_json` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `event_at` (`event_at`),
    KEY `user_id_event_at` (`user_id`, `event_at`),
    KEY `user_id_event_type_event_at` (`user_id`, `event_type`, `event_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. USER PROFILES TABLE (Custom — stores names, phone, address)
--    Foreign key links to the auth library's users.id
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 4. USER SESSIONS TABLE (Legacy — kept for admin force-logout)
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `session_token` varchar(128) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `session_token` (`session_token`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 5. CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. PRODUCTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `name` varchar(150) NOT NULL,
    `description` text DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `stock` int(11) NOT NULL DEFAULT 0,
    `image_url` varchar(255) DEFAULT 'logo.png',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 6. REVIEWS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `product_id` int(11) NOT NULL,
    `rating` tinyint(4) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 7. ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(10) UNSIGNED NOT NULL,
    `total` decimal(10,2) NOT NULL,
    `shipping_address` text NOT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT 'credit_card',
    `coupon_code` varchar(50) DEFAULT NULL,
    `discount` decimal(10,2) DEFAULT 0.00,
    `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 8. ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `price` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 9. COUPONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `discount_percent` int(11) NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Living Room', 'Sofas, coffee tables, TV consoles and living room essentials'),
(2, 'Bedroom', 'Beds, mattresses, wardrobes and bedroom furniture'),
(3, 'Dining', 'Dining tables, chairs and dining room sets'),
(4, 'Office', 'Desks, office chairs and workspace solutions'),
(5, 'Storage', 'Shelving units, cabinets and storage organisers');

-- Products (14 EKEA items across 5 categories)
INSERT IGNORE INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`) VALUES
-- Living Room
(1, 1, 'SOLVIK Sofa', 'Modern 3-seater sofa in premium grey fabric with solid oak legs. Perfect for contemporary living spaces.', 899.00, 14, 'solvik_sofa.jpg'),
(2, 1, 'BJÖRK Coffee Table', 'Minimalist round coffee table in natural birch veneer with powder-coated steel legs.', 249.00, 30, 'bjork_coffee.jpg'),
(3, 1, 'STRÖM TV Console', 'Sleek TV bench in walnut finish with cable management and two drawers.', 399.00, 20, 'strom_tv.jpg'),
-- Bedroom
(4, 2, 'NORRA Bed Frame', 'King-size bed frame in solid pine with slatted base. Clean Scandinavian design.', 649.00, 10, 'norra_bed.jpg'),
(5, 2, 'FJÄLL Wardrobe', 'Spacious 3-door wardrobe in white with mirror panel and adjustable shelves.', 799.00, 8, 'fjall_wardrobe.jpg'),
(6, 2, 'LUGN Bedside Table', 'Compact bedside table with one drawer and open shelf. Available in oak finish.', 129.00, 40, 'lugn_bedside.jpg'),
-- Dining
(7, 3, 'VILA Dining Table', 'Extendable dining table seating 4-8 in solid oak. Built for family gatherings.', 599.00, 12, 'vila_dining.jpg'),
(8, 3, 'SKÅL Dining Chair', 'Ergonomic dining chair in moulded beech plywood with felt-padded feet.', 89.00, 50, 'skal_chair.jpg'),
-- Office
(9, 4, 'TANKE Standing Desk', 'Height-adjustable standing desk with bamboo top and motorised legs.', 749.00, 18, 'tanke_desk.jpg'),
(10, 4, 'FOKUS Office Chair', 'Ergonomic mesh office chair with lumbar support and adjustable armrests.', 449.00, 25, 'fokus_chair.jpg'),
(11, 4, 'HYLLA Bookshelf', 'Open 5-tier bookshelf in powder-coated black steel with oak shelves.', 299.00, 22, 'hylla_bookshelf.jpg'),
-- Storage
(12, 5, 'ORDNA Storage Cabinet', 'Tall storage cabinet with 4 doors and internal adjustable shelves. White finish.', 349.00, 14, 'ordna_cabinet.jpg'),
(13, 5, 'PLATS Shelving Unit', 'Modular wall-mounted shelving system in natural pine. Customisable layout.', 199.00, 35, 'plats_shelving.jpg'),
(14, 5, 'RENSA Organisers Set', 'Set of 6 fabric storage boxes in assorted neutral colours. Fits most shelving units.', 49.00, 60, 'rensa_organisers.jpg');

-- Coupons
INSERT IGNORE INTO `coupons` (`code`, `discount_percent`, `active`) VALUES
('SAVE10', 10, 1),
('SAVE20', 20, 1),
('EKEA50', 50, 1);

-- ============================================================
-- NOTE: No seed users are inserted here.
-- The 'users' table is managed by delight-im/auth.
-- Register accounts through the website, then promote to admin:
--   UPDATE users SET roles_mask = 1 WHERE email = 'your@email.com';
-- ============================================================
>>>>>>> 711dded9b228df3b3d2f2dea3781152b075ebf91
