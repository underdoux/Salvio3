<?php
/**
 * Category Controller
 * Handles CRUD operations for categories
 */
class CategoryController extends Controller {
    private $categoryModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->categoryModel = $this->model('Category');
    }

    /**
     * List categories with pagination and search
     */
    public function index() {
        $search = $this->getGet('search') ?? '';
        $page = max(1, (int)($this->getGet('page') ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $categories = $this->categoryModel->search(['name', 'description'], $search, 'id', 'DESC', $perPage, $offset);
        $totalCategories = $this->categoryModel->count("name LIKE '%{$search}%' OR description LIKE '%{$search}%'");

        $this->view('categories/index', [
            'title' => 'Category Management - ' . APP_NAME,
            'categories' => $categories,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalCategories' => $totalCategories
        ]);
    }

    /**
     * Show create category form
     */
    public function create() {
        $this->view('categories/create', [
            'title' => 'Create Category - ' . APP_NAME,
            'data' => []
        ]);
    }

    /**
     * Store new category
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('category');
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if (!empty($data['errors'])) {
            $this->view('categories/create', [
                'title' => 'Create Category - ' . APP_NAME,
                'data' => $data
            ]);
            return;
        }

        // Insert category
        if ($this->categoryModel->insert($data)) {
            $this->setFlash('success', 'Category created successfully');
            $this->redirect('category');
        } else {
            $this->setFlash('error', 'Failed to create category');
            $this->view('categories/create', [
                'title' => 'Create Category - ' . APP_NAME,
                'data' => $data
            ]);
        }
    }

    /**
     * Show edit category form
     */
    public function edit($id) {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('category');
        }

        $this->view('categories/edit', [
            'title' => 'Edit Category - ' . APP_NAME,
            'data' => $category
        ]);
    }

    /**
     * Update category
     */
    public function update($id) {
        if (!$this->isPost()) {
            $this->redirect('category');
        }

        $category = $this->categoryModel->find($id);
        if (!$category) {
            $this->setFlash('error', 'Category not found');
            $this->redirect('category');
        }

        $data = [
            'id' => $id,
            'name' => trim($this->getPost('name')),
            'description' => trim($this->getPost('description')),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if (!empty($data['errors'])) {
            $this->view('categories/edit', [
                'title' => 'Edit Category - ' . APP_NAME,
                'data' => $data
            ]);
            return;
        }

        // Update category
        if ($this->categoryModel->update($id, $data)) {
            $this->setFlash('success', 'Category updated successfully');
            $this->redirect('category');
        } else {
            $this->setFlash('error', 'Failed to update category');
            $this->view('categories/edit', [
                'title' => 'Edit Category - ' . APP_NAME,
                'data' => $data
            ]);
        }
    }

    /**
     * Soft delete category
     */
    public function delete($id) {
        if ($this->categoryModel->softDelete($id)) {
            $this->setFlash('success', 'Category deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete category');
        }
        $this->redirect('category');
    }
}
