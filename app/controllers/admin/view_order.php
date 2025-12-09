<?php
// View order controller - handles order detail display
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index#orders-section');
    exit;
}

// Ensure required tables/columns exist
ensure_customers_table($db);
ensure_orders_structure($db);
migrate_orders_columns($db);

// Fetch order details
$stmt = $db->prepare('SELECT o.*, c.full_name AS registered_name, c.email AS customer_email FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: index#orders-section');
    exit;
}

// Fetch order items with product names
$stmt = $db->prepare('SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Render view using layout
$admin_view = 'view_order';
$admin_page_title = 'Order #' . htmlspecialchars($id);
require_once __DIR__ . '/../../views/admin/layout.php';

