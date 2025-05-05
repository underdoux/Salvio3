<?php
/**
 * Session Class
 * Handles session management and flash messages
 */
class Session {
    private static $initialized = false;

    /**
     * Start session
     */
    public static function start() {
        if (self::$initialized) {
            return;
        }

        if (headers_sent()) {
            error_log("[Session] Headers already sent, cannot start session");
            return;
        }

        // Get application path for cookie
        $appPath = '/';  // Use root path to ensure cookie is available across all paths

        // Set session save handler if needed
        ini_set('session.save_handler', 'files');
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        
        // Get the application base path from APP_URL
        $basePath = parse_url(APP_URL, PHP_URL_PATH);
        if (!$basePath) $basePath = '';

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => $basePath,
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Debug log cookie settings
        error_log("[Session] Cookie settings:");
        error_log("[Session] Path: " . ($basePath ? '/' . $basePath : '/'));
        error_log("[Session] Domain: " . $_SERVER['HTTP_HOST']);
        error_log("[Session] Secure: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'true' : 'false'));

        // Set session handler configurations
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cache_limiter', 'nocache');

        // Debug log
        error_log("[Session] Starting session with parameters:");
        error_log("[Session] Path: " . $appPath);
        error_log("[Session] Cookie lifetime: " . SESSION_LIFETIME);
        error_log("[Session] Session name: " . SESSION_NAME);

        // Set session name
        session_name(SESSION_NAME);

        // Start or resume session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
            self::$initialized = true;

            // Initialize session if needed
            if (!isset($_SESSION['__initialized'])) {
                session_regenerate_id(true);
                $_SESSION['__initialized'] = true;
                $_SESSION['__last_activity'] = time();
            }

            // Check session expiry
            if (isset($_SESSION['__last_activity']) && (time() - $_SESSION['__last_activity'] > SESSION_LIFETIME)) {
                self::clear();
                session_start();
                self::$initialized = true;
                $_SESSION['__initialized'] = true;
            }

            // Update last activity time
            $_SESSION['__last_activity'] = time();
        } else {
            error_log("[Session] Failed to start session - Headers already sent or session_start failed");
        }
    }

    /**
     * Set session value
     * @param string $key Session key
     * @param mixed $value Session value
     */
    public static function set($key, $value) {
        self::ensureStarted();
        $_SESSION[$key] = $value;
        error_log("[Session] Set {$key}: " . print_r($value, true));
    }

    /**
     * Get session value
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::ensureStarted();
        $value = $_SESSION[$key] ?? $default;
        error_log("[Session] Get {$key}: " . print_r($value, true));
        return $value;
    }

    /**
     * Check if session key exists
     * @param string $key Session key
     * @return bool
     */
    public static function has($key) {
        self::ensureStarted();
        $exists = isset($_SESSION[$key]);
        error_log("[Session] Check {$key} exists: " . ($exists ? 'true' : 'false'));
        return $exists;
    }

    /**
     * Remove session key
     * @param string $key Session key
     */
    public static function remove($key) {
        self::ensureStarted();
        error_log("[Session] Remove {$key}");
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data
     */
    public static function clear() {
        self::ensureStarted();
        error_log("[Session] Clear all session data");
        $_SESSION = [];
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }
        
        session_destroy();
        self::$initialized = false;
    }

    /**
     * Regenerate session ID
     * @param bool $deleteOldSession Delete old session data
     */
    public static function regenerate($deleteOldSession = true) {
        self::ensureStarted();
        $oldId = session_id();
        if (session_regenerate_id($deleteOldSession)) {
            error_log("[Session] Regenerated session ID. Old: {$oldId}, New: " . session_id());
            return true;
        }
        error_log("[Session] Failed to regenerate session ID!");
        return false;
    }

    /**
     * Set flash message
     * @param string $type Message type (success, error, info, warning)
     * @param string $message Message content
     */
    public static function setFlash($type, $message) {
        self::ensureStarted();
        error_log("[Session] Set flash message - Type: {$type}, Message: {$message}");
        self::set('flash_' . $type, $message);
    }

    /**
     * Get flash message
     * @param string $type Message type
     * @return string|null Message content
     */
    public static function getFlash($type) {
        self::ensureStarted();
        $key = 'flash_' . $type;
        $message = self::get($key);
        self::remove($key);
        error_log("[Session] Get flash message - Type: {$type}, Message: " . ($message ?? 'null'));
        return $message;
    }

    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateCsrfToken() {
        self::ensureStarted();
        $token = bin2hex(random_bytes(32));
        self::set('csrf_token', $token);
        error_log("[Session] Generated CSRF token: {$token}");
        return $token;
    }

    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool
     */
    public static function verifyCsrfToken($token) {
        self::ensureStarted();
        $valid = hash_equals(self::get('csrf_token', ''), $token);
        error_log("[Session] Verify CSRF token: " . ($valid ? 'valid' : 'invalid'));
        return $valid;
    }

    /**
     * Check if flash message exists
     * @param string $type Message type
     * @return bool
     */
    public static function hasFlash($type) {
        self::ensureStarted();
        $exists = isset($_SESSION['flash_' . $type]);
        error_log("[Session] Check flash message exists - Type: {$type}, Exists: " . ($exists ? 'true' : 'false'));
        return $exists;
    }

    /**
     * Ensure session is started
     */
    private static function ensureStarted() {
        if (!self::$initialized) {
            self::start();
        }
    }
}
