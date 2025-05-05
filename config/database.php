<?php
/**
 * Database Configuration
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'salvio3');
define('DB_USER', 'root');
define('DB_PASS', '');

// Database charset and collation
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Database connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Connection timeout in seconds
define('DB_TIMEOUT', 30);

// Maximum number of connections
define('DB_MAX_CONNECTIONS', 100);

// Whether to use persistent connections
define('DB_PERSISTENT', true);

// Backup settings
define('DB_BACKUP_PATH', __DIR__ . '/../backups/database');
define('DB_BACKUP_COMPRESS', true);
define('DB_BACKUP_MAX_FILES', 30);

/**
 * Get database configuration as array
 */
function get_db_config() {
    return [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'charset' => DB_CHARSET,
        'options' => DB_OPTIONS
    ];
}

/**
 * Get database DSN
 */
function get_db_dsn() {
    return sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
}

/**
 * Create database backup
 */
function create_db_backup() {
    $backupPath = DB_BACKUP_PATH;
    if (!file_exists($backupPath)) {
        mkdir($backupPath, 0755, true);
    }

    $filename = sprintf(
        '%s/backup_%s.sql%s',
        $backupPath,
        date('Y-m-d_H-i-s'),
        DB_BACKUP_COMPRESS ? '.gz' : ''
    );

    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s %s > %s',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        DB_BACKUP_COMPRESS ? '| gzip' : '',
        escapeshellarg(DB_NAME),
        escapeshellarg($filename)
    );

    exec($command, $output, $returnVar);
    
    if ($returnVar !== 0) {
        throw new Exception('Database backup failed');
    }

    // Clean old backups
    $files = glob($backupPath . '/backup_*.sql*');
    if (count($files) > DB_BACKUP_MAX_FILES) {
        array_map('unlink', array_slice($files, 0, count($files) - DB_BACKUP_MAX_FILES));
    }

    return $filename;
}

/**
 * Restore database from backup
 */
function restore_db_backup($filename) {
    if (!file_exists($filename)) {
        throw new Exception('Backup file not found');
    }

    $isCompressed = pathinfo($filename, PATHINFO_EXTENSION) === 'gz';
    
    $command = sprintf(
        '%s %s | mysql --host=%s --user=%s --password=%s %s',
        $isCompressed ? 'gunzip -c' : 'cat',
        escapeshellarg($filename),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_NAME)
    );

    exec($command, $output, $returnVar);
    
    if ($returnVar !== 0) {
        throw new Exception('Database restore failed');
    }

    return true;
}

/**
 * Check database connection
 */
function check_db_connection() {
    try {
        $db = new PDO(
            get_db_dsn(),
            DB_USER,
            DB_PASS,
            DB_OPTIONS
        );
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get database size in bytes
 */
function get_db_size() {
    $db = new PDO(
        get_db_dsn(),
        DB_USER,
        DB_PASS,
        DB_OPTIONS
    );

    $sql = "SELECT 
        SUM(data_length + index_length) AS size
        FROM information_schema.tables
        WHERE table_schema = ?
        GROUP BY table_schema";

    $stmt = $db->prepare($sql);
    $stmt->execute([DB_NAME]);
    
    return (int) $stmt->fetchColumn();
}

// Load environment-specific database configuration if exists
$envConfig = __DIR__ . '/database.' . (DEBUG ? 'development' : 'production') . '.php';
if (file_exists($envConfig)) {
    require_once $envConfig;
}
