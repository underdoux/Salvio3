<?php
/**
 * Dashboard Controller
 * Handles dashboard functionality and statistics
 */
class DashboardController extends Controller {
    private $saleModel;
    private $productModel;
    private $customerModel;
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();

        // Initialize models
        $this->saleModel = $this->model('Sale');
        $this->productModel = $this->model('Product');
        $this->customerModel = $this->model('Customer');
        $this->userModel = $this->model('User');
    }

    /**
     * Display dashboard
     */
    public function index() {
        // Get date range
        $startDate = $this->getQuery('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));

        // Get sales statistics
        $salesStats = $this->saleModel->getStatistics($startDate, $endDate);
        
        // Get top selling products
        $topProducts = $this->productModel->getTopSelling($startDate, $endDate, 5);
        
        // Get low stock products
        $lowStockProducts = $this->productModel->getLowStock(10);
        
        // Get recent sales
        $recentSales = $this->saleModel->getRecent(5);
        
        // Get customer statistics
        $customerStats = $this->customerModel->getStatistics($startDate, $endDate);

        // Get sales performance by user (if admin)
        $salesPerformance = [];
        if ($this->auth->isAdmin()) {
            $salesPerformance = $this->userModel->getSalesPerformance($startDate, $endDate);
        }

        // Get payment statistics
        $paymentStats = $this->saleModel->getPaymentStatistics($startDate, $endDate);

        // Get sales chart data
        $salesChart = $this->getSalesChartData($startDate, $endDate);

        // Get notifications
        $notifications = $this->getNotifications();

        $this->view->render('dashboard/index', [
            'title' => 'Dashboard - ' . APP_NAME,
            'salesStats' => $salesStats,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'recentSales' => $recentSales,
            'customerStats' => $customerStats,
            'salesPerformance' => $salesPerformance,
            'paymentStats' => $paymentStats,
            'salesChart' => $salesChart,
            'notifications' => $notifications,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    /**
     * Get sales chart data
     */
    private function getSalesChartData($startDate, $endDate) {
        $data = $this->saleModel->getDailySales($startDate, $endDate);
        
        $labels = [];
        $values = [];
        $currentDate = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        
        // Initialize all dates with 0
        while ($currentDate <= $endTimestamp) {
            $date = date('Y-m-d', $currentDate);
            $labels[] = date('M d', $currentDate);
            $values[$date] = 0;
            $currentDate = strtotime('+1 day', $currentDate);
        }

        // Fill in actual values
        foreach ($data as $row) {
            $values[$row['date']] = (float)$row['total'];
        }

        return [
            'labels' => $labels,
            'values' => array_values($values)
        ];
    }

    /**
     * Get system notifications
     */
    private function getNotifications() {
        $notifications = [];

        // Check low stock products
        $lowStock = $this->productModel->getLowStock(5);
        foreach ($lowStock as $product) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'box',
                'message' => "Low stock alert: {$product['name']} ({$product['stock']} remaining)",
                'link' => "products/view/{$product['id']}"
            ];
        }

        // Check overdue payments
        $overduePayments = $this->saleModel->getOverduePayments();
        foreach ($overduePayments as $payment) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => 'money-bill',
                'message' => "Overdue payment: Invoice #{$payment['invoice_number']} ({$payment['days_overdue']} days)",
                'link' => "sales/view/{$payment['id']}"
            ];
        }

        // Check expiring products
        $expiringProducts = $this->productModel->getExpiring(30); // 30 days
        foreach ($expiringProducts as $product) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'calendar',
                'message' => "Product expiring soon: {$product['name']} ({$product['days_until_expiry']} days)",
                'link' => "products/view/{$product['id']}"
            ];
        }

        return $notifications;
    }

    /**
     * Get sales report data (AJAX)
     */
    public function getSalesReport() {
        if (!$this->isPost()) {
            $this->error('Invalid request method');
        }

        $startDate = $this->getPost('start_date');
        $endDate = $this->getPost('end_date');
        
        if (!$startDate || !$endDate) {
            $this->error('Invalid date range');
        }

        $data = $this->saleModel->getDetailedReport($startDate, $endDate);
        $this->success($data);
    }

    /**
     * Export dashboard data
     */
    public function export() {
        $format = $this->getQuery('format', 'pdf');
        $startDate = $this->getQuery('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));

        // Get report data
        $data = [
            'salesStats' => $this->saleModel->getStatistics($startDate, $endDate),
            'topProducts' => $this->productModel->getTopSelling($startDate, $endDate, 10),
            'customerStats' => $this->customerModel->getStatistics($startDate, $endDate),
            'paymentStats' => $this->saleModel->getPaymentStatistics($startDate, $endDate)
        ];

        if ($format === 'pdf') {
            $this->exportPDF($data, $startDate, $endDate);
        } else if ($format === 'excel') {
            $this->exportExcel($data, $startDate, $endDate);
        } else {
            $this->error('Invalid export format');
        }
    }

    /**
     * Export data as PDF
     */
    private function exportPDF($data, $startDate, $endDate) {
        require_once 'helpers/pdf_generator.php';
        
        $pdf = new PDFGenerator();
        $pdf->generateDashboardReport($data, $startDate, $endDate);
        $pdf->output('Dashboard_Report_' . date('Y-m-d') . '.pdf', 'D');
    }

    /**
     * Export data as Excel
     */
    private function exportExcel($data, $startDate, $endDate) {
        require_once 'helpers/excel_generator.php';
        
        $excel = new ExcelGenerator();
        $excel->generateDashboardReport($data, $startDate, $endDate);
        $excel->output('Dashboard_Report_' . date('Y-m-d') . '.xlsx');
    }
}
