<?php
/**
 * Price Controller
 * Handles price adjustments and history tracking
 */
class PriceController extends Controller {
    private $priceHistoryModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->priceHistoryModel = $this->model('PriceHistory');
        $this->productModel = $this->model('Product');
    }

    /**
     * Price history dashboard
     */
    public function index() {
        $this->requireAdmin();

        // Get recent price changes
        $recentChanges = $this->priceHistoryModel->getRecentChanges();

        // Get price change statistics
        $stats = $this->priceHistoryModel->getStats(
            date('Y-m-01'), // Start of current month
            date('Y-m-d')  // Today
        );

        // Get category trends
        $categoryTrends = $this->priceHistoryModel->getCategoryTrends(
            date('Y-m-01'),
            date('Y-m-d')
        );

        // Get products with frequent changes
        $frequentChanges = $this->priceHistoryModel->getFrequentChanges();

        $this->view->render('prices/index', [
            'title' => 'Price Management - ' . APP_NAME,
            'recentChanges' => $recentChanges,
            'stats' => $stats,
            'categoryTrends' => $categoryTrends,
            'frequentChanges' => $frequentChanges
        ]);
    }

    /**
     * Show price adjustment form
     */
    public function adjust($id = null) {
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('products');
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        // Get price history
        $history = $this->priceHistoryModel->getProductHistory($id);

        $this->view->render('prices/adjust', [
            'title' => 'Adjust Price - ' . APP_NAME,
            'product' => $product,
            'history' => $history,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process price adjustment
     */
    public function update($id = null) {
        $this->requireAdmin();

        if (!$this->isPost() || !$id) {
            $this->redirect('products');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('prices/adjust/' . $id);
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        $newPrice = floatval($this->getPost('price'));
        $reason = trim($this->getPost('reason'));
        $changeType = $this->getPost('change_type', 'regular');

        // Validate input
        if ($newPrice <= 0) {
            $this->setFlash('error', 'Price must be greater than 0');
            $this->redirect('prices/adjust/' . $id);
            return;
        }

        if (empty($reason)) {
            $this->setFlash('error', 'Please provide a reason for the price change');
            $this->redirect('prices/adjust/' . $id);
            return;
        }

        // Validate price change
        $validation = $this->priceHistoryModel->validateChange(
            $product['selling_price'],
            $newPrice,
            $this->getUserId()
        );

        if (!$validation['valid']) {
            $this->setFlash('error', $validation['message']);
            $this->redirect('prices/adjust/' . $id);
            return;
        }

        // Log price change
        if ($this->priceHistoryModel->logChange(
            $id,
            $product['selling_price'],
            $newPrice,
            $reason,
            $changeType,
            $this->getUserId()
        )) {
            $this->logActivity('price', "Updated price for {$product['name']} from {$product['selling_price']} to {$newPrice}");
            $this->setFlash('success', 'Price updated successfully');
            $this->redirect('prices/adjust/' . $id);
        } else {
            $this->setFlash('error', 'Failed to update price');
            $this->redirect('prices/adjust/' . $id);
        }
    }

    /**
     * Bulk price adjustment
     */
    public function bulkAdjust() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('prices');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('prices');
            return;
        }

        $type = $this->getPost('adjustment_type');
        $value = floatval($this->getPost('adjustment_value'));
        $categoryId = $this->getPost('category_id');
        $reason = trim($this->getPost('reason'));

        if (empty($reason)) {
            $this->setFlash('error', 'Please provide a reason for the price changes');
            $this->redirect('prices');
            return;
        }

        try {
            $this->db->beginTransaction();

            // Get products to adjust
            $sql = "SELECT id, selling_price FROM products WHERE status = 'active'";
            $params = [];

            if ($categoryId) {
                $sql .= " AND category_id = ?";
                $params[] = $categoryId;
            }

            $query = $this->db->query($sql);
            foreach ($params as $i => $param) {
                $query->bind($i + 1, $param);
            }
            $products = $query->resultSet();

            $updated = 0;
            foreach ($products as $product) {
                // Calculate new price
                $newPrice = $type === 'percentage' 
                    ? $product['selling_price'] * (1 + ($value / 100))
                    : $product['selling_price'] + $value;

                // Validate price change
                $validation = $this->priceHistoryModel->validateChange(
                    $product['selling_price'],
                    $newPrice,
                    $this->getUserId()
                );

                if ($validation['valid']) {
                    // Log price change
                    if ($this->priceHistoryModel->logChange(
                        $product['id'],
                        $product['selling_price'],
                        $newPrice,
                        $reason . ' (Bulk Update)',
                        'bulk',
                        $this->getUserId()
                    )) {
                        $updated++;
                    }
                }
            }

            $this->db->commit();
            $this->logActivity('price', "Bulk price update: {$updated} products updated");
            $this->setFlash('success', "{$updated} products updated successfully");
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to update prices: ' . $e->getMessage());
        }

        $this->redirect('prices');
    }

    /**
     * Export price history
     */
    public function export() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));
        $categoryId = $this->getQuery('category_id');

        // Get price history data
        $sql = "
            SELECT 
                p.name as product_name,
                p.sku,
                c.name as category_name,
                ph.old_price,
                ph.new_price,
                ph.change_type,
                ph.reason,
                u.name as user_name,
                ph.created_at
            FROM price_history ph
            JOIN products p ON ph.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON ph.user_id = u.id
            WHERE DATE(ph.created_at) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];

        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY ph.created_at DESC";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $data = $query->resultSet();

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="price_history.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Product',
            'SKU',
            'Category',
            'Old Price',
            'New Price',
            'Change Type',
            'Reason',
            'Updated by',
            'Date'
        ]);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['product_name'],
                $row['sku'],
                $row['category_name'],
                $row['old_price'],
                $row['new_price'],
                ucfirst($row['change_type']),
                $row['reason'],
                $row['user_name'],
                formatDate($row['created_at'])
            ]);
        }

        fclose($output);
        exit;
    }
}
