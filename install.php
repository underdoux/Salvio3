<?php
/**
 * Installation Script
 * Sets up the database and initial configuration
 */

// Check if already installed
if (file_exists('config/installed.php')) {
    die('Application is already installed. Remove config/installed.php to reinstall.');
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database default configuration
$config = [
    'host' => 'localhost',
    'name' => 'salvio3_pos',
    'user' => 'root',
    'pass' => ''
];

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $config['host'] = $_POST['db_host'] ?? 'localhost';
    $config['name'] = $_POST['db_name'] ?? 'salvio3_pos';
    $config['user'] = $_POST['db_user'] ?? 'root';
    $config['pass'] = $_POST['db_pass'] ?? '';

    try {
        // Test database connection
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Create database if not exists
        $pdo->exec("DROP DATABASE IF EXISTS `{$config['name']}`");
        $pdo->exec("CREATE DATABASE `{$config['name']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$config['name']}`");

        // Import schema
        $schema = file_get_contents('database/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $schema)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }

        // Create default admin user
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, email, name, role, status) VALUES 
                   ('admin', '{$password}', 'admin@example.com', 'Administrator', 'admin', 'active')");

        // Create database config file
        $dbConfig = "<?php
/**
 * Database Configuration
 */

// Database credentials
define('DB_HOST', '{$config['host']}');
define('DB_NAME', '{$config['name']}');
define('DB_USER', '{$config['user']}');
define('DB_PASS', '{$config['pass']}');

// Additional database settings
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
";

        file_put_contents('config/database.php', $dbConfig);

        // Create installed file
        file_put_contents('config/installed.php', '<?php return true;');

        // Create required directories
        $directories = [
            'uploads',
            'uploads/products',
            'uploads/users',
            'uploads/invoices',
            'logs',
            'cache',
            'backups'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $success = 'Installation completed successfully! Please delete install.php for security.';

    } catch (PDOException $e) {
        $error = 'Database Error: ' . $e->getMessage();
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Salvio POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Salvio POS Installation</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= $success ?>
                                <hr>
                                <p class="mb-0">
                                    Login credentials:<br>
                                    Username: admin<br>
                                    Password: admin123
                                </p>
                            </div>
                            <div class="text-center">
                                <a href="<?= 'http://localhost/Salvio3/auth' ?>" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="db_host" 
                                           name="db_host" 
                                           value="<?= htmlspecialchars($config['host']) ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="db_name" 
                                           name="db_name" 
                                           value="<?= htmlspecialchars($config['name']) ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Database Username</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="db_user" 
                                           name="db_user" 
                                           value="<?= htmlspecialchars($config['user']) ?>" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="db_pass" 
                                           name="db_pass" 
                                           value="<?= htmlspecialchars($config['pass']) ?>">
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        Install
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>
