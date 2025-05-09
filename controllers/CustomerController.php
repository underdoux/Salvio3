<?php
/**
 * Customer Controller
 * Handles CRUD operations for customers
 */
class CustomerController extends Controller {
    private $customerModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->customerModel = $this->model('Customer');
    }

    /**
     * List customers with pagination and search
     */
    public function index() {
        $search = $this->getGet('search') ?? '';
        $page = max(1, (int)($this->getGet('page') ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $customers = $this->customerModel->search(['name', 'email', 'phone', 'address'], $search, 'id', 'DESC', $perPage, $offset);
        $totalCustomers = $this->customerModel->count("name LIKE '%{$search}%' OR email LIKE '%{$search}%' OR phone LIKE '%{$search}%' OR address LIKE '%{$search}%'");

        $this->view('customers/index', [
            'title' => 'Customer Management - ' . APP_NAME,
            'customers' => $customers,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalCustomers' => $totalCustomers
        ]);
    }

    /**
     * Show create customer form
     */
    public function create() {
        $this->view('customers/create', [
            'title' => 'Create Customer - ' . APP_NAME,
            'data' => []
        ]);
    }

    /**
     * Store new customer
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('customer');
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'email' => trim($this->getPost('email')),
            'phone' => trim($this->getPost('phone')),
            'address' => trim($this->getPost('address')),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['errors']['email'] = 'Invalid email format';
        }

        if (!empty($data['phone']) && !preg_match('/^\+?[0-9\s\-]+$/', $data['phone'])) {
            $data['errors']['phone'] = 'Invalid phone number';
        }

        if (!empty($data['errors'])) {
            $this->view('customers/create', [
                'title' => 'Create Customer - ' . APP_NAME,
                'data' => $data
            ]);
            return;
        }

        // Insert customer
        if ($this->customerModel->insert($data)) {
            $this->setFlash('success', 'Customer created successfully');
            $this->redirect('customer');
        } else {
            $this->setFlash('error', 'Failed to create customer');
            $this->view('customers/create', [
                'title' => 'Create Customer - ' . APP_NAME,
                'data' => $data
            ]);
        }
    }

    /**
     * Show edit customer form
     */
    public function edit($id) {
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('customer');
        }

        $this->view('customers/edit', [
            'title' => 'Edit Customer - ' . APP_NAME,
            'data' => $customer
        ]);
    }

    /**
     * Update customer
     */
    public function update($id) {
        if (!$this->isPost()) {
            $this->redirect('customer');
        }

        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('customer');
        }

        $data = [
            'id' => $id,
            'name' => trim($this->getPost('name')),
            'email' => trim($this->getPost('email')),
            'phone' => trim($this->getPost('phone')),
            'address' => trim($this->getPost('address')),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['errors']['email'] = 'Invalid email format';
        }

        if (!empty($data['phone']) && !preg_match('/^\+?[0-9\s\-]+$/', $data['phone'])) {
            $data['errors']['phone'] = 'Invalid phone number';
        }

        if (!empty($data['errors'])) {
            $this->view('customers/edit', [
                'title' => 'Edit Customer - ' . APP_NAME,
                'data' => $data
            ]);
            return;
        }

        // Update customer
        if ($this->customerModel->update($id, $data)) {
            $this->setFlash('success', 'Customer updated successfully');
            $this->redirect('customer');
        } else {
            $this->setFlash('error', 'Failed to update customer');
            $this->view('customers/edit', [
                'title' => 'Edit Customer - ' . APP_NAME,
                'data' => $data
            ]);
        }
    }

    /**
     * Soft delete customer
     */
    public function delete($id) {
        if ($this->customerModel->softDelete($id)) {
            $this->setFlash('success', 'Customer deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete customer');
        }
        $this->redirect('customer');
    }
}
