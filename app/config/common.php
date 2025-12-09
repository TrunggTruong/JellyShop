<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}
// Common utility functions for admin panel - shared across admin pages

// Redirect to login if admin is not authenticated
function require_admin() {
    if (!isset($_SESSION['admin'])) {
        header('Location: /raucau/public/admin/login');
        exit;
    }
}

// Return array of product categories
function get_categories() {
    return [
        'Traditional',
        'Fruit',
        'Coffee & Tea',
        'Dessert',
        'Special'
    ];
}

// Ensure customers table exists (for customer accounts)
function ensure_customers_table($db) {
    $db->query("
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            address TEXT,
            is_locked TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    ensure_column($db, 'customers', 'phone', 'VARCHAR(50) NULL');
    ensure_column($db, 'customers', 'address', 'TEXT NULL');
    ensure_column($db, 'customers', 'is_locked', 'TINYINT(1) NOT NULL DEFAULT 0');
}

// Ensure orders table has modern columns + relationships
function ensure_orders_structure($db) {
    ensure_column($db, 'orders', 'order_code', 'VARCHAR(50)');
    ensure_column($db, 'orders', 'customer_id', 'INT NULL');
    ensure_column($db, 'orders', 'shipped', 'TINYINT(1) DEFAULT 0');
    ensure_column($db, 'orders', 'total_price', 'INT DEFAULT 0');
    ensure_column($db, 'orders', 'cancelled', 'TINYINT(1) DEFAULT 0');
    
    // Add foreign key if missing
    $fkCheck = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'orders' 
          AND COLUMN_NAME = 'customer_id' 
          AND REFERENCED_TABLE_NAME = 'customers'
    ");
    
    if ($fkCheck && $fkCheck->num_rows === 0) {
        $db->query("
            ALTER TABLE orders 
            ADD CONSTRAINT fk_orders_customers_auto 
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
        ");
    }
}

// Ensure database column exists, create if missing (for backward compatibility)
function ensure_column($db, $table, $column, $definition) {
    $check = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        $db->query("ALTER TABLE $table ADD COLUMN $column $definition");
    }
}

// Migrate old column names to new ones (for databases created with old schema)
function migrate_orders_columns($db) {
    // Check if old 'phone' column exists and migrate to 'customer_phone'
    $check_phone = $db->query("SHOW COLUMNS FROM orders LIKE 'phone'");
    if ($check_phone && $check_phone->num_rows > 0) {
        // Check if customer_phone doesn't exist
        $check_customer_phone = $db->query("SHOW COLUMNS FROM orders LIKE 'customer_phone'");
        if ($check_customer_phone && $check_customer_phone->num_rows == 0) {
            // Rename phone to customer_phone
            $db->query("ALTER TABLE orders CHANGE COLUMN phone customer_phone VARCHAR(50)");
        } else {
            // Both exist, copy data and drop old column
            $db->query("UPDATE orders SET customer_phone = phone WHERE customer_phone IS NULL OR customer_phone = ''");
            $db->query("ALTER TABLE orders DROP COLUMN phone");
        }
    }
    
    // Check if old 'address' column exists and migrate to 'customer_address'
    $check_address = $db->query("SHOW COLUMNS FROM orders LIKE 'address'");
    if ($check_address && $check_address->num_rows > 0) {
        // Check if customer_address doesn't exist
        $check_customer_address = $db->query("SHOW COLUMNS FROM orders LIKE 'customer_address'");
        if ($check_customer_address && $check_customer_address->num_rows == 0) {
            // Rename address to customer_address
            $db->query("ALTER TABLE orders CHANGE COLUMN address customer_address TEXT");
        } else {
            // Both exist, copy data and drop old column
            $db->query("UPDATE orders SET customer_address = address WHERE customer_address IS NULL OR customer_address = ''");
            $db->query("ALTER TABLE orders DROP COLUMN address");
        }
    }
}

// Create resized thumbnail image from source (supports JPG/PNG)
// Falls back to copying original file if GD extension is not available
function create_thumbnail($source_path, $dest_path, $max_w = 300, $max_h = 200) {
    $ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
    
    if (!file_exists($source_path)) return '';
    
    // Check if GD extension is available
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        // GD not available, just copy the file
        copy($source_path, $dest_path);
        return $dest_path;
    }
    
    if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png') {
        $imageInfo = @getimagesize($source_path);
        if (!$imageInfo) {
            // Invalid image, just copy
            copy($source_path, $dest_path);
            return $dest_path;
        }
        
        list($w, $h) = $imageInfo;
        $ratio = min($max_w / $w, $max_h / $h, 1);
        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);
        
        // If image is already smaller than max size, just copy
        if ($nw >= $w && $nh >= $h) {
            copy($source_path, $dest_path);
            return $dest_path;
        }
        
        $dst = @imagecreatetruecolor($nw, $nh);
        if (!$dst) {
            copy($source_path, $dest_path);
            return $dest_path;
        }
        
        if ($ext === 'png') {
            $src = @imagecreatefrompng($source_path);
            if ($src) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagepng($dst, $dest_path);
                imagedestroy($src);
            } else {
                copy($source_path, $dest_path);
            }
        } else {
            $src = @imagecreatefromjpeg($source_path);
            if ($src) {
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagejpeg($dst, $dest_path, 85);
                imagedestroy($src);
            } else {
                copy($source_path, $dest_path);
            }
        }
        
        imagedestroy($dst);
        return $dest_path;
    }
    
    // For other file types, just copy
    copy($source_path, $dest_path);
    return $dest_path;
}

