<?php
// Delete product controller - removes product and its image file
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/common.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $db = db_connect();
    if ($db) {
        // Get product image path before deletion
        $stmt = $db->prepare('SELECT image FROM products WHERE id=?');
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($row && !empty($row['image'])) {
                    $imagePath = realpath(__DIR__ . '/../../public/' . $row['image']);
                    if ($imagePath && file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
            }
            $stmt->close();
        }

        // Delete product from database
        $stmt2 = $db->prepare('DELETE FROM products WHERE id=?');
        if ($stmt2) {
            $stmt2->bind_param('i', $id);
            if ($stmt2->execute() && $stmt2->affected_rows > 0) {
                $_SESSION['admin_message'] = 'Product deleted successfully.';
                $_SESSION['admin_message_type'] = 'success';
                $_SESSION['flash_message'] = 'Product deleted successfully.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['admin_message'] = 'Failed to delete product.';
                $_SESSION['admin_message_type'] = 'error';
                $_SESSION['flash_message'] = 'Failed to delete product.';
                $_SESSION['flash_type'] = 'error';
            }
            $stmt2->close();
        } else {
            $_SESSION['admin_message'] = 'Database error when preparing delete.';
            $_SESSION['admin_message_type'] = 'error';
            $_SESSION['flash_message'] = 'Database error when preparing delete.';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['admin_message'] = 'Database connection failed.';
        $_SESSION['admin_message_type'] = 'error';
        $_SESSION['flash_message'] = 'Database connection failed.';
        $_SESSION['flash_type'] = 'error';
    }
}

header('Location: products');
exit;
