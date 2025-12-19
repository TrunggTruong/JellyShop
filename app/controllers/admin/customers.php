<?php
// Customers controller - handles customer list display
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$db = db_connect();
ensure_customers_table($db);
ensure_orders_structure($db);
migrate_orders_columns($db);

process_customer_admin_action($db, 'customers');

// Get flash message from session (set by process_customer_admin_action)
$GLOBALS['flashMessage'] = $_SESSION['flash_message'] ?? '';
$GLOBALS['flashType'] = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$accountStats = [
    'total_accounts' => 0,
    'locked_accounts' => 0
];

$statsRes = $db->query("
    SELECT 
        COUNT(*) AS total_accounts,
        SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) AS locked_accounts
    FROM customers
");
if ($statsRes && $row = $statsRes->fetch_assoc()) {
    $accountStats['total_accounts'] = (int)$row['total_accounts'];
    $accountStats['locked_accounts'] = (int)$row['locked_accounts'];
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClause = '';
if ($search !== '') {
    $searchEscaped = $db->real_escape_string($search);
    $whereClause = "WHERE (c.full_name LIKE '%$searchEscaped%' OR c.email LIKE '%$searchEscaped%')";
}

$countSql = "SELECT COUNT(*) AS cnt FROM customers c $whereClause";
$countRes = $db->query($countSql);
$totalCustomers = ($countRes && $r = $countRes->fetch_assoc()) ? (int)$r['cnt'] : 0;
$totalPages = max(1, (int)ceil($totalCustomers / $perPage));

$customersSql = "
    SELECT 
        c.*
    FROM customers c
    $whereClause
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$customersRes = $db->query($customersSql);
$customers = [];
if ($customersRes) {
    while ($row = $customersRes->fetch_assoc()) {
        $customers[] = $row;
    }
}

$currentUrl = 'customers';
if (!empty($_SERVER['QUERY_STRING'])) {
    $currentUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// Render view using layout
$admin_view = 'customers';
$admin_page_title = 'Account Management';
require_once __DIR__ . '/../../views/admin/layout.php';

