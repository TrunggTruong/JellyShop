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
