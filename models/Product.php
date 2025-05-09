<?php
/**
 * Product Model
 * Handles product-related database operations
 */
class Product extends Model {
    protected $table = 'products';
    protected $fillable = [
        'name',
        'description',
        'sku',
        'barcode',
        'category_id',
        'supplier_id',
        'bpom_id',
        'purchase_price',
        'selling_price',
        'stock',
        'min_stock',
        'status'
    ];

    /**
     * Get low stock products
     * @param int $limit Number of records to return
     * @return array
     */
    public function getLowStockProducts($limit = 5) {
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE stock <= min_stock 
            AND status = 'active'
            ORDER BY stock ASC
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();
    }

    /**
     * Get top selling products
     * @param int $limit Number of records to return
     * @param string $period Time period (daily, weekly, monthly)
     * @return array
     */
    public function getTopSellingProducts($limit = 5, $period = 'monthly') {
        $dateCondition = match($period) {
            'daily' => 'DATE(s.created_at) = CURDATE()',
            'weekly' => 'YEARWEEK(s.created_at) = YEARWEEK(CURDATE())',
            'monthly' => 'YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())',
            default => 'YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())'
        };

        return $this->db->query("
            SELECT 
                p.*,
                SUM(si.quantity) as total_quantity,
                SUM(si.total_amount) as total_sales
            FROM {$this->table} p
            JOIN sale_items si ON p.id = si.product_id
            JOIN sales s ON si.sale_id = s.id
            WHERE {$dateCondition}
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();
    }

    /**
     * Get products by category
     * @param int $categoryId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByCategory($categoryId, $limit = 10, $offset = 0) {
        return $this->db->query("
            SELECT p.*, c.name as category_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ?
            AND p.status = 'active'
            ORDER BY p.name ASC
            LIMIT ? OFFSET ?
        ")
        ->bind(1, $categoryId)
        ->bind(2, $limit)
        ->bind(3, $offset)
        ->resultSet();
    }

    /**
     * Search products
     * Override parent search method
     * @param array $fields Fields to search in
     * @param string $keyword Search keyword
     * @param string|null $orderBy Order by field
     * @param string $order Order direction (ASC/DESC)
     * @return array
     */
    public function search($fields, $keyword, $orderBy = null, $order = 'ASC', $limit = null, $offset = null) {
        if (empty($fields) || $keyword === '') {
            // Return all active products if no search keyword or fields
            $sql = "
                SELECT p.*, c.name as category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'
            ";
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy} {$order}";
            }
            if ($limit !== null) {
                $sql .= " LIMIT " . (int)$limit;
            }
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
            return $this->db->query($sql)->resultSet();
        }

        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $sql = "
            SELECT p.*, c.name as category_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE (" . implode(' OR ', $conditions) . ")
            AND p.status = 'active'
        ";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }
        
        $query = $this->db->query($sql);
        
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        
        return $query->resultSet();
    }

    /**
     * Update stock
     * @param int $id Product ID
     * @param int $quantity Quantity to add (positive) or subtract (negative)
     * @return bool
     */
    public function updateStock($id, $quantity) {
        return $this->db->query("
            UPDATE {$this->table}
            SET stock = stock + ?
            WHERE id = ?
        ")
        ->bind(1, $quantity)
        ->bind(2, $id)
        ->execute();
    }

    /**
     * Check if product has enough stock
     * @param int $id Product ID
     * @param int $quantity Quantity to check
     * @return bool
     */
    public function hasStock($id, $quantity) {
        $result = $this->db->query("
            SELECT stock >= ? as has_stock
            FROM {$this->table}
            WHERE id = ?
            AND status = 'active'
        ")
        ->bind(1, $quantity)
        ->bind(2, $id)
        ->single();

        return (bool)($result['has_stock'] ?? false);
    }

    /**
     * Get product with BPOM data
     * @param int $id Product ID
     * @return array|null
     */
    public function getWithBpomData($id) {
        return $this->db->query("
            SELECT p.*, b.*
            FROM {$this->table} p
            LEFT JOIN bpom_references b ON p.bpom_id = b.id
            WHERE p.id = ?
            AND p.status = 'active'
        ")
        ->bind(1, $id)
        ->single();
    }

    /**
     * Get products needing restock
     * @param int $limit
     * @return array
     */
    public function getNeedingRestock($limit = 10) {
        return $this->db->query("
            SELECT *,
                   CASE
                       WHEN stock = 0 THEN 'out_of_stock'
                       WHEN stock <= min_stock THEN 'low_stock'
                       ELSE 'normal'
                   END as stock_status
            FROM {$this->table}
            WHERE status = 'active'
            AND stock <= min_stock
            ORDER BY stock ASC
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();
    }

    /**
     * Get product statistics
     * @return array
     */
    public function getStats() {
        return $this->db->query("
            SELECT
                COUNT(*) as total_products,
                COUNT(CASE WHEN stock <= min_stock THEN 1 END) as low_stock_count,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_count,
                AVG(selling_price) as avg_price,
                SUM(stock * purchase_price) as total_inventory_value
            FROM {$this->table}
            WHERE status = 'active'
        ")->single();
    }
}
