<?php
/**
 * Price Controller
 * Handles price adjustments and history
 */
class PriceController extends Controller {
    private $priceHistoryModel;
    private $productModel;
    private $discountRuleModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->priceHistoryModel = $this->model('PriceHistory');
        $this->productModel = $this->model('Product');
        $this->discountRuleModel = $this->model('DiscountRule');
    }

    /**
     * Show price history
     */
    public function index() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        $summary = $this->priceHistoryModel->getSummary($startDate, $endDate);

        $this->view->render('prices/index', [
            'title' => 'Price History - ' . APP_NAME,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Show price adjustment form
     */
    public function adjust($productId = null) {
        $this->requireAdmin();

        if (!$productId) {
            $this->redirect('products');
            return;
        }

        $product = $this->productModel->getById($productId);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        $history = $this->priceHistoryModel->getByProduct($productId);

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
    public function update($productId = null) {
        $this->requireAdmin();

        if (!$this->isPost() || !$productId) {
            $this->redirect('products');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('prices/adjust/' . $productId);
            return;
        }

        $product = $this->productModel->getById($productId);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        $data = [
            'product_id' => $productId,
            'old_price' => $product['selling_price'],
            'new_price' => floatval($this->getPost('new_price')),
            'change_type' => $this->getPost('change_type'),
            'reason' => trim($this->getPost('reason')),
            'user_id' => $this->getUserId()
        ];

        // Validate price change
        $validation = $this->priceHistoryModel->validatePriceChange(
            $data['new_price'],
            $product,
            $this->getUserRole()
        );

        if (!$validation['valid']) {
            $this->setFlash('error', $validation['message']);
            $this->redirect('prices/adjust/' . $productId);
            return;
        }

        // Record price change
        if ($this->priceHistoryModel->recordChange($data)) {
            $this->logActivity('price', "Updated price for product #{$productId}");
            $this->setFlash('success', 'Price updated successfully');
            $this->redirect('products/view/' . $productId);
        } else {
            $this->setFlash('error', 'Failed to update price');
            $this->redirect('prices/adjust/' . $productId);
        }
    }

    /**
     * Show discount rules
     */
    public function discounts() {
        $this->requireAdmin();

        $rules = $this->discountRuleModel->getAll();
        $summary = $this->discountRuleModel->getDiscountSummary();

        $this->view->render('prices/discounts', [
            'title' => 'Discount Rules - ' . APP_NAME,
            'rules' => $rules,
            'summary' => $summary,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Create discount rule
     */
    public function createDiscount() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('prices/discounts');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('prices/discounts');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'type' => $this->getPost('type'),
            'value' => floatval($this->getPost('value')),
            'min_purchase' => floatval($this->getPost('min_purchase')) ?: null,
            'max_discount' => floatval($this->getPost('max_discount')) ?: null,
            'start_date' => $this->getPost('start_date'),
            'end_date' => $this->getPost('end_date'),
            'allowed_roles' => json_encode($this->getPost('allowed_roles', [])),
            'status' => 'active'
        ];

        if ($this->discountRuleModel->createRule($data)) {
            $this->logActivity('discount', "Created discount rule: {$data['name']}");
            $this->setFlash('success', 'Discount rule created successfully');
        } else {
            $this->setFlash('error', 'Failed to create discount rule');
        }

        $this->redirect('prices/discounts');
    }

    /**
     * Update discount rule
     */
    public function updateDiscount($id = null) {
        $this->requireAdmin();

        if (!$this->isPost() || !$id) {
            $this->redirect('prices/discounts');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('prices/discounts');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'type' => $this->getPost('type'),
            'value' => floatval($this->getPost('value')),
            'min_purchase' => floatval($this->getPost('min_purchase')) ?: null,
            'max_discount' => floatval($this->getPost('max_discount')) ?: null,
            'start_date' => $this->getPost('start_date'),
            'end_date' => $this->getPost('end_date'),
            'allowed_roles' => json_encode($this->getPost('allowed_roles', [])),
            'status' => $this->getPost('status')
        ];

        if ($this->discountRuleModel->update($id, $data)) {
            $this->logActivity('discount', "Updated discount rule #{$id}");
            $this->setFlash('success', 'Discount rule updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update discount rule');
        }

        $this->redirect('prices/discounts');
    }

    /**
     * Get best discount (AJAX)
     */
    public function getBestDiscount() {
        if (!$this->isAjax()) {
            $this->redirect('products');
            return;
        }

        $amount = floatval($this->getQuery('amount'));
        if (!$amount) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid amount']);
            return;
        }

        $discount = $this->discountRuleModel->getBestDiscount($amount, $this->getUserRole());
        
        if ($discount) {
            $discountAmount = $this->discountRuleModel->calculateDiscount($amount, $discount);
            $this->jsonResponse([
                'success' => true,
                'discount' => [
                    'rule' => $discount,
                    'amount' => $discountAmount,
                    'final_price' => $amount - $discountAmount
                ]
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No applicable discount found'
            ]);
        }
    }
}
