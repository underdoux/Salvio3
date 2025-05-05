<?php
/**
 * Investor Model
 * Handles investor management and profit sharing
 */
class Investor extends Model {
    protected $table = 'investors';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'initial_capital',
        'current_capital',
        'ownership_percentage',
        'join_date',
        'status',
        'bank_name',
        'bank_account',
        'bank_holder',
        'notes'
    ];

    /**
     * Create investor with capital transaction
     * @param array $data Investor data
     * @return int|false Investor ID or false on failure
     */
    public function createWithCapital($data) {
        try {
            $this->db->beginTransaction();

            // Create investor
            $investorId = $this->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'initial_capital' => $data['initial_capital'],
                'current_capital' => $data['initial_capital'],
                'ownership_percentage' => $data['ownership_percentage'],
                'join_date' => $data['join_date'],
                'bank_name' => $data['bank_name'],
                'bank_account' => $data['bank_account'],
                'bank_holder' => $data['bank_holder'],
                'notes' => $data['notes'],
                'status' => 'active'
            ]);

            if (!$investorId) {
                throw new Exception("Failed to create investor");
            }

            // Record capital transaction
            $this->db->query("
                INSERT INTO capital_transactions (
                    investor_id,
                    type,
                    amount,
                    transaction_date,
                    reference_number,
                    notes
                ) VALUES (?, 'investment', ?, ?, ?, ?)
            ")
            ->bind(1, $investorId)
            ->bind(2, $data['initial_capital'])
            ->bind(3, $data['join_date'])
            ->bind(4, $data['reference_number'])
            ->bind(5, "Initial investment")
            ->execute();

            $this->db->commit();
            return $investorId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Investor Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record capital transaction
     * @param array $data Transaction data
     * @return bool Success status
     */
    public function recordTransaction($data) {
        try {
            $this->db->beginTransaction();

            // Get current capital
            $investor = $this->getById($data['investor_id']);
            if (!$investor) {
                throw new Exception("Investor not found");
            }

            // Calculate new capital
            $newCapital = $investor['current_capital'];
            if ($data['type'] === 'investment') {
                $newCapital += $data['amount'];
            } elseif (in_array($data['type'], ['withdrawal', 'loss'])) {
                $newCapital -= $data['amount'];
            }

            // Update investor capital
            $this->update($data['investor_id'], [
                'current_capital' => $newCapital
            ]);

            // Record transaction
            $this->db->query("
                INSERT INTO capital_transactions (
                    investor_id,
                    type,
                    amount,
                    transaction_date,
                    reference_number,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?)
            ")
            ->bind(1, $data['investor_id'])
            ->bind(2, $data['type'])
            ->bind(3, $data['amount'])
            ->bind(4, $data['transaction_date'])
            ->bind(5, $data['reference_number'])
            ->bind(6, $data['notes'])
            ->execute();

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Capital Transaction Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get investor transactions
     * @param int $investorId Investor ID
     * @param array $filters Filter options
     * @return array Transactions
     */
    public function getTransactions($investorId, $filters = []) {
        $where = ['ct.investor_id = ?'];
        $params = [$investorId];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'ct.transaction_date >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'ct.transaction_date <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        if (!empty($filters['type'])) {
            $where[] = 'ct.type = ?';
            $params[] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                ct.*,
                i.name as investor_name,
                i.ownership_percentage
            FROM capital_transactions ct
            JOIN investors i ON ct.investor_id = i.id
            WHERE {$whereClause}
            ORDER BY ct.transaction_date DESC, ct.id DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Calculate profit distribution
     * @param array $profitData Profit calculation data
     * @return array|false Distribution data or false on failure
     */
    public function calculateDistribution($profitData) {
        try {
            // Get active investors
            $investors = $this->db->query("
                SELECT *
                FROM {$this->table}
                WHERE status = 'active'
                AND join_date <= ?
            ")
            ->bind(1, $profitData['period_end'])
            ->resultSet();

            if (empty($investors)) {
                throw new Exception("No active investors found");
            }

            // Calculate distribution
            $distributions = [];
            $totalPercentage = array_sum(array_column($investors, 'ownership_percentage'));

            foreach ($investors as $investor) {
                $share = ($investor['ownership_percentage'] / $totalPercentage) * $profitData['net_profit'];
                $distributions[] = [
                    'investor_id' => $investor['id'],
                    'amount' => round($share, 2),
                    'percentage' => $investor['ownership_percentage']
                ];
            }

            return $distributions;

        } catch (Exception $e) {
            error_log("Profit Distribution Calculation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get investor summary
     * @param int $investorId Investor ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Summary data
     */
    public function getSummary($investorId, $startDate = null, $endDate = null) {
        $where = ['investor_id = ?'];
        $params = [$investorId];
        $index = 2;

        if ($startDate) {
            $where[] = 'transaction_date >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'transaction_date <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                type,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                MIN(transaction_date) as first_transaction,
                MAX(transaction_date) as last_transaction
            FROM capital_transactions
            WHERE {$whereClause}
            GROUP BY type
            ORDER BY type
        ")
        ->bind(1, $investorId)
        ->resultSet();
    }

    /**
     * Validate ownership percentages
     * @param float $newPercentage New percentage
     * @param int $excludeId Investor ID to exclude
     * @return bool Valid status
     */
    public function validateOwnership($newPercentage, $excludeId = null) {
        $sql = "
            SELECT SUM(ownership_percentage) as total
            FROM {$this->table}
            WHERE status = 'active'
        ";

        if ($excludeId) {
            $sql .= " AND id != ?";
        }

        $query = $this->db->query($sql);
        if ($excludeId) {
            $query->bind(1, $excludeId);
        }

        $result = $query->single();
        $currentTotal = $result['total'] ?? 0;

        return ($currentTotal + $newPercentage) <= 100;
    }
}
