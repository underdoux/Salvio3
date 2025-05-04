<?php
/**
 * Authentication Controller
 * Handles user authentication operations
 */
class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = $this->model('User');
    }

    /**
     * Login page
     */
    public function index() {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        // Set layout for login page
        $this->view->setLayout(null);

        // Show login form
        $this->view('auth/login', [
            'title' => 'Login - ' . APP_NAME
        ]);
    }

    /**
     * Handle login attempt
     */
    public function login() {
        // Redirect if already logged in
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        if ($this->isPost()) {
            // Get form data
            $username = $this->getPost('username');
            $password = $this->getPost('password');
            $remember = (bool)$this->getPost('remember');

            // Validate input
            if (empty($username) || empty($password)) {
                $this->setFlash('error', 'Please fill in all fields');
                $this->redirect('auth');
                return;
            }

            // Debug log
            error_log("Login attempt for username: " . $username);

            // Attempt login
            if (Auth::attempt($username, $password)) {
                error_log("Login successful for username: " . $username);
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = Auth::generateToken();
                    $this->userModel->storeRememberToken(Auth::id(), $token);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                }

                // Log activity
                $this->logActivity('auth', 'User logged in successfully');

                // Redirect to dashboard
                $this->redirect('dashboard');
            } else {
                error_log("Login failed for username: " . $username);
                $this->setFlash('error', 'Invalid username or password');
                $this->redirect('auth');
            }
        } else {
            $this->redirect('auth');
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->userModel->clearRememberToken(Auth::id());
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Log activity before logout
        if (Auth::check()) {
            $this->logActivity('auth', 'User logged out');
        }

        Auth::logout();
        $this->setFlash('success', 'You have been logged out');
        $this->redirect('auth');
    }

    /**
     * Handle forgotten password
     */
    public function forgot() {
        // Set layout for forgot password page
        $this->view->setLayout(null);

        if ($this->isPost()) {
            $email = $this->getPost('email');
            
            // Validate email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->setFlash('error', 'Please enter a valid email address');
                $this->redirect('auth/forgot');
                return;
            }

            // Check if email exists
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                // Generate reset token
                $token = Auth::generateToken();
                $this->userModel->storeResetToken($user['id'], $token);

                // Send reset email
                // TODO: Implement email sending
                
                $this->setFlash('success', 'Password reset instructions have been sent to your email');
            } else {
                // Don't reveal if email exists or not
                $this->setFlash('success', 'If your email exists in our system, you will receive reset instructions');
            }

            $this->redirect('auth/forgot');
        }

        $this->view('auth/forgot', [
            'title' => 'Forgot Password - ' . APP_NAME
        ]);
    }

    /**
     * Handle password reset
     */
    public function reset($token = null) {
        // Set layout for reset password page
        $this->view->setLayout(null);

        if (!$token) {
            $this->redirect('auth');
        }

        // Verify token
        $user = $this->userModel->findByResetToken($token);
        if (!$user) {
            $this->setFlash('error', 'Invalid or expired reset token');
            $this->redirect('auth');
            return;
        }

        if ($this->isPost()) {
            $password = $this->getPost('password');
            $confirmPassword = $this->getPost('confirm_password');

            // Validate passwords
            if (empty($password) || strlen($password) < 6) {
                $this->setFlash('error', 'Password must be at least 6 characters long');
                $this->redirect('auth/reset/' . $token);
                return;
            }

            if ($password !== $confirmPassword) {
                $this->setFlash('error', 'Passwords do not match');
                $this->redirect('auth/reset/' . $token);
                return;
            }

            // Update password and clear reset token
            $this->userModel->updatePassword($user['id'], Auth::hashPassword($password));
            $this->userModel->clearResetToken($user['id']);

            $this->setFlash('success', 'Your password has been reset. You can now login');
            $this->redirect('auth');
        }

        $this->view('auth/reset', [
            'title' => 'Reset Password - ' . APP_NAME,
            'token' => $token
        ]);
    }
}
