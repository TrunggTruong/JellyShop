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
    $stmt->execute();
    $stmt->close();
}

header('Location: view_order?id=' . $id);
exit;

