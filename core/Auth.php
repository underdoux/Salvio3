<?php
/**
 * Auth Class
 * Handles authentication and authorization
 */
class Auth {
    private static $instance = null;
    private $user = null;
    private $session;

    private function __construct() {
        $this->session = Session::getInstance();
        $this->checkSession();
    }

    /**
     * Get Auth instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if user is logged in
     */
    private function checkSession() {
        $userId = $this->session->get('user_id');
        if ($userId) {
            $db = Database::getInstance();
            $this->user = $db->query("
                SELECT id, username, email, name, role, status
                FROM users 
                WHERE id = ? AND status = 'active'
            ")
            ->bind(1, $userId)
            ->single();
        }
    }

    /**
     * Attempt to login user
     */
    public function attempt($username, $password) {
        $db = Database::getInstance();
        $user = $db->query("
            SELECT id, username, password, email, name, role, status
            FROM users 
            WHERE username = ? AND status = 'active'
        ")
        ->bind(1, $username)
        ->single();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $this->user = $user;
            $this->session->set('user_id', $user['id']);

            // Update last login
            $db->query("
                UPDATE users 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE id = ?
            ")
            ->bind(1, $user['id'])
            ->execute();

            // Log activity
            $this->logActivity('auth', 'User logged in');

            return true;
        }

        return false;
    }

    /**
     * Log user out
     */
    public function logout() {
        $this->logActivity('auth', 'User logged out');
        $this->session->destroy();
        $this->user = null;
    }

    /**
     * Check if user is logged in
     */
    public function check() {
        return $this->user !== null;
    }

    /**
     * Get current user
     */
    public function user() {
        return $this->user;
    }

    /**
     * Check if user has role
     */
    public function hasRole($role) {
        return $this->check() && $this->user['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Log activity
     */
    private function logActivity($type, $description) {
        if ($this->check()) {
            $db = Database::getInstance();
            $db->query("
                INSERT INTO activity_logs 
                (user_id, type, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ")
            ->bind(1, $this->user['id'])
            ->bind(2, $type)
            ->bind(3, $description)
            ->bind(4, $_SERVER['REMOTE_ADDR'])
            ->bind(5, $_SERVER['HTTP_USER_AGENT'])
            ->execute();
        }
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserializing of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
