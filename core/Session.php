<?php
/**
 * Session Class
 * Handles session management and flash messages
 */
class Session {
    private static $instance = null;
    private $flash = [];

    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize flash data
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        
        // Move flash data to instance
        $this->flash = $_SESSION['flash'];
        $_SESSION['flash'] = [];
    }

    /**
     * Get Session instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set session value
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     */
    public function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Remove session value
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Check if session key exists
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Set flash message
     */
    public function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get flash messages
     */
    public function getFlash() {
        return $this->flash;
    }

    /**
     * Check if has flash message
     */
    public function hasFlash($type) {
        return isset($this->flash[$type]);
    }

    /**
     * Get flash message
     */
    public function getFlashMessage($type) {
        return isset($this->flash[$type]) ? $this->flash[$type] : null;
    }

    /**
     * Clear all flash messages
     */
    public function clearFlash() {
        $this->flash = [];
        $_SESSION['flash'] = [];
    }

    /**
     * Regenerate session ID
     */
    public function regenerate() {
        session_regenerate_id(true);
    }

    /**
     * Destroy session
     */
    public function destroy() {
        session_destroy();
        $this->flash = [];
        $_SESSION = [];
    }

    /**
     * Set multiple session values
     */
    public function setMultiple(array $data) {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get multiple session values
     */
    public function getMultiple(array $keys, $default = null) {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * Remove multiple session values
     */
    public function removeMultiple(array $keys) {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    /**
     * Get all session data
     */
    public function all() {
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear() {
        $_SESSION = [];
    }

    /**
     * Get session ID
     */
    public function getId() {
        return session_id();
    }

    /**
     * Set session name
     */
    public function setName($name) {
        session_name($name);
    }

    /**
     * Get session name
     */
    public function getName() {
        return session_name();
    }

    /**
     * Set session cookie parameters
     */
    public function setCookieParams($lifetime, $path, $domain, $secure = false, $httponly = true) {
        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    }

    /**
     * Get session cookie parameters
     */
    public function getCookieParams() {
        return session_get_cookie_params();
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
