<?php
// Orders API endpoint - handles new order creation from frontend
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => 'Please login to place order']);
    exit;
}

$orderData = file_get_contents('php://input');
$orderInfo = json_decode($orderData, true);

// Validate required data
if (!isset($orderInfo['customer_name']) || empty($orderInfo['items']) || !is_array($orderInfo['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_data', 'message' => 'Missing customer information or cart data']);
    exit;
}

$database = db_connect();
if (!$database) {
    http_response_code(500);
    echo json_encode(['error' => 'database_connection_failed']);
    exit;
}

// Ensure latest schema
ensure_customers_table($database);
ensure_orders_structure($database);
// Migrate old column names if needed (for backward compatibility)
migrate_orders_columns($database);

// Generate unique order code (format: RC-YYYYMMDD-XXXXXX)
function generateOrderCode() {
    return 'RC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
}

/**
 * Validates customer name
 * - At least 2 characters
 * - Contains only letters, spaces, Vietnamese characters, and common name characters
 * - Cannot be only numbers or special characters
 */
function validateName($name) {
    if (empty($name) || !is_string($name)) {
        return ['valid' => false, 'message' => 'Please enter your full name'];
    }
    
    $trimmedName = trim($name);
    
    if (strlen($trimmedName) < 2) {
        return ['valid' => false, 'message' => 'Full name must be at least 2 characters'];
    }
    
    if (strlen($trimmedName) > 100) {
        return ['valid' => false, 'message' => 'Full name cannot exceed 100 characters'];
    }
    
    // Allow letters (including Vietnamese), spaces, apostrophes, hyphens, and dots
    // Using Unicode property \p{L} for letters
    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $trimmedName)) {
        return ['valid' => false, 'message' => 'Full name can only contain letters, spaces and valid special characters'];
    }
    
    // Check if it contains at least one letter
    if (!preg_match('/[\p{L}]/u', $trimmedName)) {
        return ['valid' => false, 'message' => 'Full name must contain at least one letter'];
    }
    
    return ['valid' => true, 'message' => ''];
}

/**
 * Validates Vietnamese phone number
 * - 10 digits starting with 0 (domestic format: 0xxxxxxxxx)
 * - Or international format: +84xxxxxxxxx (11 digits after +84)
 */
function validatePhone($phone) {
    if (empty($phone) || !is_string($phone)) {
        return ['valid' => false, 'message' => 'Vui lòng nhập số điện thoại'];
    }
    
    // Remove all spaces, dashes, and parentheses
    $cleanedPhone = preg_replace('/[\s\-\(\)]/', '', trim($phone));
    
    if (strlen($cleanedPhone) === 0) {
        return ['valid' => false, 'message' => 'Vui lòng nhập số điện thoại'];
    }
    
    // Check for Vietnamese domestic format: 0xxxxxxxxx (10 digits)
    if (preg_match('/^0\d{9}$/', $cleanedPhone)) {
        // Validate first digit after 0 (should be 3, 5, 7, 8, or 9 for valid Vietnamese mobile)
        $secondDigit = substr($cleanedPhone, 1, 1);
        if (!in_array($secondDigit, ['3', '5', '7', '8', '9'])) {
            return ['valid' => false, 'message' => 'Invalid phone number. Vietnamese numbers usually start with 03, 05, 07, 08, 09'];
        }
        return ['valid' => true, 'message' => ''];
    }
    
    // Check for international format: +84xxxxxxxxx or +84xxxxxxxxxx
    if (preg_match('/^\+84\d{9,10}$/', $cleanedPhone)) {
        // For +84 format, remove +84 and check if it starts with valid digit
        $withoutPrefix = substr($cleanedPhone, 3);
        $firstDigit = substr($withoutPrefix, 0, 1);
        if (!in_array($firstDigit, ['3', '5', '7', '8', '9'])) {
            return ['valid' => false, 'message' => 'Invalid phone number'];
        }
        return ['valid' => true, 'message' => ''];
    }
    
    return ['valid' => false, 'message' => 'Invalid phone number. Please enter 10 digits (starting with 0) or +84 format'];
}

/**
 * Validates delivery address
 * - At least 10 characters
 * - Contains meaningful content (not just numbers or special characters)
 */
