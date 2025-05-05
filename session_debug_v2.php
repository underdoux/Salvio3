<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Load required files
require_once 'config/config.php';
require_once 'core/Session.php';
require_once 'core/Auth.php';

// Debug function
function debugLog($message, $data = null) {
    error_log("[Session Debug] " . $message);
    if ($data !== null) {
        error_log("[Session Debug] Data: " . print_r($data, true));
    }
}

// Debug session before start
debugLog("Before session_start()");
debugLog("Session Cookie Params:", session_get_cookie_params());
debugLog("Session Name: " . session_name());

// Start session
Session::start();

// Debug session after start
debugLog("After session_start()");
debugLog("Session ID: " . session_id());
debugLog("Session Data:", $_SESSION);

// Debug headers
debugLog("Response Headers:", headers_list());

// Debug cookie
if (isset($_COOKIE[session_name()])) {
    debugLog("Session Cookie Found:", $_COOKIE[session_name()]);
} else {
    debugLog("No Session Cookie Found");
}

// Debug Auth state
debugLog("Auth Check Result:", Auth::check());
debugLog("Current User:", Auth::user());

// Output debug info
echo "<pre>";
echo "Session Debug Information:\n\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Cookie Params:\n";
print_r(session_get_cookie_params());
echo "\nSession Data:\n";
print_r($_SESSION);
echo "\nCookies:\n";
print_r($_COOKIE);
echo "</pre>";

// Flush output buffer
ob_end_flush();
