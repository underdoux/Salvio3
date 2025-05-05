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
     * Generate unique invoice number
     * @return string Invoice number
     */
    public function generateInvoiceNumber() {
        $prefix = date('Ymd');
        $lastInvoice = $this->db->query("
            SELECT invoice_number 
            FROM {$this->table} 
            WHERE invoice_number LIKE ?
            ORDER BY id DESC 
            LIMIT 1
        ")
        ->bind(1, $prefix . '%')
        ->single();

        if ($lastInvoice) {
            $sequence = intval(substr($lastInvoice['invoice_number'], -4)) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create sale with items
     * @param array $saleData Sale data
     * @param array $items Sale items
     * @return int|false Sale ID or false on failure
     */
    public function createWithItems($saleData, $items) {
        try {
            $this->db->beginTransaction();

            // Create sale
            $saleId = $this->create($saleData);
            if (!$saleId) {
                throw new Exception("Failed to create sale");
            }

            // Add items
            foreach ($items as $item) {
                $this->addSaleItem($saleId, $item);
            }

            // Create installments if payment type is installment
            if ($saleData['payment_type'] === 'installment') {
                $this->createInstallments($saleId, $saleData['final_amount']);
            }

            // Create initial payment if status is paid or partial
            if (in_array($saleData['payment_status'], ['paid', 'partial'])) {
                $this->createInitialPayment($saleId, $saleData);
            }

            $this->db->commit();
            return $saleId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Sale Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add sale item
     * @param int $saleId Sale ID
     * @param array $item Item data
     * @return bool Success status
     */
    private function addSaleItem($saleId, $item) {
        // Insert sale item
        $this->db->query("
            INSERT INTO sale_items (
                sale_id, 
                product_id, 
                quantity, 
                unit_price, 
                discount_amount, 
                total_amount
            ) VALUES (?, ?, ?, ?, ?, ?)
        ")
        ->bind(1, $saleId)
        ->bind(2, $item['product_id'])
        ->bind(3, $item['quantity'])
        ->bind(4, $item['unit_price'])
        ->bind(5, $item['discount_amount'])
        ->bind(6, $item['total_amount'])
        ->execute();

        // Update stock
        $this->updateStock($item['product_id'], $item['quantity']);

        return true;
    }

    /**
     * Update product stock
     * @param int $productId Product ID
     * @param int $quantity Quantity sold
     */
    private function updateStock($productId, $quantity) {
        // Update product stock
        $this->db->query("
            UPDATE products 
            SET stock = stock - ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ")
        ->bind(1, $quantity)
        ->bind(2, $productId)
        ->execute();

        // Record stock movement
        $this->db->query("
            INSERT INTO stock_movements (
                product_id, 
                reference_id, 
                reference_type, 
                quantity, 
                movement_type, 
                notes
            ) VALUES (?, ?, 'sale', ?, 'out', 'Sale deduction')
        ")
        ->bind(1, $productId)
        ->bind(2, $this->db->lastInsertId())
        ->bind(3, $quantity)
        ->execute();
    }

    /**
     * Create installments for sale
     * @param int $saleId Sale ID
     * @param float $totalAmount Total amount
     * @param int $terms Number of installments
     */
    private function createInstallments($saleId, $totalAmount, $terms = 3) {
        $installmentAmount = round($totalAmount / $terms, 2);
        $remainder = $totalAmount - ($installmentAmount * $terms);
        
        for ($i = 0; $i < $terms; $i++) {
            $amount = $installmentAmount;
            if ($i === $terms - 1) {
                $amount += $remainder; // Add remainder to last installment
            }

            $dueDate = date('Y-m-d', strtotime("+".($i+1)." month"));
            
            $this->db->query("
                INSERT INTO installments (
                    sale_id, 
                    amount, 
                    due_date, 
                    status
                ) VALUES (?, ?, ?, 'pending')
            ")
            ->bind(1, $saleId)
            ->bind(2, $amount)
            ->bind(3, $dueDate)
            ->execute();
        }
    }

    /**
     * Create initial payment for sale
     * @param int $saleId Sale ID
     * @param array $saleData Sale data
     */
    private function createInitialPayment($saleId, $saleData) {
        $amount = $saleData['payment_status'] === 'paid' 
            ? $saleData['final_amount'] 
            : ($saleData['final_amount'] * 0.5); // 50% for partial payment

        $this->db->query("
            INSERT INTO payments (
                sale_id, 
                amount, 
                payment_method,
                payment_date,
                notes
            ) VALUES (?, ?, ?, CURRENT_DATE, 'Initial payment')
        ")
        ->bind(1, $saleId)
        ->bind(2, $amount)
        ->bind(3, $saleData['payment_type'])
        ->execute();
    }

    /**
     * Get sales statistics
     * @return array Statistics data
     */
    public function getStatistics() {
        return [
            'today_sales' => $this->getTodaySales(),
            'today_orders' => $this->getTodayOrderCount(),
            'monthly_revenue' => $this->getMonthlyRevenue(),
            'last_month_revenue' => $this->getLastMonthRevenue()
        ];
    }

    /**
     * Get today's total sales
     * @return float Total sales amount
     */
    public function getTodaySales() {
        $result = $this->db->query("
            SELECT COALESCE(SUM(final_amount), 0) as total 
            FROM {$this->table} 
            WHERE DATE(created_at) = CURRENT_DATE
        ")->single();

        return floatval($result['total']);
    }

    /**
     * Get today's order count
     * @return int Order count
     */
    public function getTodayOrderCount() {
        $result = $this->db->query("
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE DATE(created_at) = CURRENT_DATE
        ")->single();

        return intval($result['count']);
    }

    /**
     * Get current month's revenue
     * @return float Monthly revenue
     */
    public function getMonthlyRevenue() {
        $result = $this->db->query("
            SELECT COALESCE(SUM(final_amount), 0) as total 
            FROM {$this->table} 
            WHERE YEAR(created_at) = YEAR(CURRENT_DATE) 
            AND MONTH(created_at) = MONTH(CURRENT_DATE)
        ")->single();

        return floatval($result['total']);
    }

    /**
     * Get last month's revenue
     * @return float Last month's revenue
     */
    public function getLastMonthRevenue() {
        $result = $this->db->query("
            SELECT COALESCE(SUM(final_amount), 0) as total 
            FROM {$this->table} 
            WHERE created_at >= DATE_SUB(DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), INTERVAL 1 MONTH)
            AND created_at < DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')
        ")->single();

        return floatval($result['total']);
    }
}
