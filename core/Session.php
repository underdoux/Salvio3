<?php
/**
 * Session Class
 * Handles session management and flash messages
 */
class Session {
    /**
     * Start session
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set session value
     * @param string $key Session key
     * @param mixed $value Session value
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     * @param string $key Session key
     * @return bool
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     * @param string $key Session key
     */
    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data
     */
    public static function clear() {
        session_unset();
        session_destroy();
    }

    /**
     * Regenerate session ID
     * @param bool $deleteOldSession Delete old session data
     */
    public static function regenerate($deleteOldSession = true) {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Set flash message
     * @param string $type Message type (success, error, info, warning)
     * @param string $message Message content
     */
    public static function setFlash($type, $message) {
        self::set('flash_' . $type, $message);
    }

    /**
     * Get flash message
     * @param string $type Message type
     * @return string|null Message content
     */
    public static function getFlash($type) {
        $key = 'flash_' . $type;
        $message = self::get($key);
        self::remove($key);
        return $message;
    }

    /**
     * Check if flash message exists
     * @param string $type Message type
     * @return bool
     */
    public static function hasFlash($type) {
        return self::has('flash_' . $type);
    }

    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        return $token;
    }

    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool
     */
    public static function verifyCsrfToken($token) {
        return hash_equals(self::get('csrf_token', ''), $token);
    }
}
