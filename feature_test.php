<?php
// Load required files
require_once 'config/config.php';
require_once 'core/Database.php';
require_once 'core/Session.php';
require_once 'core/Auth.php';
require_once 'helpers/functions.php';

class FeatureTest {
    private $results = [];
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $this->addResult('Database Connection', true, 'Successfully connected to database');
        } catch (Exception $e) {
            $this->addResult('Database Connection', false, $e->getMessage());
        }
    }
    
    private function addResult($feature, $success, $message) {
        $this->results[] = [
            'feature' => $feature,
            'success' => $success,
            'message' => $message
        ];
    }
    
    public function testDatabaseTables() {
        $requiredTables = ['categories', 'products', 'sales', 'users'];
        
        foreach ($requiredTables as $table) {
            try {
                $result = $this->db->query("SHOW TABLES LIKE ?")->bind(1, $table)->resultSet();
                $exists = !empty($result);
                $this->addResult(
                    "Table: {$table}",
                    $exists,
                    $exists ? "Table exists" : "Table missing"
                );
                
                if ($exists) {
                    // Check table structure
                    $columns = $this->db->query("DESCRIBE {$table}")->resultSet();
                    $this->addResult(
                        "Table Structure: {$table}",
                        true,
                        "Columns: " . implode(", ", array_column($columns, 'Field'))
                    );
                }
            } catch (Exception $e) {
                $this->addResult("Table: {$table}", false, $e->getMessage());
            }
        }
    }
    
    public function testSessionHandling() {
        try {
            Session::start();
            $testKey = 'test_' . time();
            $testValue = 'test_value';
            
            // Test setting session value
            Session::set($testKey, $testValue);
            $retrieved = Session::get($testKey);
            $this->addResult(
                'Session Write/Read',
                $testValue === $retrieved,
                "Session value match: " . ($testValue === $retrieved ? "Yes" : "No")
            );
            
            // Test session security
            $this->addResult(
                'Session Security',
                session_get_cookie_params()['httponly'],
                'HTTPOnly: ' . (session_get_cookie_params()['httponly'] ? 'Yes' : 'No')
            );
            
        } catch (Exception $e) {
            $this->addResult('Session Handling', false, $e->getMessage());
        }
    }
    
    public function testHelperFunctions() {
        // Test URL helper
        try {
            $testUrl = url('test/path');
            $this->addResult(
                'URL Helper',
                !empty($testUrl),
                "URL generation: " . ($testUrl ? "Working" : "Failed")
            );
        } catch (Exception $e) {
            $this->addResult('URL Helper', false, $e->getMessage());
        }
    }
    
    public function testModelQueries() {
        // Test category queries
        try {
            $query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     WHERE c.status = 'active' 
                     GROUP BY c.id";
            $result = $this->db->query($query)->resultSet();
            $this->addResult(
                'Category Query',
                true,
                "Retrieved " . count($result) . " categories"
            );
        } catch (Exception $e) {
            $this->addResult('Category Query', false, $e->getMessage());
        }
    }
    
    public function runAllTests() {
        echo "Starting Feature Tests...\n\n";
        
        $this->testDatabaseTables();
        $this->testSessionHandling();
        $this->testHelperFunctions();
        $this->testModelQueries();
        
        echo "\nTest Results:\n";
        echo "=============\n\n";
        
        foreach ($this->results as $result) {
            echo sprintf(
                "%s: %s\n%s\n\n",
                $result['feature'],
                $result['success'] ? 'PASS' : 'FAIL',
                $result['message']
            );
        }
        
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($r) { return $r['success']; }));
        
        echo "\nSummary:\n";
        echo "========\n";
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: " . ($totalTests - $passedTests) . "\n";
    }
}

// Run the tests
$tester = new FeatureTest();
$tester->runAllTests();
