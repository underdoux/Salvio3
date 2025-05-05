<?php
/**
 * Database Class
 * Handles database connections and queries using PDO
 */
class Database {
    private static $instance = null;
    private $pdo;
    private $stmt;
    private $error;
    private $inTransaction = false;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Database Connection Error: " . $this->error);
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Get database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prepare statement
     */
    public function query($sql) {
        try {
            $this->stmt = $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query Preparation Error: " . $this->error);
            throw new Exception("Query preparation failed");
        }
        return $this;
    }

    /**
     * Bind value to parameter
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        try {
            $this->stmt->bindValue($param, $value, $type);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Bind Error: " . $this->error);
            throw new Exception("Parameter binding failed");
        }

        return $this;
    }

    /**
     * Execute prepared statement
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Execution Error: " . $this->error);
            throw new Exception("Query execution failed");
        }
    }

    /**
     * Get result set
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Get single record
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * Get row count
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->pdo->beginTransaction();
        }
        return $this->inTransaction;
    }

    /**
     * Commit transaction
     */
    public function commit() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->pdo->commit();
        }
        return false;
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->pdo->rollBack();
        }
        return false;
    }

    /**
     * Get error info
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Check if in transaction
     */
    public function isInTransaction() {
        return $this->inTransaction;
    }

    /**
     * Get PDO instance
     */
    public function getPdo() {
        return $this->pdo;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserializing of singleton
     */
    public function __wakeup(): void {
        throw new Exception("Cannot unserialize singleton");
    }
}
