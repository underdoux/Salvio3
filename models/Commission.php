<?php
/**
 * Commission Model
 * Handles commission calculations and tracking
 */
class Commission extends Model {
    protected $table = 'commissions';
    protected $fillable = [
        'sale_id',
        'user_id',
        'amount',
        'status',
        'paid_at'
    ];

    /**
     * Get commission rates
     * @param int|null $userId Specific user ID or null for global rates
     * @return array
     */
    public function getRates($userId = null) {
        $sql = "
            SELECT 
                cr.*,
                c.name as category_name,
                p.name as product_name,
                u.name as user_name
            FROM commission_rates cr
            LEFT JOIN categories c ON cr.category_id = c.id
            LEFT JOIN products p ON cr.product_id = p.id
            LEFT JOIN users u ON cr.user_id = u.id
            WHERE (cr.user_id = ? OR cr.user_id IS NULL)
            ORDER BY 
                CASE 
                    WHEN cr.product_id IS NOT NULL THEN 1
                    WHEN cr.category_id IS NOT NULL THEN 2
                    ELSE 3
                END
        ";

        return $this->db->query($sql)
            ->bind(1, $userId)
            ->resultSet();
    }

    /**
     * Calculate commission for a sale
     * @param array $sale Sale data
     * @return float Commission amount
     */
    public function calculateCommission($sale) {
        $rates = $this->getRates($sale['user_id']);
        $commission = 0;

        // Get sale items with product details
        $items = $this->db->query("
            SELECT 
                si.*,
                p.category_id,
                p.id as product_id
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ")
        ->bind(1, $sale['id'])
        ->resultSet();

        foreach ($items as $item) {
            $rate = $this->getApplicableRate($rates, $item['product_id'], $item['category_id']);
            $baseAmount = $item['unit_price'] * $item['quantity'];
            $commission += $baseAmount * ($rate / 100);
        }

        return $commission;
    }

    /**
     * Get applicable commission rate
     * @param array $rates Available rates
     * @param int $productId Product ID
     * @param int $categoryId Category ID
     * @return float Commission rate percentage
     */
    private function getApplicableRate($rates, $productId, $categoryId) {
        // Check product-specific rate
        foreach ($rates as $rate) {
            if ($rate['product_id'] === $productId) {
                return $rate['rate'];
            }
        }

        // Check category rate
        foreach ($rates as $rate) {
            if ($rate['category_id'] === $categoryId) {
                return $rate['rate'];
            }
        }

        // Use global rate
        foreach ($rates as $rate) {
            if ($rate['product_id'] === null && $rate['category_id'] === null) {
                return $rate['rate'];
            }
        }

        return 0; // Default if no rate found
    }

    /**
     * Get pending commissions for user
     * @param int $userId User ID
     * @return array
     */
    public function getPendingCommissions($userId) {
        return $this->db->query("
            SELECT 
                c.*,
                s.invoice_number,
                s.final_amount as sale_amount,
                cu.name as customer_name
            FROM {$this->table} c
            JOIN sales s ON c.sale_id = s.id
            LEFT JOIN customers cu ON s.customer_id = cu.id
            WHERE c.user_id = ?
            AND c.status = 'pending'
            ORDER BY c.created_at DESC
        ")
        ->bind(1, $userId)
        ->resultSet();
    }

    /**
     * Get commission history
     * @param int $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array
     */
    public function getHistory($userId, $startDate = null, $endDate = null) {
        $sql = "
            SELECT 
                c.*,
                s.invoice_number,
                s.final_amount as sale_amount,
                cu.name as customer_name
            FROM {$this->table} c
            JOIN sales s ON c.sale_id = s.id
            LEFT JOIN customers cu ON s.customer_id = cu.id
            WHERE c.user_id = ?
        ";

        $params = [$userId];

        if ($startDate) {
            $sql .= " AND DATE(c.created_at) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(c.created_at) <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY c.created_at DESC";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get commission statistics
     * @param int $userId User ID
     * @return array
     */
    public function getStats($userId) {
        return $this->db->query("
            SELECT
                COUNT(*) as total_commissions,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                MAX(created_at) as last_commission,
                MAX(paid_at) as last_payment
            FROM {$this->table}
            WHERE user_id = ?
        ")
        ->bind(1, $userId)
        ->single();
    }

    /**
     * Pay pending commissions
     * @param int $userId User ID
     * @return bool Success status
     */
    public function payPendingCommissions($userId) {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                UPDATE {$this->table}
                SET 
                    status = 'paid',
                    paid_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
                AND status = 'pending'
            ")
            ->bind(1, $userId)
            ->execute();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Commission payment failed: " . $e->getMessage());
            return false;
        }
    }
}
