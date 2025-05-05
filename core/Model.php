<?php
/**
 * Base Model Class
 * All models should extend this class
 */
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get database instance
     */
    public function getDb() {
        return $this->db;
    }

    /**
     * Find record by ID
     */
    public function find($id) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?")
            ->bind(1, $id)
            ->single();
    }

    /**
     * Find record by field value
     */
    public function findBy($field, $value) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE {$field} = ?")
            ->bind(1, $value)
            ->single();
    }

    /**
     * Get all records
     */
    public function all() {
        return $this->db->query("SELECT * FROM {$this->table}")
            ->resultSet();
    }

    /**
     * Get paginated records
     */
    public function paginate($page = 1, $limit = 10, $where = '1', $params = []) {
        $offset = ($page - 1) * $limit;

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $countQuery = $this->db->query($countSql);
        foreach ($params as $i => $param) {
            $countQuery->bind($i + 1, $param);
        }
        $total = $countQuery->single()['total'];

        // Get paginated data
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT ? OFFSET ?";
        $query = $this->db->query($sql);
        
        // Bind where clause parameters
        $paramIndex = 1;
        foreach ($params as $param) {
            $query->bind($paramIndex++, $param);
        }
        
        // Bind pagination parameters
        $query->bind($paramIndex++, $limit);
        $query->bind($paramIndex, $offset);

        return [
            'data' => $query->resultSet(),
            'total' => $total,
            'page' => $page,
            'last_page' => ceil($total / $limit)
        ];
    }

    /**
     * Create new record
     */
    public function create($data) {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        
        if ($this->timestamps) {
            $fields['created_at'] = date('Y-m-d H:i:s');
            $fields['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = implode(', ', array_keys($fields));
        $values = implode(', ', array_fill(0, count($fields), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        
        $query = $this->db->query($sql);
        $i = 1;
        foreach ($fields as $value) {
            $query->bind($i++, $value);
        }
        
        if ($query->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update record
     */
    public function update($id, $data) {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        
        if ($this->timestamps) {
            $fields['updated_at'] = date('Y-m-d H:i:s');
        }

        $set = implode(', ', array_map(function($field) {
            return "{$field} = ?";
        }, array_keys($fields)));
        
        $sql = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?";
        
        $query = $this->db->query($sql);
        $i = 1;
        foreach ($fields as $value) {
            $query->bind($i++, $value);
        }
        $query->bind($i, $id);
        
        return $query->execute();
    }

    /**
     * Delete record
     */
    public function delete($id) {
        return $this->db->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?")
            ->bind(1, $id)
            ->execute();
    }

    /**
     * Soft delete record (if status column exists)
     */
    public function softDelete($id) {
        return $this->db->query("UPDATE {$this->table} SET status = 'inactive' WHERE {$this->primaryKey} = ?")
            ->bind(1, $id)
            ->execute();
    }

    /**
     * Begin transaction
     */
    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    protected function commit() {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    protected function rollback() {
        return $this->db->rollback();
    }

    /**
     * Get error info
     */
    protected function getError() {
        return $this->db->getError();
    }

    /**
     * Check if record exists
     */
    public function exists($field, $value, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$field} = ?";
        $params = [$value];

        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->single()['count'] > 0;
    }

    /**
     * Get records by field values
     */
    public function whereIn($field, array $values) {
        if (empty($values)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE {$field} IN ({$placeholders})";
        
        $query = $this->db->query($sql);
        foreach ($values as $i => $value) {
            $query->bind($i + 1, $value);
        }

        return $query->resultSet();
    }
}
