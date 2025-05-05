<?php
require_once 'index.php';

// Test error handling
function testErrorHandling() {
    try {
        // Test database error
        $db = Database::getInstance();
        $db->query('SELECT * FROM non_existent_table');
    } catch (Exception $e) {
        echo "Error handling test: " . $e->getMessage() . "\n";
    }
}

// Test maintenance mode
function testMaintenanceMode() {
    $maintenance = MaintenanceMode::getInstance();
    
    // Enable maintenance mode
    $maintenance->enable(3600, "Test maintenance mode");
    echo "Maintenance mode enabled: " . ($maintenance->isEnabled() ? "Yes" : "No") . "\n";
    
    // Test bypass
    $maintenance->allowIP('127.0.0.1');
    echo "Should allow localhost: " . ($maintenance->shouldAllow() ? "Yes" : "No") . "\n";
    
    // Disable maintenance mode
    $maintenance->disable();
    echo "Maintenance mode disabled: " . (!$maintenance->isEnabled() ? "Yes" : "No") . "\n";
}

// Test notifications
function testNotifications() {
    $notifier = NotificationHandler::getInstance();
    
    // Test sending notification
    $data = [
        'title' => 'Test Notification',
        'message' => 'This is a test notification'
    ];
    
    try {
        $notifier->send('system_info', $data, [
            ['id' => 1, 'email' => 'test@example.com']
        ]);
        echo "Notification sent successfully\n";
    } catch (Exception $e) {
        echo "Notification error: " . $e->getMessage() . "\n";
    }
}

// Test session handling
function testSession() {
    Session::start();
    Session::set('test_key', 'test_value');
    echo "Session value matches: " . (Session::get('test_key') === 'test_value' ? "Yes" : "No") . "\n";
    Session::remove('test_key');
    echo "Session value removed: " . (Session::get('test_key') === null ? "Yes" : "No") . "\n";
}

// Test authentication
function testAuth() {
    $auth = new Auth();
    
    try {
        // Test with invalid credentials
        $result = $auth->login('invalid@example.com', 'wrongpassword');
        echo "Invalid login blocked: " . (!$result ? "Yes" : "No") . "\n";
    } catch (Exception $e) {
        echo "Auth error handling works: Yes\n";
    }
}

// Run tests
echo "Starting feature tests...\n\n";

echo "=== Error Handling Test ===\n";
testErrorHandling();
echo "\n";

echo "=== Maintenance Mode Test ===\n";
testMaintenanceMode();
echo "\n";

echo "=== Notification System Test ===\n";
testNotifications();
echo "\n";

echo "=== Session Handling Test ===\n";
testSession();
echo "\n";

echo "=== Authentication Test ===\n";
testAuth();
echo "\n";

echo "Feature tests completed.\n";
