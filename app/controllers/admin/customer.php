<?php
// Customer detail controller - handles single customer display
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
ensure_customers_table($db);
ensure_orders_structure($db);
migrate_orders_columns($db);

$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    $_SESSION['customer_flash'] = 'Account does not exist.';
    $_SESSION['customer_flash_type'] = 'flash-error';
    header('Location: customers');
    exit;
}

$detailRedirect = 'customer?id=' . $customerId;
process_customer_admin_action($db, $detailRedirect);

$flashMessage = $_SESSION['customer_flash'] ?? null;
$flashType = $_SESSION['customer_flash_type'] ?? null;
unset($_SESSION['customer_flash'], $_SESSION['customer_flash_type']);

$customerStmt = $db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
if (!$customerStmt) {
    $_SESSION['customer_flash'] = 'Unable to load account information.';
    $_SESSION['customer_flash_type'] = 'flash-error';
    header('Location: customers');
    exit;
}
$customerStmt->bind_param('i', $customerId);
$customerStmt->execute();
$customerResult = $customerStmt->get_result();
$customer = $customerResult ? $customerResult->fetch_assoc() : null;
$customerStmt->close();

if (!$customer) {
    $_SESSION['customer_flash'] = 'Account does not exist.';
    $_SESSION['customer_flash_type'] = 'flash-error';
    header('Location: customers');
    exit;
}

$statsStmt = $db->prepare('SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS total_spent, MAX(created_at) AS last_order FROM orders WHERE customer_id = ?');
$customerStats = [
    'total_orders' => 0,
    'total_spent' => 0,
    'last_order' => null,
    'avg_order' => 0
];
if ($statsStmt) {
    $statsStmt->bind_param('i', $customerId);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    if ($statsResult && $row = $statsResult->fetch_assoc()) {
        $customerStats['total_orders'] = (int)$row['total_orders'];
        $customerStats['total_spent'] = (int)$row['total_spent'];
        $customerStats['last_order'] = $row['last_order'];
        if ($customerStats['total_orders'] > 0) {
            $customerStats['avg_order'] = (int)round($customerStats['total_spent'] / $customerStats['total_orders']);
        }
    }
    $statsStmt->close();
}

$ordersStmt = $db->prepare('SELECT id, order_code, total_price, created_at, shipped, cancelled FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$orders = [];
if ($ordersStmt) {
    $ordersStmt->bind_param('i', $customerId);
    $ordersStmt->execute();
    $ordersResult = $ordersStmt->get_result();
    if ($ordersResult) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $ordersStmt->close();
}

// Render view using layout
$admin_view = 'customer';
$admin_page_title = htmlspecialchars($customer['full_name'] ?? 'Customer');
require_once __DIR__ . '/../../views/admin/layout.php';

