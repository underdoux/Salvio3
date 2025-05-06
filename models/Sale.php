<?php
/**
 * Sale Model
 * Handles sales-related database operations
 */
class Sale extends Model {
    protected $table = 'sales';
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'total_amount',
        'discount_amount',
        'final_amount',
        'payment_type',
        'payment_status',
        'notes'
    ];

    /**
     * Get today's total sales
     * @return float
     */
    public function getTodaySales() {
        $result = $this->db->query("
            SELECT COALESCE(SUM(final_amount), 0) as total 
            FROM {$this->table} 
            WHERE DATE(created_at) = CURDATE()
        ")->single();
        
        return (float)$result['total'];
    }

    /**
     * Get today's order count
     * @return int
     */
    public function getTodayOrderCount() {
        $result = $this->db->query("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE DATE(created_at) = CURDATE()
        ")->single();
        
        return (int)$result['count'];
    }

    /**
     * Get current month's revenue
     * @return float
     */
    public function getMonthlyRevenue() {
        $result = $this->db->query("
            SELECT COALESCE(SUM(final_amount), 0) as total 
            FROM {$this->table} 
            WHERE YEAR(created_at) = YEAR(CURDATE()) 
            AND MONTH(created_at) = MONTH(CURDATE())
        ")->single();
        
        return (float)$result['total'];
    }

    /**
     * Get last month's revenue
     * @return float
     */
    public function getLastMonthRevenue() {
        $result = $this->db->query("
            SELECT COALESCE(SUM(final_amount), 0) as total 
            FROM {$this->table} 
            WHERE created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
            AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')
        ")->single();
        
        return (float)$result['total'];
    }

    /**
     * Get recent sales with customer details
     * @param int $limit Number of records to return
     * @return array
     */
    public function getRecentSales($limit = 5) {
        return $this->db->query("
            SELECT s.*, c.name as customer_name 
            FROM {$this->table} s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            ORDER BY s.created_at DESC 
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();
    }

    /**
     * Search sales
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
            $conditions[] = "s.{$field} LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $sql = "
            SELECT s.*, 
                   c.name as customer_name,
                   u.name as user_name,
                   COUNT(si.id) as total_items
            FROM {$this->table} s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            WHERE (" . implode(' OR ', $conditions) . ")
            GROUP BY s.id
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
     * Get sales chart data
     * @return array
     */
    public function getSalesChartData() {
        return $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                SUM(final_amount) as revenue
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->resultSet();
    }

    /**
     * Get payment type statistics
     * @return array
     */
    public function getPaymentStats() {
        return $this->db->query("
            SELECT 
                payment_type,
                COUNT(*) as count,
                SUM(final_amount) as total
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY payment_type
        ")->resultSet();
    }

    /**
     * Generate unique invoice number
     * @return string
     */
    public function generateInvoiceNumber() {
        $prefix = date('Ymd');
        $result = $this->db->query("
            SELECT MAX(CAST(SUBSTRING(invoice_number, 9) AS UNSIGNED)) as last_number
            FROM {$this->table}
            WHERE invoice_number LIKE ?
        ")
        ->bind(1, $prefix . '%')
        ->single();

        $lastNumber = (int)($result['last_number'] ?? 0);
        $nextNumber = $lastNumber + 1;

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create sale with items
     * @param array $saleData Sale data
     * @param array $items Sale items
     * @return int|bool Sale ID on success, false on failure
     */
    public function createWithItems($saleData, $items) {
        try {
            $this->db->beginTransaction();

            // Create sale
            $saleId = $this->create($saleData);
            if (!$saleId) {
                throw new Exception("Failed to create sale");
            }

            // Create sale items
            foreach ($items as $item) {
                $item['sale_id'] = $saleId;
                $this->db->query("
                    INSERT INTO sale_items (
                        sale_id, product_id, quantity, 
                        unit_price, discount_amount, total_amount
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ")
                ->bind(1, $item['sale_id'])
                ->bind(2, $item['product_id'])
                ->bind(3, $item['quantity'])
                ->bind(4, $item['unit_price'])
                ->bind(5, $item['discount_amount'])
                ->bind(6, $item['total_amount'])
                ->execute();

                // Update product stock
                $this->db->query("
                    UPDATE products 
                    SET stock = stock - ? 
                    WHERE id = ?
                ")
                ->bind(1, $item['quantity'])
                ->bind(2, $item['product_id'])
                ->execute();
            }

            $this->db->commit();
            return $saleId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Sale creation failed: " . $e->getMessage());
            return false;
        }
    }
}
