<?php
/**
 * BankAccount Model
 * Handles bank account management
 */
class BankAccount extends Model {
    protected $table = 'bank_accounts';
    protected $fillable = [
        'bank_name',
        'account_number',
        'account_name',
        'branch',
        'status'
    ];

    /**
     * Get active bank accounts
     * @return array Active bank accounts
     */
    public function getActive() {
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE status = 'active'
            ORDER BY bank_name ASC, account_name ASC
        ")->resultSet();
    }

    /**
     * Get bank account with transaction summary
     * @param int $id Bank account ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array|null Bank account data with transactions
     */
    public function getWithTransactions($id, $startDate = null, $endDate = null) {
        // Get bank account details
        $account = $this->getById($id);
        if (!$account) {
            return null;
        }

        // Build transaction query
        $where = ['p.bank_account_id = ?'];
        $params = [$id];
        $index = 2;

        if ($startDate) {
            $where[] = 'p.payment_date >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'p.payment_date <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        // Get transactions
        $query = $this->db->query("
            SELECT 
                p.*,
                s.invoice_number,
                c.name as customer_name,
                u.name as user_name
            FROM payments p
            JOIN sales s ON p.sale_id = s.id
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE {$whereClause}
            ORDER BY p.payment_date DESC, p.id DESC
        ");

        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        $account['transactions'] = $query->resultSet();

        // Get summary
        $summary = $this->db->query("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(amount) as total_amount,
                MIN(payment_date) as first_transaction,
                MAX(payment_date) as last_transaction
            FROM payments
            WHERE bank_account_id = ?
        ")
        ->bind(1, $id)
        ->single();

        $account['summary'] = $summary;

        return $account;
    }

    /**
     * Get transaction summary by bank account
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Transaction summary
     */
    public function getTransactionSummary($startDate = null, $endDate = null) {
        $where = ['p.bank_account_id IS NOT NULL'];
        $params = [];
        $index = 1;

        if ($startDate) {
            $where[] = 'p.payment_date >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'p.payment_date <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                ba.id,
                ba.bank_name,
                ba.account_number,
                ba.account_name,
                COUNT(p.id) as transaction_count,
                SUM(p.amount) as total_amount
            FROM {$this->table} ba
            LEFT JOIN payments p ON ba.id = p.bank_account_id
            WHERE {$whereClause}
            GROUP BY ba.id, ba.bank_name, ba.account_number, ba.account_name
            ORDER BY total_amount DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Validate bank account number
     * @param string $accountNumber Account number
     * @param int $excludeId Exclude bank account ID (optional)
     * @return bool True if valid and unique
     */
    public function isValidAccountNumber($accountNumber, $excludeId = null) {
        $sql = "
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE account_number = ?
        ";
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
        }

        $query = $this->db->query($sql)->bind(1, $accountNumber);
        
        if ($excludeId !== null) {
            $query->bind(2, $excludeId);
        }

        $result = $query->single();
        return $result['count'] === 0;
    }

    /**
     * Create bank account with validation
     * @param array $data Bank account data
     * @return int|false Bank account ID or false on failure
     */
    public function createWithValidation($data) {
        // Validate account number
        if (!$this->isValidAccountNumber($data['account_number'])) {
            $this->error = "Account number already exists";
            return false;
        }

        // Clean input
        $data['bank_name'] = trim($data['bank_name']);
        $data['account_number'] = preg_replace('/\s+/', '', $data['account_number']);
        $data['account_name'] = trim($data['account_name']);
        $data['branch'] = trim($data['branch'] ?? '');
        $data['status'] = $data['status'] ?? 'active';

        return $this->create($data);
    }

    /**
     * Update bank account with validation
     * @param int $id Bank account ID
     * @param array $data Bank account data
     * @return bool Success status
     */
    public function updateWithValidation($id, $data) {
        // Validate account number if changed
        if (isset($data['account_number'])) {
            if (!$this->isValidAccountNumber($data['account_number'], $id)) {
                $this->error = "Account number already exists";
                return false;
            }
            $data['account_number'] = preg_replace('/\s+/', '', $data['account_number']);
        }

        // Clean input
        if (isset($data['bank_name'])) {
            $data['bank_name'] = trim($data['bank_name']);
        }
        if (isset($data['account_name'])) {
            $data['account_name'] = trim($data['account_name']);
        }
        if (isset($data['branch'])) {
            $data['branch'] = trim($data['branch']);
        }

        return $this->update($id, $data);
    }

    /**
     * Get error message
     * @return string|null Error message
     */
    public function getError() {
        return $this->error;
    }
}
