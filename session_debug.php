<?php
require_once 'config/config.php';
require_once 'core/Session.php';

// Start session using our Session class
Session::start();

// Try to set a value
Session::set('test_key', 'test_value');

// Print session info
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie Parameters:\n";
print_r(session_get_cookie_params());

echo "\nSession Data:\n";
print_r($_SESSION);

// Try to get the value back
echo "\nRetrieving test value:\n";
echo Session::get('test_key');
