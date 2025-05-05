<?php
echo "Running database migrations...\n";

$host = 'localhost';
$dbname = 'salvio3';
$username = 'root';
$password = '';

try {
    // First connect without database selected
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    echo "Creating database if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database created or already exists.\n";

    // Select the database
    $pdo->exec("USE $dbname");
    echo "Using database: $dbname\n";

    // Drop all existing tables
    echo "Dropping existing tables...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Disable foreign key checks before dropping tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped table: $table\n";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "All existing tables dropped.\n";

    $migrations = [
        'schema.sql',
        'updates/004_add_commission_and_price_tables.sql',
        'updates/005_add_sales_tables.sql',
        'updates/006_add_price_and_commission_tables.sql',
        'updates/007_add_investor_tables.sql',
        'updates/008_add_supplier_tables.sql',
        'updates/009_add_reporting_tables.sql',
        'updates/010_add_notification_tables.sql'
    ];

    foreach ($migrations as $migration) {
        echo "\nRunning $migration...\n";
        $sql = file_get_contents(__DIR__ . '/' . $migration);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    // Check if statement is creating a table
                    if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                        $tableName = $matches[1];
                        // Skip if table already exists
                        $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
                        if ($tableExists) {
                            echo "Table exists: $tableName\n";
                            echo "Current structure:\n";
                            $structure = $pdo->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_ASSOC);
                            print_r($structure);
                            continue;
                        }
                    }
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Skip "table already exists" errors
                    if ($e->getCode() != '42S01') {
                        echo "Error in statement: " . substr($statement, 0, 100) . "...\n";
                        throw $e;
                    }
                }
            }
        }
        echo "Completed $migration\n";
    }

    echo "\nAll migrations completed successfully!\n";
    
    // Show all created tables
    echo "\nCreated tables:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
