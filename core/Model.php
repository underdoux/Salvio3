<?php
/**
 * Base Model Class
 * All models extend this class
 */
class Model {
    protected $db;
    protected $table;
    protected $fillable = [];
    protected $hidden = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     * @param int $id
     * @return array|null
     */
    public function find($id) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE id = ?")
                       ->bind(1, $id)
                       ->single();
    }

    /**
     * Find record by field value
     * @param string $field
     * @param mixed $value
     * @return array|null
     */
    public function findOneBy($field, $value) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE {$field} = ?")
                       ->bind(1, $value)
                       ->single();
    }

    /**
     * Find records by field value
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function findBy($field, $value) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE {$field} = ?")
                       ->bind(1, $value)
                       ->resultSet();
    }

    /**
     * Get all records
     * @return array
     */
    public function all() {
        return $this->db->query("SELECT * FROM {$this->table}")
                       ->resultSet();
    }

    /**
     * Create new record
     * @param array $data
     * @return int|bool Last insert ID or false on failure
     */
    public function create($data) {
        // Filter only fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($data)) {
            return false;
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = str_repeat('?, ', count($data) - 1) . '?';
        
        $query = $this->db->query(
            "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})"
        );

        $i = 1;
        foreach ($data as $value) {
            $query->bind($i++, $value);
        }

        return $query->execute() ? $this->db->lastInsertId() : false;
    }

    /**
     * Update record
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        // Filter only fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($data)) {
            return false;
        }

        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = ?";
        }
        
        $query = $this->db->query(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?"
        );

        $i = 1;
        foreach ($data as $value) {
            $query->bind($i++, $value);
        }
        $query->bind($i, $id);

        return $query->execute();
    }

    /**
     * Delete record
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?")
                       ->bind(1, $id)
                       ->execute();
    }

    /**
     * Count records
     * @param string|null $where Optional WHERE clause
     * @return int
     */
    public function count($where = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->query($sql)->single();
        return (int)$result['count'];
    }

    /**
     * Search records
     * @param array $fields Fields to search in
     * @param string $keyword Search keyword
     * @param string|null $orderBy Order by field
     * @param string $order Order direction (ASC/DESC)
     * @return array
     */
    public function search($fields, $keyword, $orderBy = null, $order = 'ASC') {
        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $conditions);
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }
        
        $query = $this->db->query($sql);
        
        $i = 1;
        foreach ($params as $param) {
            $query->bind($i++, $param);
        }
        
        return $query->resultSet();
    }

    /**
     * Paginate records
     * @param int $page Page number
     * @param int $perPage Records per page
     * @param string|null $where Optional WHERE clause
     * @param string|null $orderBy Optional ORDER BY clause
     * @return array
     */
    public function paginate($page = 1, $perPage = 10, $where = null, $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        // Count total records
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $total = (int)$this->db->query($sql)->single()['count'];
        
        // Get records for current page
        $sql = "SELECT * FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $records = $this->db->query($sql)->resultSet();
        
        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }

    /**
     * Get database instance
     */
    public function getDb() {
        return $this->db;
    }
}
