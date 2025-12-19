<?php
// Revenue controller - handles revenue statistics display
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
ensure_orders_structure($db);
migrate_orders_columns($db);

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Revenue statistics
$revenueStats = [
    'total_revenue' => 0,
    'total_orders' => 0
];

$statsRes = $db->query("
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_price), 0) AS total_revenue
    FROM orders
    WHERE cancelled = 0
");
if ($statsRes && $row = $statsRes->fetch_assoc()) {
    $revenueStats['total_orders'] = (int)$row['total_orders'];
    $revenueStats['total_revenue'] = (int)$row['total_revenue'];
}

// Get revenue by date (last 30 days)
$dailyRevenueRes = $db->query("
    SELECT 
        DATE(created_at) AS order_date,
        COUNT(*) AS order_count,
        COALESCE(SUM(total_price), 0) AS daily_revenue
    FROM orders
    WHERE cancelled = 0
      AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY order_date DESC
    LIMIT 30
");
$dailyRevenue = [];
if ($dailyRevenueRes) {
    while ($row = $dailyRevenueRes->fetch_assoc()) {
        $dailyRevenue[] = $row;
    }
}

// Get orders list with search
$whereClause = "WHERE o.cancelled = 0";
if ($search !== '') {
    $searchEscaped = $db->real_escape_string($search);
    $whereClause .= " AND (o.order_code LIKE '%$searchEscaped%' OR o.customer_name LIKE '%$searchEscaped%' OR c.email LIKE '%$searchEscaped%')";
}

$ordersSql = "
    SELECT 
        o.*,
        c.full_name AS registered_name,
        c.email AS customer_email
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    $whereClause
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$ordersRes = $db->query($ordersSql);
$orders = [];
if ($ordersRes) {
    while ($row = $ordersRes->fetch_assoc()) {
        $orders[] = $row;
    }
}

$countSql = "SELECT COUNT(*) AS cnt FROM orders o LEFT JOIN customers c ON c.id = o.customer_id $whereClause";
$countRes = $db->query($countSql);
$totalOrders = ($countRes && $r = $countRes->fetch_assoc()) ? (int)$r['cnt'] : 0;
$totalPages = max(1, (int)ceil($totalOrders / $perPage));

// Render view using layout
$admin_view = 'revenue';
$admin_page_title = 'Revenue Management';
require_once __DIR__ . '/../../views/admin/layout.php';

