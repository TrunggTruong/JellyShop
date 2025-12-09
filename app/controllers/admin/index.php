<?php
// Admin dashboard index controller - main landing page
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
ensure_customers_table($db);
ensure_orders_structure($db);
// Migrate old column names if needed
migrate_orders_columns($db);

// Render view using layout
$admin_view = 'index';
$admin_page_title = 'Admin Dashboard';
require_once __DIR__ . '/../../views/admin/layout.php';
?>
