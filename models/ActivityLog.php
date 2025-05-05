<?php
/**
 * ActivityLog Model
 * Handles activity logging operations
 */
class ActivityLog extends Model {
    protected $table = 'activity_logs';
    protected $fillable = [
        'user_id',
        'type',
        'description',
        'ip_address',
        'user_agent'
    ];

    /**
     * Log a new activity
     * @param string $type Activity type
     * @param string $description Activity description
     * @param int|null $userId User ID
     * @param string $ipAddress IP address
     * @param string $userAgent User agent
     * @return bool Success status
     */
    public function log($type, $description, $userId = null, $ipAddress = null, $userAgent = null) {
        return $this->create([
            'user_id' => $userId,
            'type' => $type,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
    }
}
