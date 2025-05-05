<?php
/**
 * ProfitCalculation Model
 * Handles profit calculations and distributions
 */
class ProfitCalculation extends Model {
    protected $table = 'profit_calculations';
    protected $fillable = [
        'period_start',
        'period_end',
        'total_revenue',
        'total_costs',
        'gross_profit',
        'net_profit',
        'status',
        'calculated_by',
        'notes'
    ];

    /**
     * Calculate period profits
     * @param string $startDate Period start date
     * @param string $endDate Period end date
     * @return array|false Calculation data or false on failure
     */
    public function calculatePeriod($startDate, $endDate) {
        try {
            // Get total revenue from sales
            $revenue = $this->db->query("
                SELECT 
                    COUNT(*) as sale_count,
                    SUM(final_amount) as total_revenue,
                    SUM(cost_of_goods) as total_cogs,
                    SUM(gross_profit) as total_gross_profit,
                    SUM(net_profit) as total_net_profit
                FROM sales
                WHERE DATE(created_at) BETWEEN ? AND ?
                AND payment_status = 'paid'
            ")
            ->bind(1, $startDate)
            ->bind(2, $endDate)
            ->single();

            // Get operational costs
            $costs = $this->db->query("
                SELECT 
                    cc.type,
                    SUM(c.amount) as total_amount
                FROM costs c
                JOIN cost_categories cc ON c.category_id = cc.id
                WHERE DATE(c.cost_date) BETWEEN ? AND ?
                GROUP BY cc.type
            ")
            ->bind(1, $startDate)
            ->bind(2, $endDate)
            ->resultSet();

            // Calculate totals
            $totalCosts = array_sum(array_column($costs, 'total_amount'));
            $grossProfit = $revenue['total_gross_profit'];
            $netProfit = $grossProfit - $totalCosts;

            return [
                'period_start' => $startDate,
                'period_end' => $endDate,
                'total_revenue' => $revenue['total_revenue'],
                'total_costs' => $totalCosts,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'details' => [
                    'sale_count' => $revenue['sale_count'],
                    'cogs' => $revenue['total_cogs'],
                    'costs_breakdown' => $costs
                ]
            ];

        } catch (Exception $e) {
            error_log("Profit Calculation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create profit calculation with distributions
     * @param array $data Calculation data
     * @return int|false Calculation ID or false on failure
     */
    public function createWithDistributions($data) {
        try {
            $this->db->beginTransaction();

            // Create calculation record
            $calculationId = $this->create([
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_revenue' => $data['total_revenue'],
                'total_costs' => $data['total_costs'],
                'gross_profit' => $data['gross_profit'],
                'net_profit' => $data['net_profit'],
                'status' => 'draft',
                'calculated_by' => $data['calculated_by'],
                'notes' => $data['notes'] ?? null
            ]);

            if (!$calculationId) {
                throw new Exception("Failed to create profit calculation");
            }

            // Create distributions
            require_once 'Investor.php';
            $investorModel = new Investor();
            $distributions = $investorModel->calculateDistribution([
                'period_end' => $data['period_end'],
                'net_profit' => $data['net_profit']
            ]);

            if (!$distributions) {
                throw new Exception("Failed to calculate distributions");
            }

            foreach ($distributions as $dist) {
                $this->db->query("
                    INSERT INTO profit_distributions (
                        calculation_id,
                        investor_id,
                        amount,
                        percentage,
                        status
                    ) VALUES (?, ?, ?, ?, 'pending')
                ")
                ->bind(1, $calculationId)
                ->bind(2, $dist['investor_id'])
                ->bind(3, $dist['amount'])
                ->bind(4, $dist['percentage'])
                ->execute();
            }

            $this->db->commit();
            return $calculationId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Profit Calculation Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finalize calculation
     * @param int $id Calculation ID
     * @return bool Success status
     */
    public function finalize($id) {
        try {
            $this->db->beginTransaction();

            $this->update($id, [
                'status' => 'finalized',
                'finalized_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Profit Calculation Finalization Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process distributions
     * @param int $calculationId Calculation ID
     * @param array $distributions Distribution data
     * @return bool Success status
     */
    public function processDistributions($calculationId, $distributions) {
        try {
            $this->db->beginTransaction();

            foreach ($distributions as $dist) {
                // Update distribution status
                $this->db->query("
                    UPDATE profit_distributions
                    SET 
                        status = 'paid',
                        payment_date = ?,
                        payment_reference = ?,
                        notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE calculation_id = ?
                    AND investor_id = ?
                ")
                ->bind(1, $dist['payment_date'])
                ->bind(2, $dist['payment_reference'])
                ->bind(3, $dist['notes'])
                ->bind(4, $calculationId)
                ->bind(5, $dist['investor_id'])
                ->execute();

                // Record capital transaction
                $this->db->query("
                    INSERT INTO capital_transactions (
                        investor_id,
                        type,
                        amount,
                        transaction_date,
                        reference_number,
                        notes
                    ) VALUES (?, 'profit_share', ?, ?, ?, ?)
                ")
                ->bind(1, $dist['investor_id'])
                ->bind(2, $dist['amount'])
                ->bind(3, $dist['payment_date'])
                ->bind(4, $dist['payment_reference'])
                ->bind(5, "Profit distribution for period")
                ->execute();
            }

            // Update calculation status
            $this->update($calculationId, [
                'status' => 'distributed',
                'distributed_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Distribution Processing Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get calculation with distributions
     * @param int $id Calculation ID
     * @return array|null Calculation data with distributions
     */
    public function getWithDistributions($id) {
        $calculation = $this->getById($id);
        if (!$calculation) {
            return null;
        }

        $distributions = $this->db->query("
            SELECT 
                pd.*,
                i.name as investor_name,
                i.bank_name,
                i.bank_account,
                i.bank_holder
            FROM profit_distributions pd
            JOIN investors i ON pd.investor_id = i.id
            WHERE pd.calculation_id = ?
            ORDER BY pd.amount DESC
        ")
        ->bind(1, $id)
        ->resultSet();

        $calculation['distributions'] = $distributions;
        return $calculation;
    }

    /**
     * Get period summary
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Summary data
     */
    public function getPeriodSummary($startDate, $endDate) {
        return $this->db->query("
            SELECT 
                COUNT(*) as calculation_count,
                SUM(total_revenue) as total_revenue,
                SUM(total_costs) as total_costs,
                SUM(gross_profit) as total_gross_profit,
                SUM(net_profit) as total_net_profit,
                AVG(net_profit) as average_net_profit
            FROM {$this->table}
            WHERE period_start >= ?
            AND period_end <= ?
            AND status != 'draft'
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->single();
    }
}
