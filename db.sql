-- Create our shop's database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS raucaushop
CHARACTER SET utf8mb4  -- Support all characters including emoji
COLLATE utf8mb4_unicode_ci;  -- Case-insensitive sorting

-- Switch to our database
USE raucaushop;

-- Table for admin users who can manage the shop
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique ID for each admin
    username VARCHAR(100) NOT NULL UNIQUE,  -- Admin's login name (must be unique)
    password_hash VARCHAR(255) NOT NULL,  -- Encrypted password (never store plain passwords!)
    created_at DATETIME DEFAULT NOW()  -- When this admin account was created
);

-- Table for site customers (registered users)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique ID for each customer
    full_name VARCHAR(255) NOT NULL,  -- Customer full name
    email VARCHAR(255) NOT NULL UNIQUE,  -- Customer email (used for login)
    password_hash VARCHAR(255) NOT NULL,  -- Securely hashed password
    phone VARCHAR(50),  -- Optional default phone
    address TEXT,  -- Optional default delivery address
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP  -- When the customer registered
);

-- Table for our products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique ID for each product
    name VARCHAR(200) NOT NULL,  -- Product name
    description TEXT,  -- Product description (can be long)
    price INT NOT NULL,  -- Price in VND (whole numbers only)
    image VARCHAR(500),  -- Path to product image
    category VARCHAR(100) DEFAULT 'Truyền Thống'  -- Product category (Truyền Thống if none specified)
);

-- Table for customer orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique order number
    order_code VARCHAR(50),  -- Human-friendly order code
    customer_id INT,  -- Registered customer ID
    customer_name VARCHAR(255),  -- Customer's name for this order
    customer_phone VARCHAR(50),  -- Customer's phone number
    customer_address TEXT,  -- Delivery address
    total_price INT DEFAULT 0,  -- Total order price
    shipped TINYINT(1) DEFAULT 0,  -- Shipping status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- When the order was placed
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Table for items in each order
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- Unique ID for each order item
    order_id INT NOT NULL,  -- Which order this belongs to
    product_id INT NOT NULL,  -- Which product was ordered
    quantity INT NOT NULL,  -- How many were ordered
    price INT NOT NULL,  -- Price at time of order (might be different from current price)
    
    -- Links to other tables (foreign keys)
    FOREIGN KEY (order_id) REFERENCES orders(id),  -- Link to orders table
    FOREIGN KEY (product_id) REFERENCES products(id)  -- Link to products table
);

-- Add some sample products to start with
INSERT INTO products (name, description, price, image, category) VALUES
('Rau Câu Kiwi', 'Rau câu vị kiwi chua ngọt, 200g', 37000, 'images/kiwi.png', 'Trái Cây'),
('Rau Câu Trà Sữa', 'Rau câu hương trà sữa nhẹ nhàng, 200g', 41000, 'images/trasua.png', 'Cà Phê & Trà'),
('Rau Câu Pudding Socola', 'Rau câu kết hợp pudding socola mềm mịn, 200g', 39000, 'images/puddingsocola.png', 'Tráng Miệng');
