<?php
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'core/Model.php';
require_once 'models/User.php';
require_once 'core/Session.php';
require_once 'core/Auth.php';

// Start session
Session::start();

// Initialize Auth
Auth::init();

// Create User model instance
$userModel = new User();

// Test admin credentials
$username = 'admin';
$password = 'admin123';

echo "Testing admin login with:\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n\n";

// Attempt login
if (Auth::attempt($username, $password)) {
    echo "Login successful!\n";
    echo "User data:\n";
    print_r(Auth::user());
} else {
    echo "Login failed!\n";
    
    // Debug: Check if user exists
    $user = $userModel->findByUsername($username);
    if ($user) {
        echo "User found in database:\n";
        echo "ID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Role: {$user['role']}\n";
        echo "Status: {$user['status']}\n";
    } else {
        echo "User not found in database!\n";
    }
}

// Display session data
echo "\nSession data:\n";
print_r($_SESSION);
