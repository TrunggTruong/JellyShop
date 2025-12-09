<?php
// Admin dashboard index controller - prepares admin dashboard and renders via layout
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
ensure_customers_table($db);
ensure_orders_structure($db);
// Migrate old column names if needed
migrate_orders_columns($db);

$per_page = 10;

// Products pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$res_total = $db->query('SELECT COUNT(*) AS cnt FROM products');
$total_products = ($res_total && $r = $res_total->fetch_assoc()) ? (int)$r['cnt'] : 0;
$products_res = $db->query('SELECT * FROM products ORDER BY id DESC LIMIT ' . $per_page . ' OFFSET ' . $offset);
$products_pages = max(1, ceil($total_products / $per_page));

// Orders pagination setup
$orders_page = max(1, (int)($_GET['orders_page'] ?? 1));
$orders_offset = ($orders_page - 1) * $per_page;
$orders_total_res = $db->query('SELECT COUNT(*) AS cnt FROM orders');
$total_orders = ($orders_total_res && $r = $orders_total_res->fetch_assoc()) ? (int)$r['cnt'] : 0;
// Cancel order is handled by controller

$orders_res = $db->query('SELECT o.*, c.full_name AS registered_name, c.email AS customer_email FROM orders o LEFT JOIN customers c ON c.id = o.customer_id ORDER BY o.id DESC LIMIT ' . $per_page . ' OFFSET ' . $orders_offset);
$orders_pages = max(1, ceil($total_orders / $per_page));

$categoryOptions = get_categories();

// Render view using layout
$admin_view = 'index';
$admin_page_title = 'Admin Dashboard';
require_once __DIR__ . '/../../views/admin/layout.php';
