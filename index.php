<?php
/**
 * Main entry point for the Pharmacy POS System
 * Handles routing and bootstrapping
 */

// Define root path
define('ROOT_PATH', __DIR__);

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'config/config.php';

// Load core classes
require_once 'core/Database.php';
require_once 'core/Model.php';
require_once 'core/View.php';
require_once 'core/Controller.php';
require_once 'core/Session.php';
require_once 'core/Auth.php';

// Start session
Session::start();

// Initialize authentication
Auth::init();

// Parse the URL
$url = isset($_GET['url']) ? $_GET['url'] : '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);

// Default controller and action
$controller = !empty($url[0]) ? $url[0] : 'auth';
$action = isset($url[1]) ? $url[1] : 'index';

// Capitalize controller name for class loading
$controller = ucfirst($controller) . 'Controller';

// Load and initialize controller
if (file_exists('controllers/' . $controller . '.php')) {
    require_once 'controllers/' . $controller . '.php';
    $controller = new $controller();
    
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        // Handle 404
        header("HTTP/1.0 404 Not Found");
        echo "Action not found!";
    }
} else {
    // Handle 404
    header("HTTP/1.0 404 Not Found");
    echo "Controller not found!";
}
