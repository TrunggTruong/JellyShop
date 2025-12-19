<?php
// Customer Model - Database operations for customers

class Customer {
    /**
     * Get customer by ID
     */
    public static function getById($db, $id) {
        $stmt = $db->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $customer;
    }
    
    /**
     * Get all customers with pagination
     */
    public static function getAll($db, $page = 1, $perPage = 10, $search = '') {
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        
        if ($search !== '') {
            $searchEscaped = $db->real_escape_string($search);
            $whereClause = "WHERE (full_name LIKE '%$searchEscaped%' OR email LIKE '%$searchEscaped%')";
        }
        
        $sql = "SELECT * FROM customers $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        $result = $db->query($sql);
        
        $customers = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
        }
        
        return $customers;
    }
    
    /**
     * Get total count of customers
     */
    public static function getCount($db, $search = '') {
        $whereClause = '';
        if ($search !== '') {
            $searchEscaped = $db->real_escape_string($search);
            $whereClause = "WHERE (full_name LIKE '%$searchEscaped%' OR email LIKE '%$searchEscaped%')";
        }
        
        $sql = "SELECT COUNT(*) AS cnt FROM customers $whereClause";
        $result = $db->query($sql);
        return ($result && $row = $result->fetch_assoc()) ? (int)$row['cnt'] : 0;
    }
    
    /**
     * Lock customer account
     */
    public static function lock($db, $id) {
        $stmt = $db->prepare('UPDATE customers SET is_locked = 1 WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
    
    /**
     * Unlock customer account
     */
    public static function unlock($db, $id) {
        $stmt = $db->prepare('UPDATE customers SET is_locked = 0 WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
    
    /**
     * Delete customer account
     */
    public static function delete($db, $id) {
        $stmt = $db->prepare('DELETE FROM customers WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $success = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
    
    /**
     * Get customer statistics
     */
    public static function getStats($db, $id) {
        $stmt = $db->prepare('SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS total_spent, MAX(created_at) AS last_order FROM orders WHERE customer_id = ?');
        if (!$stmt) return null;
        
        $stats = [
            'total_orders' => 0,
            'total_spent' => 0,
            'last_order' => null,
            'avg_order' => 0
        ];
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_orders'] = (int)$row['total_orders'];
            $stats['total_spent'] = (int)$row['total_spent'];
            $stats['last_order'] = $row['last_order'];
            if ($stats['total_orders'] > 0) {
                $stats['avg_order'] = (int)round($stats['total_spent'] / $stats['total_orders']);
            }
        }
        $stmt->close();
        
        return $stats;
    }
    
    /**
     * Get customer orders
     */
    public static function getOrders($db, $id) {
        $stmt = $db->prepare('SELECT id, order_code, total_price, created_at, shipped, cancelled FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
        if (!$stmt) return [];
        
        $orders = [];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        $stmt->close();
        
        return $orders;
    }
}

