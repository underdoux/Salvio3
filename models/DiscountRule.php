<?php
/**
 * DiscountRule Model
 * Handles discount rules and validations
 */
class DiscountRule extends Model {
    protected $table = 'discount_rules';
    protected $fillable = [
        'name',
        'type',
        'value',
        'min_purchase',
        'max_discount',
        'start_date',
        'end_date',
        'allowed_roles',
        'status'
    ];

    /**
     * Get active discount rules
     * @param string $userRole User role
     * @return array Active discount rules
     */
    public function getActive($userRole) {
        return $this->db->query("
            SELECT *
            FROM {$this->table}
            WHERE status = 'active'
            AND (start_date IS NULL OR start_date <= CURRENT_DATE)
            AND (end_date IS NULL OR end_date >= CURRENT_DATE)
            AND JSON_CONTAINS(allowed_roles, ?)
            ORDER BY value DESC
        ")
        ->bind(1, json_encode($userRole))
        ->resultSet();
    }

    /**
     * Calculate discount
     * @param float $amount Purchase amount
     * @param array $rule Discount rule
     * @return float Calculated discount amount
     */
    public function calculateDiscount($amount, $rule) {
        // Check minimum purchase requirement
        if ($rule['min_purchase'] && $amount < $rule['min_purchase']) {
            return 0;
        }

        // Calculate discount
        $discount = $rule['type'] === 'percentage' 
            ? $amount * ($rule['value'] / 100)
            : $rule['value'];

        // Apply maximum discount limit
        if ($rule['max_discount'] && $discount > $rule['max_discount']) {
            $discount = $rule['max_discount'];
        }

        return $discount;
    }

    /**
     * Validate discount application
     * @param array $data Discount data
     * @param string $userRole User role
     * @return array Validation result
     */
    public function validateDiscount($data, $userRole) {
        $result = [
            'valid' => true,
            'message' => 'Discount is valid',
            'calculated_amount' => 0
        ];

        // Get applicable rule
        $rule = $this->getById($data['discount_rule_id']);
        if (!$rule) {
            $result['valid'] = false;
            $result['message'] = 'Invalid discount rule';
            return $result;
        }

        // Check rule status
        if ($rule['status'] !== 'active') {
            $result['valid'] = false;
            $result['message'] = 'Discount rule is not active';
            return $result;
        }

        // Check date validity
        $today = new DateTime();
        if ($rule['start_date'] && new DateTime($rule['start_date']) > $today) {
            $result['valid'] = false;
            $result['message'] = 'Discount rule is not yet active';
            return $result;
        }
        if ($rule['end_date'] && new DateTime($rule['end_date']) < $today) {
            $result['valid'] = false;
            $result['message'] = 'Discount rule has expired';
            return $result;
        }

        // Check role permission
        $allowedRoles = json_decode($rule['allowed_roles'], true);
        if (!in_array($userRole, $allowedRoles)) {
            $result['valid'] = false;
            $result['message'] = 'User role not allowed to apply this discount';
            return $result;
        }

        // Calculate discount
        $discount = $this->calculateDiscount($data['amount'], $rule);
        if ($discount <= 0) {
            $result['valid'] = false;
            $result['message'] = "Minimum purchase amount is " . 
                formatCurrency($rule['min_purchase']);
            return $result;
        }

        $result['calculated_amount'] = $discount;
        return $result;
    }

    /**
     * Create discount rule
     * @param array $data Rule data
     * @return int|false Rule ID or false on failure
     */
    public function createRule($data) {
        try {
            // Validate allowed roles
            $allowedRoles = json_decode($data['allowed_roles'], true);
            if (!is_array($allowedRoles) || empty($allowedRoles)) {
                throw new Exception("Invalid allowed roles");
            }

            // Format dates
            $data['start_date'] = !empty($data['start_date']) 
                ? date('Y-m-d', strtotime($data['start_date'])) 
                : null;
            $data['end_date'] = !empty($data['end_date']) 
                ? date('Y-m-d', strtotime($data['end_date'])) 
                : null;

            return $this->create($data);

        } catch (Exception $e) {
            error_log("Discount Rule Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get discount summary
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Discount summary
     */
    public function getDiscountSummary($startDate = null, $endDate = null) {
        $where = ['si.discount_rule_id IS NOT NULL'];
        $params = [];
        $index = 1;

        if ($startDate) {
            $where[] = 'DATE(s.created_at) >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'DATE(s.created_at) <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                dr.name as rule_name,
                dr.type as rule_type,
                COUNT(DISTINCT s.id) as usage_count,
                COUNT(DISTINCT s.customer_id) as customer_count,
                SUM(si.discount_amount) as total_discount,
                AVG(si.discount_amount) as avg_discount,
                MIN(s.created_at) as first_use,
                MAX(s.created_at) as last_use
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN {$this->table} dr ON si.discount_rule_id = dr.id
            WHERE {$whereClause}
            GROUP BY dr.id, dr.name, dr.type
            ORDER BY usage_count DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get best discount for amount
     * @param float $amount Purchase amount
     * @param string $userRole User role
     * @return array|null Best discount rule or null
     */
    public function getBestDiscount($amount, $userRole) {
        $rules = $this->getActive($userRole);
        $bestDiscount = null;
        $maxDiscountAmount = 0;

        foreach ($rules as $rule) {
            $discountAmount = $this->calculateDiscount($amount, $rule);
            if ($discountAmount > $maxDiscountAmount) {
                $maxDiscountAmount = $discountAmount;
                $bestDiscount = $rule;
            }
        }

        return $bestDiscount;
    }
}
