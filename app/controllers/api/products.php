<?php
// Products API endpoint - returns product list with search, filter, and pagination
require_once __DIR__ . '/../../config/config.php';
header('Content-Type: application/json; charset=utf-8');

$db = db_connect();
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'error' => 'database_connection_failed',
        'message' => 'Unable to connect to database. Please check your database configuration.'
    ]);
    exit;
}

// Check if products table exists
$tableCheck = $db->query("SHOW TABLES LIKE 'products'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    http_response_code(500);
    echo json_encode([
        'error' => 'table_not_found',
        'message' => 'Products table does not exist. Please run install.php first.'
    ]);
    exit;
}

// Get query parameters
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 9;

// Build search conditions with prepared statements
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "(name LIKE ? OR description LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if ($category !== '') {
    $conditions[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}

$where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';

// Count total products for pagination
$stmt = $db->prepare('SELECT COUNT(*) AS total FROM products' . $where);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total = ($result->fetch_assoc())['total'] ?? 0;
$stmt->close();

header('X-Total-Count: ' . $total);

// Fetch products for current page
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("SELECT id, name, description, price, image, category FROM products$where ORDER BY id LIMIT ? OFFSET ?");
if (count($params) > 0) {
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
