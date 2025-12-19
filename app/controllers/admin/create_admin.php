<?php
// Create admin controller - handles admin creation form submission
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

// Note: This page is public for initial admin setup - no require_admin() check

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['admin_message'] = 'Please provide both username and password.';
        $_SESSION['admin_message_type'] = 'error';
        $_SESSION['flash_message'] = 'Please provide both username and password.';
        $_SESSION['flash_type'] = 'error';
        header('Location: create_admin');
        exit;
    }

    $db = db_connect();
    if (!$db) {
        $_SESSION['admin_message'] = 'Database connection failed.';
        $_SESSION['admin_message_type'] = 'error';
        $_SESSION['flash_message'] = 'Database connection failed.';
        $_SESSION['flash_type'] = 'error';
        header('Location: create_admin');
        exit;
    }

    // Hash password and insert new admin user
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
    
    if ($stmt) {
        $stmt->bind_param('ss', $username, $hash);
        if ($stmt->execute()) {
            $_SESSION['admin_message'] = "Admin user '$username' created successfully.";
            $_SESSION['admin_message_type'] = 'success';
            $_SESSION['flash_message'] = "Admin user '$username' created successfully.";
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['admin_message'] = 'Failed to create admin. Username may already exist.';
            $_SESSION['admin_message_type'] = 'error';
            $_SESSION['flash_message'] = 'Failed to create admin. Username may already exist.';
            $_SESSION['flash_type'] = 'error';
        }
        $stmt->close();
    } else {
        $_SESSION['admin_message'] = 'Database error.';
        $_SESSION['admin_message_type'] = 'error';
        $_SESSION['flash_message'] = 'Database error.';
        $_SESSION['flash_type'] = 'error';
    }
    
    header('Location: create_admin');
    exit;
}

// If not POST, display create admin form using layout
$admin_view = 'create_admin';
$admin_page_title = 'Create Admin User';
require_once __DIR__ . '/../../views/admin/layout.php';
