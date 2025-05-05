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

// Load helper functions
require_once 'helpers/functions.php';
