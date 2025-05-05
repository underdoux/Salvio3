<?php
/**
 * StockReceipt Model
 * Handles stock receipts and inventory updates
 */
class StockReceipt extends Model {
    protected $table = 'stock_receipts';
    protected $fillable = [
        'purchase_order_id',
        'receipt_date',
        'notes',
        'created_by'
    ];

    /**
     * Create receipt with items
     * @param array $data Receipt data
     * @param array $items Receipt items
     * @return int|false Receipt ID or false on failure
     */
    public function createWithItems($data, $items) {
        try {
            $this->db->beginTransaction();

            // Create receipt
            $receiptId = $this->create($data);
            if (!$receiptId) {
                throw new Exception("Failed to create stock receipt");
            }

            // Process items
            foreach ($items as $item) {
                // Validate remaining quantity
                $poItem = $this->db->query("
                    SELECT 
                        poi.*,
                        p.name as product_name
                    FROM purchase_order_items poi
                    JOIN products p ON poi.product_id = p.id
                    WHERE poi.id = ?
                ")
                ->bind(1, $item['purchase_order_item_id'])
                ->single();

                $remainingQuantity = $poItem['quantity'] - $poItem['received_quantity'];
                if ($item['quantity'] > $remainingQuantity) {
                    throw new Exception("Cannot receive more than ordered quantity for {$poItem['product_name']}");
                }

                // Create receipt item
                $this->db->query("
                    INSERT INTO stock_receipt_items (
                        stock_receipt_id,
                        purchase_order_item_id,
                        quantity,
                        notes
                    ) VALUES (?, ?, ?, ?)
                ")
                ->bind(1, $receiptId)
                ->bind(2, $item['purchase_order_item_id'])
                ->bind(3, $item['quantity'])
                ->bind(4, $item['notes'] ?? null)
                ->execute();
            }

            $this->db->commit();
            return $receiptId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Stock Receipt Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get receipt with details
     * @param int $id Receipt ID
     * @return array|null Receipt data with details
     */
    public function getWithDetails($id) {
        $receipt = $this->getById($id);
        if (!$receipt) {
            return null;
        }

        // Get receipt items
        $items = $this->db->query("
            SELECT 
                sri.*,
                poi.product_id,
                poi.unit_price,
                p.name as product_name,
                p.sku
            FROM stock_receipt_items sri
            JOIN purchase_order_items poi ON sri.purchase_order_item_id = poi.id
            JOIN products p ON poi.product_id = p.id
            WHERE sri.stock_receipt_id = ?
        ")
        ->bind(1, $id)
        ->resultSet();

        // Get purchase order details
        $order = $this->db->query("
            SELECT 
                po.*,
                s.name as supplier_name,
                s.company_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE po.id = ?
        ")
        ->bind(1, $receipt['purchase_order_id'])
        ->single();

        // Get stock movements
        $movements = $this->db->query("
            SELECT 
                sm.*,
                p.name as product_name,
                p.sku
            FROM stock_movements sm
            JOIN products p ON sm.product_id = p.id
            WHERE sm.reference_type = 'receipt'
            AND sm.reference_id = ?
        ")
        ->bind(1, $id)
        ->resultSet();

        return array_merge($receipt, [
            'items' => $items,
            'order' => $order,
            'movements' => $movements
        ]);
    }

    /**
     * Get receipts by purchase order
     * @param int $orderId Purchase order ID
     * @return array Receipts data
     */
    public function getByOrder($orderId) {
        return $this->db->query("
            SELECT 
                sr.*,
                u.name as created_by_name,
                (
                    SELECT COUNT(DISTINCT poi.product_id)
                    FROM stock_receipt_items sri
                    JOIN purchase_order_items poi ON sri.purchase_order_item_id = poi.id
                    WHERE sri.stock_receipt_id = sr.id
                ) as product_count,
                (
                    SELECT SUM(sri.quantity)
                    FROM stock_receipt_items sri
                    WHERE sri.stock_receipt_id = sr.id
                ) as total_quantity
            FROM {$this->table} sr
            JOIN users u ON sr.created_by = u.id
            WHERE sr.purchase_order_id = ?
            ORDER BY sr.receipt_date DESC
        ")
        ->bind(1, $orderId)
        ->resultSet();
    }

    /**
     * Get receipt items by product
     * @param int $productId Product ID
     * @param array $filters Filter options
     * @return array Receipt items
     */
    public function getItemsByProduct($productId, $filters = []) {
        $where = ['poi.product_id = ?'];
        $params = [$productId];
        $index = 2;

        if (!empty($filters['start_date'])) {
            $where[] = 'sr.receipt_date >= ?';
            $params[] = $filters['start_date'];
            $index++;
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'sr.receipt_date <= ?';
            $params[] = $filters['end_date'];
            $index++;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                sri.*,
                sr.receipt_date,
                po.po_number,
                s.name as supplier_name,
                s.company_name,
                poi.unit_price
            FROM stock_receipt_items sri
            JOIN {$this->table} sr ON sri.stock_receipt_id = sr.id
            JOIN purchase_order_items poi ON sri.purchase_order_item_id = poi.id
            JOIN purchase_orders po ON sr.purchase_order_id = po.id
            JOIN suppliers s ON po.supplier_id = s.id
            WHERE {$whereClause}
            ORDER BY sr.receipt_date DESC
        ")
        ->bind(1, $productId)
        ->resultSet();
    }

    /**
     * Get receipt summary
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Summary data
     */
    public function getSummary($startDate = null, $endDate = null) {
        $where = ['1=1'];
        $params = [];
        $index = 1;

        if ($startDate) {
            $where[] = 'sr.receipt_date >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'sr.receipt_date <= ?';
            $params[] = $endDate;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query("
            SELECT 
                COUNT(DISTINCT sr.id) as receipt_count,
                COUNT(DISTINCT sr.purchase_order_id) as order_count,
                COUNT(DISTINCT poi.product_id) as product_count,
                SUM(sri.quantity) as total_quantity,
                SUM(sri.quantity * poi.unit_price) as total_value
            FROM {$this->table} sr
            JOIN stock_receipt_items sri ON sr.id = sri.stock_receipt_id
            JOIN purchase_order_items poi ON sri.purchase_order_item_id = poi.id
            WHERE {$whereClause}
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->single();
    }
}
