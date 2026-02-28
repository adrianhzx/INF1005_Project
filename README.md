# EKEA Furniture E-Commerce Website

A full-stack e-commerce web application for EKEA Furniture, built with PHP, MySQL, and Bootstrap 5. Features a public storefront, user accounts with order history, and a complete admin portal.

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [Test Accounts](#test-accounts)
- [Project Structure](#project-structure)
- [Features](#features)
- [Security](#security)
- [External APIs and Libraries](#external-apis-and-libraries)
- [Notes](#notes)

---

## Prerequisites

- **XAMPP** (or any local stack with Apache + PHP 7.4+ and MySQL 5.7+)
- PHP extensions: `pdo_mysql`, `finfo`, `mbstring`, `json`
- A modern web browser (Chrome, Firefox, Edge, Safari)

---

## Installation

1. Clone or copy the `ekea/` folder into your XAMPP `htdocs` directory:

   ```
   C:\xampp\htdocs\ekea\
   ```

2. Ensure the `uploads/` directory exists and is writable by the web server. This is where product images are stored.

3. Ensure the `logs/` directory exists. The application writes debug and error logs to `logs/ekea.log`. The included `.htaccess` file blocks direct web access to log files.

---

## Database Setup

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.

2. Open **phpMyAdmin** at `http://localhost/phpmyadmin`.

3. Import the database schema and seed data:
   - Click **Import** in the top navigation.
   - Select the file `database/ekea_db.sql`.
   - Click **Go** to execute.

   This will create the `ekea_db` database with the following tables:
   - `users` -- user accounts and roles
   - `categories` -- product categories
   - `products` -- product catalog
   - `reviews` -- customer reviews (purchase-gated)
   - `orders` -- order records
   - `order_items` -- line items for each order
   - `coupons` -- discount coupon codes

---

## Configuration

Database credentials are stored in `includes/db_config.ini`:

```ini
[database]
host = localhost
dbname = ekea_db
username = root
password =
```

Update these values if your MySQL setup uses different credentials. The default XAMPP configuration uses `root` with no password.

---

## Running the Application

1. Start Apache and MySQL from XAMPP Control Panel.
2. Open a browser and navigate to:

   ```
   http://localhost/ekea/
   ```

3. The homepage should load with the navigation bar, hero section, and featured products.

---

## Test Accounts

The database seed includes the following test accounts:

| Email               | Password     | Role  |
|---------------------|-------------|-------|
| admin@ekea.com      | Admin@123   | Admin |
| manager@ekea.com    | Manager@123 | Admin |
| john@example.com    | User@123    | User  |
| jane@example.com    | User@123    | User  |

If login fails, visit `http://localhost/ekea/diagnostics.php` to verify password hashes and database connectivity. **Delete `diagnostics.php` before any production deployment.**

---

## Project Structure

```
ekea/
|-- admin/
|   |-- admin.php           # Admin dashboard with stats
|   |-- inventory.php       # Product CRUD (add, edit, delete)
|   |-- orders.php          # Order management and status updates
|   |-- users.php           # User management and review moderation
|
|-- css/
|   |-- style.css           # Design system and all component styles
|
|-- database/
|   |-- ekea_db.sql         # Database schema and seed data
|
|-- includes/
|   |-- auth_guard.php      # Login/admin guards, CSRF token helpers
|   |-- db_config.ini       # Database credentials (do not commit)
|   |-- db_connect.php      # PDO connection, session start, logger init
|   |-- footer.php          # Footer partial, chatbot widget
|   |-- header.php          # Header partial, navbar, flash messages
|   |-- logger.php          # Backend logger (writes to logs/ekea.log)
|
|-- js/
|   |-- main.js             # Client-side interactions and animations
|
|-- logs/
|   |-- ekea.log            # Application log (auto-created)
|   |-- .htaccess           # Blocks web access to log files
|
|-- uploads/                # Product images (uploaded via admin)
|
|-- index.php               # Homepage
|-- product.php             # Product catalog with filters and pagination
|-- product_detail.php      # Single product view with reviews
|-- cart.php                 # Shopping cart
|-- checkout.php            # Checkout with Stripe, OneMap, coupon apply
|-- summary.php             # Order confirmation page
|-- login.php               # User login
|-- register.php            # User registration
|-- profile.php             # User profile management
|-- history.php             # Order history
|-- news.php                # Community reviews page
|-- about.php               # About us with interactive map
|-- logout.php              # Session destroy and redirect
|-- chatbot.php             # AJAX chatbot endpoint
|-- diagnostics.php         # Health check (delete in production)
```

---

## Features

### Public Pages
- Homepage with hero section, category browsing, and featured products
- Product catalog with category filtering, search, sorting, and pagination
- Product detail page with image, stock status, add-to-cart, and customer reviews
- About Us page with company info and interactive OpenStreetMap
- Community reviews page with aggregate statistics

### User Features
- Registration and login with secure password hashing
- Profile management (email locked for non-admin users)
- Shopping cart with quantity controls
- Checkout with structured address input (postal code + unit number)
- OneMap API integration for Singapore address lookup
- Stripe test mode for credit/debit card payments
- Coupon application with AJAX validation and live price update
- Order history with detailed order view

### Admin Portal
- Dashboard with statistics (products, orders, unique customers, revenue)
- Inventory management (add, edit, delete products with image upload)
- Order management (view orders, update statuses)
- User management (view all users, toggle roles, delete accounts)
- Review moderation (view and remove any review)

### Chatbot
- Floating chat widget on all pages
- Keyword-based search across products, categories, and site pages
- Rate limiting (20 requests/minute per session)
- Input sanitisation and SQL injection pattern blocking

### Purchase-Gated Reviews
- Users can only review products from delivered orders
- Backend validation prevents bypassing the restriction
- Admins can moderate and delete reviews

---

## Security

- **SQL Injection**: All database queries use PDO prepared statements.
- **XSS**: All user-facing output is escaped with `htmlspecialchars()`.
- **CSRF**: All state-changing forms include a CSRF token validated server-side.
- **Password Storage**: Passwords are hashed with `password_hash()` (bcrypt).
- **Session Management**: `session_start()` is called at application entry. `session_regenerate_id()` is used on login.
- **File Upload Validation**: MIME type checked with `finfo`, size limited to 5MB, restricted to image types.
- **Rate Limiting**: Chatbot endpoint limits to 20 requests per minute per session.
- **Access Control**: Admin pages are protected with `require_admin()`.
- **Log Protection**: `.htaccess` in `logs/` prevents web access to log files.

---

## External APIs and Libraries

| Dependency          | Purpose                            | Requires API Key |
|---------------------|------------------------------------|------------------|
| Bootstrap 5.3.2     | CSS framework and components       | No               |
| Bootstrap Icons     | Icon library                       | No               |
| Google Fonts (Inter)| Typography                         | No               |
| Leaflet 1.9.4       | Interactive map on About Us page   | No               |
| OpenStreetMap       | Map tiles for Leaflet              | No               |
| OneMap Singapore    | Postal code address lookup         | No               |
| Stripe.js v3        | Test mode card payment input       | No (uses test key) |

**Stripe Test Card**: Use `4242 4242 4242 4242` with any future expiry date and any 3-digit CVC.

**Available Coupon Codes**: `SAVE10` (10%), `SAVE20` (20%), `EKEA50` (50%)

---

## Notes

- The Stripe integration uses a **test publishable key** (`pk_test_TYooMQauvdEDq54NiTphI7jx`). No real payments are processed.
- Product images should be placed in the `uploads/` directory. The admin inventory page handles image uploads automatically.
- The application logs errors and key events to `logs/ekea.log`. Check this file for debugging.
- `diagnostics.php` is provided for development health checks. Remove it before going live.
- The `db_config.ini` file contains database credentials. Do not commit it to version control.
