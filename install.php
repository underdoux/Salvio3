<?php
/**
 * Installation Script
 * Sets up initial admin user
 */

// Load configuration
require_once 'config/config.php';

// Connect to database
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminExists = (int)$stmt->fetchColumn() > 0;

    if (!$adminExists) {
        // Create admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, email, name, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['admin', $password, 'admin@example.com', 'Administrator', 'admin']);
        
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists.\n";
    }

    // Create installed flag file
    file_put_contents('config/installed.php', '<?php define("APP_INSTALLED", true);');

    echo "Installation completed successfully!\n";

} catch (PDOException $e) {
    die("Installation failed: " . $e->getMessage());
}
