<?php
/**
 * Commissions Controller
 * Handles commission management and payments
 */
class CommissionsController extends Controller {
    private $commissionModel;
    private $userModel;
    private $saleModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->commissionModel = $this->model('Commission');
        $this->userModel = $this->model('User');
        $this->saleModel = $this->model('Sale');
    }

    /**
     * Show commission dashboard
     */
    public function index() {
        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        
        if ($this->isAdmin()) {
            // Admin sees all commissions
            $users = $this->userModel->getCommissionEligible();
            $summaries = [];
            
            foreach ($users as $user) {
                $summaries[$user['id']] = $this->commissionModel->getUserSummary(
                    $user['id'],
                    $startDate,
                    $endDate
                );
            }
        } else {
            // Sales staff sees only their commissions
            $users = [$this->getUser()];
            $summaries = [
                $this->getUserId() => $this->commissionModel->getUserSummary(
                    $this->getUserId(),
                    $startDate,
                    $endDate
                )
            ];
        }

        $this->view->render('commissions/index', [
            'title' => 'Commissions - ' . APP_NAME,
            'users' => $users,
            'summaries' => $summaries,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Show commission rates
     */
    public function rates() {
        $this->requireAdmin();

        $globalRate = $this->db->query("
            SELECT * FROM commission_rates
            WHERE type = 'global'
            AND status = 'active'
            LIMIT 1
        ")->single();

        $categoryRates = $this->db->query("
            SELECT 
                cr.*,
                c.name as category_name
            FROM commission_rates cr
            JOIN categories c ON cr.reference_id = c.id
            WHERE cr.type = 'category'
            AND cr.status = 'active'
            ORDER BY c.name
        ")->resultSet();

        $productRates = $this->db->query("
            SELECT 
                cr.*,
                p.name as product_name,
                p.sku
            FROM commission_rates cr
            JOIN products p ON cr.reference_id = p.id
            WHERE cr.type = 'product'
            AND cr.status = 'active'
            ORDER BY p.name
        ")->resultSet();

        $this->view->render('commissions/rates', [
            'title' => 'Commission Rates - ' . APP_NAME,
            'globalRate' => $globalRate,
            'categoryRates' => $categoryRates,
            'productRates' => $productRates,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Create commission rate
     */
    public function createRate() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('commissions/rates');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('commissions/rates');
            return;
        }

        $data = [
            'type' => $this->getPost('type'),
            'reference_id' => $this->getPost('reference_id'),
            'rate' => floatval($this->getPost('rate')),
            'min_sale_amount' => floatval($this->getPost('min_sale_amount')) ?: null
        ];

        if ($this->commissionModel->createRate($data)) {
            $this->logActivity('commission', "Created new commission rate");
            $this->setFlash('success', 'Commission rate created successfully');
        } else {
            $this->setFlash('error', 'Failed to create commission rate');
        }

        $this->redirect('commissions/rates');
    }

    /**
     * Show commission reports
     */
    public function reports() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        $userId = $this->getQuery('user_id');

        $where = ['1=1'];
        $params = [];
        $index = 1;

        if ($startDate) {
            $where[] = 'DATE(cc.created_at) >= ?';
            $params[] = $startDate;
            $index++;
        }

        if ($endDate) {
            $where[] = 'DATE(cc.created_at) <= ?';
            $params[] = $endDate;
            $index++;
        }

        if ($userId) {
            $where[] = 'cc.user_id = ?';
            $params[] = $userId;
        }

        $whereClause = implode(' AND ', $where);

        // Get commission data
        $commissions = $this->db->query("
            SELECT 
                cc.*,
                u.name as user_name,
                s.invoice_number,
                cr.type as rate_type,
                CASE 
                    WHEN cr.type = 'product' THEN p.name
                    WHEN cr.type = 'category' THEN c.name
                    ELSE 'Global'
                END as reference_name
            FROM commission_calculations cc
            JOIN users u ON cc.user_id = u.id
            JOIN sales s ON cc.sale_id = s.id
            JOIN commission_rates cr ON cc.commission_rate_id = cr.id
            LEFT JOIN products p ON cr.type = 'product' AND cr.reference_id = p.id
            LEFT JOIN categories c ON cr.type = 'category' AND cr.reference_id = c.id
            WHERE {$whereClause}
            ORDER BY cc.created_at DESC
        ");

        foreach ($params as $i => $param) {
            $commissions->bind($i + 1, $param);
        }

        $this->view->render('commissions/reports', [
            'title' => 'Commission Reports - ' . APP_NAME,
            'commissions' => $commissions->resultSet(),
            'users' => $this->userModel->getCommissionEligible(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedUser' => $userId
        ]);
    }

    /**
     * Process commission payments
     */
    public function processPayments() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('commissions');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('commissions');
            return;
        }

        $commissionIds = $this->getPost('commission_ids', []);
        if (empty($commissionIds)) {
            $this->setFlash('error', 'No commissions selected');
            $this->redirect('commissions');
            return;
        }

        $paymentDate = $this->getPost('payment_date', date('Y-m-d'));
        $notes = trim($this->getPost('notes'));

        if ($this->commissionModel->processPayment($commissionIds, $paymentDate, $notes)) {
            $this->logActivity('commission', "Processed commission payments");
            $this->setFlash('success', 'Commission payments processed successfully');
        } else {
            $this->setFlash('error', 'Failed to process commission payments');
        }

        $this->redirect('commissions');
    }

    /**
     * Get commission details (AJAX)
     */
    public function getDetails() {
        if (!$this->isAjax()) {
            $this->redirect('commissions');
            return;
        }

        $saleId = $this->getQuery('sale_id');
        if (!$saleId) {
            $this->jsonResponse(['success' => false, 'message' => 'Sale ID required']);
            return;
        }

        $details = $this->db->query("
            SELECT 
                cc.*,
                cr.type as rate_type,
                cr.rate,
                s.invoice_number,
                u.name as user_name
            FROM commission_calculations cc
            JOIN commission_rates cr ON cc.commission_rate_id = cr.id
            JOIN sales s ON cc.sale_id = s.id
            JOIN users u ON cc.user_id = u.id
            WHERE cc.sale_id = ?
        ")
        ->bind(1, $saleId)
        ->resultSet();

        $this->jsonResponse([
            'success' => true,
            'data' => $details
        ]);
    }
}
