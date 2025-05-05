<?php
/**
 * PurchaseOrder Model
 * Handles purchase orders and stock receipts
 */
class PurchaseOrder extends Model {
    protected $table = 'purchase_orders';
    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_date',
        'delivery_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'paid_amount',
        'payment_status',
        'order_status',
        'payment_due_date',
        'notes',
        'created_by'
    ];

    /**
     * Create purchase order with items
     * @param array $data Purchase order data
     * @param array $items Order items
     * @return int|false Purchase order ID or false on failure
     */
    public function createWithItems($data, $items) {
        try {
            $this->db->beginTransaction();

            // Generate PO number
            $data['po_number'] = $this->generatePoNumber();

            // Create purchase order
            $orderId = $this->create($data);
            if (!$orderId) {
                throw new Exception("Failed to create purchase order");
            }

            // Create order items
            foreach ($items as $item) {
                $item['purchase_order_id'] = $orderId;
                $item['total_amount'] = ($item['quantity'] * $item['unit_price']) - 
                    ($item['discount_amount'] ?? 0);

                $this->db->query("
                    INSERT INTO purchase_order_items (
                        purchase_order_id,
                        product_id,
                        quantity,
                        unit_price,
                        tax_rate,
                        discount_amount,
                        total_amount,
                        notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")
                ->bind(1, $orderId)
                ->bind(2, $item['product_id'])
                ->bind(3, $item['quantity'])
                ->bind(4, $item['unit_price'])
                ->bind(5, $item['tax_rate'] ?? 0)
                ->bind(6, $item['discount_amount'] ?? 0)
                ->bind(7, $item['total_amount'])
                ->bind(8, $item['notes'] ?? null)
                ->execute();
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Purchase Order Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate PO number
     * @return string PO number
     */
    private function generatePoNumber() {
        $prefix = 'PO';
        $year = date('Y');
        $month = date('m');

        $lastPo = $this->db->query("
            SELECT po_number 
            FROM {$this->table}
            WHERE po_number LIKE ?
            ORDER BY id DESC
            LIMIT 1
        ")
        ->bind(1, "{$prefix}{$year}{$month}%")
        ->single();

        if ($lastPo) {
            $sequence = intval(substr($lastPo['po_number'], -4)) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf("%s%s%s%04d", $prefix, $year, $month, $sequence);
    }

    /**
     * Record stock receipt
     * @param int $orderId Purchase order ID
     * @param array $items Receipt items
     * @param array $data Receipt data
     * @return int|false Receipt ID or false on failure
     */
    public function recordReceipt($orderId, $items, $data) {
        try {
            $this->db->beginTransaction();

            // Create stock receipt
            $receiptId = $this->db->query("
                INSERT INTO stock_receipts (
                    purchase_order_id,
                    receipt_date,
                    notes,
                    created_by
                ) VALUES (?, ?, ?, ?)
            ")
            ->bind(1, $orderId)
            ->bind(2, $data['receipt_date'])
            ->bind(3, $data['notes'] ?? null)
            ->bind(4, $data['created_by'])
            ->execute();

            if (!$receiptId) {
                throw new Exception("Failed to create stock receipt");
            }

            // Record receipt items
            foreach ($items as $item) {
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

            // Update purchase order status if all items received
            $this->updateOrderStatus($orderId);

            $this->db->commit();
            return $receiptId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Stock Receipt Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record supplier payment
     * @param array $data Payment data
     * @return bool Success status
     */
    public function recordPayment($data) {
        try {
            $this->db->beginTransaction();

            // Create payment record
            $this->db->query("
                INSERT INTO supplier_payments (
                    purchase_order_id,
                    amount,
                    payment_date,
                    payment_method,
                    reference_number,
                    bank_account_id,
                    notes,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")
            ->bind(1, $data['purchase_order_id'])
            ->bind(2, $data['amount'])
            ->bind(3, $data['payment_date'])
            ->bind(4, $data['payment_method'])
            ->bind(5, $data['reference_number'])
            ->bind(6, $data['bank_account_id'])
            ->bind(7, $data['notes'])
            ->bind(8, $data['created_by'])
            ->execute();

            // Update purchase order paid amount and status
            $order = $this->getById($data['purchase_order_id']);
            $newPaidAmount = $order['paid_amount'] + $data['amount'];
            $paymentStatus = $newPaidAmount >= $order['total_amount'] ? 'paid' : 
                ($newPaidAmount > 0 ? 'partial' : 'unpaid');

            $this->update($data['purchase_order_id'], [
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus
            ]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment Recording Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order status based on received items
     * @param int $orderId Purchase order ID
     */
    private function updateOrderStatus($orderId) {
        $items = $this->db->query("
            SELECT 
                quantity,
                received_quantity
            FROM purchase_order_items
            WHERE purchase_order_id = ?
        ")
        ->bind(1, $orderId)
        ->resultSet();

        $allReceived = true;
        foreach ($items as $item) {
            if ($item['received_quantity'] < $item['quantity']) {
                $allReceived = false;
                break;
            }
        }

        if ($allReceived) {
            $this->update($orderId, [
                'order_status' => 'received',
                'delivery_date' => date('Y-m-d')
            ]);
        }
    }

    /**
     * Get order with items and receipts
     * @param int $id Order ID
     * @return array|null Order data with items and receipts
     */
    public function getWithDetails($id) {
        $order = $this->getById($id);
        if (!$order) {
            return null;
        }

        // Get order items
        $items = $this->db->query("
            SELECT 
                poi.*,
                p.name as product_name,
                p.sku
            FROM purchase_order_items poi
            JOIN products p ON poi.product_id = p.id
            WHERE poi.purchase_order_id = ?
        ")
        ->bind(1, $id)
        ->resultSet();

        // Get receipts
        $receipts = $this->db->query("
            SELECT 
                sr.*,
                u.name as created_by_name,
                (
                    SELECT SUM(sri.quantity)
                    FROM stock_receipt_items sri
                    WHERE sri.stock_receipt_id = sr.id
                ) as total_quantity
            FROM stock_receipts sr
            JOIN users u ON sr.created_by = u.id
            WHERE sr.purchase_order_id = ?
            ORDER BY sr.receipt_date DESC
        ")
        ->bind(1, $id)
        ->resultSet();

        // Get payments
        $payments = $this->db->query("
            SELECT 
                sp.*,
                u.name as created_by_name,
                ba.bank_name,
                ba.account_number
            FROM supplier_payments sp
            JOIN users u ON sp.created_by = u.id
            LEFT JOIN bank_accounts ba ON sp.bank_account_id = ba.id
            WHERE sp.purchase_order_id = ?
            ORDER BY sp.payment_date DESC
        ")
        ->bind(1, $id)
        ->resultSet();

        return array_merge($order, [
            'items' => $items,
            'receipts' => $receipts,
            'payments' => $payments
        ]);
    }
}
