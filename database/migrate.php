<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

function runMigration($file) {
    $db = Database::getInstance();
    $sql = file_get_contents($file);
    
    try {
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', 
                explode(';', $sql)
            )
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->query($statement)->execute();
            }
        }
        echo "Successfully executed migration: " . basename($file) . "\n";
    } catch (Exception $e) {
        echo "Error executing migration: " . $e->getMessage() . "\n";
        throw $e;
    }
}

// Run the migration
$migrationFile = $argv[1] ?? null;
if (!$migrationFile) {
    die("Please provide a migration file path\n");
}

runMigration($migrationFile);
