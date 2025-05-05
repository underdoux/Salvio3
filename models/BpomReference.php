<?php
/**
 * BPOM Reference Model
 * Handles BPOM data storage and retrieval
 */
class BpomReference extends Model {
    protected $table = 'bpom_references';
    protected $fillable = [
        'id',
        'product_name',
        'registration_number',
        'manufacturer',
        'category',
        'issued_date',
        'expired_date',
        'status'
    ];

    /**
     * Search BPOM references
     * @param string $keyword Search keyword
     * @param int $limit Results limit
     * @return array
     */
    public function search($keyword, $limit = 10) {
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE (registration_number LIKE ? OR product_name LIKE ?)
            AND status = 'active'
            ORDER BY product_name ASC
            LIMIT ?
        ")
        ->bind(1, "%{$keyword}%")
        ->bind(2, "%{$keyword}%")
        ->bind(3, $limit)
        ->resultSet();
    }

    /**
     * Get by registration number
     * @param string $registrationNumber BPOM registration number
     * @return array|null
     */
    public function getByRegistrationNumber($registrationNumber) {
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE registration_number = ?
            AND status = 'active'
        ")
        ->bind(1, $registrationNumber)
        ->single();
    }

    /**
     * Get expired or soon to expire registrations
     * @param int $daysThreshold Days threshold for expiration warning
     * @return array
     */
    public function getExpiringRegistrations($daysThreshold = 90) {
        return $this->db->query("
            SELECT *,
                   DATEDIFF(expired_date, CURDATE()) as days_remaining
            FROM {$this->table}
            WHERE status = 'active'
            AND expired_date IS NOT NULL
            AND expired_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY expired_date ASC
        ")
        ->bind(1, $daysThreshold)
        ->resultSet();
    }

    /**
     * Update or create BPOM reference
     * @param array $data BPOM data
     * @return bool Success status
     */
    public function updateOrCreate($data) {
        // Check if record exists
        $existing = $this->getByRegistrationNumber($data['registration_number']);
        
        if ($existing) {
            // Update existing record
            return $this->update($existing['id'], $data);
        } else {
            // Create new record
            return $this->create($data);
        }
    }

    /**
     * Get BPOM statistics
     * @return array
     */
    public function getStats() {
        return $this->db->query("
            SELECT
                COUNT(*) as total_records,
                COUNT(CASE WHEN expired_date < CURDATE() THEN 1 END) as expired_count,
                COUNT(CASE 
                    WHEN expired_date >= CURDATE() 
                    AND expired_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) 
                    THEN 1 
                END) as expiring_soon_count,
                MIN(created_at) as oldest_record,
                MAX(created_at) as latest_record
            FROM {$this->table}
            WHERE status = 'active'
        ")->single();
    }

    /**
     * Get products by category
     * @param string $category BPOM category
     * @param int $limit Results limit
     * @return array
     */
    public function getByCategory($category, $limit = 10) {
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE category = ?
            AND status = 'active'
            ORDER BY product_name ASC
            LIMIT ?
        ")
        ->bind(1, $category)
        ->bind(2, $limit)
        ->resultSet();
    }

    /**
     * Get unique categories
     * @return array
     */
    public function getCategories() {
        return $this->db->query("
            SELECT DISTINCT category
            FROM {$this->table}
            WHERE category IS NOT NULL
            AND status = 'active'
            ORDER BY category ASC
        ")->resultSet();
    }

    /**
     * Get unique manufacturers
     * @return array
     */
    public function getManufacturers() {
        return $this->db->query("
            SELECT DISTINCT manufacturer
            FROM {$this->table}
            WHERE manufacturer IS NOT NULL
            AND status = 'active'
            ORDER BY manufacturer ASC
        ")->resultSet();
    }

    /**
     * Mark registration as expired
     * @param string $registrationNumber BPOM registration number
     * @return bool Success status
     */
    public function markAsExpired($registrationNumber) {
        return $this->db->query("
            UPDATE {$this->table}
            SET status = 'inactive',
                updated_at = CURRENT_TIMESTAMP
            WHERE registration_number = ?
        ")
        ->bind(1, $registrationNumber)
        ->execute();
    }

    /**
     * Clean up old records
     * @param int $days Days threshold for deletion
     * @return int Number of records deleted
     */
    public function cleanupOldRecords($days = 365) {
        return $this->db->query("
            DELETE FROM {$this->table}
            WHERE status = 'inactive'
            AND updated_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ")
        ->bind(1, $days)
        ->execute();
    }
}
