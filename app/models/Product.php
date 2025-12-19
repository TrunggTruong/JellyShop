class Product {
    /**
     * Get product by ID
     */
    public static function getById($db, $id) {
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $product;
    }

    /**
     * Get all products with pagination
     */
    public static function getAll($db, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM products ORDER BY id DESC LIMIT $perPage OFFSET $offset";
        $result = $db->query($sql);
        
        $products = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }
        
        return $products;
    }

    /**
     * Get total count of products
     */
    public static function getCount($db) {
        $result = $db->query('SELECT COUNT(*) AS cnt FROM products');
        return ($result && $row = $result->fetch_assoc()) ? (int)$row['cnt'] : 0;
    }
}
