<?php
/**
 * Report Model
 * Handles report generation, caching, and scheduling
 */
class Report extends Model {
    protected $table = 'report_configurations';
    protected $fillable = [
        'name',
        'type',
        'description',
        'parameters',
        'schedule',
        'recipients',
        'created_by',
        'status'
    ];

    /**
     * Generate report
     * @param array $config Report configuration
     * @param array $params Report parameters
     * @return array|false Report data or false on failure
     */
    public function generate($config, $params = []) {
        try {
            // Check cache first
            $cached = $this->getCachedReport($config['id'], $params);
            if ($cached) {
                return json_decode($cached['data'], true);
            }

            // Generate report based on type
            $data = $this->generateByType($config['type'], array_merge(
                json_decode($config['parameters'], true),
                $params
            ));

            if (!$data) {
                throw new Exception("Failed to generate report data");
            }

            // Cache the results
            $this->cacheReport($config['id'], $params, $data);

            return $data;

        } catch (Exception $e) {
            error_log("Report Generation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate report by type
     * @param string $type Report type
     * @param array $params Report parameters
     * @return array Report data
     */
    private function generateByType($type, $params) {
        switch ($type) {
            case 'sales':
                return $this->generateSalesReport($params);
            case 'inventory':
                return $this->generateInventoryReport($params);
            case 'commission':
                return $this->generateCommissionReport($params);
            case 'financial':
                return $this->generateFinancialReport($params);
            case 'customer':
                return $this->generateCustomerReport($params);
            case 'supplier':
                return $this->generateSupplierReport($params);
            default:
                throw new Exception("Invalid report type: {$type}");
        }
    }

    /**
     * Generate sales report
     * @param array $params Report parameters
     * @return array Report data
     */
    private function generateSalesReport($params) {
        $period = $params['period'] ?? 'daily';
        $startDate = $this->getStartDate($period);
        $endDate = date('Y-m-d H:i:s');

        // Get sales summary
        $summary = $this->db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                SUM(final_amount) as net_sales,
                SUM(discount_amount) as total_discounts,
                AVG(final_amount) as average_order,
                SUM(cost_of_goods) as total_cogs,
                SUM(gross_profit) as total_gross_profit,
                SUM(net_profit) as total_net_profit
            FROM sales
            WHERE created_at BETWEEN ? AND ?
            AND status != 'cancelled'
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->single();

        // Get sales by product
        $products = $this->db->query("
            SELECT 
                p.id,
                p.name,
                p.sku,
                COUNT(DISTINCT s.id) as order_count,
                SUM(si.quantity) as total_quantity,
                SUM(si.total_amount) as total_amount,
                AVG(si.unit_price) as average_price
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN products p ON si.product_id = p.id
            WHERE s.created_at BETWEEN ? AND ?
            AND s.status != 'cancelled'
            GROUP BY p.id, p.name, p.sku
            ORDER BY total_amount DESC
            LIMIT 10
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->resultSet();

        // Get sales by payment method
        $payments = $this->db->query("
            SELECT 
                payment_type,
                COUNT(*) as count,
                SUM(final_amount) as total_amount
            FROM sales
            WHERE created_at BETWEEN ? AND ?
            AND status != 'cancelled'
            GROUP BY payment_type
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->resultSet();

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'type' => $period
            ],
            'summary' => $summary,
            'top_products' => $products,
            'payment_methods' => $payments
        ];
    }

    /**
     * Generate inventory report
     * @param array $params Report parameters
     * @return array Report data
     */
    private function generateInventoryReport($params) {
        $threshold = $params['threshold'] ?? 'min_stock';
        
        // Get stock summary
        $summary = $this->db->query("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock) as total_stock,
                SUM(stock * purchase_price) as total_value,
                COUNT(CASE WHEN stock <= min_stock THEN 1 END) as low_stock_count,
                COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_count
            FROM products
            WHERE status = 'active'
        ")->single();

        // Get low stock products
        $lowStock = $this->db->query("
            SELECT 
                p.*,
                c.name as category_name,
                s.name as supplier_name,
                (
                    SELECT SUM(quantity)
                    FROM sale_items si
                    JOIN sales s ON si.sale_id = s.id
                    WHERE si.product_id = p.id
                    AND s.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                ) as monthly_sales
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON p.default_supplier_id = s.id
            WHERE p.status = 'active'
            AND p.stock <= p.{$threshold}
            ORDER BY p.stock ASC
        ")->resultSet();

        // Get stock movements
        $movements = $this->db->query("
            SELECT 
                DATE(created_at) as date,
                movement_type,
                COUNT(*) as count,
                SUM(quantity) as total_quantity
            FROM stock_movements
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            GROUP BY DATE(created_at), movement_type
            ORDER BY date DESC
        ")->resultSet();

        return [
            'summary' => $summary,
            'low_stock' => $lowStock,
            'movements' => $movements
        ];
    }

    /**
     * Get cached report
     * @param int $configId Configuration ID
     * @param array $params Report parameters
     * @return array|null Cached report data
     */
    private function getCachedReport($configId, $params) {
        return $this->db->query("
            SELECT *
            FROM report_cache
            WHERE configuration_id = ?
            AND parameters = ?
            AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
            ORDER BY generated_at DESC
            LIMIT 1
        ")
        ->bind(1, $configId)
        ->bind(2, json_encode($params))
        ->single();
    }

    /**
     * Cache report data
     * @param int $configId Configuration ID
     * @param array $params Report parameters
     * @param array $data Report data
     * @return bool Success status
     */
    private function cacheReport($configId, $params, $data) {
        return $this->db->query("
            INSERT INTO report_cache (
                configuration_id,
                parameters,
                data,
                format,
                expires_at
            ) VALUES (?, ?, ?, 'json', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))
        ")
        ->bind(1, $configId)
        ->bind(2, json_encode($params))
        ->bind(3, json_encode($data))
        ->execute();
    }

    /**
     * Get start date based on period
     * @param string $period Period type
     * @return string Start date
     */
    private function getStartDate($period) {
        switch ($period) {
            case 'daily':
                return date('Y-m-d 00:00:00');
            case 'weekly':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'monthly':
                return date('Y-m-01 00:00:00');
            case 'yearly':
                return date('Y-01-01 00:00:00');
            default:
                return date('Y-m-d 00:00:00');
        }
    }

    /**
     * Schedule report generation
     * @param int $configId Configuration ID
     * @return bool Success status
     */
    public function schedule($configId) {
        $config = $this->getById($configId);
        if (!$config || !$config['schedule']) {
            return false;
        }

        return $this->db->query("
            INSERT INTO report_schedules (
                configuration_id,
                next_run
            ) VALUES (?, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))
        ")
        ->bind(1, $configId)
        ->execute();
    }

    /**
     * Process scheduled reports
     * @return int Number of reports processed
     */
    public function processScheduled() {
        $schedules = $this->db->query("
            SELECT 
                rs.*,
                rc.name,
                rc.type,
                rc.parameters,
                rc.recipients
            FROM report_schedules rs
            JOIN report_configurations rc ON rs.configuration_id = rc.id
            WHERE rs.next_run <= CURRENT_TIMESTAMP
            AND rs.status = 'pending'
            LIMIT 10
        ")->resultSet();

        $processed = 0;
        foreach ($schedules as $schedule) {
            try {
                // Update status to running
                $this->db->query("
                    UPDATE report_schedules
                    SET status = 'running', updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ")
                ->bind(1, $schedule['id'])
                ->execute();

                // Generate report
                $data = $this->generate($schedule, []);
                if (!$data) {
                    throw new Exception("Failed to generate report");
                }

                // Update schedule status
                $this->db->query("
                    UPDATE report_schedules
                    SET 
                        status = 'completed',
                        last_run = CURRENT_TIMESTAMP,
                        next_run = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ")
                ->bind(1, $schedule['id'])
                ->execute();

                $processed++;

            } catch (Exception $e) {
                // Log error and update status
                error_log("Scheduled Report Error: " . $e->getMessage());
                $this->db->query("
                    UPDATE report_schedules
                    SET 
                        status = 'failed',
                        error_message = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ")
                ->bind(1, $e->getMessage())
                ->bind(2, $schedule['id'])
                ->execute();
            }
        }

        return $processed;
    }
}
