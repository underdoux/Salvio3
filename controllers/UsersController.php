<?php
/**
 * Users Controller
 * Handles user management operations
 */
class UsersController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        
        // Require admin role for all actions
        if (!Auth::isAdmin()) {
            $this->redirect('dashboard');
        }

        $this->userModel = $this->model('User');
    }

    /**
     * List users
     */
    public function index() {
        $page = (int)($this->getQuery('page', 1));
        $search = $this->getQuery('search', '');
        $users = $this->userModel->getAll($page, ITEMS_PER_PAGE, $search);

        $this->view('users/index', [
            'title' => 'Manage Users - ' . APP_NAME,
            'users' => $users['data'],
            'pagination' => [
                'page' => $users['page'],
                'total_pages' => $users['last_page'],
                'total' => $users['total']
            ],
            'search' => $search
        ]);
    }

    /**
     * Create user form
     */
    public function create() {
        if ($this->isPost()) {
            $data = [
                'username' => $this->getPost('username'),
                'email' => $this->getPost('email'),
                'name' => $this->getPost('name'),
                'password' => $this->getPost('password'),
                'role' => $this->getPost('role'),
                'status' => 'active'
            ];

            // Validate input
            $errors = [];

            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            } elseif ($this->userModel->usernameExists($data['username'])) {
                $errors['username'] = 'Username already exists';
            }

            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif ($this->userModel->emailExists($data['email'])) {
                $errors['email'] = 'Email already exists';
            }

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required';
            }

            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
            }

            if (empty($data['role']) || !in_array($data['role'], ['admin', 'sales'])) {
                $errors['role'] = 'Invalid role';
            }

            if (empty($errors)) {
                // Hash password
                $data['password'] = Auth::hashPassword($data['password']);

                // Create user
                if ($this->userModel->create($data)) {
                    $this->logActivity('users', 'Created new user: ' . $data['username']);
                    $this->setFlash('success', 'User created successfully');
                    $this->redirect('users');
                    return;
                }

                $this->setFlash('error', 'Failed to create user');
            }

            // If we get here, there were errors
            $this->view('users/create', [
                'title' => 'Create User - ' . APP_NAME,
                'data' => $data,
                'errors' => $errors
            ]);
            return;
        }

        $this->view('users/create', [
            'title' => 'Create User - ' . APP_NAME,
            'data' => [],
            'errors' => []
        ]);
    }

    /**
     * Edit user form
     */
    public function edit($id = null) {
        if (!$id) {
            $this->redirect('users');
        }

        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('users');
        }

        if ($this->isPost()) {
            $data = [
                'username' => $this->getPost('username'),
                'email' => $this->getPost('email'),
                'name' => $this->getPost('name'),
                'role' => $this->getPost('role')
            ];

            // Only update password if provided
            $password = $this->getPost('password');
            if (!empty($password)) {
                $data['password'] = $password;
            }

            // Validate input
            $errors = [];

            if (empty($data['username'])) {
                $errors['username'] = 'Username is required';
            } elseif ($this->userModel->usernameExists($data['username'], $id)) {
                $errors['username'] = 'Username already exists';
            }

            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif ($this->userModel->emailExists($data['email'], $id)) {
                $errors['email'] = 'Email already exists';
            }

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required';
            }

            if (!empty($password) && strlen($password) < PASSWORD_MIN_LENGTH) {
                $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
            }

            if (empty($data['role']) || !in_array($data['role'], ['admin', 'sales'])) {
                $errors['role'] = 'Invalid role';
            }

            if (empty($errors)) {
                // Hash password if provided
                if (!empty($password)) {
                    $data['password'] = Auth::hashPassword($password);
                }

                // Update user
                if ($this->userModel->update($id, $data)) {
                    $this->logActivity('users', 'Updated user: ' . $data['username']);
                    $this->setFlash('success', 'User updated successfully');
                    $this->redirect('users');
                    return;
                }

                $this->setFlash('error', 'Failed to update user');
            }

            // If we get here, there were errors
            $this->view('users/edit', [
                'title' => 'Edit User - ' . APP_NAME,
                'user' => $user,
                'data' => $data,
                'errors' => $errors
            ]);
            return;
        }

        $this->view('users/edit', [
            'title' => 'Edit User - ' . APP_NAME,
            'user' => $user,
            'data' => $user,
            'errors' => []
        ]);
    }

    /**
     * Delete user
     */
    public function delete($id = null) {
        if (!$this->isPost() || !$id) {
            $this->redirect('users');
        }

        $user = $this->userModel->getById($id);
        if (!$user) {
            $this->setFlash('error', 'User not found');
            $this->redirect('users');
        }

        // Prevent deleting own account
        if ($user['id'] === Auth::id()) {
            $this->setFlash('error', 'Cannot delete your own account');
            $this->redirect('users');
        }

        if ($this->userModel->delete($id)) {
            $this->logActivity('users', 'Deleted user: ' . $user['username']);
            $this->setFlash('success', 'User deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete user');
        }

        $this->redirect('users');
    }
}
