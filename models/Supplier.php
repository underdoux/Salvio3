<?php
/**
 * Supplier Model
 * Handles supplier management and purchasing
 */
class Supplier extends Model {
    protected $table = 'suppliers';
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'contact_person',
        'tax_number',
        'bank_name',
        'bank_account',
        'bank_holder',
        'credit_limit',
        'payment_terms',
        'status',
        'notes'
    ];

    /**
     * Get supplier with payment summary
     * @param int $id Supplier ID
     * @return array|null Supplier data with payment summary
     */
    public function getWithSummary($id) {
        $supplier = $this->getById($id);
        if (!$supplier) {
            return null;
        }

        // Get purchase orders summary
        $poSummary = $this->db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_ordered,
                SUM(paid_amount) as total_paid,
                SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount - paid_amount ELSE 0 END) as total_unpaid,
                MAX(order_date) as last_order_date
            FROM purchase_orders
            WHERE supplier_id = ?
            AND order_status != 'cancelled'
        ")
        ->bind(1, $id)
        ->single();

        // Get payment history
        $payments = $this->db->query("
            SELECT 
                sp.*,
                po.po_number,
                u.name as created_by_name
            FROM supplier_payments sp
            JOIN purchase_orders po ON sp.purchase_order_id = po.id
            JOIN users u ON sp.created_by = u.id
            WHERE po.supplier_id = ?
            ORDER BY sp.payment_date DESC
            LIMIT 10
        ")
        ->bind(1, $id)
        ->resultSet();

        // Get supplied products
        $products = $this->db->query("
            SELECT 
                p.*,
                (
                    SELECT poi.unit_price 
                    FROM purchase_order_items poi
                    JOIN purchase_orders po ON poi.purchase_order_id = po.id
                    WHERE po.supplier_id = ?
                    AND poi.product_id = p.id
                    AND po.order_status = 'received'
                    ORDER BY po.order_date DESC
                    LIMIT 1
                ) as last_purchase_price
            FROM products p
            WHERE p.default_supplier_id = ?
            OR p.id IN (
                SELECT DISTINCT poi.product_id
                FROM purchase_order_items poi
                JOIN purchase_orders po ON poi.purchase_order_id = po.id
                WHERE po.supplier_id = ?
            )
        ")
        ->bind(1, $id)
        ->bind(2, $id)
        ->bind(3, $id)
        ->resultSet();

        return array_merge($supplier, [
            'summary' => $poSummary,
            'recent_payments' => $payments,
            'products' => $products
        ]);
    }

    /**
     * Get unpaid purchase orders
     * @param int $supplierId Supplier ID
     * @return array Unpaid orders
     */
    public function getUnpaidOrders($supplierId) {
        return $this->db->query("
            SELECT 
                po.*,
                DATEDIFF(CURRENT_DATE, payment_due_date) as days_overdue
            FROM purchase_orders po
            WHERE po.supplier_id = ?
            AND po.payment_status != 'paid'
            AND po.order_status = 'received'
            ORDER BY po.payment_due_date ASC
        ")
        ->bind(1, $supplierId)
        ->resultSet();
    }

    /**
     * Get payment history
     * @param int $supplierId Supplier ID
     * @param array $filters Filter options
     * @return array Payment history
     */
    public function getPaymentHistory($supplierId, $filters = []) {
        $where = ['po.supplier_id = ?'];
        $params = [$supplierId];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'sp.payment_date >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'sp.payment_date <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        if (!empty($filters['payment_method'])) {
            $where[] = 'sp.payment_method = ?';
            $params[] = $filters['payment_method'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                sp.*,
                po.po_number,
                po.order_date,
                po.total_amount as order_amount,
                u.name as created_by_name,
                ba.bank_name,
                ba.account_number
            FROM supplier_payments sp
            JOIN purchase_orders po ON sp.purchase_order_id = po.id
            JOIN users u ON sp.created_by = u.id
            LEFT JOIN bank_accounts ba ON sp.bank_account_id = ba.id
            WHERE {$whereClause}
            ORDER BY sp.payment_date DESC, sp.id DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }

    /**
     * Get purchase history
     * @param int $supplierId Supplier ID
     * @param array $filters Filter options
     * @return array Purchase history
     */
    public function getPurchaseHistory($supplierId, $filters = []) {
        $where = ['po.supplier_id = ?'];
        $params = [$supplierId];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'po.order_date >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'po.order_date <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        if (!empty($filters['status'])) {
            $where[] = 'po.order_status = ?';
            $params[] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                po.*,
                u.name as created_by_name,
                (
                    SELECT COUNT(DISTINCT poi.product_id)
                    FROM purchase_order_items poi
                    WHERE poi.purchase_order_id = po.id
                ) as product_count,
                (
                    SELECT SUM(poi.quantity)
                    FROM purchase_order_items poi
                    WHERE poi.purchase_order_id = po.id
                ) as total_quantity
            FROM purchase_orders po
            JOIN users u ON po.created_by = u.id
            WHERE {$whereClause}
            ORDER BY po.order_date DESC, po.id DESC
        ")
        ->bind(1, $supplierId)
        ->resultSet();
    }

    /**
     * Get product purchase history
     * @param int $productId Product ID
     * @param int|null $supplierId Optional supplier ID
     * @return array Purchase history
     */
    public function getProductPurchaseHistory($productId, $supplierId = null) {
        $where = ['poi.product_id = ?'];
        $params = [$productId];
        $index = 2;

        if ($supplierId) {
            $where[] = 'po.supplier_id = ?';
            $params[] = $supplierId;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                poi.*,
                po.po_number,
                po.order_date,
                po.order_status,
                s.name as supplier_name,
                s.company_name
            FROM purchase_order_items poi
            JOIN purchase_orders po ON poi.purchase_order_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE {$whereClause}
            ORDER BY po.order_date DESC
        ")
        ->bind(1, $productId)
        ->resultSet();
    }

    /**
     * Get payment summary
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Payment summary
     */
    public function getPaymentSummary($startDate = null, $endDate = null) {
        $where = ['1=1'];
        $params = [];
        $index = 1;

        if ($startDate) {
            $where[] = 'sp.payment_date >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'sp.payment_date <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT 
                s.id,
                s.name,
                s.company_name,
                COUNT(DISTINCT po.id) as order_count,
                SUM(po.total_amount) as total_ordered,
                SUM(po.paid_amount) as total_paid,
                SUM(po.total_amount - po.paid_amount) as total_unpaid
            FROM suppliers s
            LEFT JOIN purchase_orders po ON s.id = po.supplier_id
            LEFT JOIN supplier_payments sp ON po.id = sp.purchase_order_id
            WHERE {$whereClause}
            GROUP BY s.id, s.name, s.company_name
            ORDER BY total_ordered DESC
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->resultSet();
    }
}
