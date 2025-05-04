<?php
class Session {
    /**
     * Start the session with secure settings
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Lax');
            
            session_start();
            
            // Regenerate session ID periodically to prevent fixation attacks
            if (!isset($_SESSION['last_regeneration'])) {
                self::regenerate();
            } else if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
                self::regenerate();
            }
        }
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Set a session value
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get a session value
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove a session value
     */
    public static function remove($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Get and remove a session value (flash data)
     */
    public static function flash($key, $default = null) {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($message, $type = 'success') {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Get all flash messages and clear them
     */
    public static function getFlash() {
        $messages = self::get('flash_messages', []);
        self::remove('flash_messages');
        return $messages;
    }
    
    /**
     * Set user data in session
     */
    public static function setUser($user) {
        self::set('user_id', $user->id);
        self::set('user_name', $user->name);
        self::set('user_role', $user->role);
        self::set('user_email', $user->email);
        self::set('last_activity', time());
    }
    
    /**
     * Get current user data
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return (object) [
            'id' => self::get('user_id'),
            'name' => self::get('user_name'),
            'role' => self::get('user_role'),
            'email' => self::get('user_email')
        ];
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return self::has('user_id');
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return self::get('user_role') === $role;
    }
    
    /**
     * Clear user session (logout)
     */
    public static function destroy() {
        session_unset();
        session_destroy();
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    /**
     * Check session timeout
     * Returns true if session has timed out
     */
    public static function checkTimeout($timeout = 1800) { // 30 minutes default
        if (!self::has('last_activity')) {
            return true;
        }
        
        if (time() - self::get('last_activity') > $timeout) {
            self::destroy();
            return true;
        }
        
        self::set('last_activity', time());
        return false;
    }
    
    /**
     * Get CSRF token
     */
    public static function getCsrfToken() {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        return hash_equals(self::getCsrfToken(), $token);
    }
}
