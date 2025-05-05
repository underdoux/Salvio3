<?php
/**
 * Commissions Controller
 * Handles commission management and reporting
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
     * Commission dashboard
     */
    public function index() {
        $userId = $this->isAdmin() ? null : $this->getUserId();
        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));

        // Get commission rates
        $rates = $this->commissionModel->getRates($userId);

        // Get commission statistics
        $stats = $this->commissionModel->getStats($userId ?: $this->getUserId());

        // Get pending commissions
        $pendingCommissions = $this->commissionModel->getPendingCommissions($userId ?: $this->getUserId());

        // Get commission history
        $history = $this->commissionModel->getHistory($userId ?: $this->getUserId(), $startDate, $endDate);

        $this->view->render('commissions/index', [
            'title' => 'Commissions - ' . APP_NAME,
            'rates' => $rates,
            'stats' => $stats,
            'pendingCommissions' => $pendingCommissions,
            'history' => $history,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Commission rates management
     */
    public function rates() {
        $this->requireAdmin();

        $users = $this->userModel->getActive();
        $rates = $this->commissionModel->getRates();

        $this->view->render('commissions/rates', [
            'title' => 'Commission Rates - ' . APP_NAME,
            'users' => $users,
            'rates' => $rates,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Store commission rate
     */
    public function storeRate() {
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
            'user_id' => $this->getPost('user_id'),
            'category_id' => $this->getPost('category_id'),
            'product_id' => $this->getPost('product_id'),
            'rate' => floatval($this->getPost('rate'))
        ];

        // Validate rate
        if ($data['rate'] < 0 || $data['rate'] > 100) {
            $this->setFlash('error', 'Commission rate must be between 0 and 100');
            $this->redirect('commissions/rates');
            return;
        }

        try {
            $this->db->beginTransaction();

            // Delete existing rate for the same scope
            $this->db->query("
                DELETE FROM commission_rates 
                WHERE 
                    (user_id = ? OR (user_id IS NULL AND ? IS NULL))
                    AND (category_id = ? OR (category_id IS NULL AND ? IS NULL))
                    AND (product_id = ? OR (product_id IS NULL AND ? IS NULL))
            ")
            ->bind(1, $data['user_id'])
            ->bind(2, $data['user_id'])
            ->bind(3, $data['category_id'])
            ->bind(4, $data['category_id'])
            ->bind(5, $data['product_id'])
            ->bind(6, $data['product_id'])
            ->execute();

            // Insert new rate
            $this->db->query("
                INSERT INTO commission_rates (user_id, category_id, product_id, rate)
                VALUES (?, ?, ?, ?)
            ")
            ->bind(1, $data['user_id'])
            ->bind(2, $data['category_id'])
            ->bind(3, $data['product_id'])
            ->bind(4, $data['rate'])
            ->execute();

            $this->db->commit();
            $this->setFlash('success', 'Commission rate saved successfully');
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to save commission rate');
        }

        $this->redirect('commissions/rates');
    }

    /**
     * Delete commission rate
     */
    public function deleteRate($id = null) {
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('commissions/rates');
            return;
        }

        try {
            $this->db->query("DELETE FROM commission_rates WHERE id = ?")
                ->bind(1, $id)
                ->execute();

            $this->setFlash('success', 'Commission rate deleted successfully');
        } catch (Exception $e) {
            $this->setFlash('error', 'Failed to delete commission rate');
        }

        $this->redirect('commissions/rates');
    }

    /**
     * Pay pending commissions
     */
    public function pay() {
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

        $userId = $this->getPost('user_id');
        if (!$userId) {
            $this->setFlash('error', 'User ID is required');
            $this->redirect('commissions');
            return;
        }

        if ($this->commissionModel->payPendingCommissions($userId)) {
            $this->logActivity('commission', "Paid pending commissions for user #{$userId}");
            $this->setFlash('success', 'Commissions paid successfully');
        } else {
            $this->setFlash('error', 'Failed to pay commissions');
        }

        $this->redirect('commissions');
    }

    /**
     * Commission reports
     */
    public function reports() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));
        $userId = $this->getQuery('user_id');

        // Get users for filter
        $users = $this->userModel->getActive();

        // Get commission data
        $commissions = $this->db->query("
            SELECT 
                u.name as user_name,
                COUNT(c.id) as total_sales,
                SUM(c.amount) as total_commission,
                MIN(c.created_at) as first_commission,
                MAX(c.created_at) as last_commission,
                SUM(CASE WHEN c.status = 'paid' THEN c.amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN c.status = 'pending' THEN c.amount ELSE 0 END) as pending_amount
            FROM users u
            LEFT JOIN commissions c ON u.id = c.user_id
            WHERE u.role = 'sales'
            AND (? IS NULL OR u.id = ?)
            AND (c.created_at IS NULL OR (DATE(c.created_at) BETWEEN ? AND ?))
            GROUP BY u.id
            ORDER BY total_commission DESC
        ")
        ->bind(1, $userId)
        ->bind(2, $userId)
        ->bind(3, $startDate)
        ->bind(4, $endDate)
        ->resultSet();

        $this->view->render('commissions/reports', [
            'title' => 'Commission Reports - ' . APP_NAME,
            'users' => $users,
            'commissions' => $commissions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedUser' => $userId
        ]);
    }

    /**
     * Export commission report
     */
    public function export() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));
        $userId = $this->getQuery('user_id');

        // Get commission data
        $data = $this->db->query("
            SELECT 
                u.name as user_name,
                s.invoice_number,
                s.final_amount as sale_amount,
                c.amount as commission_amount,
                c.status,
                c.created_at,
                c.paid_at,
                cu.name as customer_name
            FROM commissions c
            JOIN users u ON c.user_id = u.id
            JOIN sales s ON c.sale_id = s.id
            LEFT JOIN customers cu ON s.customer_id = cu.id
            WHERE (? IS NULL OR c.user_id = ?)
            AND DATE(c.created_at) BETWEEN ? AND ?
            ORDER BY c.created_at DESC
        ")
        ->bind(1, $userId)
        ->bind(2, $userId)
        ->bind(3, $startDate)
        ->bind(4, $endDate)
        ->resultSet();

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="commission_report.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Sales Person',
            'Invoice',
            'Customer',
            'Sale Amount',
            'Commission',
            'Status',
            'Sale Date',
            'Payment Date'
        ]);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['user_name'],
                $row['invoice_number'],
                $row['customer_name'],
                $row['sale_amount'],
                $row['commission_amount'],
                ucfirst($row['status']),
                formatDate($row['created_at']),
                $row['paid_at'] ? formatDate($row['paid_at']) : '-'
            ]);
        }

        fclose($output);
        exit;
    }
}
