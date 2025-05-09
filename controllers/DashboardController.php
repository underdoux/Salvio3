<?php
/**
 * Dashboard Controller
 * Handles dashboard operations
 */
class DashboardController extends Controller {
    private $saleModel;
    private $productModel;
    private $customerModel;

    public function __construct() {
        parent::__construct();

        // Require normal authentication (remove temporary login bypass)
        $this->requireAuth();
        
        // Load required models
        $this->saleModel = $this->model('Sale');
        $this->productModel = $this->model('Product');
        $this->customerModel = $this->model('Customer');
    }

    /**
     * Dashboard index page
     */
    public function index() {
        // Get today's sales
        $todaySales = $this->saleModel->getTodaySales();
        $todayOrders = $this->saleModel->getTodayOrderCount();

        // Get product stats
        $totalProducts = $this->productModel->count("status = 'active'");
        $lowStock = $this->productModel->count("stock <= min_stock AND status = 'active'");

        // Get customer stats
        $totalCustomers = $this->customerModel->count("status = 'active'");
        $newCustomers = $this->customerModel->getNewCustomersCount();

        // Get monthly revenue
        $monthlyRevenue = $this->saleModel->getMonthlyRevenue();
        $lastMonthRevenue = $this->saleModel->getLastMonthRevenue();

        // Calculate revenue change percentage
        $revenueChange = 0;
        if ($lastMonthRevenue > 0) {
            $revenueChange = (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        }

        // Get recent sales
        $recentSales = $this->saleModel->getRecentSales(5);

        // Get low stock products
        $lowStockProducts = $this->productModel->getLowStockProducts(5);

        // Pass data to view
        $this->view('dashboard/index', [
            'title' => 'Dashboard - ' . APP_NAME,
            'todaySales' => $todaySales,
            'todayOrders' => $todayOrders,
            'totalProducts' => $totalProducts,
            'lowStock' => $lowStock,
            'totalCustomers' => $totalCustomers,
            'newCustomers' => $newCustomers,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueChange' => $revenueChange,
            'recentSales' => $recentSales,
            'lowStockProducts' => $lowStockProducts
        ]);
    }

    /**
     * Get sales chart data
     */
    public function salesChart() {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
            return;
        }

        $data = $this->saleModel->getSalesChartData();
        $this->json($data);
    }

    /**
     * Get top selling products
     */
    public function topProducts() {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
            return;
        }

        $data = $this->productModel->getTopSellingProducts();
        $this->json($data);
    }

    /**
     * Get sales by payment type
     */
    public function paymentStats() {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
            return;
        }

        $data = $this->saleModel->getPaymentStats();
        $this->json($data);
    }
}
