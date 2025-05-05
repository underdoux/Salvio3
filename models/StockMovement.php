<?php
/**
 * StockMovement Model
 * Handles stock movement tracking and inventory management
 */
class StockMovement extends Model {
    protected $table = 'stock_movements';
    protected $fillable = [
        'product_id',
        'reference_id',
        'reference_type',
        'quantity',
        'movement_type',
        'notes'
    ];

    /**
     * Record stock movement
     * @param array $data Movement data
     * @return int|false Movement ID or false on failure
     */
    public function recordMovement($data) {
        try {
            $this->db->beginTransaction();

            // Create movement record
            $movementId = $this->create($data);
            if (!$movementId) {
                throw new Exception("Failed to create stock movement record");
            }

            // Update product stock
            $this->updateProductStock(
                $data['product_id'],
                $data['quantity'],
                $data['movement_type']
            );

            $this->db->commit();
            return $movementId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Stock Movement Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update product stock
     * @param int $productId Product ID
     * @param int $quantity Quantity
     * @param string $movementType Movement type (in/out)
     */
    private function updateProductStock($productId, $quantity, $movementType) {
        $operator = $movementType === 'in' ? '+' : '-';
        
        $this->db->query("
            UPDATE products 
            SET 
                stock = stock {$operator} ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ")
        ->bind(1, $quantity)
        ->bind(2, $productId)
        ->execute();
    }

    /**
     * Get stock movements by product
     * @param int $productId Product ID
     * @param array $filters Filter options
     * @return array Stock movements
     */
    public function getByProduct($productId, $filters = []) {
        $where = ['sm.product_id = ?'];
        $params = [$productId];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(sm.created_at) >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(sm.created_at) <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        if (!empty($filters['movement_type'])) {
            $where[] = 'sm.movement_type = ?';
            $params[] = $filters['movement_type'];
            $index++;
        }

        if (!empty($filters['reference_type'])) {
            $where[] = 'sm.reference_type = ?';
            $params[] = $filters['reference_type'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                sm.*,
                p.name as product_name,
                p.sku,
                CASE 
                    WHEN sm.reference_type = 'sale' THEN s.invoice_number
                    WHEN sm.reference_type = 'purchase' THEN po.reference_number
                    ELSE NULL 
                END as reference_number
            FROM {$this->table} sm
            JOIN products p ON sm.product_id = p.id
            LEFT JOIN sales s ON sm.reference_type = 'sale' AND sm.reference_id = s.id
            LEFT JOIN purchase_orders po ON sm.reference_type = 'purchase' AND sm.reference_id = po.id
            WHERE {$whereClause}
            ORDER BY sm.created_at DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get stock movement summary
     * @param array $filters Filter options
     * @return array Summary data
     */
    public function getSummary($filters = []) {
        $where = ['1=1'];
        $params = [];
        $index = 1;

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                reference_type,
                movement_type,
                COUNT(*) as movement_count,
                SUM(quantity) as total_quantity
            FROM {$this->table}
            WHERE {$whereClause}
            GROUP BY reference_type, movement_type
            ORDER BY reference_type, movement_type
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get low stock products
     * @param int $threshold Optional threshold override
     * @return array Low stock products
     */
    public function getLowStockProducts($threshold = null) {
        $sql = "
            SELECT 
                p.*,
                COALESCE(reserved.total, 0) as reserved_quantity
            FROM products p
            LEFT JOIN (
                SELECT 
                    product_id,
                    SUM(quantity) as total
                FROM stock_movements
                WHERE movement_type = 'out'
                AND reference_type = 'sale'
                AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                GROUP BY product_id
            ) as reserved ON p.id = reserved.product_id
            WHERE p.status = 'active'
            AND (
                p.stock <= COALESCE(?, p.min_stock)
                OR p.stock - COALESCE(reserved.total, 0) <= COALESCE(?, p.min_stock)
            )
            ORDER BY p.stock ASC
        ";

        return $this->db->query($sql)
            ->bind(1, $threshold)
            ->bind(2, $threshold)
            ->resultSet();
    }

    /**
     * Get stock value
     * @return array Stock value summary
     */
    public function getStockValue() {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock) as total_stock,
                SUM(stock * purchase_price) as total_cost,
                SUM(stock * selling_price) as total_value
            FROM products
            WHERE status = 'active'
        ")->single();
    }

    /**
     * Check if product has sufficient stock
     * @param int $productId Product ID
     * @param int $quantity Required quantity
     * @return bool True if sufficient stock
     */
    public function hasStock($productId, $quantity) {
        $result = $this->db->query("
            SELECT stock, stock_reserved 
            FROM products 
            WHERE id = ?
        ")
        ->bind(1, $productId)
        ->single();

        return $result && ($result['stock'] - $result['stock_reserved']) >= $quantity;
    }

    /**
     * Reserve stock for a product
     * @param int $productId Product ID
     * @param int $quantity Quantity to reserve
     * @return bool Success status
     */
    public function reserveStock($productId, $quantity) {
        return $this->db->query("
            UPDATE products 
            SET 
                stock_reserved = stock_reserved + ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            AND (stock - stock_reserved) >= ?
        ")
        ->bind(1, $quantity)
        ->bind(2, $productId)
        ->bind(3, $quantity)
        ->execute();
    }

    /**
     * Release reserved stock
     * @param int $productId Product ID
     * @param int $quantity Quantity to release
     * @return bool Success status
     */
    public function releaseReservedStock($productId, $quantity) {
        return $this->db->query("
            UPDATE products 
            SET 
                stock_reserved = GREATEST(0, stock_reserved - ?),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ")
        ->bind(1, $quantity)
        ->bind(2, $productId)
        ->execute();
    }
}
