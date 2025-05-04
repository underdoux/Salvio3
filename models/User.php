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
}
