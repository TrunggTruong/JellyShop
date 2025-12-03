<?php
// Authentication API for customer accounts (register, login, logout, session status)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

header('Content-Type: application/json; charset=utf-8');

$db = db_connect();
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'database_connection_failed']);
    exit;
}

// Ensure required tables/columns exist
ensure_customers_table($db);
ensure_orders_structure($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (isset($_SESSION['customer'])) {
        $customerId = (int)($_SESSION['customer']['id'] ?? 0);
        
        if ($customerId > 0) {
            $stmt = $db->prepare('SELECT id, full_name, email, created_at, is_locked FROM customers WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $customerId);
                $stmt->execute();
                $result = $stmt->get_result();
                $customer = $result ? $result->fetch_assoc() : null;
                $stmt->close();
                
                if ($customer && !(int)$customer['is_locked']) {
                    $_SESSION['customer']['full_name'] = $customer['full_name'];
                    $_SESSION['customer']['email'] = $customer['email'];
                    $_SESSION['customer']['created_at'] = $customer['created_at'];
                    
                    echo json_encode([
                        'authenticated' => true,
                        'customer' => $_SESSION['customer']
                    ]);
                    exit;
                }
            }
        }
        
        unset($_SESSION['customer']);
    }
    
    echo json_encode(['authenticated' => false]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'register':
        handle_register($db, $input);
        break;
    case 'login':
        handle_login($db, $input);
        break;
    case 'logout':
        handle_logout();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'invalid_action', 'message' => 'Invalid request']);
        exit;
}

function handle_register($db, $input) {
    $fullName = trim($input['full_name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    $nameValidation = validate_full_name($fullName);
    if ($nameValidation !== true) {
        respond_error($nameValidation);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond_error('Invalid email');
    }
    
    $passwordValidation = validate_password($password);
    if ($passwordValidation !== true) {
        respond_error($passwordValidation);
    }
    
    if ($confirmPassword !== '' && $password !== $confirmPassword) {
        respond_error('Passwords do not match');
    }
    
    $checkStmt = $db->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult && $checkResult->num_rows > 0) {
        $checkStmt->close();
        respond_error('Email already in use, please choose another email');
    }
    $checkStmt->close();
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare('INSERT INTO customers (full_name, email, password_hash) VALUES (?, ?, ?)');
    if (!$stmt) {
        respond_error('Unable to register account, please try again later');
    }
    $stmt->bind_param('sss', $fullName, $email, $passwordHash);
    
    if (!$stmt->execute()) {
        respond_error('Unable to register account, please try again later');
    }
    
    $customerId = $stmt->insert_id;
    $stmt->close();
    
    $customerData = [
        'id' => (int)$customerId,
        'full_name' => $fullName,
        'email' => $email,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    set_customer_session($customerData);
    
    echo json_encode(['success' => true, 'customer' => $customerData]);
    exit;
}

function handle_login($db, $input) {
    $email = strtolower(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond_error('Please enter a valid email');
    }
    
    if ($password === '') {
        respond_error('Please enter password');
    }
    
    $stmt = $db->prepare('SELECT id, full_name, email, password_hash, created_at, is_locked FROM customers WHERE email = ? LIMIT 1');
    if (!$stmt) {
        respond_error('Unable to login, please try again later');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    
    if (!$customer || !password_verify($password, $customer['password_hash'])) {
        respond_error('Email or password is incorrect');
    }
    
    if (!empty($customer['is_locked'])) {
        respond_error('Your account has been locked. Please contact support.');
    }
    
    $customerData = [
        'id' => (int)$customer['id'],
        'full_name' => $customer['full_name'],
        'email' => $customer['email'],
        'created_at' => $customer['created_at']
    ];
    
    set_customer_session($customerData);
    
    echo json_encode(['success' => true, 'customer' => $customerData]);
    exit;
}

function handle_logout() {
    if (isset($_SESSION['customer'])) {
        unset($_SESSION['customer']);
    }
    session_regenerate_id(true);
    echo json_encode(['success' => true]);
    exit;
}

function set_customer_session($customerData) {
    session_regenerate_id(true);
    $_SESSION['customer'] = $customerData;
}

function respond_error($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => 'validation_error', 'message' => $message]);
    exit;
}

function validate_full_name($name) {
    if ($name === '') {
        return 'Please enter your full name';
    }
    
    $length = mb_strlen($name);
    if ($length < 2) {
        return 'Full name must be at least 2 characters';
    }
    if ($length > 255) {
        return 'Full name is too long';
    }
    
    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $name)) {
        return 'Full name can only contain letters and spaces';
    }
    
    return true;
}

function validate_password($password) {
    if ($password === '') {
        return 'Please enter password';
    }
    
    if (strlen($password) < 6) {
        return 'Password must be at least 6 characters';
    }
    
    return true;
}

