<?php
/**
 * Sales Controller
 * Handles sales operations and transactions
 */
class SalesController extends Controller {
    private $saleModel;
    private $productModel;
    private $customerModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->saleModel = $this->model('Sale');
        $this->productModel = $this->model('Product');
        $this->customerModel = $this->model('Customer');
    }

    /**
     * List all sales
     */
    public function index() {
        $page = $this->getQuery('page', 1);
        $search = $this->getQuery('search', '');
        $status = $this->getQuery('status', '');
        $dateRange = $this->getQuery('date_range', '');

        // Get sales with pagination
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = '(s.invoice_number LIKE ? OR c.name LIKE ?)';
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        if (!empty($status)) {
            $where[] = 's.payment_status = ?';
            $params[] = $status;
        }

        if (!empty($dateRange)) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) === 2) {
                $where[] = 'DATE(s.created_at) BETWEEN ? AND ?';
                $params[] = trim($dates[0]);
                $params[] = trim($dates[1]);
            }
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "
            SELECT COUNT(DISTINCT s.id) as total 
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            {$whereClause}
        ";

        $countQuery = $this->saleModel->db->query($countSql);
        foreach ($params as $i => $param) {
            $countQuery->bind($i + 1, $param);
        }
        $total = $countQuery->single()['total'];

        // Get paginated data
        $sql = "
            SELECT 
                s.*,
                c.name as customer_name,
                u.name as user_name,
                COUNT(si.id) as total_items
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            {$whereClause}
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ";

        $query = $this->saleModel->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $query->bind(count($params) + 1, $limit);
        $query->bind(count($params) + 2, $offset);

        $sales = $query->resultSet();

        // Get sales statistics
        $stats = [
            'today_sales' => $this->saleModel->getTodaySales(),
            'today_orders' => $this->saleModel->getTodayOrderCount(),
            'monthly_revenue' => $this->saleModel->getMonthlyRevenue(),
            'last_month_revenue' => $this->saleModel->getLastMonthRevenue()
        ];

        $this->view->render('sales/index', [
            'title' => 'Sales - ' . APP_NAME,
            'sales' => $sales,
            'stats' => $stats,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'last_page' => ceil($total / $limit)
            ],
            'search' => $search,
            'status' => $status,
            'dateRange' => $dateRange
        ]);
    }

    /**
     * Show new sale form
     */
    public function create() {
        $customers = $this->customerModel->getActive();
        $products = $this->productModel->getActive();

        $this->view->render('sales/create', [
            'title' => 'New Sale - ' . APP_NAME,
            'customers' => $customers,
            'products' => $products,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Store new sale
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('sales');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('sales/create');
            return;
        }

        $data = [
            'customer_id' => $this->getPost('customer_id'),
            'user_id' => $this->getUserId(),
            'total_amount' => floatval($this->getPost('total_amount')),
            'discount_amount' => floatval($this->getPost('discount_amount')),
            'final_amount' => floatval($this->getPost('final_amount')),
            'payment_type' => $this->getPost('payment_type'),
            'payment_status' => $this->getPost('payment_status', 'paid'),
            'notes' => trim($this->getPost('notes')),
            'invoice_number' => $this->saleModel->generateInvoiceNumber()
        ];

        // Validate input
        if (empty($data['customer_id'])) {
            $this->setFlash('error', 'Please select a customer');
            $this->redirect('sales/create');
            return;
        }

        $items = json_decode($this->getPost('items'), true);
        if (empty($items)) {
            $this->setFlash('error', 'Please add at least one product');
            $this->redirect('sales/create');
            return;
        }

        // Validate stock availability
        foreach ($items as $item) {
            if (!$this->productModel->hasStock($item['product_id'], $item['quantity'])) {
                $product = $this->productModel->getById($item['product_id']);
                $this->setFlash('error', "Insufficient stock for {$product['name']}");
                $this->redirect('sales/create');
                return;
            }
        }

        // Create sale with items
        try {
            $saleId = $this->saleModel->createWithItems($data, $items);
            if ($saleId) {
                $this->logActivity('sale', "Created new sale: {$data['invoice_number']}");
                $this->setFlash('success', 'Sale created successfully');
                $this->redirect('sales/view/' . $saleId);
            } else {
                $this->setFlash('error', 'Failed to create sale');
                $this->redirect('sales/create');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error creating sale: ' . $e->getMessage());
            $this->redirect('sales/create');
        }
    }

    /**
     * View sale details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('sales');
            return;
        }

        // Get sale with items
        $sale = $this->db->query("
            SELECT 
                s.*,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone,
                c.address as customer_address,
                u.name as user_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ")
        ->bind(1, $id)
        ->single();

        if (!$sale) {
            $this->setFlash('error', 'Sale not found');
            $this->redirect('sales');
            return;
        }

        // Get sale items
        $items = $this->db->query("
            SELECT 
                si.*,
                p.name as product_name,
                p.sku
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ")
        ->bind(1, $id)
        ->resultSet();

        $sale['items'] = $items;

        $this->view->render('sales/view', [
            'title' => "Invoice #{$sale['invoice_number']} - " . APP_NAME,
            'sale' => $sale
        ]);
    }

    /**
     * Update payment status
     */
    public function updatePayment($id = null) {
        if (!$this->isPost() || !$id) {
            $this->redirect('sales');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('sales/view/' . $id);
            return;
        }

        $status = $this->getPost('payment_status');
        if (!in_array($status, ['paid', 'partial', 'unpaid'])) {
            $this->setFlash('error', 'Invalid payment status');
            $this->redirect('sales/view/' . $id);
            return;
        }

        try {
            if ($this->saleModel->update($id, ['payment_status' => $status])) {
                $this->logActivity('sale', "Updated payment status for sale #{$id} to {$status}");
                $this->setFlash('success', 'Payment status updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update payment status');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error updating payment status: ' . $e->getMessage());
        }

        $this->redirect('sales/view/' . $id);
    }

    /**
     * Get product details for sale form
     */
    public function getProduct() {
        $id = $this->getQuery('id');
        if (!$id) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->jsonResponse(['success' => false]);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'data' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'sku' => $product['sku'],
                'price' => $product['selling_price'],
                'stock' => $product['stock']
            ]
        ]);
    }

    /**
     * Generate invoice PDF
     */
    public function invoice($id = null) {
        if (!$id) {
            $this->redirect('sales');
            return;
        }

        // Get sale data
        $sale = $this->db->query("
            SELECT 
                s.*,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone,
                c.address as customer_address,
                u.name as user_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ")
        ->bind(1, $id)
        ->single();

        if (!$sale) {
            $this->setFlash('error', 'Sale not found');
            $this->redirect('sales');
            return;
        }

        // Get sale items
        $items = $this->db->query("
            SELECT 
                si.*,
                p.name as product_name,
                p.sku
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ")
        ->bind(1, $id)
        ->resultSet();

        $sale['items'] = $items;

        // Generate PDF
        require_once HELPER_PATH . '/pdf_generator.php';
        $pdf = new PdfGenerator();
        $pdf->generateInvoice($sale);
    }
}
