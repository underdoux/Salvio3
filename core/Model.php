<?php
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    protected $softDelete = true;
    protected $errors = [];
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
    
    /**
     * Get all records
     */
    public function all($orderBy = null) {
        $sql = "SELECT * FROM {$this->table}";
        if ($this->softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->query($sql);
        
        foreach ($data as $key => $value) {
            $this->db->bind(":{$key}", $value);
        }
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_map(function($field) {
            return "{$field} = :{$field}";
        }, array_keys($data));
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = :id";
        
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        
        foreach ($data as $key => $value) {
            $this->db->bind(":{$key}", $value);
        }
        
        return $this->db->execute();
    }
    
    /**
     * Delete record (soft delete if enabled)
     */
    public function delete($id) {
        if ($this->softDelete) {
            return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    /**
     * Find records by field value
     */
    public function findBy($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $this->db->query($sql);
        $this->db->bind(':value', $value);
        
        return $this->db->resultSet();
    }
    
    /**
     * Find one record by field value
     */
    public function findOneBy($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";
        
        $this->db->query($sql);
        $this->db->bind(':value', $value);
        
        return $this->db->single();
    }
    
    /**
     * Count total records
     */
    public function count($conditions = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($this->softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
            if ($conditions) {
                $sql .= " AND {$conditions}";
            }
        } elseif ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        
        $this->db->query($sql);
        $result = $this->db->single();
        
        return $result->total;
    }
    
    /**
     * Get paginated results
     */
    public function paginate($page = 1, $perPage = 10, $conditions = '', $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        if ($this->softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
            if ($conditions) {
                $sql .= " AND {$conditions}";
            }
        } elseif ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $this->db->query($sql);
        $results = $this->db->resultSet();
        
        $total = $this->count($conditions);
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollBack() {
        return $this->db->rollBack();
    }
    
    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if model has validation errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Add validation error
     */
    protected function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    /**
     * Clear validation errors
     */
    protected function clearErrors() {
        $this->errors = [];
    }
}
