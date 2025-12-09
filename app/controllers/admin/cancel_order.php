<?php
// Cancel order controller
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
$orders_page = max(1, (int)($_GET['orders_page'] ?? 1));

if ($id > 0) {
    $db = db_connect();
    $stmt = $db->prepare('UPDATE orders SET cancelled=1 WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

header('Location: index?orders_page=' . $orders_page . '#orders-section');
exit;

