-- ============================================================
-- EKEA — Master Database Setup Script
-- Combines: ekea_db.sql + user_sessions.sql + password_resets + newsletter_subscribers
-- 
-- USAGE (XAMPP):
--   1. Open phpMyAdmin (http://localhost/phpmyadmin)
--   2. Create a new database named 'ekea_db' (utf8mb4_general_ci)
--   3. Select the database → Import → Choose this file → Execute
--
-- This script is IDEMPOTENT — safe to re-run without data loss.
-- ============================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS ekea_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ekea_db;

-- ============================================================
-- 1. USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    role ENUM('customer', 'admin', 'manager') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. USER SESSIONS TABLE (Legacy — kept for admin force-logout feature)
-- NOTE: delight-im/auth creates its own tables automatically on first use:
--   users_confirmations, users_remembered, users_resets, users_throttling
-- Those tables do NOT need to be manually created.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 3. PASSWORD RESETS TABLE (Handled by Guy B - delight-im/auth)
-- Skipped — the auth library manages its own reset tokens.
-- ============================================================

-- ============================================================
-- 3b. USER PROFILES TABLE (Stores profile data for delight-im/auth users)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 4. NEWSLETTER SUBSCRIBERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 5. CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 6. PRODUCTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 7. REVIEWS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. ORDERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    payment_method VARCHAR(50),
    coupon_code VARCHAR(20) DEFAULT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 9. ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 10. COUPONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    discount_percent INT NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Users (passwords hashed with password_hash)
INSERT IGNORE INTO users (first_name, last_name, email, password, role) VALUES
('Admin', 'User', 'admin@ekea.com', '$2y$10$wMnW5dNNbGBjUwSfr1r9aOdrW2SlYFPUqiPzD3GQXD5xjTMxcQOC2', 'admin'),
('Store', 'Manager', 'manager@ekea.com', '$2y$10$BHJt1L/VR4QoOk2RXuJXxON5OhLXCWr5RVpf21JKU.l.P17B.ld9G', 'manager'),
('John', 'Doe', 'john@example.com', '$2y$10$Zy/YH0jGbwKXCKWYGKFdOeqc3yX0/XFz7v5E1L3HaJ6X1bI9w14/O', 'customer'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$Zy/YH0jGbwKXCKWYGKFdOeqc3yX0/XFz7v5E1L3HaJ6X1bI9w14/O', 'customer');

-- Categories
INSERT IGNORE INTO categories (name, description) VALUES
('Living Room', 'Sofas, coffee tables, TV consoles and more for your living space.'),
('Bedroom', 'Beds, wardrobes, and bedside furniture for restful nights.'),
('Dining', 'Dining tables and chairs for memorable meals.'),
('Office', 'Desks, chairs, and shelving for productive workspaces.'),
('Storage', 'Organisers, cabinets, and shelving to keep your home tidy.');

-- Products (15 items across 5 categories)
INSERT IGNORE INTO products (category_id, name, description, price, stock, image_url) VALUES
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

-- Coupons
INSERT IGNORE INTO coupons (code, discount_percent, active) VALUES
('SAVE10', 10, 1),
('WELCOME20', 20, 1),
('VIP30', 30, 1);

-- Sample reviews
INSERT IGNORE INTO reviews (user_id, product_id, rating, comment) VALUES
(3, 1, 5, 'Absolutely love this sofa! The grey fabric is so comfortable and the oak legs are stunning.'),
(4, 1, 4, 'Great quality for the price. Delivery was fast too.'),
(3, 4, 5, 'Best bed frame I have ever owned. Sturdy and beautiful Scandinavian design.'),
(4, 7, 4, 'The extendable dining table is perfect for hosting. Solid oak and easy to assemble.'),
(3, 10, 5, 'This office chair saved my back! Excellent lumbar support and very adjustable.');
