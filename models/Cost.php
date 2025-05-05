<?php
/**
 * Cost Model
 * Handles operational costs and categories
 */
class Cost extends Model {
    protected $table = 'costs';
    protected $fillable = [
        'category_id',
        'amount',
        'description',
        'cost_date',
        'recurring',
        'recurring_type',
        'recurring_end_date',
        'created_by'
    ];

    /**
     * Create cost with recurring entries
     * @param array $data Cost data
     * @return int|false Cost ID or false on failure
     */
    public function createWithRecurring($data) {
        try {
            $this->db->beginTransaction();

            // Create initial cost entry
            $costId = $this->create($data);
            if (!$costId) {
                throw new Exception("Failed to create cost entry");
            }

            // Create recurring entries if applicable
            if ($data['recurring'] && $data['recurring_type'] && $data['recurring_end_date']) {
                $dates = $this->generateRecurringDates(
                    $data['cost_date'],
                    $data['recurring_end_date'],
                    $data['recurring_type']
                );

                foreach ($dates as $date) {
                    $recurringData = array_merge($data, ['cost_date' => $date]);
                    $this->create($recurringData);
                }
            }

            $this->db->commit();
            return $costId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Cost Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate recurring dates
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param string $type Recurring type
     * @return array Dates
     */
    private function generateRecurringDates($startDate, $endDate, $type) {
        $dates = [];
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);

        while ($current <= $end) {
            $current->modify('+1 ' . $this->getRecurringInterval($type));
            if ($current <= $end) {
                $dates[] = $current->format('Y-m-d');
            }
        }

        return $dates;
    }

    /**
     * Get recurring interval
     * @param string $type Recurring type
     * @return string Interval
     */
    private function getRecurringInterval($type) {
        switch ($type) {
            case 'daily':
                return 'day';
            case 'weekly':
                return 'week';
            case 'monthly':
                return 'month';
            case 'yearly':
                return 'year';
            default:
                return 'month';
        }
    }

    /**
     * Get costs by period
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param array $filters Filter options
     * @return array Costs data
     */
    public function getByPeriod($startDate, $endDate, $filters = []) {
        $where = ['DATE(c.cost_date) BETWEEN ? AND ?'];
        $params = [$startDate, $endDate];
        $index = 3;

        if (!empty($filters['category_id'])) {
            $where[] = 'c.category_id = ?';
            $params[] = $filters['category_id'];
            $index++;
        }

        if (!empty($filters['recurring'])) {
            $where[] = 'c.recurring = ?';
            $params[] = $filters['recurring'];
            $index++;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                c.*,
                cc.name as category_name,
                cc.type as category_type,
                u.name as created_by_name
            FROM {$this->table} c
            JOIN cost_categories cc ON c.category_id = cc.id
            JOIN users u ON c.created_by = u.id
            WHERE {$whereClause}
            ORDER BY c.cost_date DESC, c.id DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get cost summary by category
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Summary data
     */
    public function getCategorySummary($startDate, $endDate) {
        return $this->db->query("
            SELECT 
                cc.id as category_id,
                cc.name as category_name,
                cc.type as category_type,
                COUNT(*) as cost_count,
                SUM(c.amount) as total_amount,
                AVG(c.amount) as average_amount,
                MIN(c.cost_date) as first_cost,
                MAX(c.cost_date) as last_cost
            FROM {$this->table} c
            JOIN cost_categories cc ON c.category_id = cc.id
            WHERE DATE(c.cost_date) BETWEEN ? AND ?
            GROUP BY cc.id, cc.name, cc.type
            ORDER BY total_amount DESC
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->resultSet();
    }

    /**
     * Get recurring costs
     * @return array Recurring costs
     */
    public function getRecurring() {
        return $this->db->query("
            SELECT 
                c.*,
                cc.name as category_name,
                cc.type as category_type
            FROM {$this->table} c
            JOIN cost_categories cc ON c.category_id = cc.id
            WHERE c.recurring = 1
            AND (c.recurring_end_date IS NULL OR c.recurring_end_date >= CURRENT_DATE)
            ORDER BY c.cost_date DESC
        ")->resultSet();
    }

    /**
     * Create cost category
     * @param array $data Category data
     * @return int|false Category ID or false on failure
     */
    public function createCategory($data) {
        return $this->db->query("
            INSERT INTO cost_categories (
                name,
                description,
                type,
                status
            ) VALUES (?, ?, ?, 'active')
        ")
        ->bind(1, $data['name'])
        ->bind(2, $data['description'])
        ->bind(3, $data['type'])
        ->execute();
    }

    /**
     * Get active categories
     * @return array Active categories
     */
    public function getActiveCategories() {
        return $this->db->query("
            SELECT * FROM cost_categories
            WHERE status = 'active'
            ORDER BY name ASC
        ")->resultSet();
    }

    /**
     * Get upcoming recurring costs
     * @param int $days Days to look ahead
     * @return array Upcoming costs
     */
    public function getUpcomingRecurring($days = 30) {
        return $this->db->query("
            SELECT 
                c.*,
                cc.name as category_name,
                cc.type as category_type
            FROM {$this->table} c
            JOIN cost_categories cc ON c.category_id = cc.id
            WHERE c.recurring = 1
            AND c.cost_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
            ORDER BY c.cost_date ASC
        ")
        ->bind(1, $days)
        ->resultSet();
    }
}
