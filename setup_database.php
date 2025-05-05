<?php
// Load configuration
require_once 'config/config.php';

try {
    // Create database connection
    $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "Database created successfully\n";

    // Select database
    $pdo->exec("USE " . DB_NAME);

    // Read and execute schema.sql
    $schema = file_get_contents('database/schema.sql');
    $pdo->exec($schema);
    echo "Schema imported successfully\n";

    // Create admin user if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminExists = (int)$stmt->fetchColumn() > 0;

    if (!$adminExists) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, email, name, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', $password, 'admin@example.com', 'Administrator', 'admin']);
        echo "Admin user created successfully\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    }

    // Create installed flag file
    file_put_contents('config/installed.php', '<?php define("APP_INSTALLED", true);');
    echo "Installation completed successfully!\n";

} catch (PDOException $e) {
    die("Installation failed: " . $e->getMessage() . "\n");
}
