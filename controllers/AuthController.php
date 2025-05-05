<?php
/**
 * Auth Controller
 * Handles user authentication and authorization
 */
class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = $this->model('User');
    }

    /**
     * Show login form
     */
    public function login() {
        // Redirect if already logged in
        if ($this->auth->check()) {
            $this->redirect('dashboard');
        }

        $this->view->setLayout('layout/auth');
        $this->view->render('auth/login', [
            'title' => 'Login - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process login
     */
    public function authenticate() {
        if (!$this->isPost()) {
            $this->redirect('auth/login');
        }

        // Validate CSRF token
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('auth/login');
            return;
        }

        $username = $this->getPost('username');
        $password = $this->getPost('password');
        $remember = $this->getPost('remember') === 'on';

        // Validate input
        if (empty($username) || empty($password)) {
            $this->setFlash('error', 'Please enter username and password');
            $this->redirect('auth/login');
            return;
        }

        // Check login attempts
        if ($this->isLoginBlocked($username)) {
            $this->setFlash('error', 'Too many login attempts. Please try again later.');
            $this->redirect('auth/login');
            return;
        }

        // Attempt login
        if ($this->auth->attempt($username, $password)) {
            // Clear login attempts
            $this->clearLoginAttempts($username);

            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberToken($this->auth->user()['id']);
            }

            // Log activity
            $this->logActivity('auth', 'User logged in successfully');

            // Redirect based on role
            $this->redirectBasedOnRole();
        } else {
            // Increment login attempts
            $this->incrementLoginAttempts($username);

            $this->setFlash('error', 'Invalid username or password');
            $this->redirect('auth/login');
        }
    }

    /**
     * Process logout
     */
    public function logout() {
        if ($this->auth->check()) {
            // Clear remember token
            $this->clearRememberToken($this->auth->user()['id']);
            
            // Log activity
            $this->logActivity('auth', 'User logged out');
            
            // Logout
            $this->auth->logout();
        }

        $this->redirect('auth/login');
    }

    /**
     * Show forgot password form
     */
    public function forgot() {
        if ($this->auth->check()) {
            $this->redirect('dashboard');
        }

        $this->view->setLayout('layout/auth');
        $this->view->render('auth/forgot', [
            'title' => 'Forgot Password - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process forgot password request
     */
    public function sendReset() {
        if (!$this->isPost()) {
            $this->redirect('auth/forgot');
        }

        // Validate CSRF token
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('auth/forgot');
            return;
        }

        $email = $this->getPost('email');

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Please enter a valid email address');
            $this->redirect('auth/forgot');
            return;
        }

        // Find user by email
        $user = $this->userModel->findByEmail($email);
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save reset token
            $this->userModel->update($user['id'], [
                'reset_token' => $token,
                'reset_expires' => $expires
            ]);

            // Send reset email
            $resetUrl = BASE_URL . '/auth/reset/' . $token;
            $emailContent = "Click the link below to reset your password:\n\n{$resetUrl}\n\nThis link will expire in 1 hour.";
            
            // TODO: Implement email sending
            // mail($email, 'Password Reset - ' . APP_NAME, $emailContent);

            $this->setFlash('success', 'Password reset instructions have been sent to your email');
        } else {
            // Don't reveal that email doesn't exist
            $this->setFlash('success', 'If an account exists with this email, password reset instructions will be sent');
        }

        $this->redirect('auth/login');
    }

    /**
     * Show reset password form
     */
    public function reset($token = null) {
        if ($this->auth->check() || !$token) {
            $this->redirect('dashboard');
        }

        // Validate token
        $user = $this->userModel->findByResetToken($token);
        if (!$user || strtotime($user['reset_expires']) < time()) {
            $this->setFlash('error', 'Invalid or expired reset token');
            $this->redirect('auth/login');
            return;
        }

        $this->view->setLayout('layout/auth');
        $this->view->render('auth/reset', [
            'title' => 'Reset Password - ' . APP_NAME,
            'token' => $token,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process password reset
     */
    public function updatePassword() {
        if (!$this->isPost()) {
            $this->redirect('auth/login');
        }

        // Validate CSRF token
        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('auth/login');
            return;
        }

        $token = $this->getPost('token');
        $password = $this->getPost('password');
        $confirmPassword = $this->getPost('confirm_password');

        // Validate input
        if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
            $this->setFlash('error', 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
            $this->redirect('auth/reset/' . $token);
            return;
        }

        if ($password !== $confirmPassword) {
            $this->setFlash('error', 'Passwords do not match');
            $this->redirect('auth/reset/' . $token);
            return;
        }

        // Find user by reset token
        $user = $this->userModel->findByResetToken($token);
        if (!$user || strtotime($user['reset_expires']) < time()) {
            $this->setFlash('error', 'Invalid or expired reset token');
            $this->redirect('auth/login');
            return;
        }

        // Update password
        $this->userModel->update($user['id'], [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_expires' => null
        ]);

        $this->setFlash('success', 'Password has been reset successfully');
        $this->redirect('auth/login');
    }

    /**
     * Check if login is blocked due to too many attempts
     */
    private function isLoginBlocked($username) {
        $attempts = $this->session->get('login_attempts_' . $username, 0);
        $lastAttempt = $this->session->get('login_last_attempt_' . $username, 0);

        if ($attempts >= LOGIN_MAX_ATTEMPTS && time() - $lastAttempt < LOGIN_LOCKOUT_TIME) {
            return true;
        }

        return false;
    }

    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts($username) {
        $attempts = $this->session->get('login_attempts_' . $username, 0);
        $this->session->set('login_attempts_' . $username, $attempts + 1);
        $this->session->set('login_last_attempt_' . $username, time());
    }

    /**
     * Clear login attempts
     */
    private function clearLoginAttempts($username) {
        $this->session->remove('login_attempts_' . $username);
        $this->session->remove('login_last_attempt_' . $username);
    }

    /**
     * Set remember me token
     */
    private function setRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $this->userModel->update($userId, ['remember_token' => $token]);
        setcookie('remember_token', $token, time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
    }

    /**
     * Clear remember me token
     */
    private function clearRememberToken($userId) {
        $this->userModel->update($userId, ['remember_token' => null]);
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }

    /**
     * Redirect based on user role
     */
    private function redirectBasedOnRole() {
        if ($this->auth->isAdmin()) {
            $this->redirect('dashboard');
        } else {
            $this->redirect('sales');
        }
    }
}
