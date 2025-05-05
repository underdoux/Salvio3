<?php
/**
 * Main Application Entry Point
 */

// Define base path
define('BASE_PATH', __DIR__);
define('CONFIG_PATH', BASE_PATH . '/config');
define('LOG_PATH', BASE_PATH . '/logs');

// Load configuration
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/database.php';

// Load core classes
require_once 'core/Controller.php';
require_once 'core/Model.php';
require_once 'core/View.php';
require_once 'core/Database.php';
require_once 'core/Session.php';
require_once 'core/Auth.php';

// Load helpers
require_once 'helpers/error_handler.php';
require_once 'helpers/maintenance_mode.php';

// Start session
Session::start();

try {
    // Initialize error handler
    $errorHandler = ErrorHandler::getInstance();
    $errorHandler->register();
    
    // Set error reporting based on environment
    if (DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    // Check maintenance mode
    $maintenance = MaintenanceMode::getInstance();
    $maintenance->handle();

    // Parse URL
    $url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
    $url = filter_var($url, FILTER_SANITIZE_URL);
    $url = explode('/', $url);

    // Determine controller
    $controllerName = !empty($url[0]) ? ucfirst($url[0]) . 'Controller' : 'DashboardController';
    $controllerFile = 'controllers/' . $controllerName . '.php';

    if (!file_exists($controllerFile)) {
        throw new Exception('Controller not found: ' . $controllerName);
    }

    require_once $controllerFile;
    $controller = new $controllerName();

    // Determine action
    $action = isset($url[1]) ? $url[1] : 'index';
    if (!method_exists($controller, $action)) {
        throw new Exception('Action not found: ' . $action);
    }

    // Get parameters
    $params = array_slice($url, 2);

    // Execute action
    call_user_func_array([$controller, $action], $params);

} catch (Exception $e) {
    // Handle any uncaught exceptions
    if (DEBUG) {
        $error = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    } else {
        $error = [
            'message' => 'An error occurred. Please try again later.',
            'file' => '',
            'line' => '',
            'trace' => ''
        ];
    }

    require_once 'controllers/ErrorController.php';
    $errorController = new ErrorController();
    $errorController->serverError($error);
}

/**
 * Helper Functions
 */

/**
 * Get base URL
 */
function base_url($path = '') {
    $base = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $base .= $_SERVER['HTTP_HOST'];
    $base .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    return $base . ltrim($path, '/');
}

/**
 * Format currency
 */
function format_currency($amount, $currency = DEFAULT_CURRENCY) {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($datetime));
}

/**
 * Check if URL matches pattern
 */
function url_is($pattern) {
    $url = isset($_GET['url']) ? $_GET['url'] : '';
    return fnmatch($pattern, $url);
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . base_url($url));
    exit;
}

/**
 * Get current URL
 */
function current_url() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

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
