# JellyShop 🍮

A modern e-commerce web application for selling jelly products, built with PHP and MySQL.

## 📋 Description

JellyShop is a full-featured online store management system that allows administrators to manage products, orders, and customers. The application includes both a public-facing storefront and an administrative dashboard for managing the business.

## ✨ Features

### Public Features
- Browse jelly products by category
- View product details with images and descriptions
- Shopping cart functionality
- Customer registration and login
- Order placement and tracking

### Admin Features
- **Product Management**: Add, edit, and delete products
- **Order Management**: View orders, mark as shipped, or cancel
- **Customer Management**: View customer list and lock/unlock accounts
- **Revenue Tracking**: View sales statistics and revenue reports
- **Dashboard**: Overview of store performance

### API Endpoints (for Frontend)
- **Products API**: Get products with search, filter, and pagination
- **Orders API**: Create new orders from shopping cart
- **Customer Auth API**: Login, register, logout, and session management
- **Customer Portal API**: Profile management and order history

## 🛠️ Technologies

- **Backend**: PHP 8.x
- **Database**: MySQL/MariaDB
- **Frontend**: HTML, CSS, JavaScript
- **Server**: Apache (XAMPP)

## 📦 Installation

### Prerequisites

- XAMPP (or similar Apache/MySQL stack)
- PHP 8.0 or higher
- MySQL 5.7 or higher

### Method 1: Automatic Installation (Recommended)

1. **Clone or download the repository**
   ```bash
   git clone https://github.com/TrunggTruong/JellyShop.git
   cd JellyShop
   ```

2. **Move to XAMPP htdocs**
   - Copy the `JellyShop` folder to `C:\xampp\htdocs\`

3. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services

4. **Run the installer**
   - Open your browser and navigate to: `http://localhost/JellyShop/install.php`
   - Fill in the database configuration:
     - **Host**: `127.0.0.1`
     - **Username**: `root`
     - **Password**: *(leave empty for default XAMPP)*
     - **Database Name**: `raucaushop` *(or your preferred name)*
   
5. **Click "Install"**
   - The installer will:
     - Create the database
     - Create all necessary tables
     - Insert sample products
     - Create a default admin account
     - Generate configuration file

6. **Login Credentials**
   - **Admin Username**: `admin`
   - **Admin Password**: `admin123`

### Method 2: Manual Installation with SQL File

1. **Clone or download the repository**
   ```bash
   git clone https://github.com/TrunggTruong/JellyShop.git
   ```

2. **Move to XAMPP htdocs**
   - Copy the `JellyShop` folder to `C:\xampp\htdocs\`

3. **Start XAMPP**
   - Start **Apache** and **MySQL** services

4. **Import SQL File via phpMyAdmin**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Go to **Import** tab (you don't need to create database manually)
   - Click **Choose File** and select `db.sql` from the JellyShop folder
   - Click **Go** to import
   - The SQL file will automatically:
     - Create database `raucaushop`
     - Create all necessary tables
     - Insert sample products

5. **Create Admin Account**
   - After importing, go to SQL tab and run:
   ```sql
   USE raucaushop;
   INSERT INTO admin_users (username, password_hash) 
   VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
   ```
   - This creates admin account: `admin` / `admin123`

6. **Configure Database Connection**
   - Edit file: `app/config/config.php` (this file already exists)
   - Update if needed:
   ```php
   <?php
   $DB_HOST = '127.0.0.1';
   $DB_USER = 'root';
   $DB_PASS = '';
   $DB_NAME = 'raucaushop';

   function db_connect(){
       global $DB_HOST,$DB_USER,$DB_PASS,$DB_NAME;
       $db = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
       if($db->connect_errno){
           error_log('DB connect error: '.$db->connect_error);
           return null;
       }
       $db->set_charset('utf8mb4');
       return $db;
   }
   ?>
   ```

## 🚀 Usage

### Access the Application

- **Public Store**: `http://localhost/JellyShop/public/`
- **Admin Panel**: `http://localhost/JellyShop/public/admin/login`
- **Admin Dashboard**: `http://localhost/JellyShop/public/admin/`

### Default Admin Login
- **Username**: `admin`
- **Password**: `admin123`

