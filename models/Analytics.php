<?php
/**
 * Analytics Model
 * Handles analytics data collection and processing
 */
class Analytics extends Model {
    protected $table = 'analytics_events';

    /**
     * Track event
     * @param string $type Event type
     * @param array $data Event data
     * @param int|null $userId User ID
     * @return bool Success status
     */
    public function trackEvent($type, $data, $userId = null) {
        return $this->db->query("
            INSERT INTO {$this->table} (
                event_type,
                event_data,
                user_id,
                ip_address,
                user_agent
            ) VALUES (?, ?, ?, ?, ?)
        ")
        ->bind(1, $type)
        ->bind(2, json_encode($data))
        ->bind(3, $userId)
        ->bind(4, $_SERVER['REMOTE_ADDR'] ?? null)
        ->bind(5, $_SERVER['HTTP_USER_AGENT'] ?? null)
        ->execute();
    }

    /**
     * Record metric
     * @param string $name Metric name
     * @param float $value Metric value
     * @param string|null $dimension Dimension name
     * @param string|null $dimensionValue Dimension value
     * @param string $period Period type (daily/monthly)
     * @return bool Success status
     */
    public function recordMetric($name, $value, $dimension = null, $dimensionValue = null, $period = 'daily') {
        $startDate = $period === 'monthly' ? date('Y-m-01') : date('Y-m-d');
        $endDate = $period === 'monthly' ? date('Y-m-t') : date('Y-m-d');

        return $this->db->query("
            INSERT INTO analytics_metrics (
                metric_name,
                metric_value,
                dimension,
                dimension_value,
                period_start,
                period_end
            ) VALUES (?, ?, ?, ?, ?, ?)
        ")
        ->bind(1, $name)
        ->bind(2, $value)
        ->bind(3, $dimension)
        ->bind(4, $dimensionValue)
        ->bind(5, $startDate)
        ->bind(6, $endDate)
        ->execute();
    }

    /**
     * Update product analytics
     * @param int $productId Product ID
     * @param string $action Action type
     * @return bool Success status
     */
    public function updateProductAnalytics($productId, $action) {
        try {
            switch ($action) {
                case 'view':
                    $this->db->query("
                        UPDATE products 
                        SET 
                            view_count = view_count + 1,
                            last_viewed_at = CURRENT_TIMESTAMP,
                            trending_score = (
                                view_count * 0.3 + 
                                purchase_count * 0.7
                            ) / GREATEST(1, DATEDIFF(CURRENT_DATE, DATE(created_at)))
                        WHERE id = ?
                    ")
                    ->bind(1, $productId)
                    ->execute();
                    break;

                case 'purchase':
                    $this->db->query("
                        UPDATE products 
                        SET 
                            purchase_count = purchase_count + 1,
                            trending_score = (
                                view_count * 0.3 + 
                                (purchase_count + 1) * 0.7
                            ) / GREATEST(1, DATEDIFF(CURRENT_DATE, DATE(created_at)))
                        WHERE id = ?
                    ")
                    ->bind(1, $productId)
                    ->execute();
                    break;
            }

            return true;
        } catch (Exception $e) {
            error_log("Product Analytics Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update customer analytics
     * @param int $customerId Customer ID
     * @return bool Success status
     */
    public function updateCustomerAnalytics($customerId) {
        try {
            // Get customer purchase data
            $data = $this->db->query("
                SELECT 
                    COUNT(*) as total_purchases,
                    SUM(final_amount) as total_spent,
                    MAX(created_at) as last_purchase,
                    AVG(DATEDIFF(
                        LEAD(created_at) OVER (ORDER BY created_at),
                        created_at
                    )) as avg_days_between_purchases
                FROM sales
                WHERE customer_id = ?
                AND status != 'cancelled'
            ")
            ->bind(1, $customerId)
            ->single();

            if (!$data) return false;

            // Calculate churn risk
            $daysSinceLastPurchase = $data['last_purchase'] ? 
                (time() - strtotime($data['last_purchase'])) / (60 * 60 * 24) : 0;
            
            $churnRisk = min(100, max(0,
                ($daysSinceLastPurchase / ($data['avg_days_between_purchases'] ?: 30)) * 100
            ));

            // Update customer metrics
            $this->db->query("
                UPDATE customers 
                SET 
                    lifetime_value = ?,
                    last_purchase_at = ?,
                    purchase_frequency = ?,
                    churn_risk = ?
                WHERE id = ?
            ")
            ->bind(1, $data['total_spent'])
            ->bind(2, $data['last_purchase'])
            ->bind(3, $data['total_purchases'])
            ->bind(4, $churnRisk)
            ->bind(5, $customerId)
            ->execute();

            return true;
        } catch (Exception $e) {
            error_log("Customer Analytics Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event analytics
     * @param string $type Event type
     * @param array $filters Filter options
     * @return array Analytics data
     */
    public function getEventAnalytics($type, $filters = []) {
        $where = ['event_type = ?'];
        $params = [$type];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(created_at) >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(created_at) <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as event_count,
                COUNT(DISTINCT user_id) as unique_users
            FROM {$this->table}
            WHERE {$whereClause}
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ")
        ->bind(1, $type)
        ->resultSet();
    }

    /**
     * Get metric trends
     * @param string $metricName Metric name
     * @param array $filters Filter options
     * @return array Trend data
     */
    public function getMetricTrends($metricName, $filters = []) {
        $where = ['metric_name = ?'];
        $params = [$metricName];
        $index = 2;

        if (!empty($filters['dimension'])) {
            $where[] = 'dimension = ?';
            $params[] = $filters['dimension'];
            $index++;
        }

        if (!empty($filters['dimension_value'])) {
            $where[] = 'dimension_value = ?';
            $params[] = $filters['dimension_value'];
            $index++;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                period_start,
                period_end,
                metric_value,
                LAG(metric_value) OVER (ORDER BY period_start) as previous_value,
                (
                    metric_value - LAG(metric_value) OVER (ORDER BY period_start)
                ) / LAG(metric_value) OVER (ORDER BY period_start) * 100 as change_percentage
            FROM analytics_metrics
            WHERE {$whereClause}
            ORDER BY period_start DESC
        ")
        ->bind(1, $metricName)
        ->resultSet();
    }
}
