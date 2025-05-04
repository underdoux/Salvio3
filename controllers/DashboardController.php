<?php
class DashboardController extends Controller {
    private $userModel;
    
    public function __construct() {
        // Require login for all dashboard actions
        if(!Session::isLoggedIn()) {
            Session::setFlash('Please log in to access the dashboard', 'warning');
            $this->redirect('auth');
        }
        
        $this->userModel = $this->model('User');
    }
    
    /**
     * Display appropriate dashboard based on user role
     */
    public function index() {
        $user = Session::getUser();
        
        // Get dashboard data based on role
        switch($user->role) {
            case 'admin':
                $this->adminDashboard();
                break;
            case 'sales':
                $this->salesDashboard();
                break;
            default:
                Session::setFlash('Invalid user role', 'error');
                $this->redirect('auth/logout');
        }
    }
    
    /**
     * Admin Dashboard
     */
    private function adminDashboard() {
        // Get summary data
        $data = [
            'title' => 'Admin Dashboard',
            'user' => Session::getUser(),
            'stats' => $this->getAdminStats(),
            'recent_activities' => $this->getRecentActivities(),
            'bodyClass' => 'dashboard admin-dashboard'
        ];
        
        $this->view('dashboard/admin', $data);
    }
    
    /**
     * Sales Dashboard
     */
    private function salesDashboard() {
        // Get summary data
        $data = [
            'title' => 'Sales Dashboard',
            'user' => Session::getUser(),
            'stats' => $this->getSalesStats(),
            'recent_sales' => $this->getRecentSales(),
            'bodyClass' => 'dashboard sales-dashboard'
        ];
        
        $this->view('dashboard/sales', $data);
    }
    
    /**
     * Get statistics for admin dashboard
     */
    private function getAdminStats() {
        try {
            $db = new Database();
            
            // Total users
            $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
            $totalUsers = $db->single()->total;
            
            // Total products
            $db->query("SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL");
            $totalProducts = $db->single()->total;
            
            // Total sales today
            $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount 
                       FROM sales 
                       WHERE DATE(created_at) = CURDATE()");
            $todaySales = $db->single();
            
            // Low stock products
            $db->query("SELECT COUNT(*) as total FROM products 
                       WHERE stock_quantity <= min_stock_level 
                       AND deleted_at IS NULL");
            $lowStock = $db->single()->total;
            
            return [
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'today_sales' => [
                    'count' => $todaySales->total,
                    'amount' => $todaySales->amount
                ],
                'low_stock' => $lowStock
            ];
        } catch (Exception $e) {
            error_log("Error getting admin stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics for sales dashboard
     */
    private function getSalesStats() {
        try {
            $db = new Database();
            $userId = Session::get('user_id');
            
            // Today's sales
            $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount 
                       FROM sales 
                       WHERE user_id = :user_id 
                       AND DATE(created_at) = CURDATE()");
            $db->bind(':user_id', $userId);
            $todaySales = $db->single();
            
            // This month's sales
            $db->query("SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount 
                       FROM sales 
                       WHERE user_id = :user_id 
                       AND MONTH(created_at) = MONTH(CURRENT_DATE())
                       AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $db->bind(':user_id', $userId);
            $monthSales = $db->single();
            
            // Commission earned
            $db->query("SELECT COALESCE(SUM(commission_amount), 0) as total 
                       FROM sales_commissions 
                       WHERE user_id = :user_id 
                       AND MONTH(created_at) = MONTH(CURRENT_DATE())
                       AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $db->bind(':user_id', $userId);
            $commission = $db->single()->total;
            
            return [
                'today_sales' => [
                    'count' => $todaySales->total,
                    'amount' => $todaySales->amount
                ],
                'month_sales' => [
                    'count' => $monthSales->total,
                    'amount' => $monthSales->amount
                ],
                'commission' => $commission
            ];
        } catch (Exception $e) {
            error_log("Error getting sales stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent system activities
     */
    private function getRecentActivities($limit = 10) {
        try {
            $db = new Database();
            
            $db->query("SELECT al.*, u.name as user_name 
                       FROM activity_logs al 
                       LEFT JOIN users u ON al.user_id = u.id 
                       ORDER BY al.created_at DESC 
                       LIMIT :limit");
            $db->bind(':limit', $limit);
            
            return $db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting recent activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent sales for a sales user
     */
    private function getRecentSales($limit = 10) {
        try {
            $db = new Database();
            $userId = Session::get('user_id');
            
            $db->query("SELECT s.*, c.name as customer_name 
                       FROM sales s 
                       LEFT JOIN customers c ON s.customer_id = c.id 
                       WHERE s.user_id = :user_id 
                       ORDER BY s.created_at DESC 
                       LIMIT :limit");
            $db->bind(':user_id', $userId);
            $db->bind(':limit', $limit);
            
            return $db->resultSet();
        } catch (Exception $e) {
            error_log("Error getting recent sales: " . $e->getMessage());
            return [];
        }
    }
}
