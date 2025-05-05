<?php
// Load configuration
require_once 'config/config.php';
require_once 'core/Session.php';

// Start session with debug info
Session::start();
error_log("[Session Debug] Starting session debug test");
error_log("[Session Debug] Session ID: " . session_id());

// Display session info
echo "<h2>Session Debug Information</h2>";
echo "<pre>";

echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";

echo "\nApplication Info:\n";
echo "APP_URL: " . BASE_URL . "\n";
echo "SESSION_NAME: " . session_name() . "\n";
echo "SESSION_LIFETIME: " . SESSION_LIFETIME . "\n";

echo "\nRequest Info:\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";

echo "\nSession Cookie Parameters:\n";
print_r(session_get_cookie_params());

echo "\nSession Data:\n";
print_r($_SESSION);

echo "\nCookies:\n";
print_r($_COOKIE);

echo "\nSession Name: " . session_name() . "\n";
echo "Session ID: " . session_id() . "\n";

echo "\nServer Variables:\n";
foreach ($_SERVER as $name => $value) {
    if (is_string($value)) {
        echo "$name: $value\n";
    }
}

echo "\nPHP Session Settings:\n";
$sessionSettings = [
    'session.save_handler',
    'session.save_path',
    'session.use_cookies',
    'session.use_only_cookies',
    'session.name',
    'session.auto_start',
    'session.cookie_lifetime',
    'session.cookie_path',
    'session.cookie_domain',
    'session.cookie_secure',
    'session.cookie_httponly',
    'session.serialize_handler',
    'session.gc_maxlifetime',
    'session.gc_probability',
    'session.gc_divisor',
    'session.cache_limiter',
    'session.cache_expire',
];

foreach ($sessionSettings as $setting) {
    echo "$setting: " . ini_get($setting) . "\n";
}

// Test session write
$_SESSION['test_value'] = 'Session test at ' . date('Y-m-d H:i:s');
echo "\nTest value written to session\n";

// Check session directory permissions
$sessionPath = session_save_path();
echo "\nSession Directory Permissions:\n";
echo "Path: $sessionPath\n";
echo "Exists: " . (file_exists($sessionPath) ? 'Yes' : 'No') . "\n";
echo "Readable: " . (is_readable($sessionPath) ? 'Yes' : 'No') . "\n";
echo "Writable: " . (is_writable($sessionPath) ? 'Yes' : 'No') . "\n";

// Check for session file
$sessionFile = $sessionPath . '/sess_' . session_id();
echo "\nSession File:\n";
echo "Path: $sessionFile\n";
echo "Exists: " . (file_exists($sessionFile) ? 'Yes' : 'No') . "\n";
if (file_exists($sessionFile)) {
    echo "Size: " . filesize($sessionFile) . " bytes\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($sessionFile)), -4) . "\n";
    echo "Content:\n" . file_get_contents($sessionFile) . "\n";
}

echo "</pre>";
