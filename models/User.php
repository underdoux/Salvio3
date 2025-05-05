<?php
/**
 * User Model
 * Handles user-related database operations
 */
class User extends Model {
    protected $table = 'users';
    protected $fillable = [
        'username',
        'password',
        'email',
        'name',
        'role',
        'status',
        'remember_token',
        'reset_token',
        'reset_expires',
        'last_login'
    ];

    /**
     * Find user by username
     * @param string $username
     * @return array|null
     */
    public function findByUsername($username) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE username = ? AND status = 'active'")
                       ->bind(1, $username)
                       ->single();
    }

    /**
     * Find user by email
     * @param string $email
     * @return array|null
     */
    public function findByEmail($email) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE email = ? AND status = 'active'")
                       ->bind(1, $email)
                       ->single();
    }

    /**
     * Find user by remember token
     * @param string $token
     * @return array|null
     */
    public function findByRememberToken($token) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE remember_token = ? AND status = 'active'")
                       ->bind(1, $token)
                       ->single();
    }

    /**
     * Find user by reset token
     * @param string $token
     * @return array|null
     */
    public function findByResetToken($token) {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE reset_token = ? 
            AND reset_expires > NOW() 
            AND status = 'active'
        ")
        ->bind(1, $token)
        ->single();
    }

    /**
     * Store remember token
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function storeRememberToken($userId, $token) {
        return $this->db->query("UPDATE {$this->table} SET remember_token = ? WHERE id = ?")
                       ->bind(1, $token)
                       ->bind(2, $userId)
                       ->execute();
    }

    /**
     * Clear remember token
     * @param int $userId
     * @return bool
     */
    public function clearRememberToken($userId) {
        return $this->db->query("UPDATE {$this->table} SET remember_token = NULL WHERE id = ?")
                       ->bind(1, $userId)
                       ->execute();
    }

    /**
     * Store reset token
     * @param int $userId
     * @param string $token
     * @return bool
     */
    public function storeResetToken($userId, $token) {
        return $this->db->query("
            UPDATE {$this->table} 
            SET reset_token = ?, 
                reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
            WHERE id = ?
        ")
        ->bind(1, $token)
        ->bind(2, $userId)
        ->execute();
    }

    /**
     * Clear reset token
     * @param int $userId
     * @return bool
     */
    public function clearResetToken($userId) {
        return $this->db->query("
            UPDATE {$this->table} 
            SET reset_token = NULL, 
                reset_expires = NULL 
            WHERE id = ?
        ")
        ->bind(1, $userId)
        ->execute();
    }

    /**
     * Update password
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function updatePassword($userId, $password) {
        return $this->db->query("UPDATE {$this->table} SET password = ? WHERE id = ?")
                       ->bind(1, $password)
                       ->bind(2, $userId)
                       ->execute();
    }

    /**
     * Update last login
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin($userId) {
        return $this->db->query("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?")
                       ->bind(1, $userId)
                       ->execute();
    }

    /**
     * Get active users count
     * @return int
     */
    public function getActiveCount() {
        $result = $this->db->query("SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'active'")
                          ->single();
        return (int)$result['count'];
    }

    /**
     * Get users by role
     * @param string $role
     * @return array
     */
    public function getByRole($role) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE role = ? AND status = 'active'")
                       ->bind(1, $role)
                       ->resultSet();
    }

    /**
     * Check if username exists
     * @param string $username
     * @param int|null $excludeId Exclude user ID when checking (for updates)
     * @return bool
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ?";
        $params = [$username];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $query = $this->db->query($sql);
        for ($i = 0; $i < count($params); $i++) {
            $query->bind($i + 1, $params[$i]);
        }

        $result = $query->single();
        return (int)$result['count'] > 0;
    }

    /**
     * Check if email exists
     * @param string $email
     * @param int|null $excludeId Exclude user ID when checking (for updates)
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $query = $this->db->query($sql);
        for ($i = 0; $i < count($params); $i++) {
            $query->bind($i + 1, $params[$i]);
        }

        $result = $query->single();
        return (int)$result['count'] > 0;
    }

    /**
     * Create a new user
     * @param array $data User data
     * @return int|false The new user ID or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (username, password, email, name, role, status) VALUES (?, ?, ?, ?, ?, ?)";
        
        return $this->db->query($sql)
            ->bind(1, $data['username'])
            ->bind(2, $data['password'])
            ->bind(3, $data['email'])
            ->bind(4, $data['name'])
            ->bind(5, $data['role'])
            ->bind(6, $data['status'] ?? 'active')
            ->lastInsertId();
    }

    /**
     * Update a user
     * @param int $id User ID
     * @param array $data User data
     * @return bool Success status
     */
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $query = $this->db->query($sql);
        for ($i = 0; $i < count($params); $i++) {
            $query->bind($i + 1, $params[$i]);
        }
        
        return $query->execute();
    }

    /**
     * Delete a user (soft delete)
     * @param int $id User ID
     * @return bool Success status
     */
    public function delete($id) {
        return $this->db->query("UPDATE {$this->table} SET status = 'inactive' WHERE id = ?")
            ->bind(1, $id)
            ->execute();
    }

    /**
     * Get all users with pagination
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $search Search term
     * @return array Users list and total count
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $where = '';
        $params = [];

        if ($search) {
            $where = "WHERE (username LIKE ? OR email LIKE ? OR name LIKE ?)";
            $search = "%{$search}%";
            $params = [$search, $search, $search];
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} " . $where;
        $countQuery = $this->db->query($countSql);
        foreach ($params as $i => $param) {
            $countQuery->bind($i + 1, $param);
        }
        $total = (int)$countQuery->single()['total'];

        // Get paginated results
        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $query = $this->db->query($sql);
        
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $query->bind(count($params) + 1, $limit);
        $query->bind(count($params) + 2, $offset);

        return [
            'data' => $query->resultSet(),
            'total' => $total,
            'page' => $page,
            'last_page' => ceil($total / $limit)
        ];
    }

    /**
     * Get user by ID
     * @param int $id User ID
     * @return array|null User data
     */
    public function getById($id) {
        return $this->db->query("SELECT * FROM {$this->table} WHERE id = ?")
            ->bind(1, $id)
            ->single();
    }
}
