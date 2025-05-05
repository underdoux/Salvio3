<?php
/**
 * Product Controller
 * Handles product management operations
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
     * List all products
     */
    public function index() {
        $page = $this->getQuery('page', 1);
        $search = $this->getQuery('search', '');
        $categoryId = $this->getQuery('category', '');
        
        // Get categories for filter
        $categories = $this->categoryModel->getActive();
        
        // Get products with filters
        $products = $this->getFilteredProducts($page, $search, $categoryId);

        $this->view->render('products/index', [
            'title' => 'Products - ' . APP_NAME,
            'products' => $products['data'],
            'categories' => $categories,
            'pagination' => [
                'total' => $products['total'],
                'page' => $products['page'],
                'lastPage' => $products['last_page']
            ],
            'search' => $search,
            'selectedCategory' => $categoryId
        ]);
    }

    /**
     * Show product creation form
     */
    public function create() {
        $this->requireAdmin();
        
        $categories = $this->categoryModel->getActive();
        
        $this->view->render('products/create', [
            'title' => 'Create Product - ' . APP_NAME,
            'categories' => $categories,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Store new product
     */
    public function store() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('products');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('products/create');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'sku' => trim($this->getPost('sku')),
            'barcode' => trim($this->getPost('barcode')),
            'category_id' => $this->getPost('category_id'),
            'purchase_price' => floatval($this->getPost('purchase_price')),
            'selling_price' => floatval($this->getPost('selling_price')),
            'stock' => intval($this->getPost('stock')),
            'min_stock' => intval($this->getPost('min_stock')),
            'status' => 'active'
        ];

        // Validate input
        if (empty($data['name'])) {
            $this->setFlash('error', 'Product name is required');
            $this->redirect('products/create');
            return;
        }

        if ($data['selling_price'] <= 0) {
            $this->setFlash('error', 'Selling price must be greater than 0');
            $this->redirect('products/create');
            return;
        }

        // Create product
        try {
            $productId = $this->productModel->create($data);
            if ($productId) {
                $this->logActivity('product', 'Created new product: ' . $data['name']);
                $this->setFlash('success', 'Product created successfully');
                $this->redirect('products');
            } else {
                $this->setFlash('error', 'Failed to create product');
                $this->redirect('products/create');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error creating product: ' . $e->getMessage());
            $this->redirect('products/create');
        }
    }

    /**
     * Show product edit form
     */
    public function edit($id = null) {
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('products');
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        $categories = $this->categoryModel->getActive();

        $this->view->render('products/edit', [
            'title' => 'Edit Product - ' . APP_NAME,
            'product' => $product,
            'categories' => $categories,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Update product
     */
    public function update($id = null) {
        $this->requireAdmin();

        if (!$this->isPost() || !$id) {
            $this->redirect('products');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('products/edit/' . $id);
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'sku' => trim($this->getPost('sku')),
            'barcode' => trim($this->getPost('barcode')),
            'category_id' => $this->getPost('category_id'),
            'purchase_price' => floatval($this->getPost('purchase_price')),
            'selling_price' => floatval($this->getPost('selling_price')),
            'stock' => intval($this->getPost('stock')),
            'min_stock' => intval($this->getPost('min_stock')),
            'status' => $this->getPost('status', 'active')
        ];

        // Validate input
        if (empty($data['name'])) {
            $this->setFlash('error', 'Product name is required');
            $this->redirect('products/edit/' . $id);
            return;
        }

        if ($data['selling_price'] <= 0) {
            $this->setFlash('error', 'Selling price must be greater than 0');
            $this->redirect('products/edit/' . $id);
            return;
        }

        // Update product
        try {
            if ($this->productModel->update($id, $data)) {
                $this->logActivity('product', 'Updated product: ' . $data['name']);
                $this->setFlash('success', 'Product updated successfully');
                $this->redirect('products');
            } else {
                $this->setFlash('error', 'Failed to update product');
                $this->redirect('products/edit/' . $id);
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error updating product: ' . $e->getMessage());
            $this->redirect('products/edit/' . $id);
        }
    }

    /**
     * Delete product (soft delete)
     */
    public function delete($id = null) {
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('products');
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        // Delete product
        if ($this->productModel->delete($id)) {
            $this->logActivity('product', 'Deleted product: ' . $product['name']);
            $this->setFlash('success', 'Product deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete product');
        }

        $this->redirect('products');
    }

    /**
     * Show product details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('products');
            return;
        }

        $product = $this->productModel->getWithBpomData($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('products');
            return;
        }

        $this->view->render('products/view', [
            'title' => $product['name'] . ' - ' . APP_NAME,
            'product' => $product
        ]);
    }

    /**
     * Update product stock
     */
    public function updateStock($id = null) {
        $this->requireAdmin();

        if (!$this->isPost() || !$id) {
            $this->redirect('products');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('products/view/' . $id);
            return;
        }

        $quantity = intval($this->getPost('quantity'));
        
        try {
            if ($this->productModel->updateStock($id, $quantity)) {
                $this->logActivity('product', "Updated stock for product ID {$id}: {$quantity}");
                $this->setFlash('success', 'Stock updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update stock');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error updating stock: ' . $e->getMessage());
        }

        $this->redirect('products/view/' . $id);
    }

    /**
     * Get filtered products with pagination
     */
    private function getFilteredProducts($page = 1, $search = '', $categoryId = '') {
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $where = ['p.status = ?'];
        $params = ['active'];
        
        if (!empty($search)) {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)';
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($categoryId)) {
            $where[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "
            SELECT COUNT(*) as total 
            FROM {$this->productModel->table} p 
            WHERE {$whereClause}
        ";
        
        $countQuery = $this->productModel->db->query($countSql);
        foreach ($params as $i => $param) {
            $countQuery->bind($i + 1, $param);
        }
        $total = $countQuery->single()['total'];
        
        // Get paginated data
        $sql = "
            SELECT 
                p.*,
                c.name as category_name,
                CASE
                    WHEN p.stock = 0 THEN 'out_of_stock'
                    WHEN p.stock <= p.min_stock THEN 'low_stock'
                    ELSE 'normal'
                END as stock_status
            FROM {$this->productModel->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE {$whereClause}
            ORDER BY p.name ASC
            LIMIT ? OFFSET ?
        ";
        
        $query = $this->productModel->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $query->bind(count($params) + 1, $limit);
        $query->bind(count($params) + 2, $offset);
        
        return [
            'data' => $query->resultSet(),
            'total' => $total,
            'page' => $page,
            'last_page' => ceil($total / $limit)
        ];
    }
}
