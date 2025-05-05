<?php
/**
 * Categories Controller
 * Handles category management operations
 */
class CategoriesController extends Controller {
    private $categoryModel;

    public function __construct() {
        parent::__construct();
        // Require authentication for all category operations
        $this->requireAuth();
        $this->categoryModel = $this->model('Category');
    }

    /**
     * List all categories
     */
    public function index() {
        $page = $this->getQuery('page', 1);
        $search = $this->getQuery('search', '');
        $categories = $this->categoryModel->getAll($page, ITEMS_PER_PAGE, $search);
        $tree = $this->categoryModel->getTree();

        $this->view->render('categories/index', [
            'title' => 'Categories - ' . APP_NAME,
            'categories' => $categories['data'],
            'tree' => $tree,
            'pagination' => [
                'total' => $categories['total'],
                'page' => $categories['page'],
                'lastPage' => $categories['last_page']
            ],
            'search' => $search
        ]);
    }

    /**
     * Show category creation form
     */
    public function create() {
        // Only admin can create categories
        $this->requireAdmin();

        $parentCategories = $this->categoryModel->getParentCategories();

        $this->view->render('categories/create', [
            'title' => 'Create Category - ' . APP_NAME,
            'parentCategories' => $parentCategories,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Store new category
     */
    public function store() {
        // Only admin can create categories
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('categories');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('categories/create');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'parent_id' => $this->getPost('parent_id'),
            'status' => 'active'
        ];

        // Validate input
        if (empty($data['name'])) {
            $this->setFlash('error', 'Category name is required');
            $this->redirect('categories/create');
            return;
        }

        // Convert empty parent_id to null
        if (empty($data['parent_id'])) {
            $data['parent_id'] = null;
        }

        // Create category
        if ($this->categoryModel->create($data)) {
            $this->logActivity('category', 'Created new category: ' . $data['name']);
            $this->setFlash('success', 'Category created successfully');
            $this->redirect('categories');
        } else {
            $this->setFlash('error', 'Failed to create category');
            $this->redirect('categories/create');
        }
    }

    /**
     * Show category edit form
     */
    public function edit($id = null) {
        // Only admin can edit categories
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('categories');
            return;
        }

        $category = $this->categoryModel->getById($id);
        if (!$category) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('categories');
            return;
        }

        $parentCategories = $this->categoryModel->getParentCategories($id);

        $this->view->render('categories/edit', [
            'title' => 'Edit Category - ' . APP_NAME,
            'category' => $category,
            'parentCategories' => $parentCategories,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Update category
     */
    public function update($id = null) {
        // Only admin can update categories
        $this->requireAdmin();

        if (!$this->isPost() || !$id) {
            $this->redirect('categories');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('categories/edit/' . $id);
            return;
        }

        $category = $this->categoryModel->getById($id);
        if (!$category) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('categories');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'parent_id' => $this->getPost('parent_id'),
            'status' => $this->getPost('status', 'active')
        ];

        // Validate input
        if (empty($data['name'])) {
            $this->setFlash('error', 'Category name is required');
            $this->redirect('categories/edit/' . $id);
            return;
        }

        // Convert empty parent_id to null
        if (empty($data['parent_id'])) {
            $data['parent_id'] = null;
        }

        // Update category
        if ($this->categoryModel->update($id, $data)) {
            $this->logActivity('category', 'Updated category: ' . $data['name']);
            $this->setFlash('success', 'Category updated successfully');
            $this->redirect('categories');
        } else {
            $this->setFlash('error', 'Failed to update category');
            $this->redirect('categories/edit/' . $id);
        }
    }

    /**
     * Delete category
     */
    public function delete($id = null) {
        // Only admin can delete categories
        $this->requireAdmin();

        if (!$id) {
            $this->redirect('categories');
            return;
        }

        $category = $this->categoryModel->getById($id);
        if (!$category) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('categories');
            return;
        }

        // Check if category has children
        if ($this->categoryModel->hasChildren($id)) {
            $this->setFlash('error', 'Cannot delete category with subcategories');
            $this->redirect('categories');
            return;
        }

        // Check if category has products
        if ($this->categoryModel->hasProducts($id)) {
            $this->setFlash('error', 'Cannot delete category with products');
            $this->redirect('categories');
            return;
        }

        // Delete category
        if ($this->categoryModel->delete($id)) {
            $this->logActivity('category', 'Deleted category: ' . $category['name']);
            $this->setFlash('success', 'Category deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete category');
        }

        $this->redirect('categories');
    }

    /**
     * View category details
     */
    public function view($id = null) {
        if (!$id) {
            $this->redirect('categories');
            return;
        }

        $category = $this->categoryModel->getById($id);
        if (!$category) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('categories');
            return;
        }

        // Get category products
        $page = $this->getQuery('page', 1);
        $products = $this->categoryModel->getProducts($id, $page, ITEMS_PER_PAGE);

        $this->view->render('categories/view', [
            'title' => $category['name'] . ' - ' . APP_NAME,
            'category' => $category,
            'products' => $products['data'],
            'pagination' => [
                'total' => $products['total'],
                'page' => $products['page'],
                'lastPage' => $products['last_page']
            ]
        ]);
    }
}
