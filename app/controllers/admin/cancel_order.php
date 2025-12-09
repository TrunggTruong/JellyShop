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
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = 'Order cancelled successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to cancel order.';
        $_SESSION['flash_type'] = 'error';
    }
    $stmt->close();
} else {
    $_SESSION['flash_message'] = 'Invalid order.';
    $_SESSION['flash_type'] = 'error';
}

header('Location: orders?orders_page=' . $orders_page);
exit;

