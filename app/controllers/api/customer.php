<?php
// Customer portal API: profile info and order history
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['customer']) || empty($_SESSION['customer']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized', 'message' => 'You need to login to continue']);
    exit;
}

$db = db_connect();
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'database_connection_failed']);
    exit;
}

ensure_customers_table($db);
ensure_orders_structure($db);
migrate_orders_columns($db);

$customerId = (int)$_SESSION['customer']['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? 'profile';

if ($method === 'GET') {
    if ($action === 'orders') {
        fetch_orders($db, $customerId);
    } else {
        fetch_profile($db, $customerId);
    }
    exit;
}

if ($method === 'POST') {
    if ($action === 'update_profile') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        update_profile($db, $customerId, $payload);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_action']);
    }
    exit;
}

http_response_code(405);
header('Allow: GET, POST');
echo json_encode(['error' => 'method_not_allowed']);
exit;

function fetch_profile($db, $customerId) {
    $stmt = $db->prepare('SELECT id, full_name, email, phone, address, created_at FROM customers WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    
    if (!$profile) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        return;
    }
    
    echo json_encode(['success' => true, 'profile' => $profile]);
}

function fetch_orders($db, $customerId) {
    $stmt = $db->prepare('SELECT id, order_code, total_price, created_at, shipped, cancelled FROM orders WHERE customer_id = ? ORDER BY id DESC LIMIT 50');
    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function update_profile($db, $customerId, $payload) {
    $fullName = trim($payload['full_name'] ?? '');
    
    if ($fullName === '' || mb_strlen($fullName) < 2) {
        respond_error('Full name must be at least 2 characters');
    }
    
    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $fullName)) {
        respond_error('Full name can only contain letters and spaces');
    }
    
    $stmt = $db->prepare('UPDATE customers SET full_name = ? WHERE id = ?');
    $stmt->bind_param('si', $fullName, $customerId);
    $stmt->execute();
    $stmt->close();
    
    // Update session copy
    $_SESSION['customer']['full_name'] = $fullName;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
}

function respond_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => 'validation_error', 'message' => $message]);
    exit;
}

