<?php
// Add product controller - handles product creation form submission
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$categoryOptions = get_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $desc = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? $categoryOptions[0];
    
    if (!in_array($category, $categoryOptions, true)) {
        $category = $categoryOptions[0];
    }
    
    $image_path = '';

    // Handle image upload and create thumbnail
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $image_path = upload_product_image();
    }

    // Insert new product into database
    $db = db_connect();
    if ($db) {
        $stmt = $db->prepare('INSERT INTO products (name,description,price,image,category) VALUES (?,?,?,?,?)');
        if ($stmt) {
            $stmt->bind_param('ssiss', $name, $desc, $price, $image_path, $category);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['admin_message'] = 'Product added successfully.';
                $_SESSION['admin_message_type'] = 'success';
                $_SESSION['flash_message'] = 'Product added successfully.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['admin_message'] = 'Failed to add product.';
                $_SESSION['admin_message_type'] = 'error';
                $_SESSION['flash_message'] = 'Failed to add product.';
                $_SESSION['flash_type'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['admin_message'] = 'Database error when preparing statement.';
            $_SESSION['admin_message_type'] = 'error';
            $_SESSION['flash_message'] = 'Database error when preparing statement.';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['admin_message'] = 'Database connection failed.';
        $_SESSION['admin_message_type'] = 'error';
        $_SESSION['flash_message'] = 'Database connection failed.';
        $_SESSION['flash_type'] = 'error';
    }

    header('Location: products');
    exit;
}

// If not POST, redirect to add product form page
header('Location: add_product_form');
exit;

