<?php
// Load configuration
require_once 'config/config.php';
require_once 'config/database.php';

// Load core classes
require_once 'core/Controller.php';
require_once 'core/Model.php';
require_once 'core/View.php';
require_once 'core/Database.php';
require_once 'core/Session.php';
require_once 'core/Auth.php';

// Load helpers
require_once 'helpers/error_handler.php';
require_once 'helpers/maintenance_mode.php';
require_once 'helpers/notification_handler.php';

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
    try {
        $db = Database::getInstance();
        
        // Check if notification tables exist
        $tables = $db->query("SHOW TABLES LIKE 'notification_%'")->resultSet();
        echo "Found notification tables:\n";
        foreach ($tables as $table) {
            $tableName = current($table);
            echo "- $tableName\n";
            
            // Show table structure
            $columns = $db->query("DESCRIBE $tableName")->resultSet();
            echo "  Columns:\n";
            foreach ($columns as $column) {
                echo "    {$column['Field']} ({$column['Type']})\n";
            }
            echo "\n";
        }
        
        // Now test notification sending
        $notifier = NotificationHandler::getInstance();
        
        // Create a test user first with unique username and email
        $timestamp = time();
        $uniqueUsername = 'testuser_' . $timestamp;
        $uniqueEmail = 'test' . $timestamp . '@example.com';
        $db->query("
            INSERT INTO users (username, password, email, name, role, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ")
        ->bind(1, $uniqueUsername)
        ->bind(2, password_hash('testpass', PASSWORD_DEFAULT))
        ->bind(3, $uniqueEmail)
        ->bind(4, 'Test User')
        ->bind(5, 'admin')
        ->bind(6, 'active')
        ->execute();
        
        $userId = $db->lastInsertId();
        
        // Send notification
        $data = [
            'type' => 'system_info',
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'priority' => 'normal',
            'icon' => 'bell',
            'color' => 'primary',
            'user_id' => $userId  // Pass the user ID for template creation
        ];
        
        $notifier->send('system_info', $data, [
            ['id' => $userId, 'email' => $uniqueEmail]
        ]);
        echo "Notification sent successfully\n";
        
        // Clean up test user
        $db->query("DELETE FROM users WHERE id = ?")
        ->bind(1, $userId)
        ->execute();
        
    } catch (Exception $e) {
        echo "Notification error: " . $e->getMessage() . "\n";
    }
}

// Test session handling
function testSession() {
    $session = Session::getInstance();
    $session->set('test_key', 'test_value');
    echo "Session value matches: " . ($session->get('test_key') === 'test_value' ? "Yes" : "No") . "\n";
    $session->remove('test_key');
    echo "Session value removed: " . ($session->get('test_key') === null ? "Yes" : "No") . "\n";
}

// Test authentication
function testAuth() {
    $auth = Auth::getInstance();
    
    try {
        // Test with invalid credentials
        $result = $auth->attempt('invalid@example.com', 'wrongpassword');
        echo "Invalid login blocked: " . (!$result ? "Yes" : "No") . "\n";
    } catch (Exception $e) {
        echo "Auth error handling works: Yes\n";
    }
}

// Initialize session
Session::start();

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
