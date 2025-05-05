<?php
/**
 * Auth Class
 * Handles user authentication and authorization
 */
class Auth {
    private static $user = null;

    /**
     * Initialize Auth
     */
    public static function init() {
        // Check for remember me cookie
        if (!self::check() && isset($_COOKIE['remember_token'])) {
            self::loginWithToken($_COOKIE['remember_token']);
        }
    }

    /**
     * Attempt login
     * @param string $username Username
     * @param string $password Password
     * @return bool Success status
     */
    public static function attempt($username, $password) {
        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            self::login($user);
            return true;
        }

        return false;
    }

    /**
     * Login with remember token
     * @param string $token Remember token
     * @return bool Success status
     */
    public static function loginWithToken($token) {
        $userModel = new User();
        $user = $userModel->findByRememberToken($token);

        if ($user) {
            self::login($user);
            return true;
        }

        return false;
    }

    /**
     * Login user
     * @param array $user User data
     */
    public static function login($user) {
        // Remove sensitive data
        unset($user['password']);
        unset($user['remember_token']);
        unset($user['reset_token']);
        unset($user['reset_expires']);

        // Store user data in session
        Session::set('user', $user);
        Session::regenerate();

        // Update static cache
        self::$user = $user;

        // Debug log
        error_log("[Auth] Login successful for user: " . $user['username']);
        error_log("[Auth] Session ID: " . session_id());
        error_log("[Auth] Session data: " . print_r($_SESSION, true));
    }

    /**
     * Logout user
     */
    public static function logout() {
        Session::remove('user');
        Session::regenerate();
        self::$user = null;

        // Remove remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function check() {
        return Session::has('user');
    }

    /**
     * Get current user
     * @return array|null User data
     */
    public static function user() {
        if (self::$user === null && self::check()) {
            self::$user = Session::get('user');
        }
        return self::$user;
    }

    /**
     * Get user ID
     * @return int|null User ID
     */
    public static function id() {
        $user = self::user();
        return $user ? $user['id'] : null;
    }

    /**
     * Get user name
     * @return string|null User name
     */
    public static function name() {
        $user = self::user();
        return $user ? $user['name'] : null;
    }

    /**
     * Check if user has role
     * @param string $role Role name
     * @return bool
     */
    public static function hasRole($role) {
        $user = self::user();
        return $user && $user['role'] === $role;
    }

    /**
     * Check if user is admin
     * @return bool
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }

    /**
     * Hash password
     * @param string $password Password to hash
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Generate random token
     * @param int $length Token length
     * @return string Random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Require authentication
     * Redirects to login if not authenticated
     */
    public static function requireAuth() {
        if (!self::check()) {
            Session::setFlash('error', 'Please login to continue');
            header('Location: ' . APP_URL . '/auth');
            exit;
        }
    }

    /**
     * Require admin role
     * Redirects to dashboard if not admin
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            Session::setFlash('error', 'Access denied');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }
}
