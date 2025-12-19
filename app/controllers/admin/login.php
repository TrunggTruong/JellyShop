<?php
// Admin login controller - handles login form submission
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['login_error'] = 'Please enter both username and password.';
        header('Location: /JellyShop/public/admin/login');
        exit;
    }

    $db = db_connect();
    if (!$db) {
        $_SESSION['login_error'] = 'Could not connect to database.';
        header('Location: /JellyShop/public/admin/login');
        exit;
    }

    // Verify username and password
    $stmt = $db->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                // Login successful - create session
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                $_SESSION['admin_user'] = $username;
                $_SESSION['admin_id'] = (int)$user['id'];
                $stmt->close();
                header('Location: /JellyShop/public/admin/index');
                exit;
            } else {
                $_SESSION['login_error'] = 'Username or password is incorrect.';
            }
        } else {
            $_SESSION['login_error'] = 'No admin account found. Please create one first.';
        }
        $stmt->close();
    } else {
        $_SESSION['login_error'] = 'Database error.';
    }
    
    header('Location: /JellyShop/public/admin/login');
    exit;
}

// If not POST, display login form using layout
$admin_view = 'login';
$admin_page_title = 'Admin Login';
require_once __DIR__ . '/../../views/admin/layout.php';