function validateAddress($address) {
    if (empty($address) || !is_string($address)) {
        return ['valid' => false, 'message' => 'Please enter delivery address'];
    }
    
    $trimmedAddress = trim($address);
    
    if (strlen($trimmedAddress) < 10) {
        return ['valid' => false, 'message' => 'Address must be at least 10 characters'];
    }
    
    if (strlen($trimmedAddress) > 500) {
        return ['valid' => false, 'message' => 'Address cannot exceed 500 characters'];
    }
    
    // Check if address contains meaningful content (letters, Vietnamese characters)
    if (!preg_match('/[\p{L}]/u', $trimmedAddress)) {
        return ['valid' => false, 'message' => 'Địa chỉ phải chứa chữ cái (không chỉ số hoặc ký tự đặc biệt)'];
    }
    
    // Check if it's not just repeated characters or whitespace
    $withoutSpaces = preg_replace('/\s+/', '', $trimmedAddress);
    $uniqueChars = count_chars($withoutSpaces, 3);
    if (strlen($uniqueChars) < 3) {
        return ['valid' => false, 'message' => 'Địa chỉ không hợp lệ'];
    }
    
    return ['valid' => true, 'message' => ''];
}

$inTransaction = false;
$database->begin_transaction();
$inTransaction = true;

try {
    $customerSession = $_SESSION['customer'];
    $customerId = (int)$customerSession['id'];
    
    // Generate unique order code (retry if duplicate)
    $orderCode = generateOrderCode();
    $maxRetries = 10;
    $retryCount = 0;
    
    while ($retryCount < $maxRetries) {
        $checkStmt = $database->prepare('SELECT id FROM orders WHERE order_code = ?');
        $checkStmt->bind_param('s', $orderCode);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $checkStmt->close();
        
        if ($result->num_rows === 0) {
            break;
        }
        
        $orderCode = generateOrderCode();
        $retryCount++;
    }
    
    // Validate customer information
    $customerName = trim($orderInfo['customer_name'] ?? '');
    $phone = trim($orderInfo['phone'] ?? '');
    $address = trim($orderInfo['address'] ?? '');
    
    // Validate name
    $nameValidation = validateName($customerName);
    if (!$nameValidation['valid']) {
        throw new Exception($nameValidation['message']);
    }
    
    // Validate phone
    $phoneValidation = validatePhone($phone);
    if (!$phoneValidation['valid']) {
        throw new Exception($phoneValidation['message']);
    }
    
    // Clean phone number (remove spaces, dashes, parentheses)
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Validate address
    $addressValidation = validateAddress($address);
    if (!$addressValidation['valid']) {
        throw new Exception($addressValidation['message']);
    }
    
    // Prepare cart items with verified prices
    $sanitizedItems = [];
    $totalPrice = 0;
    
    $productStmt = $database->prepare('SELECT id, price FROM products WHERE id = ? LIMIT 1');
    if (!$productStmt) {
        throw new Exception('Unable to process product');
    }
    
    foreach ($orderInfo['items'] as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        
        if ($productId <= 0 || $quantity <= 0) {
            throw new Exception('Invalid product information');
        }
        
        $productStmt->bind_param('i', $productId);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        $product = $productResult ? $productResult->fetch_assoc() : null;
        
        if (!$product) {
            throw new Exception('Product does not exist');
        }
        
        $price = (int)$product['price'];
        if ($price <= 0) {
            throw new Exception('Invalid product price');
        }
        
        if (!isset($sanitizedItems[$productId])) {
            $sanitizedItems[$productId] = [
                'product_id' => $productId,
                'quantity' => 0,
                'price' => $price
            ];
        }
        
        $sanitizedItems[$productId]['quantity'] += $quantity;
        $totalPrice += $price * $quantity;
    }
    
    $productStmt->close();
    
    if ($totalPrice <= 0 || empty($sanitizedItems)) {
        throw new Exception('Invalid cart');
    }
    
    // Insert order record
    $stmt = $database->prepare("INSERT INTO orders (customer_id, customer_name, customer_phone, customer_address, order_code, total_price, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->bind_param('issssi', $customerId, $customerName, $phone, $address, $orderCode, $totalPrice);
    if (!$stmt->execute()) {
        throw new Exception('Could not save order');
    }
    
    $orderId = $database->insert_id;
    $stmt->close();
    
    if (!$orderId) {
        throw new Exception('Could not get order ID');
    }
    
    // Insert order items
    $itemStmt = $database->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    if (!$itemStmt) {
        throw new Exception('Database error');
    }
    
    foreach ($sanitizedItems as $item) {
        $itemStmt->bind_param('iiii', $orderId, $item['product_id'], $item['quantity'], $item['price']);
        if (!$itemStmt->execute()) {
            throw new Exception('Could not save order item');
        }
    }
    
    $itemStmt->close();
    $database->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'orderCode' => $orderCode
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($inTransaction && $database) {
        $database->rollback();
    }
    error_log('Order failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'order_failed', 'message' => $e->getMessage()]);
} catch (Error $e) {
    if ($inTransaction && $database) {
        $database->rollback();
    }
    error_log('Order fatal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'order_failed', 'message' => 'An error occurred while processing order. Please try again later.']);
}
