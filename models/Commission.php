<?php
/**
 * Commission Model
 * Handles commission rates, calculations, and payments
 */
class Commission extends Model {
    protected $table = 'commission_calculations';
    protected $fillable = [
        'sale_id',
        'user_id',
        'commission_rate_id',
        'sale_amount',
        'commission_amount',
        'status',
        'payment_date',
        'notes'
    ];

    /**
     * Calculate commission for sale
     * @param array $sale Sale data
     * @return array|false Commission data or false on failure
     */
    public function calculateForSale($sale) {
        try {
            // Get user's commission eligibility
            $user = $this->db->query("
                SELECT commission_eligible, default_commission_rate
                FROM users
                WHERE id = ?
            ")
            ->bind(1, $sale['user_id'])
            ->single();

            if (!$user || !$user['commission_eligible']) {
                return false;
            }

            // Get applicable commission rates
            $rates = $this->getApplicableRates($sale);
            if (empty($rates)) {
                // Use user's default rate if no specific rates found
                if ($user['default_commission_rate']) {
                    $rates[] = [
                        'id' => null,
                        'rate' => $user['default_commission_rate'],
                        'min_sale_amount' => 0
                    ];
                } else {
                    return false;
                }
            }

            $commissions = [];
            foreach ($sale['items'] as $item) {
                $rate = $this->getBestRate($rates, $item['total_amount']);
                if ($rate) {
                    $commissionAmount = $item['total_amount'] * ($rate['rate'] / 100);
                    $commissions[] = [
                        'sale_id' => $sale['id'],
                        'user_id' => $sale['user_id'],
                        'commission_rate_id' => $rate['id'],
                        'sale_amount' => $item['total_amount'],
                        'commission_amount' => $commissionAmount,
                        'status' => 'pending'
                    ];
                }
            }

            return $commissions;

        } catch (Exception $e) {
            error_log("Commission Calculation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get applicable commission rates
     * @param array $sale Sale data
     * @return array Commission rates
     */
    private function getApplicableRates($sale) {
        $rates = [];

        // Get product-specific rates
        foreach ($sale['items'] as $item) {
            $productRate = $this->db->query("
                SELECT id, rate, min_sale_amount
                FROM commission_rates
                WHERE type = 'product'
                AND reference_id = ?
                AND status = 'active'
            ")
            ->bind(1, $item['product_id'])
            ->single();

            if ($productRate) {
                $rates[] = $productRate;
            }
        }

        // Get category rates if no product rates
        if (empty($rates)) {
            $categoryRates = $this->db->query("
                SELECT DISTINCT cr.id, cr.rate, cr.min_sale_amount
                FROM commission_rates cr
                JOIN products p ON cr.reference_id = p.category_id
                WHERE cr.type = 'category'
                AND cr.status = 'active'
                AND p.id IN (" . implode(',', array_column($sale['items'], 'product_id')) . ")
            ")->resultSet();

            $rates = array_merge($rates, $categoryRates);
        }

        // Get global rate if no specific rates
        if (empty($rates)) {
            $globalRate = $this->db->query("
                SELECT id, rate, min_sale_amount
                FROM commission_rates
                WHERE type = 'global'
                AND status = 'active'
                LIMIT 1
            ")->single();

            if ($globalRate) {
                $rates[] = $globalRate;
            }
        }

        return $rates;
    }

    /**
     * Get best applicable rate
     * @param array $rates Available rates
     * @param float $amount Sale amount
     * @return array|null Best rate or null
     */
    private function getBestRate($rates, $amount) {
        $bestRate = null;
        $highestCommission = 0;

        foreach ($rates as $rate) {
            if ($amount >= ($rate['min_sale_amount'] ?? 0)) {
                $commission = $amount * ($rate['rate'] / 100);
                if ($commission > $highestCommission) {
                    $highestCommission = $commission;
                    $bestRate = $rate;
                }
            }
        }

        return $bestRate;
    }

    /**
     * Create commission rate
     * @param array $data Rate data
     * @return int|false Rate ID or false on failure
     */
    public function createRate($data) {
        try {
            $this->db->beginTransaction();

            // Validate reference ID if not global
            if ($data['type'] !== 'global') {
                if ($data['type'] === 'category') {
                    $exists = $this->db->query("
                        SELECT id FROM categories WHERE id = ?
                    ")
                    ->bind(1, $data['reference_id'])
                    ->single();
                } else {
                    $exists = $this->db->query("
                        SELECT id FROM products WHERE id = ?
                    ")
                    ->bind(1, $data['reference_id'])
                    ->single();
                }

                if (!$exists) {
                    throw new Exception("Invalid reference ID");
                }
            }

            // Create rate
            $rateId = $this->db->query("
                INSERT INTO commission_rates (
                    type,
                    reference_id,
                    rate,
                    min_sale_amount,
                    status
                ) VALUES (?, ?, ?, ?, 'active')
            ")
            ->bind(1, $data['type'])
            ->bind(2, $data['reference_id'])
            ->bind(3, $data['rate'])
            ->bind(4, $data['min_sale_amount'])
            ->execute();

            $this->db->commit();
            return $rateId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Commission Rate Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process commission payment
     * @param array $commissionIds Commission IDs to process
     * @param string $paymentDate Payment date
     * @param string $notes Payment notes
     * @return bool Success status
     */
    public function processPayment($commissionIds, $paymentDate, $notes = '') {
        try {
            $this->db->beginTransaction();

            $this->db->query("
                UPDATE {$this->table}
                SET 
                    status = 'paid',
                    payment_date = ?,
                    notes = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id IN (" . implode(',', $commissionIds) . ")
            ")
            ->bind(1, $paymentDate)
            ->bind(2, $notes)
            ->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Commission Payment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get commission summary by user
     * @param int $userId User ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Commission summary
     */
    public function getUserSummary($userId, $startDate = null, $endDate = null) {
        $where = ['cc.user_id = ?'];
        $params = [$userId];
        $index = 2;

        if ($startDate) {
            $where[] = 'DATE(cc.created_at) >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'DATE(cc.created_at) <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                cc.status,
                COUNT(*) as count,
                SUM(cc.sale_amount) as total_sales,
                SUM(cc.commission_amount) as total_commission,
                MIN(cc.created_at) as first_commission,
                MAX(cc.created_at) as last_commission
            FROM {$this->table} cc
            WHERE {$whereClause}
            GROUP BY cc.status
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }
}
