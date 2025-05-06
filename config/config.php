<?php
/**
 * Application Configuration
 */

// Application settings
define('APP_NAME', 'Salvio POS');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, production
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost/Salvio3');
define('APP_TIMEZONE', 'Asia/Jakarta');
define('APP_LOCALE', 'id');
define('APP_CURRENCY', 'IDR');

// Load database configuration
require_once 'database.php';

// Path definitions
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CONTROLLER_PATH', ROOT_PATH . '/controllers');
define('MODEL_PATH', ROOT_PATH . '/models');
define('VIEW_PATH', ROOT_PATH . '/views');
define('CORE_PATH', ROOT_PATH . '/core');
define('HELPER_PATH', ROOT_PATH . '/helpers');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('BACKUP_PATH', ROOT_PATH . '/backups');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_NAME', 'salvio_session');
define('SESSION_LIFETIME', 7200); // 2 hours
define('REMEMBER_COOKIE_NAME', 'remember_token');
define('REMEMBER_COOKIE_LIFETIME', 2592000); // 30 days
define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);

// Upload settings
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);
define('UPLOAD_MAX_FILENAME_LENGTH', 255);

// Pagination settings
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGE_LINKS', 5);

// Email settings
define('MAIL_DRIVER', 'smtp');
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', '');
define('MAIL_FROM_NAME', APP_NAME);

// WhatsApp settings
define('WA_ENABLED', false);
define('WA_API_URL', '');
define('WA_API_KEY', '');
define('WA_SENDER', '');

// BPOM API settings
define('BPOM_API_ENABLED', true);
define('BPOM_BASE_URL', 'https://cekbpom.pom.go.id');
define('BPOM_SEARCH_URL', BPOM_BASE_URL . '/search');
define('BPOM_DETAIL_URL', BPOM_BASE_URL . '/detail');
define('BPOM_CACHE_TIME', 86400); // 24 hours

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Set locale
setlocale(LC_ALL, APP_LOCALE);

// Create required directories if they don't exist
$directories = [
    UPLOAD_PATH,
    LOG_PATH,
    CACHE_PATH,
    BACKUP_PATH,
    UPLOAD_PATH . '/products',
    UPLOAD_PATH . '/users',
    UPLOAD_PATH . '/invoices'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Initialize error logging
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/error.log');

// Set session configuration
ini_set('session.name', SESSION_NAME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = date('[Y-m-d H:i:s]') . " Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($error, 3, LOG_PATH . '/error.log');
    
    if (APP_DEBUG) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:10px;margin:10px;border:1px solid #f5c6cb;border-radius:4px'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($errstr);
        echo "<br><strong>File:</strong> " . htmlspecialchars($errfile);
        echo "<br><strong>Line:</strong> " . $errline;
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da;color:#721c24;padding:10px;margin:10px;border:1px solid #f5c6cb;border-radius:4px'>";
        echo "An error occurred. Please try again later.";
        echo "</div>";
    }
    
    return true;
}

// Set custom error handler
set_error_handler('customErrorHandler');
