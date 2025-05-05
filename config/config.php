<?php
/**
 * Application Configuration
 */

// Application constants
define('APP_NAME', 'Salvio POS');
define('APP_VERSION', '1.0.0');

// Path constants
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', __DIR__);
define('LOG_PATH', BASE_PATH . '/logs');
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Debug mode (set to false in production)
define('DEBUG', true);

// Application URL
define('BASE_URL', 'http://localhost/Salvio3');

// Default timezone
date_default_timezone_set('Asia/Jakarta');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Upload configuration
define('MAX_UPLOAD_SIZE', 64 * 1024 * 1024); // 64MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Security configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 72); // bcrypt limit
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutes
define('SESSION_LIFETIME', 2 * 60 * 60); // 2 hours
define('REMEMBER_ME_LIFETIME', 30 * 24 * 60 * 60); // 30 days

// Email configuration
define('MAIL_FROM', 'no-reply@example.com');
define('MAIL_FROM_NAME', 'Salvio POS');
define('MAIL_REPLY_TO', 'support@example.com');

// Server variables for testing
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

// WhatsApp configuration
define('WHATSAPP_ENABLED', false);
define('WHATSAPP_PROVIDER', 'wablas'); // wablas, twilio, fonnte
define('WHATSAPP_API_URL', 'https://api.wablas.com');
define('WHATSAPP_API_KEY', 'your-api-key');

// Notification settings
define('NOTIFICATION_STOCK_THRESHOLD', 10); // Low stock threshold
define('NOTIFICATION_PAYMENT_REMINDER_DAYS', 3); // Days before payment due
define('NOTIFICATION_ORDER_STATUS', true);
define('NOTIFICATION_NEW_CUSTOMER', true);

// Financial settings
define('DEFAULT_TAX_RATE', 0.11); // 11% tax
define('DEFAULT_CURRENCY', 'IDR');
define('CURRENCY_DECIMALS', 2);
define('THOUSAND_SEPARATOR', '.');
define('DECIMAL_SEPARATOR', ',');

// Commission settings
define('DEFAULT_COMMISSION_RATE', 0.05); // 5% commission
define('COMMISSION_CALCULATION_PERIOD', 'monthly'); // daily, weekly, monthly
define('MIN_COMMISSION_AMOUNT', 10000);

// Report settings
define('REPORT_CACHE_TIME', 60 * 60); // 1 hour
define('REPORT_DEFAULT_FORMAT', 'pdf');
define('REPORT_TIMEZONE', 'Asia/Jakarta');

// API settings
define('API_RATE_LIMIT', 60); // requests per minute
define('API_TOKEN_LIFETIME', 60 * 24 * 60 * 60); // 60 days

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 60 * 60); // 1 hour
define('CACHE_PREFIX', 'salvio_');

// Log settings
if (!defined('LOG_PATH')) {
    define('LOG_PATH', __DIR__ . '/../logs');
}
define('LOG_LEVEL', DEBUG ? 'debug' : 'error');
define('LOG_MAX_FILES', 30);

// Backup settings
define('BACKUP_PATH', __DIR__ . '/../backups');
define('BACKUP_MAX_FILES', 30);
define('BACKUP_COMPRESS', true);

// Helper functions
require_once __DIR__ . '/../helpers/functions.php';

// Load environment-specific configuration if exists
$envConfig = __DIR__ . '/config.' . (DEBUG ? 'development' : 'production') . '.php';
if (file_exists($envConfig)) {
    require_once $envConfig;
}
