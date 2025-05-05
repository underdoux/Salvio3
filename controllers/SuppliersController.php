<?php
/**
 * Suppliers Controller
 * Handles supplier management and purchasing
 */
class SuppliersController extends Controller {
    private $supplierModel;
    private $purchaseOrderModel;
    private $stockReceiptModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->supplierModel = $this->model('Supplier');
        $this->purchaseOrderModel = $this->model('PurchaseOrder');
        $this->stockReceiptModel = $this->model('StockReceipt');
        $this->productModel = $this->model('Product');
    }

    /**
     * Show suppliers list
     */
    public function index() {
        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        
        $summary = $this->supplierModel->getPaymentSummary($startDate, $endDate);
        $suppliers = $this->supplierModel->getAll();

        $this->view->render('suppliers/index', [
            'title' => 'Suppliers - ' . APP_NAME,
            'suppliers' => $suppliers,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show supplier creation form
     */
    public function create() {
        $this->requireAdmin();
        
        $this->view->render('suppliers/create', [
            'title' => 'Add Supplier - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process supplier creation
     */
    public function store() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('suppliers');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('suppliers/create');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'company_name' => trim($this->getPost('company_name')),
            'email' => trim($this->getPost('email')),
            'phone' => trim($this->getPost('phone')),
            'address' => trim($this->getPost('address')),
            'contact_person' => trim($this->getPost('contact_person')),
            'tax_number' => trim($this->getPost('tax_number')),
            'bank_name' => trim($this->getPost('bank_name')),
            'bank_account' => trim($this->getPost('bank_account')),
            'bank_holder' => trim($this->getPost('bank_holder')),
            'credit_limit' => floatval($this->getPost('credit_limit')),
            'payment_terms' => intval($this->getPost('payment_terms')),
            'notes' => trim($this->getPost('notes'))
        ];

        if ($this->supplierModel->create($data)) {
            $this->logActivity('supplier', "Added new supplier: {$data['name']}");
            $this->setFlash('success', 'Supplier added successfully');
            $this->redirect('suppliers');
        } else {
            $this->setFlash('error', 'Failed to add supplier');
            $this->redirect('suppliers/create');
        }
    }

    /**
     * Show supplier details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('suppliers');
            return;
        }

        $supplier = $this->supplierModel->getWithSummary($id);
        if (!$supplier) {
            $this->setFlash('error', 'Supplier not found');
            $this->redirect('suppliers');
            return;
        }

        $unpaidOrders = $this->supplierModel->getUnpaidOrders($id);
        $bankAccounts = $this->model('BankAccount')->getActive();

        $this->view->render('suppliers/view', [
            'title' => $supplier['name'] . ' - ' . APP_NAME,
            'supplier' => $supplier,
            'unpaidOrders' => $unpaidOrders,
            'bankAccounts' => $bankAccounts,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show purchase orders
     */
    public function orders($id = null) {
        if (!$id) {
            $this->redirect('suppliers');
            return;
        }

        $supplier = $this->supplierModel->getById($id);
        if (!$supplier) {
            $this->setFlash('error', 'Supplier not found');
            $this->redirect('suppliers');
            return;
        }

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        $status = $this->getQuery('status');

        $orders = $this->supplierModel->getPurchaseHistory($id, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status
        ]);

        $this->view->render('suppliers/orders', [
            'title' => 'Purchase Orders - ' . $supplier['name'],
            'supplier' => $supplier,
            'orders' => $orders,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show purchase order creation form
     */
    public function createOrder($id = null) {
        if (!$id) {
            $this->redirect('suppliers');
            return;
        }

        $supplier = $this->supplierModel->getById($id);
        if (!$supplier) {
            $this->setFlash('error', 'Supplier not found');
            $this->redirect('suppliers');
            return;
        }

        $products = $this->productModel->getAll(['status' => 'active']);

        $this->view->render('suppliers/create_order', [
            'title' => 'Create Purchase Order - ' . $supplier['name'],
            'supplier' => $supplier,
            'products' => $products,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process purchase order creation
     */
    public function storeOrder() {
        if (!$this->isPost()) {
            $this->redirect('suppliers');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('suppliers');
            return;
        }

        $data = [
            'supplier_id' => $this->getPost('supplier_id'),
            'order_date' => $this->getPost('order_date'),
            'expected_date' => $this->getPost('expected_date'),
            'shipping_cost' => floatval($this->getPost('shipping_cost')),
            'notes' => trim($this->getPost('notes')),
            'created_by' => $this->getUserId()
        ];

        $items = json_decode($this->getPost('items'), true);
        if (empty($items)) {
            $this->setFlash('error', 'No items added to purchase order');
            $this->redirect('suppliers/createOrder/' . $data['supplier_id']);
            return;
        }

        if ($this->purchaseOrderModel->createWithItems($data, $items)) {
            $this->logActivity('purchase', "Created purchase order for supplier #{$data['supplier_id']}");
            $this->setFlash('success', 'Purchase order created successfully');
            $this->redirect('suppliers/orders/' . $data['supplier_id']);
        } else {
            $this->setFlash('error', 'Failed to create purchase order');
            $this->redirect('suppliers/createOrder/' . $data['supplier_id']);
        }
    }

    /**
     * Record stock receipt
     */
    public function recordReceipt() {
        if (!$this->isPost()) {
            $this->redirect('suppliers');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('suppliers');
            return;
        }

        $data = [
            'purchase_order_id' => $this->getPost('purchase_order_id'),
            'receipt_date' => $this->getPost('receipt_date'),
            'notes' => trim($this->getPost('notes')),
            'created_by' => $this->getUserId()
        ];

        $items = json_decode($this->getPost('items'), true);
        if (empty($items)) {
            $this->setFlash('error', 'No items added to receipt');
            $this->redirect('suppliers');
            return;
        }

        if ($this->stockReceiptModel->createWithItems($data, $items)) {
            $this->logActivity('stock', "Recorded stock receipt for PO #{$data['purchase_order_id']}");
            $this->setFlash('success', 'Stock receipt recorded successfully');
        } else {
            $this->setFlash('error', 'Failed to record stock receipt');
        }

        $this->redirect('suppliers');
    }

    /**
     * Record supplier payment
     */
    public function recordPayment() {
        if (!$this->isPost()) {
            $this->redirect('suppliers');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('suppliers');
            return;
        }

        $data = [
            'purchase_order_id' => $this->getPost('purchase_order_id'),
            'amount' => floatval($this->getPost('amount')),
            'payment_date' => $this->getPost('payment_date'),
            'payment_method' => $this->getPost('payment_method'),
            'reference_number' => trim($this->getPost('reference_number')),
            'bank_account_id' => $this->getPost('bank_account_id'),
            'notes' => trim($this->getPost('notes')),
            'created_by' => $this->getUserId()
        ];

        if ($this->purchaseOrderModel->recordPayment($data)) {
            $this->logActivity('payment', "Recorded payment for PO #{$data['purchase_order_id']}");
            $this->setFlash('success', 'Payment recorded successfully');
        } else {
            $this->setFlash('error', 'Failed to record payment');
        }

        $this->redirect('suppliers');
    }
}
