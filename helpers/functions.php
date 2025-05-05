<?php
/**
 * Global Helper Functions
 */

if (!function_exists('url')) {
    /**
     * Generate URL for route
     */
    function url($path = '') {
        return base_url($path);
    }
}


if (!function_exists('base_url')) {
    /**
     * Get base URL
     */
    function base_url($path = '') {
        $base = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $base .= $_SERVER['HTTP_HOST'];
        $base .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        return $base . ltrim($path, '/');
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format currency
     */
    function format_currency($amount, $currency = DEFAULT_CURRENCY) {
        return $currency . ' ' . number_format($amount, 2);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date
     */
    function format_date($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format datetime
     */
    function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('url_is')) {
    /**
     * Check if URL matches pattern
     */
    function url_is($pattern) {
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        return fnmatch($pattern, $url);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to URL
     */
    function redirect($url) {
        header('Location: ' . base_url($url));
        exit;
    }
}

if (!function_exists('current_url')) {
    /**
     * Get current URL
     */
    function current_url() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
               "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
}

if (!function_exists('generate_csrf_token')) {
    /**
     * Generate CSRF token
     */
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validate_csrf_token')) {
    /**
     * Validate CSRF token
     */
    function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML
     */
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('log_activity')) {
    /**
     * Log activity
     */
    function log_activity($type, $description, $userId = null) {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }

        $db = Database::getInstance();
        $db->query("
            INSERT INTO activity_logs (user_id, type, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ")
        ->bind(1, $userId)
        ->bind(2, $type)
        ->bind(3, $description)
        ->bind(4, $_SERVER['REMOTE_ADDR'])
        ->bind(5, $_SERVER['HTTP_USER_AGENT'])
        ->execute();
    }
}
