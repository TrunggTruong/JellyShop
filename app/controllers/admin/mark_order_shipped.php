<?php
// Mark order as shipped controller
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id > 0) {
    $db = db_connect();
    $stmt = $db->prepare('UPDATE orders SET shipped=1 WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['flash_message'] = 'Order marked as shipped.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to mark order as shipped.';
        $_SESSION['flash_type'] = 'error';
    }
    $stmt->close();
} else {
    $_SESSION['flash_message'] = 'Invalid order.';
    $_SESSION['flash_type'] = 'error';
}

header('Location: view_order?id=' . $id);
exit;

