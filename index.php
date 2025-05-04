<?php
// Load Config
require_once 'config/config.php';

// Autoload Core Libraries
spl_autoload_register(function($className) {
    // Core classes
    if(file_exists('core/' . $className . '.php')) {
        require_once 'core/' . $className . '.php';
    }
    // Controllers
    elseif(file_exists('controllers/' . $className . '.php')) {
        require_once 'controllers/' . $className . '.php';
    }
    // Models
    elseif(file_exists('models/' . $className . '.php')) {
        require_once 'models/' . $className . '.php';
    }
});

// Start session if not already started
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parse URL
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Set default controller and method
$controllerName = !empty($url[0]) ? ucwords($url[0]) . 'Controller' : 'AuthController';
$methodName = isset($url[1]) ? $url[1] : 'index';
$params = array_slice($url, 2);

// Check if controller exists
if(file_exists('controllers/' . $controllerName . '.php')) {
    // Instantiate controller
    $controller = new $controllerName;
    
    // Check if method exists
    if(method_exists($controller, $methodName)) {
        // Call method with parameters
        call_user_func_array([$controller, $methodName], $params);
    } else {
        // Method not found - show 404
        http_response_code(404);
        require_once 'views/404.php';
    }
} else {
    // Controller not found - show 404
    http_response_code(404);
    require_once 'views/404.php';
}
