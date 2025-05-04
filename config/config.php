<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'salvio3_db');

// Base URL configuration
define('BASE_URL', '/Salvio3');

// Application settings
define('APP_NAME', 'Salvio3 POS');
define('APP_VERSION', '1.0.0');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Time zone
date_default_timezone_set('Asia/Jakarta');