function sanitize_admin_redirect($value, $fallback = 'customers.php') {
    $value = trim((string)$value);
    if ($value === '') {
        return $fallback;
    }
    if (strpos($value, '://') !== false || strpos($value, '//') === 0 || strpbrk($value, "\r\n") !== false) {
        return $fallback;
    }
    if ($value[0] === '/') {
        return $fallback;
    }
    return $value;
}

function process_customer_admin_action($db, $defaultRedirect = 'customers.php') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    $action = $_POST['action'] ?? '';
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $redirect = sanitize_admin_redirect($_POST['redirect'] ?? $defaultRedirect, $defaultRedirect);
    
    $message = 'Invalid action';
    $type = 'flash-error';
    
    if ($customerId > 0) {
        switch ($action) {
            case 'lock':
                $stmt = $db->prepare('UPDATE customers SET is_locked = 1 WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $customerId);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $message = 'Account has been locked.';
                        $type = 'flash-success';
                    } else {
                        $message = 'Unable to lock this account.';
                    }
                    $stmt->close();
                } else {
                    $message = 'Unable to process lock request.';
                }
                break;
            case 'unlock':
                $stmt = $db->prepare('UPDATE customers SET is_locked = 0 WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $customerId);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $message = 'Account has been unlocked.';
                        $type = 'flash-success';
                    } else {
                        $message = 'Unable to unlock this account.';
                    }
                    $stmt->close();
                } else {
                    $message = 'Unable to process unlock request.';
                }
                break;
            case 'delete':
                $stmt = $db->prepare('DELETE FROM customers WHERE id = ?');
                if ($stmt) {
                    $stmt->bind_param('i', $customerId);
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $message = 'Account has been deleted. Related orders are preserved.';
                        $type = 'flash-success';
                    } else {
                        $message = 'Unable to delete this account.';
                    }
                    $stmt->close();
                } else {
                    $message = 'Unable to process delete request.';
                }
                break;
            default:
                $message = 'Invalid action.';
                break;
        }
    } else {
        $message = 'Invalid customer.';
    }
    
    $_SESSION['customer_flash'] = $message;
    $_SESSION['customer_flash_type'] = $type;
    
    header('Location: ' . $redirect);
    exit;
}

