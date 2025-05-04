<?php
/**
 * Customer Model
 * Handles customer-related database operations
 */
class Customer extends Model {
    protected $table = 'customers';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'status'
    ];

    /**
     * Get new customers count for current month
     * @return int
     */
    public function getNewCustomersCount() {
        $result = $this->db->query("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE YEAR(created_at) = YEAR(CURDATE()) 
            AND MONTH(created_at) = MONTH(CURDATE())
            AND status = 'active'
        ")->single();
        
        return (int)$result['count'];
    }

    /**
     * Get customer with sales history
     * @param int $id Customer ID
     * @return array|null
     */
    public function getWithSalesHistory($id) {
        $customer = $this->find($id);
        if (!$customer) {
            return null;
        }

        // Get sales history
        $sales = $this->db->query("
            SELECT s.*, 
                   COUNT(si.id) as total_items,
                   u.name as sales_person
            FROM sales s
            LEFT JOIN sale_items si ON s.id = si.sale_id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.customer_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ")
        ->bind(1, $id)
        ->resultSet();

        $customer['sales_history'] = $sales;
        return $customer;
    }

    /**
     * Get top customers
     * @param int $limit Number of records to return
     * @param string $period Time period (daily, weekly, monthly, yearly)
     * @return array
     */
    public function getTopCustomers($limit = 5, $period = 'monthly') {
        $dateCondition = match($period) {
            'daily' => 'DATE(s.created_at) = CURDATE()',
            'weekly' => 'YEARWEEK(s.created_at) = YEARWEEK(CURDATE())',
            'monthly' => 'YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())',
            'yearly' => 'YEAR(s.created_at) = YEAR(CURDATE())',
            default => 'YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())'
        };

        return $this->db->query("
            SELECT 
                c.*,
                COUNT(s.id) as total_orders,
                SUM(s.final_amount) as total_spent
            FROM {$this->table} c
            JOIN sales s ON c.id = s.customer_id
            WHERE {$dateCondition}
            AND c.status = 'active'
            GROUP BY c.id
            ORDER BY total_spent DESC
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();
    }

    /**
     * Search customers
     * Override parent search method
     * @param array $fields Fields to search in
     * @param string $keyword Search keyword
     * @param string|null $orderBy Order by field
     * @param string $order Order direction (ASC/DESC)
     * @return array
     */
    public function search($fields, $keyword, $orderBy = null, $order = 'ASC') {
        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $sql = "
            SELECT c.*,
                   COUNT(s.id) as total_orders,
                   COALESCE(SUM(s.final_amount), 0) as total_spent
            FROM {$this->table} c
            LEFT JOIN sales s ON c.id = s.customer_id
            WHERE (" . implode(' OR ', $conditions) . ")
            AND c.status = 'active'
            GROUP BY c.id
        ";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        $query = $this->db->query($sql);
        
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        
        return $query->resultSet();
    }

    /**
     * Get customer statistics
     * @return array
     */
    public function getStats() {
        return $this->db->query("
            SELECT
                COUNT(*) as total_customers,
                COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) 
                          AND YEAR(created_at) = YEAR(CURDATE()) 
                     THEN 1 END) as new_this_month,
                (SELECT COUNT(DISTINCT customer_id) 
                 FROM sales 
                 WHERE DATE(created_at) = CURDATE()) as active_today,
                (SELECT AVG(final_amount) 
                 FROM sales 
                 WHERE customer_id IS NOT NULL) as avg_order_value
            FROM {$this->table}
            WHERE status = 'active'
        ")->single();
    }

    /**
     * Get customer purchase frequency
     * @param int $id Customer ID
     * @param int $months Number of months to analyze
     * @return array
     */
    public function getPurchaseFrequency($id, $months = 6) {
        return $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(final_amount) as total_amount,
                AVG(final_amount) as avg_amount
            FROM sales
            WHERE customer_id = ?
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ")
        ->bind(1, $id)
        ->bind(2, $months)
        ->resultSet();
    }

    /**
     * Get customers without recent purchases
     * @param int $days Days since last purchase
     * @param int $limit
     * @return array
     */
    public function getInactiveCustomers($days = 30, $limit = 10) {
        return $this->db->query("
            SELECT c.*,
                   MAX(s.created_at) as last_purchase,
                   COUNT(s.id) as total_orders,
                   SUM(s.final_amount) as total_spent
            FROM {$this->table} c
            LEFT JOIN sales s ON c.id = s.customer_id
            WHERE c.status = 'active'
            GROUP BY c.id
            HAVING last_purchase < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                   OR last_purchase IS NULL
            ORDER BY last_purchase DESC
            LIMIT ?
        ")
        ->bind(1, $days)
        ->bind(2, $limit)
        ->resultSet();
    }
}
