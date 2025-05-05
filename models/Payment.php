<?php
/**
 * Payment Model
 * Handles payment-related database operations
 */
class Payment extends Model {
    protected $table = 'payments';
    protected $fillable = [
        'sale_id',
        'amount',
        'payment_method',
        'bank_account_id',
        'reference_number',
        'payment_date',
        'notes'
    ];

    /**
     * Create payment and update related records
     * @param array $data Payment data
     * @return int|false Payment ID or false on failure
     */
    public function createPayment($data) {
        try {
            $this->db->beginTransaction();

            // Create payment record
            $paymentId = $this->create($data);
            if (!$paymentId) {
                throw new Exception("Failed to create payment");
            }

            // Update sale payment status
            $this->updateSalePaymentStatus($data['sale_id']);

            // Update installment if applicable
            if (isset($data['installment_id'])) {
                $this->updateInstallment($data['installment_id'], $paymentId);
            }

            $this->db->commit();
            return $paymentId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update sale payment status based on payments
     * @param int $saleId Sale ID
     */
    private function updateSalePaymentStatus($saleId) {
        // Get sale total and paid amount
        $result = $this->db->query("
            SELECT 
                s.final_amount,
                COALESCE(SUM(p.amount), 0) as paid_amount
            FROM sales s
            LEFT JOIN payments p ON s.id = p.sale_id
            WHERE s.id = ?
            GROUP BY s.id, s.final_amount
        ")
        ->bind(1, $saleId)
        ->single();

        if (!$result) return;

        // Determine payment status
        $status = 'unpaid';
        if ($result['paid_amount'] >= $result['final_amount']) {
            $status = 'paid';
        } elseif ($result['paid_amount'] > 0) {
            $status = 'partial';
        }

        // Update sale status
        $this->db->query("
            UPDATE sales 
            SET payment_status = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ")
        ->bind(1, $status)
        ->bind(2, $saleId)
        ->execute();
    }

    /**
     * Update installment status
     * @param int $installmentId Installment ID
     * @param int $paymentId Payment ID
     */
    private function updateInstallment($installmentId, $paymentId) {
        $this->db->query("
            UPDATE installments 
            SET 
                status = 'paid',
                payment_id = ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ")
        ->bind(1, $paymentId)
        ->bind(2, $installmentId)
        ->execute();
    }

    /**
     * Get payments by sale ID
     * @param int $saleId Sale ID
     * @return array Payments data
     */
    public function getBySaleId($saleId) {
        return $this->db->query("
            SELECT 
                p.*,
                ba.bank_name,
                ba.account_number
            FROM {$this->table} p
            LEFT JOIN bank_accounts ba ON p.bank_account_id = ba.id
            WHERE p.sale_id = ?
            ORDER BY p.payment_date DESC
        ")
        ->bind(1, $saleId)
        ->resultSet();
    }

    /**
     * Get payment summary by date range
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Payment summary
     */
    public function getPaymentSummary($startDate, $endDate) {
        return $this->db->query("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM {$this->table}
            WHERE payment_date BETWEEN ? AND ?
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->resultSet();
    }

    /**
     * Get overdue installments
     * @return array Overdue installments
     */
    public function getOverdueInstallments() {
        return $this->db->query("
            SELECT 
                i.*,
                s.invoice_number,
                c.name as customer_name,
                c.phone as customer_phone
            FROM installments i
            JOIN sales s ON i.sale_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE i.status = 'pending'
            AND i.due_date < CURRENT_DATE
            ORDER BY i.due_date ASC
        ")->resultSet();
    }

    /**
     * Get upcoming installments
     * @param int $days Number of days to look ahead
     * @return array Upcoming installments
     */
    public function getUpcomingInstallments($days = 7) {
        return $this->db->query("
            SELECT 
                i.*,
                s.invoice_number,
                c.name as customer_name,
                c.phone as customer_phone
            FROM installments i
            JOIN sales s ON i.sale_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE i.status = 'pending'
            AND i.due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
            ORDER BY i.due_date ASC
        ")
        ->bind(1, $days)
        ->resultSet();
    }

    /**
     * Get active bank accounts
     * @return array Bank accounts
     */
    public function getActiveBankAccounts() {
        return $this->db->query("
            SELECT * FROM bank_accounts
            WHERE status = 'active'
            ORDER BY bank_name ASC
        ")->resultSet();
    }
}
