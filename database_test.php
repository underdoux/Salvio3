<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'core/Database.php';

try {
    $db = Database::getInstance();
    
    // Test categories table
    $result = $db->query("SHOW TABLES LIKE 'categories'")->resultSet();
    echo "Categories table exists: " . (!empty($result) ? "Yes" : "No") . "\n";
    
    if (!empty($result)) {
        // Check table structure
        echo "\nCategories table structure:\n";
        $columns = $db->query("DESCRIBE categories")->resultSet();
        print_r($columns);
        
        // Check if any categories exist
        echo "\nExisting categories:\n";
        $categories = $db->query("SELECT * FROM categories")->resultSet();
        print_r($categories);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
