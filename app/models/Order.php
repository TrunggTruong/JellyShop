<?php
// Order Model - Database operations for orders

class Order {
    /**
     * Get order by ID
     */
    public static function getById($db, $id) {
        $stmt = $db->prepare('SELECT o.*, c.full_name AS registered_name, c.email AS customer_email FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE o.id=?');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $order;
    }
    
    /**
     * Get all orders with pagination
     */
    public static function getAll($db, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT o.*, c.full_name AS registered_name, c.email AS customer_email FROM orders o LEFT JOIN customers c ON c.id = o.customer_id ORDER BY o.id DESC LIMIT $perPage OFFSET $offset";
        $result = $db->query($sql);
        
        $orders = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }
    
    /**
     * Get total count of orders
     */
    public static function getCount($db) {
        $result = $db->query('SELECT COUNT(*) AS cnt FROM orders');
        return ($result && $row = $result->fetch_assoc()) ? (int)$row['cnt'] : 0;
    }
    
    /**
     * Get revenue statistics
     */
    public static function getRevenueStats($db) {
        $sql = "
            SELECT 
                COUNT(*) AS total_orders,
                COALESCE(SUM(total_price), 0) AS total_revenue,
                COUNT(CASE WHEN customer_id IS NOT NULL THEN 1 END) AS orders_with_accounts,
                COALESCE(SUM(CASE WHEN customer_id IS NOT NULL THEN total_price ELSE 0 END), 0) AS revenue_from_accounts
            FROM orders
            WHERE cancelled = 0
        ";
        $result = $db->query($sql);
        return ($result && $row = $result->fetch_assoc()) ? $row : null;
    }
}

