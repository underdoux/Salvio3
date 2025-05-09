<?php
/**
 * User Controller
 * Handles CRUD operations for users
 */
class UserController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = $this->model('User');
    }

    /**
     * List users with pagination and search
     */
    public function index() {
        $search = $this->getGet('search') ?? '';
        $page = max(1, (int)($this->getGet('page') ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $users = $this->userModel->search(['username', 'email', 'name', 'role'], $search, 'id', 'DESC', $perPage, $offset);
        $totalUsers = $this->userModel->count("username LIKE '%{$search}%' OR email LIKE '%{$search}%' OR name LIKE '%{$search}%' OR role LIKE '%{$search}%'");

        $this->view('users/index', [
            'title' => 'User Management - ' . APP_NAME,
            'users' => $users,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalUsers' => $totalUsers
        ]);
    }

    /**
     * Show create user form
     */
    public function create() {
        $this->view('users/create', [
            'title' => 'Create User - ' . APP_NAME,
            'data' => []
        ]);
    }

    /**
     * Store new user
     */
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('user');
        }

        $data = [
            'username' => trim($this->getPost('username')),
            'email' => trim($this->getPost('email')),
            'name' => trim($this->getPost('name')),
            'role' => trim($this->getPost('role')),
            'password' => $this->getPost('password'),
            'password_confirm' => $this->getPost('password_confirm'),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['username'])) {
            $data['errors']['username'] = 'Username is required';
        } elseif ($this->userModel->usernameExists($data['username'])) {
            $data['errors']['username'] = 'Username already exists';
        }

        if (empty($data['email'])) {
            $data['errors']['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['errors']['email'] = 'Invalid email format';
        } elseif ($this->userModel->emailExists($data['email'])) {
            $data['errors']['email'] = 'Email already exists';
        }

        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if (empty($data['role'])) {
            $data['errors']['role'] = 'Role is required';
        }

        if (empty($data['password'])) {
            $data['errors']['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $data['errors']['password'] = 'Password must be at least 6 characters';
        }

        if ($data['password'] !== $data['password_confirm']) {
            $data['errors']['password_confirm'] = 'Passwords do not match';
        }

        if (!empty($data['errors'])) {
            $this->view('users/create', [
                'title' => 'Create User - ' . APP_NAME,
                'data' => $data
            ]);
            return;
        }

        // Hash password
        $hashedPassword = Auth::hashPassword($data['password']);

        // Prepare user data for insertion
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'name' => $data['name'],
            'role' => $data['role'],
            'password' => $hashedPassword,
            'status' => $data['status']
        ];

        // Insert user
        if ($this->userModel->insert($userData)) {
            $this->setFlash('success', 'User created successfully');
            $this->redirect('user');
        } else {
            $this->setFlash('error', 'Failed to create user');
            $this->view('users/create', [
                'title' => 'Create User - ' . APP_NAME,
                'data' => $data
            ]);
        }
    }

    /**
     * Show edit user form
     */
    public function edit($id) {
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('user');
        }

        $this->view('users/edit', [
            'title' => 'Edit User - ' . APP_NAME,
            'data' => $user
        ]);
    }

    /**
     * Update user
     */
    public function update($id) {
        if (!$this->isPost()) {
            $this->redirect('user');
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('user');
        }

        $data = [
            'id' => $id,
            'username' => trim($this->getPost('username')),
            'email' => trim($this->getPost('email')),
            'name' => trim($this->getPost('name')),
            'role' => trim($this->getPost('role')),
            'status' => $this->getPost('status') ?? 'active',
            'errors' => []
        ];

        // Validate inputs
        if (empty($data['username'])) {
            $data['errors']['username'] = 'Username is required';
        } elseif ($this->userModel->usernameExists($data['username'], $id)) {
            $data['errors']['username'] = 'Username already exists';
        }

        if (empty($data['email'])) {
            $data['errors']['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['errors']['email'] = 'Invalid email format';
        } elseif ($this->userModel->emailExists($data['email'], $id)) {
            $data['errors']['email'] = 'Email already exists';
        }

        if (empty($data['name'])) {
            $data['errors']['name'] = 'Name is required';
        }

        if (empty($data['role'])) {
            $data['errors']['role'] = 'Role is required';
        }

        if (!empty($data['errors'])) {
            $data['id'] = $id;
            $this->view('users/edit', [
                'title' => 'Edit User - ' . APP_NAME,
                'data' => $data
            ]);
            return;
        }

        // Prepare user data for update
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'name' => $data['name'],
            'role' => $data['role'],
            'status' => $data['status']
        ];

        // Update user
        if ($this->userModel->update($id, $userData)) {
            $this->setFlash('success', 'User updated successfully');
            $this->redirect('user');
        } else {
            $this->setFlash('error', 'Failed to update user');
            $this->view('users/edit', [
                'title' => 'Edit User - ' . APP_NAME,
                'data' => $data
            ]);
        }
    }

    /**
     * Soft delete user
     */
    public function delete($id) {
        if ($this->userModel->softDelete($id)) {
            $this->setFlash('success', 'User deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete user');
        }
        $this->redirect('user');
    }
}
