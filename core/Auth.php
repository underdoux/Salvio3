<?php
class Auth {
    private static $instance = null;
    private $db;
    private $user = null;
    
    private function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Get Auth instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }
    
    /**
     * Attempt to authenticate user
     */
    public function attempt($username, $password) {
        $this->db->query('SELECT * FROM users WHERE username = :username AND deleted_at IS NULL');
        $this->db->bind(':username', $username);
        
        $user = $this->db->single();
        
        if ($user && password_verify($password, $user->password)) {
            // Update last login
            $this->db->query('UPDATE users SET last_login = NOW() WHERE id = :id');
            $this->db->bind(':id', $user->id);
            $this->db->execute();
            
            // Log activity
            $this->logActivity($user->id, 'LOGIN', 'User logged in successfully');
            
            // Set session
            Session::setUser($user);
            $this->user = $user;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Log out current user
     */
    public function logout() {
        if ($this->check()) {
            $this->logActivity($this->user->id, 'LOGOUT', 'User logged out');
        }
        
        Session::destroy();
        $this->user = null;
    }
    
    /**
     * Check if user is logged in
     */
    public function check() {
        return Session::isLoggedIn();
    }
    
    /**
     * Get current authenticated user
     */
    public function user() {
        if ($this->user === null && $this->check()) {
            $userId = Session::get('user_id');
            
            $this->db->query('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL');
            $this->db->bind(':id', $userId);
            
            $this->user = $this->db->single();
        }
        
        return $this->user;
    }
    
    /**
     * Check if current user has specific role
     */
    public function hasRole($role) {
        return $this->check() && $this->user()->role === $role;
    }
    
    /**
     * Check if current user has any of the specified roles
     */
    public function hasAnyRole($roles) {
        return $this->check() && in_array($this->user()->role, (array) $roles);
    }
    
    /**
     * Check if current user has permission for route
     */
    public function hasPermission($controller, $action) {
        if (!$this->check()) {
            return false;
        }
        
        $routes = require 'config/routes.php';
        
        // Check public routes
        if (isset($routes['public'][$controller]) && 
            in_array($action, $routes['public'][$controller])) {
            return true;
        }
        
        // Check authenticated routes
        if (isset($routes['authenticated'][$controller])) {
            $route = $routes['authenticated'][$controller];
            
            // Check if action exists and user has required role
            if (isset($route['actions'][$action])) {
                return $this->hasAnyRole($route['actions'][$action]);
            }
            
            // If action is in simple array and user has any required role
            if (in_array($action, $route['actions'] ?? []) && 
                $this->hasAnyRole($route['roles'] ?? [])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate password reset token
     */
    public function generateResetToken($email) {
        $this->db->query('SELECT id FROM users WHERE email = :email AND deleted_at IS NULL');
        $this->db->bind(':email', $email);
        
        $user = $this->db->single();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->db->query('INSERT INTO password_resets (user_id, token, expires_at) 
                             VALUES (:user_id, :token, :expires_at)');
            $this->db->bind(':user_id', $user->id);
            $this->db->bind(':token', $token);
            $this->db->bind(':expires_at', $expires);
            
            if ($this->db->execute()) {
                return $token;
            }
        }
        
        return false;
    }
    
    /**
     * Verify password reset token
     */
    public function verifyResetToken($token) {
        $this->db->query('SELECT user_id FROM password_resets 
                         WHERE token = :token AND expires_at > NOW() 
                         ORDER BY created_at DESC LIMIT 1');
        $this->db->bind(':token', $token);
        
        $result = $this->db->single();
        
        return $result ? $result->user_id : false;
    }
    
    /**
     * Reset user password
     */
    public function resetPassword($userId, $password) {
        $this->db->query('UPDATE users SET password = :password WHERE id = :id');
        $this->db->bind(':id', $userId);
        $this->db->bind(':password', password_hash($password, PASSWORD_DEFAULT));
        
        if ($this->db->execute()) {
            // Delete used tokens
            $this->db->query('DELETE FROM password_resets WHERE user_id = :user_id');
            $this->db->bind(':user_id', $userId);
            $this->db->execute();
            
            $this->logActivity($userId, 'PASSWORD_RESET', 'Password was reset');
            return true;
        }
        
        return false;
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $description = '') {
        $this->db->query('INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                         VALUES (:user_id, :action, :description, :ip, :agent)');
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':action', $action);
        $this->db->bind(':description', $description);
        $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? null);
        $this->db->bind(':agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
        
        return $this->db->execute();
    }
}
