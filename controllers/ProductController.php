<?php
/**
 * Product Controller
 * Handles CRUD operations for products
 */
class ProductController extends Controller {
    private $productModel;
    private $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->productModel = $this->model('Product');
        $this->categoryModel = $this->model('Category');
    }

    /**
     * List products with pagination and search
     */
    public function index() {
        $search = $this->getQuery('search') ?? '';
        $page = max(1, (int)($this->getQuery('page') ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $products = $this->productModel->search(['name', 'description', 'sku', 'barcode'], $search, 'id', 'DESC', $perPage, $offset);
        $totalProducts = $this->productModel->count("name LIKE '%{$search}%' OR description LIKE '%{$search}%' OR sku LIKE '%{$search}%' OR barcode LIKE '%{$search}%'");

        $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);

        $this->view('products/index', [
            'title' => 'Product Management - ' . APP_NAME,
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalProducts' => $totalProducts
        ]);
    }

    /**
     * Show create product form
     */
    public function create() {
        $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);
        $this->view('products/create', [
            'title' => 'Create Product - ' . APP_NAME,
            'categories' => $categories,
            'data' => []
        ]);
    }

    /**
     * Store new product
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('product');
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'sku' => trim($this->getPost('sku')),
            'barcode' => trim($this->getPost('barcode')),
            'category_id' => (int)$this->getPost('category_id'),
            'purchase_price' => (float)$this->getPost('purchase_price'),
            'selling_price' => (float)$this->getPost('selling_price'),
            'stock' => (int)$this->getPost('stock'),
            'min_stock' => (int)$this->getPost('min_stock'),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if ($data['category_id'] <= 0) {
            $data['errors']['category_id'] = 'Category is required';
        }

        if ($data['purchase_price'] < 0) {
            $data['errors']['purchase_price'] = 'Purchase price must be non-negative';
        }

        if ($data['selling_price'] < 0) {
            $data['errors']['selling_price'] = 'Selling price must be non-negative';
        }

        if ($data['stock'] < 0) {
            $data['errors']['stock'] = 'Stock must be non-negative';
        }

        if ($data['min_stock'] < 0) {
            $data['errors']['min_stock'] = 'Minimum stock must be non-negative';
        }

        if (!empty($data['errors'])) {
            $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);
            $this->view('products/create', [
                'title' => 'Create Product - ' . APP_NAME,
                'categories' => $categories,
                'data' => $data
            ]);
            return;
        }

        // Insert product
        if ($this->productModel->insert($data)) {
            $this->setFlash('success', 'Product created successfully');
            $this->redirect('product');
        } else {
            $this->setFlash('error', 'Failed to create product');
            $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);
            $this->view('products/create', [
                'title' => 'Create Product - ' . APP_NAME,
                'categories' => $categories,
                'data' => $data
            ]);
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id) {
        $product = $this->productModel->find($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('product');
        }

        $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);
        $this->view('products/edit', [
            'title' => 'Edit Product - ' . APP_NAME,
            'categories' => $categories,
            'data' => $product
        ]);
    }

    /**
     * Update product
     */
    public function update($id) {
        if (!$this->isPost()) {
            $this->redirect('product');
        }

        $product = $this->productModel->find($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('product');
        }

        $data = [
            'id' => $id,
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'sku' => trim($this->getPost('sku')),
            'barcode' => trim($this->getPost('barcode')),
            'category_id' => (int)$this->getPost('category_id'),
            'purchase_price' => (float)$this->getPost('purchase_price'),
            'selling_price' => (float)$this->getPost('selling_price'),
            'stock' => (int)$this->getPost('stock'),
            'min_stock' => (int)$this->getPost('min_stock'),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if ($data['category_id'] <= 0) {
            $data['errors']['category_id'] = 'Category is required';
        }

        if ($data['purchase_price'] < 0) {
            $data['errors']['purchase_price'] = 'Purchase price must be non-negative';
        }

        if ($data['selling_price'] < 0) {
            $data['errors']['selling_price'] = 'Selling price must be non-negative';
        }

        if ($data['stock'] < 0) {
            $data['errors']['stock'] = 'Stock must be non-negative';
        }

        if ($data['min_stock'] < 0) {
            $data['errors']['min_stock'] = 'Minimum stock must be non-negative';
        }

        if (!empty($data['errors'])) {
            $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);
            $this->view('products/edit', [
                'title' => 'Edit Product - ' . APP_NAME,
                'categories' => $categories,
                'data' => $data
            ]);
            return;
        }

        // Update product
        if ($this->productModel->update($id, $data)) {
            $this->setFlash('success', 'Product updated successfully');
            $this->redirect('product');
        } else {
            $this->setFlash('error', 'Failed to update product');
            $categories = $this->categoryModel->search(['name'], '', 'id', 'ASC', 100, 0);
            $this->view('products/edit', [
                'title' => 'Edit Product - ' . APP_NAME,
                'categories' => $categories,
                'data' => $data
            ]);
        }
    }

    /**
     * Soft delete product
     */
    public function delete($id) {
        if ($this->productModel->softDelete($id)) {
            $this->setFlash('success', 'Product deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete product');
        }
        $this->redirect('product');
    }
}
