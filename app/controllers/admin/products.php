<?php
// Products management controller - displays list of all products
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
$per_page = 10;

// Products pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;
$res_total = $db->query('SELECT COUNT(*) AS cnt FROM products');
$total_products = ($res_total && $r = $res_total->fetch_assoc()) ? (int)$r['cnt'] : 0;
$products_res = $db->query('SELECT * FROM products ORDER BY id DESC LIMIT ' . $per_page . ' OFFSET ' . $offset);
$products_pages = max(1, ceil($total_products / $per_page));

$categoryOptions = get_categories();

// Render view using layout
$admin_view = 'products';
$admin_page_title = 'Manage Products';
require_once __DIR__ . '/../../views/admin/layout.php';
?>
