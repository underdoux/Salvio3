<?php
/**
 * Notification Handler Helper
 * Handles system notifications (email, database, browser)
 */
class NotificationHandler {
    private static $instance = null;
    private $db;
    private $config;
    private $emailQueue = [];
    private $browserQueue = [];

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
    }

    /**
     * Get NotificationHandler instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load notification configuration
     */
    private function loadConfig() {
        $this->config = require CONFIG_PATH . '/notifications.php';
    }

    /**
     * Send notification
     */
    public function send($type, $data, $recipients = [], $options = []) {
        $notification = $this->prepareNotification($type, $data, $options);

        // Store in database
        $notificationId = $this->storeNotification($notification);

        // Send to recipients
        foreach ($recipients as $recipient) {
            $this->dispatchNotification($notificationId, $recipient, $notification);
        }

        // Process queues
        $this->processQueues();

        return $notificationId;
    }

    /**
     * Prepare notification data
     */
    private function prepareNotification($type, $data, $options) {
        $template = $this->getTemplate($type);
        
        return array_merge([
            'type' => $type,
            'title' => $this->parseTemplate($template['title'], $data),
            'message' => $this->parseTemplate($template['message'], $data),
            'data' => json_encode($data),
            'priority' => $options['priority'] ?? 'normal',
            'icon' => $template['icon'] ?? 'bell',
            'color' => $template['color'] ?? 'primary',
            'created_at' => date('Y-m-d H:i:s')
        ], $options);
    }

    /**
     * Get notification template
     */
    private function getTemplate($type) {
        if (isset($this->config['templates'][$type])) {
            return $this->config['templates'][$type];
        }

        // Default template
        return [
            'title' => '{title}',
            'message' => '{message}',
            'icon' => 'bell',
            'color' => 'primary'
        ];
    }

    /**
     * Parse template with data
     */
    private function parseTemplate($template, $data) {
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($data) {
            return $data[$matches[1]] ?? '';
        }, $template);
    }

    /**
     * Store notification in database
     */
    private function storeNotification($notification) {
        // First create a template
        $this->db->query("
            INSERT INTO notification_templates (
                name, type, subject, content, variables, status, created_by
            ) VALUES (?, 'email', ?, ?, ?, 'active', ?)
        ")
        ->bind(1, 'system_' . $notification['type'])
        ->bind(2, $notification['title'])
        ->bind(3, $notification['message'])
        ->bind(4, json_encode(['icon' => $notification['icon'], 'color' => $notification['color']]))
        ->bind(5, $notification['user_id'] ?? 1) // Use provided user_id or default to 1 (admin)
        ->execute();

        $templateId = $this->db->lastInsertId();

        // Then queue the notification
        $this->db->query("
            INSERT INTO notification_queue (
                template_id, type, subject, content, variables, status, priority
            ) VALUES (?, 'email', ?, ?, ?, 'pending', ?)
        ")
        ->bind(1, $templateId)
        ->bind(2, $notification['title'])
        ->bind(3, $notification['message'])
        ->bind(4, json_encode(['icon' => $notification['icon'], 'color' => $notification['color']]))
        ->bind(5, isset($notification['priority']) ? 1 : 0)
        ->execute();

        return $this->db->lastInsertId();
    }

    /**
     * Dispatch notification to recipient
     */
    private function dispatchNotification($notificationId, $recipient, $notification) {
        // Queue email notification
        if (!empty($recipient['email']) && ($notification['email'] ?? true)) {
            $this->queueEmail($notificationId, $recipient, $notification);
        }

        // Queue browser notification
        if (!empty($recipient['id']) && ($notification['browser'] ?? true)) {
            $this->queueBrowser($notificationId, $recipient, $notification);
        }
    }

    /**
     * Queue email notification
     */
    private function queueEmail($recipientId, $recipient, $notification) {
        $this->emailQueue[] = [
            'recipient_id' => $recipientId,
            'to' => $recipient['email'],
            'subject' => $notification['title'],
            'message' => $this->generateEmailContent($notification),
            'headers' => $this->generateEmailHeaders()
        ];
    }

    /**
     * Queue browser notification
     */
    private function queueBrowser($recipientId, $recipient, $notification) {
        $this->browserQueue[] = [
            'recipient_id' => $recipientId,
            'user_id' => $recipient['id'],
            'notification' => [
                'title' => $notification['title'],
                'message' => $notification['message'],
                'icon' => $notification['icon'],
                'color' => $notification['color'],
                'url' => $notification['url'] ?? null
            ]
        ];
    }

    /**
     * Process notification queues
     */
    private function processQueues() {
        // Process email queue
        foreach ($this->emailQueue as $email) {
            $this->sendEmail($email);
        }
        $this->emailQueue = [];

        // Process browser queue
        foreach ($this->browserQueue as $notification) {
            $this->storeBrowserNotification($notification);
        }
        $this->browserQueue = [];
    }

    /**
     * Send email notification
     */
    private function sendEmail($email) {
        $sent = mail(
            $email['to'],
            $email['subject'],
            $email['message'],
            $email['headers']
        );

        $this->updateRecipientStatus(
            $email['recipient_id'],
            $sent ? 'sent' : 'failed'
        );
    }

    /**
     * Store browser notification
     */
    private function storeBrowserNotification($notification) {
        $this->db->query("
            INSERT INTO notification_history (
                queue_id, type, recipient, subject, content, status, sent_at
            ) VALUES (?, 'whatsapp', ?, ?, ?, 'sent', NOW())
        ")
        ->bind(1, $notification['recipient_id'])
        ->bind(2, $notification['user_id'])
        ->bind(3, $notification['notification']['title'])
        ->bind(4, $notification['notification']['message'])
        ->execute();

        $this->updateRecipientStatus($notification['recipient_id'], 'sent');
    }

    /**
     * Update recipient notification status
     */
    private function updateRecipientStatus($recipientId, $status) {
        $this->db->query("
            UPDATE notification_recipients 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ")
        ->bind(1, $status)
        ->bind(2, $recipientId)
        ->execute();
    }

    /**
     * Generate email content
     */
    private function generateEmailContent($notification) {
        $template = file_get_contents(BASE_PATH . '/views/emails/notification.php');
        return $this->parseTemplate($template, [
            'title' => $notification['title'],
            'message' => $notification['message'],
            'app_name' => APP_NAME,
            'year' => date('Y')
        ]);
    }

    /**
     * Generate email headers
     */
    private function generateEmailHeaders() {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . APP_NAME . ' <' . MAIL_FROM . '>';
        $headers[] = 'Reply-To: ' . MAIL_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        return implode("\r\n", $headers);
    }

    /**
     * Get user's unread notifications
     */
    public function getUnread($userId, $limit = 10) {
        return $this->db->query("
            SELECT n.*, h.sent_at
            FROM notification_queue n
            INNER JOIN notification_history h ON h.queue_id = n.id
            WHERE h.recipient = ? AND h.status = 'sent'
            ORDER BY n.created_at DESC
            LIMIT ?
        ")
        ->bind(1, $userId)
        ->bind(2, $limit)
        ->resultSet();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        return $this->db->query("
            UPDATE notification_history
            SET status = 'read'
            WHERE queue_id = ? AND recipient = ?
        ")
        ->bind(1, $notificationId)
        ->bind(2, $userId)
        ->execute();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        return $this->db->query("
            UPDATE notification_history
            SET status = 'read'
            WHERE recipient = ? AND status = 'sent'
        ")
        ->bind(1, $userId)
        ->execute();
    }

    /**
     * Get notification preferences
     */
    public function getPreferences($userId) {
        return $this->db->query("
            SELECT * FROM notification_preferences
            WHERE user_id = ?
        ")
        ->bind(1, $userId)
        ->single() ?: [
            'email' => true,
            'browser' => true,
            'types' => array_keys($this->config['templates'])
        ];
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences($userId, $preferences) {
        $this->db->query("
            INSERT INTO notification_preferences (
                user_id, email, browser, types, updated_at
            ) VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                browser = VALUES(browser),
                types = VALUES(types),
                updated_at = NOW()
        ")
        ->bind(1, $userId)
        ->bind(2, $preferences['email'])
        ->bind(3, $preferences['browser'])
        ->bind(4, json_encode($preferences['types']))
        ->execute();
    }
}
