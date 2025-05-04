<?php
class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        $this->userModel = $this->model('User');
    }
    
    // Default action - show login form
    public function index() {
        // Redirect if already logged in
        if($this->isLoggedIn()) {
            $this->redirect('dashboard');
        }
        
        $this->view('auth/login', [
            'title' => 'Login',
            'errors' => []
        ]);
    }
    
    // Handle login form submission
    public function login() {
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth');
        }
        
        $data = $this->getPost();
        $errors = [];
        
        // Validate input
        if(empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        if(empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }
        
        // If validation passes, attempt login
        if(empty($errors)) {
            $user = $this->userModel->findByUsername($data['username']);
            
            if($user && password_verify($data['password'], $user->password)) {
                // Start session
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_role'] = $user->role;
                
                $this->flash('Welcome back, ' . $user->name);
                $this->redirect('dashboard');
            } else {
                $errors['login'] = 'Invalid username or password';
            }
        }
        
        // If we get here, there were errors
        $this->view('auth/login', [
            'title' => 'Login',
            'errors' => $errors,
            'username' => $data['username'] ?? ''
        ]);
    }
    
    // Handle logout
    public function logout() {
        // Clear all session data
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        $this->redirect('auth');
    }
    
    // Password reset request form
    public function forgot() {
        if($this->isLoggedIn()) {
            $this->redirect('dashboard');
        }
        
        $this->view('auth/forgot', [
            'title' => 'Reset Password',
            'errors' => []
        ]);
    }
    
    // Handle password reset request
    public function reset() {
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/forgot');
        }
        
        $data = $this->getPost();
        
        if(empty($data['email'])) {
            $this->view('auth/forgot', [
                'title' => 'Reset Password',
                'errors' => ['email' => 'Email is required']
            ]);
            return;
        }
        
        // Check if email exists
        if($this->userModel->findByEmail($data['email'])) {
            // In a real application, send password reset email here
            $this->flash('If an account exists with that email, password reset instructions will be sent.', 'info');
        }
        
        $this->redirect('auth');
    }
}