**⚠️ Important**: Change the default admin password after first login!

## 📁 Project Structure

```
JellyShop/
├── app/
│   ├── config/
│   │   ├── common.php          # Utility functions & ASSET_PATH
│   │   └── config.php          # Database configuration
│   ├── controllers/
│   │   ├── admin/              # Admin panel controllers
│   │   │   ├── login.php       # Admin login handler
│   │   │   ├── products.php    # Product list controller
│   │   │   ├── orders.php      # Order list controller
│   │   │   ├── add_product.php # Add new product
│   │   │   ├── edit_product.php # Edit product
│   │   │   ├── customers.php   # Customer management
│   │   │   └── ...
│   │   └── api/                # API endpoints for frontend
│   │       ├── products.php    # Products API
│   │       ├── orders.php      # Order creation API
│   │       ├── auth.php        # Customer authentication
│   │       └── customer.php    # Customer portal
│   ├── models/                 # Data models (currently minimal)
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── Customer.php
│   └── views/
│       ├── admin/              # Admin panel views
│       │   ├── layout.php      # Admin layout wrapper
│       │   ├── products.php    # Products list view
│       │   ├── orders.php      # Orders list view
│       │   └── ...
│       └── index.html          # Public storefront
├── public/
│   ├── assets/
│   │   ├── css/               # Stylesheets
│   │   ├── js/                # JavaScript files
│   │   └── images/            # Product images
│   ├── admin/
│   │   └── assets/            # Admin panel CSS
│   ├── .htaccess              # URL rewriting rules
│   ├── index.php              # Public entry point
│   └── router.php             # Request router
├── db.sql                     # Database sche (not `JellyShop`), update the URLs in:
- `app/config/common.php` - Update `ASSET_PATH` constant (line 9):
  ```php
  define('ASSET_PATH', '/YourFolderName/public');
  ```
- `app/config/common.php` - Update redirect in `require_admin()` function (line 15):
  ```php
  header('Location: /YourFolderName/public/admin/login');
  ```
- `app/controllers/admin/*.php` - Update redirect URLs in login.php and logout.php
- `public/router.php` - Update path normalization (line 26):
  ```php
  if ($part === 'YourFolderName' || $part === 'public') {
  ```

## 🔧 Configuration

### URL Configuration

If you place the project in a different folder, update the URLs in:
- `app/config/common.php` - Update `ASSET_PATH`
- `app/controllers/admin/*.php` - Update redirect URLs
- `public/router.php` - Update path normalization

### Database Configuration

Edit `app/config/config.php`:
```php
$DB_HOST = '127.0.0.1';    // Database host
$DB_USER = 'root';         // Database username
$DB_PASS = '';             // Database password
$DB_NAME = 'raucaushop';   // Database name
```

## 🛡️ Security Notes

1. **Change default admin password** immediately after installation
2. **Remove or secure** `install.php` after installation
3. **Use environment variables** for sensitive configuration in production
4. **Enable HTTPS** in production environments
5. **Regularly update** PHP and MySQL versions

## 📝 Database Schema

The application uses the following main tables:

- **admin_users**: Store admin account credentials
- **customers**: Registered customer accounts
- **products**: Product catalog with images and prices
- **orders**: Customer order information
- **order_items**: Individual items in each order

## 👤 Author

**Gengar Team**
- GitHub: [@TrunggTruong](https://github.com/TrunggTruong)

## 🐛 Troubleshooting

### Common Issues

**Problem**: "Fatal error: Uncaught Error: Undefined constant"
- **Solution**: Make sure `app/config/config.php` exists and is properly configured

**Problem**: "404 Not Found" errors
- **Solution**: Check that Apache mod_rewrite is enabled and `.htaccess` files are being read

**Problem**: Images not displaying
- **Solution**: Verify that image paths in database match actual file locations in `public/assets/images/`

**Problem**: "Database connection failed"
- **Solution**: 
  - Check MySQL service is running
  - Verify database credentials in `config.php`
  - Ensure database exists

**Problem**: Cannot login to admin panel
- **Solution**: 
  - Verify admin account exists in `admin_users` table
  - Reset password using phpMyAdmin if needed

## 📞 Support

If you encounter any issues or have questions, please open an issue on GitHub.

---

