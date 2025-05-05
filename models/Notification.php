<?php
/**
 * Notification Model
 * Handles notification management and processing
 */
class Notification extends Model {
    protected $table = 'notification_queue';
    protected $fillable = [
        'template_id',
        'type',
        'recipient',
        'subject',
        'content',
        'variables',
        'priority',
        'scheduled_at'
    ];

    /**
     * Send notification
     * @param string $templateName Template name
     * @param array $data Notification data
     * @param array $recipients Recipients
     * @param array $options Additional options
     * @return bool Success status
     */
    public function send($templateName, $data, $recipients, $options = []) {
        try {
            // Get template
            $template = $this->db->query("
                SELECT * FROM notification_templates
                WHERE name = ? AND status = 'active'
                LIMIT 1
            ")
            ->bind(1, $templateName)
            ->single();

            if (!$template) {
                throw new Exception("Template not found: {$templateName}");
            }

            // Validate required variables
            $requiredVars = json_decode($template['variables'], true);
            foreach ($requiredVars as $var => $type) {
                if (!isset($data[$var])) {
                    throw new Exception("Missing required variable: {$var}");
                }
            }

            // Process content
            $content = $this->processTemplate($template['content'], $data);
            $subject = $template['subject'] ? 
                $this->processTemplate($template['subject'], $data) : null;

            // Queue notifications
            $this->db->beginTransaction();

            foreach ($recipients as $recipient) {
                $this->create([
                    'template_id' => $template['id'],
                    'type' => $template['type'],
                    'recipient' => $recipient,
                    'subject' => $subject,
                    'content' => $content,
                    'variables' => json_encode($data),
                    'priority' => $options['priority'] ?? 0,
                    'scheduled_at' => $options['scheduled_at'] ?? null
                ]);
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Notification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process notification queue
     * @param int $limit Batch size
     * @return int Number of notifications processed
     */
    public function processQueue($limit = 50) {
        $processed = 0;

        // Get pending notifications
        $notifications = $this->db->query("
            SELECT * FROM {$this->table}
            WHERE status = 'pending'
            AND (scheduled_at IS NULL OR scheduled_at <= CURRENT_TIMESTAMP)
            ORDER BY priority DESC, created_at ASC
            LIMIT ?
        ")
        ->bind(1, $limit)
        ->resultSet();

        foreach ($notifications as $notification) {
            try {
                // Update status to processing
                $this->db->query("
                    UPDATE {$this->table}
                    SET 
                        status = 'processing',
                        attempts = attempts + 1
                    WHERE id = ?
                ")
                ->bind(1, $notification['id'])
                ->execute();

                // Send notification based on type
                $success = $notification['type'] === 'email' ?
                    $this->sendEmail($notification) :
                    $this->sendWhatsApp($notification);

                if ($success) {
                    // Update status to sent
                    $this->updateStatus($notification['id'], 'sent');
                    $this->logHistory($notification, 'sent');
                    $processed++;
                } else {
                    throw new Exception("Failed to send notification");
                }

            } catch (Exception $e) {
                // Handle failure
                $this->updateStatus(
                    $notification['id'], 
                    'failed',
                    $e->getMessage()
                );
                $this->logHistory($notification, 'failed', $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Send email notification
     * @param array $notification Notification data
     * @return bool Success status
     */
    private function sendEmail($notification) {
        // Get email configuration
        $config = $this->db->query("
            SELECT * FROM email_config
            WHERE status = 'active'
            LIMIT 1
        ")->single();

        if (!$config) {
            throw new Exception("No active email configuration found");
        }

        // Initialize PHPMailer
        require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->Port = $config['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['encryption'];

            // Recipients
            $mail->setFrom($config['from_address'], $config['from_name']);
            $mail->addAddress($notification['recipient']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $notification['subject'];
            $mail->Body = $notification['content'];
            $mail->AltBody = strip_tags($notification['content']);

            return $mail->send();

        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send WhatsApp notification
     * @param array $notification Notification data
     * @return bool Success status
     */
    private function sendWhatsApp($notification) {
        // Get WhatsApp configuration
        $config = $this->db->query("
            SELECT * FROM whatsapp_config
            WHERE status = 'active'
            LIMIT 1
        ")->single();

        if (!$config) {
            throw new Exception("No active WhatsApp configuration found");
        }

        try {
            // Initialize provider client
            $client = $this->initializeWhatsAppClient($config);

            // Send message
            $response = $client->messages->create([
                'from' => $config['sender_number'],
                'to' => $notification['recipient'],
                'body' => $notification['content']
            ]);

            return !empty($response->sid);

        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Initialize WhatsApp client
     * @param array $config Provider configuration
     * @return mixed Client instance
     */
    private function initializeWhatsAppClient($config) {
        switch ($config['provider']) {
            case 'twilio':
                require_once 'vendor/twilio/sdk/src/Twilio/autoload.php';
                return new Twilio\Rest\Client(
                    $config['api_key'],
                    $config['api_secret']
                );

            case 'wablas':
                // Initialize Wablas client
                return new WablasClient($config['api_key']);

            case 'fonnte':
                // Initialize Fonnte client
                return new FontteClient($config['api_key']);

            default:
                throw new Exception("Unsupported WhatsApp provider");
        }
    }

    /**
     * Process template with variables
     * @param string $template Template content
     * @param array $data Variable data
     * @return string Processed content
     */
    private function processTemplate($template, $data) {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($data) {
            return $data[$matches[1]] ?? $matches[0];
        }, $template);
    }

    /**
     * Update notification status
     * @param int $id Notification ID
     * @param string $status New status
     * @param string|null $error Error message
     */
    private function updateStatus($id, $status, $error = null) {
        $this->db->query("
            UPDATE {$this->table}
            SET 
                status = ?,
                error_message = ?,
                sent_at = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ")
        ->bind(1, $status)
        ->bind(2, $error)
        ->bind(3, $status === 'sent' ? date('Y-m-d H:i:s') : null)
        ->bind(4, $id)
        ->execute();
    }

    /**
     * Log notification history
     * @param array $notification Notification data
     * @param string $status Status
     * @param string|null $error Error message
     */
    private function logHistory($notification, $status, $error = null) {
        $this->db->query("
            INSERT INTO notification_history (
                queue_id,
                type,
                recipient,
                subject,
                content,
                status,
                error_message
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ")
        ->bind(1, $notification['id'])
        ->bind(2, $notification['type'])
        ->bind(3, $notification['recipient'])
        ->bind(4, $notification['subject'])
        ->bind(5, $notification['content'])
        ->bind(6, $status)
        ->bind(7, $error)
        ->execute();
    }
}
