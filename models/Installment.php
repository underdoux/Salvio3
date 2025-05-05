<?php
/**
 * Installment Model
 * Handles installment payment operations
 */
class Installment extends Model {
    protected $table = 'installments';
    protected $fillable = [
        'sale_id',
        'amount',
        'due_date',
        'payment_id',
        'status'
    ];

    /**
     * Create installment plan
     * @param int $saleId Sale ID
     * @param float $totalAmount Total amount
     * @param int $terms Number of installments
     * @param string $startDate Start date (Y-m-d)
     * @return bool Success status
     */
    public function createPlan($saleId, $totalAmount, $terms = 3, $startDate = null) {
        try {
            $this->db->beginTransaction();

            $startDate = $startDate ? new DateTime($startDate) : new DateTime();
            $installmentAmount = round($totalAmount / $terms, 2);
            $remainder = $totalAmount - ($installmentAmount * $terms);

            for ($i = 0; $i < $terms; $i++) {
                $amount = $installmentAmount;
                if ($i === $terms - 1) {
                    $amount += $remainder; // Add remainder to last installment
                }

                $dueDate = clone $startDate;
                $dueDate->modify("+{$i} month");

                $this->create([
                    'sale_id' => $saleId,
                    'amount' => $amount,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'status' => 'pending'
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Installment Plan Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get installments by sale
     * @param int $saleId Sale ID
     * @return array Installments data
     */
    public function getBySaleId($saleId) {
        return $this->db->query("
            SELECT 
                i.*,
                p.payment_date,
                p.payment_method,
                p.reference_number,
                ba.bank_name,
                ba.account_number
            FROM {$this->table} i
            LEFT JOIN payments p ON i.payment_id = p.id
            LEFT JOIN bank_accounts ba ON p.bank_account_id = ba.id
            WHERE i.sale_id = ?
            ORDER BY i.due_date ASC
        ")
        ->bind(1, $saleId)
        ->resultSet();
    }

    /**
     * Process installment payment
     * @param int $installmentId Installment ID
     * @param array $paymentData Payment data
     * @return bool Success status
     */
    public function processPayment($installmentId, $paymentData) {
        try {
            $this->db->beginTransaction();

            // Get installment details
            $installment = $this->getById($installmentId);
            if (!$installment) {
                throw new Exception("Installment not found");
            }

            // Create payment record
            require_once 'Payment.php';
            $paymentModel = new Payment();
            $paymentData['sale_id'] = $installment['sale_id'];
            $paymentData['amount'] = $installment['amount'];
            $paymentData['installment_id'] = $installmentId;

            $paymentId = $paymentModel->createPayment($paymentData);
            if (!$paymentId) {
                throw new Exception("Failed to create payment record");
            }

            // Update installment status
            $this->update($installmentId, [
                'status' => 'paid',
                'payment_id' => $paymentId
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Installment Payment Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get overdue installments
     * @param int $days Days overdue (optional)
     * @return array Overdue installments
     */
    public function getOverdue($days = null) {
        $sql = "
            SELECT 
                i.*,
                s.invoice_number,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                DATEDIFF(CURRENT_DATE, i.due_date) as days_overdue
            FROM {$this->table} i
            JOIN sales s ON i.sale_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE i.status = 'pending'
            AND i.due_date < CURRENT_DATE
        ";

        if ($days !== null) {
            $sql .= " AND DATEDIFF(CURRENT_DATE, i.due_date) >= ?";
        }

        $sql .= " ORDER BY i.due_date ASC";

        $query = $this->db->query($sql);
        if ($days !== null) {
            $query->bind(1, $days);
        }

        return $query->resultSet();
    }

    /**
     * Get upcoming installments
     * @param int $days Days ahead (default: 7)
     * @return array Upcoming installments
     */
    public function getUpcoming($days = 7) {
        return $this->db->query("
            SELECT 
                i.*,
                s.invoice_number,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                DATEDIFF(i.due_date, CURRENT_DATE) as days_until_due
            FROM {$this->table} i
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
     * Update overdue statuses
     * @return int Number of updated records
     */
    public function updateOverdueStatus() {
        return $this->db->query("
            UPDATE {$this->table}
            SET 
                status = 'overdue',
                updated_at = CURRENT_TIMESTAMP
            WHERE status = 'pending'
            AND due_date < CURRENT_DATE
        ")->rowCount();
    }

    /**
     * Get installment summary
     * @return array Summary data
     */
    public function getSummary() {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_installments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue_amount
            FROM {$this->table}
        ")->single();
    }
}
