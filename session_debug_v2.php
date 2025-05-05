<?php
// Start session with debug info
session_start();
error_log("[Session Debug] Starting session debug test");
error_log("[Session Debug] Session ID: " . session_id());

// Display session info
echo "<h2>Session Debug Information</h2>";
echo "<pre>";

echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Parameters:\n";
print_r(session_get_cookie_params());

echo "\nSession Data:\n";
print_r($_SESSION);

echo "\nCookies:\n";
print_r($_COOKIE);

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
