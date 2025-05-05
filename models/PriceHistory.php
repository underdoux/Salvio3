<?php
/**
 * PriceHistory Model
 * Handles product price history and changes
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
     * Record price change
     * @param array $data Price change data
     * @return int|false Price history ID or false on failure
     */
    public function recordChange($data) {
        try {
            $this->db->beginTransaction();

            // Create price history record
            $historyId = $this->create($data);
            if (!$historyId) {
                throw new Exception("Failed to create price history record");
            }

            // Update product price
            $this->db->query("
                UPDATE products 
                SET 
                    selling_price = ?,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ")
            ->bind(1, $data['new_price'])
            ->bind(2, $data['product_id'])
            ->execute();

            $this->db->commit();
            return $historyId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Price Change Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate price change
     * @param float $newPrice New price
     * @param array $product Product data
     * @param string $userRole User role
     * @return array Validation result
     */
    public function validatePriceChange($newPrice, $product, $userRole) {
        $result = [
            'valid' => true,
            'message' => 'Price change is valid'
        ];

        // Check minimum price
        if ($product['min_price'] && $newPrice < $product['min_price']) {
            $result['valid'] = false;
            $result['message'] = "Price cannot be lower than " . 
                formatCurrency($product['min_price']);
            return $result;
        }

        // Check maximum discount
        if ($product['max_discount_rate']) {
            $maxDiscount = $product['selling_price'] * ($product['max_discount_rate'] / 100);
            $minAllowedPrice = $product['selling_price'] - $maxDiscount;

            if ($newPrice < $minAllowedPrice) {
                $result['valid'] = false;
                $result['message'] = "Maximum allowed discount is {$product['max_discount_rate']}%";
                return $result;
            }
        }

        // Additional role-based validation
        if ($userRole === 'sales') {
            // Sales can only apply predefined discounts
            $discountRules = $this->getDiscountRules($userRole);
            $validPrices = array_map(function($rule) use ($product) {
                if ($rule['type'] === 'percentage') {
                    $discount = $product['selling_price'] * ($rule['value'] / 100);
                    if ($rule['max_discount'] && $discount > $rule['max_discount']) {
                        $discount = $rule['max_discount'];
                    }
                    return $product['selling_price'] - $discount;
                } else {
                    return $product['selling_price'] - $rule['value'];
                }
            }, $discountRules);

            if (!in_array($newPrice, $validPrices)) {
                $result['valid'] = false;
                $result['message'] = "Invalid discount amount for sales role";
                return $result;
            }
        }

        return $result;
    }

    /**
     * Get price history by product
     * @param int $productId Product ID
     * @param array $filters Filter options
     * @return array Price history
     */
    public function getByProduct($productId, $filters = []) {
        $where = ['ph.product_id = ?'];
        $params = [$productId];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(ph.created_at) >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(ph.created_at) <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        if (!empty($filters['change_type'])) {
            $where[] = 'ph.change_type = ?';
            $params[] = $filters['change_type'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                ph.*,
                u.name as user_name,
                p.name as product_name,
                p.sku
            FROM {$this->table} ph
            JOIN users u ON ph.user_id = u.id
            JOIN products p ON ph.product_id = p.id
            WHERE {$whereClause}
            ORDER BY ph.created_at DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get price changes summary
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Summary data
     */
    public function getSummary($startDate = null, $endDate = null) {
        $where = ['1=1'];
        $params = [];
        $index = 1;

        if ($startDate) {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                change_type,
                COUNT(*) as change_count,
                AVG((new_price - old_price) / old_price * 100) as avg_change_percentage,
                SUM(CASE WHEN new_price > old_price THEN 1 ELSE 0 END) as increases,
                SUM(CASE WHEN new_price < old_price THEN 1 ELSE 0 END) as decreases
            FROM {$this->table}
            WHERE {$whereClause}
            GROUP BY change_type
            ORDER BY change_count DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get discount rules for role
     * @param string $role User role
     * @return array Active discount rules
     */
    private function getDiscountRules($role) {
        return $this->db->query("
            SELECT *
            FROM discount_rules
            WHERE status = 'active'
            AND (start_date IS NULL OR start_date <= CURRENT_DATE)
            AND (end_date IS NULL OR end_date >= CURRENT_DATE)
            AND JSON_CONTAINS(allowed_roles, ?)
            ORDER BY value DESC
        ")
        ->bind(1, json_encode($role))
        ->resultSet();
    }
}
