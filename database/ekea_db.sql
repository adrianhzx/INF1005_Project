-- ============================================================
-- EKEA Furniture E-Commerce Database
-- Run this script in MySQL Workbench to create and seed the DB
-- ============================================================

CREATE DATABASE IF NOT EXISTS ekea_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
