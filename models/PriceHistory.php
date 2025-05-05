<?php
/**
 * PriceHistory Model
 * Handles price change tracking and history
 */
class PriceHistory extends Model {
    protected $table = 'price_history';
    protected $fillable = [
        'product_id',
        'old_price',
        'new_price',
        'change_type',
        'reason',
        'user_id'
    ];

    /**
     * Log price change
     * @param int $productId Product ID
     * @param float $oldPrice Old price
     * @param float $newPrice New price
     * @param string $reason Reason for change
     * @param string $changeType Type of change (regular, promotion, etc.)
     * @param int $userId User making the change
     * @return bool Success status
     */
    public function logChange($productId, $oldPrice, $newPrice, $reason, $changeType = 'regular', $userId = null) {
        $data = [
            'product_id' => $productId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_type' => $changeType,
            'reason' => $reason,
            'user_id' => $userId
        ];

        try {
            $this->db->beginTransaction();

            // Create price history record
            $this->create($data);

            // Update product price
            $this->db->query("
                UPDATE products 
                SET 
                    selling_price = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ")
            ->bind(1, $newPrice)
            ->bind(2, $productId)
            ->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Price change failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get price history for product
     * @param int $productId Product ID
     * @param int $limit Number of records to return
     * @return array
     */
    public function getProductHistory($productId, $limit = null) {
        $sql = "
            SELECT 
                ph.*,
                u.name as user_name,
                p.name as product_name,
                p.sku
            FROM {$this->table} ph
            JOIN products p ON ph.product_id = p.id
            LEFT JOIN users u ON ph.user_id = u.id
            WHERE ph.product_id = ?
            ORDER BY ph.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT ?";
        }

        $query = $this->db->query($sql)->bind(1, $productId);
        if ($limit) {
            $query->bind(2, $limit);
        }

        return $query->resultSet();
    }

    /**
     * Get recent price changes
     * @param int $limit Number of records to return
     * @return array
     */
    public function getRecentChanges($limit = 10) {
        return $this->db->query("
            SELECT 
                ph.*,
                u.name as user_name,
                p.name as product_name,
                p.sku,
                c.name as category_name
            FROM {$this->table} ph
            JOIN products p ON ph.product_id = p.id
            LEFT JOIN users u ON ph.user_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY ph.created_at DESC
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();
    }

    /**
     * Get price change statistics
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array
     */
    public function getStats($startDate = null, $endDate = null) {
        $sql = "
            SELECT
                COUNT(*) as total_changes,
                COUNT(DISTINCT product_id) as products_affected,
                AVG(
                    ((new_price - old_price) / old_price) * 100
                ) as avg_change_percentage,
                SUM(CASE WHEN new_price > old_price THEN 1 ELSE 0 END) as increases,
                SUM(CASE WHEN new_price < old_price THEN 1 ELSE 0 END) as decreases,
                MAX(ABS(
                    ((new_price - old_price) / old_price) * 100
                )) as max_change_percentage
            FROM {$this->table}
            WHERE 1=1
        ";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $endDate;
        }

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->single();
    }

    /**
     * Get price trends by category
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array
     */
    public function getCategoryTrends($startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                c.name as category_name,
                COUNT(*) as total_changes,
                AVG(
                    ((ph.new_price - ph.old_price) / ph.old_price) * 100
                ) as avg_change_percentage,
                COUNT(DISTINCT ph.product_id) as products_affected
            FROM {$this->table} ph
            JOIN products p ON ph.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE 1=1
        ";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(ph.created_at) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(ph.created_at) <= ?";
            $params[] = $endDate;
        }

        $sql .= " GROUP BY c.id ORDER BY total_changes DESC";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get products with frequent price changes
     * @param int $threshold Number of changes to consider frequent
     * @param int $days Number of days to look back
     * @return array
     */
    public function getFrequentChanges($threshold = 3, $days = 30) {
        return $this->db->query("
            SELECT 
                p.id,
                p.name as product_name,
                p.sku,
                c.name as category_name,
                COUNT(*) as change_count,
                MIN(ph.old_price) as min_price,
                MAX(ph.new_price) as max_price,
                p.selling_price as current_price
            FROM {$this->table} ph
            JOIN products p ON ph.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE ph.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY p.id
            HAVING change_count >= ?
            ORDER BY change_count DESC
        ")
        ->bind(1, $days)
        ->bind(2, $threshold)
        ->resultSet();
    }

    /**
     * Validate price change
     * @param float $oldPrice Old price
     * @param float $newPrice New price
     * @param int $userId User ID making the change
     * @return array Validation result
     */
    public function validateChange($oldPrice, $newPrice, $userId) {
        $user = $this->db->query("
            SELECT role FROM users WHERE id = ?
        ")
        ->bind(1, $userId)
        ->single();

        $maxChangePercent = $user['role'] === 'admin' ? 50 : 20;
        $changePercent = abs((($newPrice - $oldPrice) / $oldPrice) * 100);

        return [
            'valid' => $changePercent <= $maxChangePercent,
            'message' => $changePercent > $maxChangePercent 
                ? "Price change of {$changePercent}% exceeds maximum allowed ({$maxChangePercent}%)"
                : null
        ];
    }
}
