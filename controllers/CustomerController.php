<?php
/**
 * Customer Controller
 * Handles customer management operations
 */
class CustomerController extends Controller {
    private $customerModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->customerModel = $this->model('Customer');
    }

    /**
     * List all customers
     */
    public function index() {
        $page = $this->getQuery('page', 1);
        $search = $this->getQuery('search', '');
        $sort = $this->getQuery('sort', 'name');
        $order = $this->getQuery('order', 'ASC');

        // Get customers with pagination
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $where = ['status = ?'];
        $params = ['active'];

        if (!empty($search)) {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->customerModel->table} WHERE {$whereClause}";
        $countQuery = $this->customerModel->db->query($countSql);
        foreach ($params as $i => $param) {
            $countQuery->bind($i + 1, $param);
        }
        $total = $countQuery->single()['total'];

        // Get paginated data with sales statistics
        $sql = "
            SELECT 
                c.*,
                COUNT(s.id) as total_orders,
                COALESCE(SUM(s.final_amount), 0) as total_spent,
                MAX(s.created_at) as last_purchase
            FROM {$this->customerModel->table} c
            LEFT JOIN sales s ON c.id = s.customer_id
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY {$sort} {$order}
            LIMIT ? OFFSET ?
        ";

        $query = $this->customerModel->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $query->bind(count($params) + 1, $limit);
        $query->bind(count($params) + 2, $offset);

        $customers = $query->resultSet();

        // Get customer statistics
        $stats = $this->customerModel->getStats();

        $this->view->render('customers/index', [
            'title' => 'Customers - ' . APP_NAME,
            'customers' => $customers,
            'stats' => $stats,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'last_page' => ceil($total / $limit)
            ],
            'search' => $search,
            'sort' => $sort,
            'order' => $order
        ]);
    }

    /**
     * Show customer creation form
     */
    public function create() {
        $this->view->render('customers/create', [
            'title' => 'Create Customer - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Store new customer
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('customers');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('customers/create');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'email' => trim($this->getPost('email')),
            'phone' => trim($this->getPost('phone')),
            'address' => trim($this->getPost('address')),
            'status' => 'active'
        ];

        // Validate input
        if (empty($data['name'])) {
            $this->setFlash('error', 'Customer name is required');
            $this->redirect('customers/create');
            return;
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Invalid email address');
            $this->redirect('customers/create');
            return;
        }

        // Create customer
        try {
            $customerId = $this->customerModel->create($data);
            if ($customerId) {
                $this->logActivity('customer', 'Created new customer: ' . $data['name']);
                $this->setFlash('success', 'Customer created successfully');
                $this->redirect('customers');
            } else {
                $this->setFlash('error', 'Failed to create customer');
                $this->redirect('customers/create');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error creating customer: ' . $e->getMessage());
            $this->redirect('customers/create');
        }
    }

    /**
     * Show customer edit form
     */
    public function edit($id = null) {
        if (!$id) {
            $this->redirect('customers');
            return;
        }

        $customer = $this->customerModel->getById($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('customers');
            return;
        }

        $this->view->render('customers/edit', [
            'title' => 'Edit Customer - ' . APP_NAME,
            'customer' => $customer,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Update customer
     */
    public function update($id = null) {
        if (!$this->isPost() || !$id) {
            $this->redirect('customers');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('customers/edit/' . $id);
            return;
        }

        $customer = $this->customerModel->getById($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('customers');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'email' => trim($this->getPost('email')),
            'phone' => trim($this->getPost('phone')),
            'address' => trim($this->getPost('address')),
            'status' => $this->getPost('status', 'active')
        ];

        // Validate input
        if (empty($data['name'])) {
            $this->setFlash('error', 'Customer name is required');
            $this->redirect('customers/edit/' . $id);
            return;
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Invalid email address');
            $this->redirect('customers/edit/' . $id);
            return;
        }

        // Update customer
        try {
            if ($this->customerModel->update($id, $data)) {
                $this->logActivity('customer', 'Updated customer: ' . $data['name']);
                $this->setFlash('success', 'Customer updated successfully');
                $this->redirect('customers');
            } else {
                $this->setFlash('error', 'Failed to update customer');
                $this->redirect('customers/edit/' . $id);
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error updating customer: ' . $e->getMessage());
            $this->redirect('customers/edit/' . $id);
        }
    }

    /**
     * View customer details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('customers');
            return;
        }

        $customer = $this->customerModel->getWithSalesHistory($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('customers');
            return;
        }

        // Get purchase frequency data
        $purchaseFrequency = $this->customerModel->getPurchaseFrequency($id);

        $this->view->render('customers/view', [
            'title' => $customer['name'] . ' - ' . APP_NAME,
            'customer' => $customer,
            'purchaseFrequency' => $purchaseFrequency
        ]);
    }

    /**
     * Delete customer (soft delete)
     */
    public function delete($id = null) {
        if (!$id) {
            $this->redirect('customers');
            return;
        }

        $customer = $this->customerModel->getById($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('customers');
            return;
        }

        // Check if customer has sales
        $hasSales = !empty($customer['sales_history']);
        if ($hasSales) {
            $this->setFlash('error', 'Cannot delete customer with sales history');
            $this->redirect('customers');
            return;
        }

        // Delete customer
        if ($this->customerModel->delete($id)) {
            $this->logActivity('customer', 'Deleted customer: ' . $customer['name']);
            $this->setFlash('success', 'Customer deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete customer');
        }

        $this->redirect('customers');
    }
}
