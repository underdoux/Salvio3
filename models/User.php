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
        'phone',
        'remember_token',
        'reset_token',
        'reset_expires',
        'last_login',
        'email_notifications',
        'whatsapp_notifications',
        'notification_preferences',
        'report_preferences',
        'last_report_access'
    ];

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE email = ? AND status = 'active'
        ")
        ->bind(1, $email)
        ->single();
    }

    /**
     * Find user by username
     */
    public function findByUsername($username) {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE username = ? AND status = 'active'
        ")
        ->bind(1, $username)
        ->single();
    }

    /**
     * Find user by remember token
     */
    public function findByRememberToken($token) {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE remember_token = ? AND status = 'active'
        ")
        ->bind(1, $token)
        ->single();
    }

    /**
     * Find user by reset token
     */
    public function findByResetToken($token) {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE reset_token = ? AND status = 'active'
        ")
        ->bind(1, $token)
        ->single();
    }

    /**
     * Get users by role
     */
    public function getByRole($role) {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE role = ? AND status = 'active'
            ORDER BY name ASC
        ")
        ->bind(1, $role)
        ->resultSet();
    }

    /**
     * Get active sales users
     */
    public function getActiveSales() {
        return $this->db->query("
            SELECT * FROM {$this->table} 
            WHERE role = 'sales' AND status = 'active'
            ORDER BY name ASC
        ")->resultSet();
    }

    /**
     * Create new user
     */
    public function create($data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return parent::create($data);
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return parent::update($id, $data);
    }

    /**
     * Get user's commission rates
     */
    public function getCommissionRates($userId) {
        return $this->db->query("
            SELECT cr.*, 
                c.name as category_name,
                p.name as product_name
            FROM commission_rates cr
            LEFT JOIN categories c ON cr.category_id = c.id
            LEFT JOIN products p ON cr.product_id = p.id
            WHERE cr.user_id = ?
            ORDER BY cr.created_at DESC
        ")
        ->bind(1, $userId)
        ->resultSet();
    }

    /**
     * Get user's sales performance
     */
    public function getSalesPerformance($userId, $startDate = null, $endDate = null) {
        $where = "WHERE s.user_id = ? AND s.status = 'completed'";
        $params = [$userId];

        if ($startDate) {
            $where .= " AND s.created_at >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $where .= " AND s.created_at <= ?";
            $params[] = $endDate;
        }

        $sql = "
            SELECT 
                COUNT(*) as total_sales,
                SUM(s.total_amount) as total_amount,
                SUM(s.discount_amount) as total_discount,
                SUM(s.final_amount) as total_final,
                AVG(s.final_amount) as average_sale,
                COUNT(DISTINCT s.customer_id) as unique_customers
            FROM sales s
            {$where}
        ";

        $query = $this->db->query($sql);
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }

        return $query->single();
    }

    /**
     * Get user's notification settings
     */
    public function getNotificationSettings($userId) {
        return $this->db->query("
            SELECT * FROM notification_settings
            WHERE user_id = ?
            ORDER BY event_type ASC
        ")
        ->bind(1, $userId)
        ->resultSet();
    }

    /**
     * Update user's notification settings
     */
    public function updateNotificationSettings($userId, $settings) {
        $this->db->beginTransaction();

        try {
            // Delete existing settings
            $this->db->query("
                DELETE FROM notification_settings
                WHERE user_id = ?
            ")
            ->bind(1, $userId)
            ->execute();

            // Insert new settings
            foreach ($settings as $setting) {
                $this->db->query("
                    INSERT INTO notification_settings 
                    (user_id, event_type, email_enabled, whatsapp_enabled)
                    VALUES (?, ?, ?, ?)
                ")
                ->bind(1, $userId)
                ->bind(2, $setting['event_type'])
                ->bind(3, $setting['email_enabled'])
                ->bind(4, $setting['whatsapp_enabled'])
                ->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Log user activity
     */
    public function logActivity($userId, $type, $description) {
        return $this->db->query("
            INSERT INTO activity_logs 
            (user_id, type, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ")
        ->bind(1, $userId)
        ->bind(2, $type)
        ->bind(3, $description)
        ->bind(4, $_SERVER['REMOTE_ADDR'])
        ->bind(5, $_SERVER['HTTP_USER_AGENT'])
        ->execute();
    }

    /**
     * Get user's recent activity
     */
    public function getRecentActivity($userId, $limit = 10) {
        return $this->db->query("
            SELECT * FROM activity_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ")
        ->bind(1, $userId)
        ->bind(2, $limit)
        ->resultSet();
    }
}
