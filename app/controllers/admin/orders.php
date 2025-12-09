<?php
// Orders management controller - displays list of all orders
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
$per_page = 10;

// Orders pagination setup
$orders_page = max(1, (int)($_GET['orders_page'] ?? 1));
$orders_offset = ($orders_page - 1) * $per_page;
$orders_total_res = $db->query('SELECT COUNT(*) AS cnt FROM orders');
$total_orders = ($orders_total_res && $r = $orders_total_res->fetch_assoc()) ? (int)$r['cnt'] : 0;

$orders_res = $db->query('SELECT o.*, c.full_name AS registered_name, c.email AS customer_email FROM orders o LEFT JOIN customers c ON c.id = o.customer_id ORDER BY o.id DESC LIMIT ' . $per_page . ' OFFSET ' . $orders_offset);
$orders_pages = max(1, ceil($total_orders / $per_page));

// Render view using layout
$admin_view = 'orders';
$admin_page_title = 'Orders';
require_once __DIR__ . '/../../views/admin/layout.php';
?>
