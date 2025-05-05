<?php
/**
 * Notifications Controller
 * Handles notification management and settings
 */
class NotificationsController extends Controller {
    private $notificationModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->notificationModel = $this->model('Notification');
    }

    /**
     * Show notifications dashboard
     */
    public function index() {
        $this->requireAdmin();

        $startDate = $this->getQuery('start_date', date('Y-m-d', strtotime('-30 days')));
        $endDate = $this->getQuery('end_date', date('Y-m-d'));

        // Get notification statistics
        $stats = $this->db->query("
            SELECT 
                type,
                status,
                COUNT(*) as count
            FROM notification_history
            WHERE sent_at BETWEEN ? AND ?
            GROUP BY type, status
        ")
        ->bind(1, $startDate)
        ->bind(2, $endDate)
        ->resultSet();

        // Get recent notifications
        $recent = $this->db->query("
            SELECT 
                nh.*,
                nt.name as template_name
            FROM notification_history nh
            JOIN notification_queue nq ON nh.queue_id = nq.id
            JOIN notification_templates nt ON nq.template_id = nt.id
            ORDER BY nh.sent_at DESC
            LIMIT 10
        ")->resultSet();

        // Get active templates
        $templates = $this->db->query("
            SELECT * FROM notification_templates
            WHERE status = 'active'
            ORDER BY name ASC
        ")->resultSet();

        $this->view->render('notifications/index', [
            'title' => 'Notifications - ' . APP_NAME,
            'stats' => $stats,
            'recent' => $recent,
            'templates' => $templates,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show template management
     */
    public function templates() {
        $this->requireAdmin();

        $templates = $this->db->query("
            SELECT 
                t.*,
                u.name as created_by_name,
                (
                    SELECT COUNT(*)
                    FROM notification_queue
                    WHERE template_id = t.id
                ) as usage_count
            FROM notification_templates t
            JOIN users u ON t.created_by = u.id
            ORDER BY t.name ASC
        ")->resultSet();

        $this->view->render('notifications/templates', [
            'title' => 'Notification Templates - ' . APP_NAME,
            'templates' => $templates,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Show template creation form
     */
    public function createTemplate() {
        $this->requireAdmin();

        $this->view->render('notifications/create_template', [
            'title' => 'Create Template - ' . APP_NAME,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Process template creation
     */
    public function storeTemplate() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('notifications/templates');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('notifications/createTemplate');
            return;
        }

        $data = [
            'name' => trim($this->getPost('name')),
            'type' => $this->getPost('type'),
            'subject' => trim($this->getPost('subject')),
            'content' => trim($this->getPost('content')),
            'variables' => json_encode($this->getPost('variables', [])),
            'created_by' => $this->getUserId()
        ];

        $this->db->query("
            INSERT INTO notification_templates (
                name,
                type,
                subject,
                content,
                variables,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?)
        ")
        ->bind(1, $data['name'])
        ->bind(2, $data['type'])
        ->bind(3, $data['subject'])
        ->bind(4, $data['content'])
        ->bind(5, $data['variables'])
        ->bind(6, $data['created_by'])
        ->execute();

        $this->logActivity('notification', "Created notification template: {$data['name']}");
        $this->setFlash('success', 'Template created successfully');
        $this->redirect('notifications/templates');
    }

    /**
     * Show configuration page
     */
    public function configure() {
        $this->requireAdmin();

        // Get email config
        $emailConfig = $this->db->query("
            SELECT * FROM email_config
            WHERE status = 'active'
            LIMIT 1
        ")->single();

        // Get WhatsApp config
        $whatsappConfig = $this->db->query("
            SELECT * FROM whatsapp_config
            WHERE status = 'active'
            LIMIT 1
        ")->single();

        $this->view->render('notifications/configure', [
            'title' => 'Notification Configuration - ' . APP_NAME,
            'emailConfig' => $emailConfig,
            'whatsappConfig' => $whatsappConfig,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Save configuration
     */
    public function saveConfig() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('notifications/configure');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('notifications/configure');
            return;
        }

        $type = $this->getPost('type');
        $data = $this->getPost('config', []);

        try {
            switch ($type) {
                case 'email':
                    $this->saveEmailConfig($data);
                    break;

                case 'whatsapp':
                    $this->saveWhatsAppConfig($data);
                    break;

                default:
                    throw new Exception("Invalid configuration type");
            }

            $this->logActivity('notification', "Updated {$type} configuration");
            $this->setFlash('success', 'Configuration saved successfully');

        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('notifications/configure');
    }

    /**
     * Show user notification settings
     */
    public function settings() {
        $settings = $this->db->query("
            SELECT * FROM notification_settings
            WHERE user_id = ?
        ")
        ->bind(1, $this->getUserId())
        ->resultSet();

        $this->view->render('notifications/settings', [
            'title' => 'Notification Settings - ' . APP_NAME,
            'settings' => $settings,
            'csrfToken' => $this->generateCsrf()
        ]);
    }

    /**
     * Save user notification settings
     */
    public function saveSettings() {
        if (!$this->isPost()) {
            $this->redirect('notifications/settings');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->setFlash('error', 'Invalid form submission');
            $this->redirect('notifications/settings');
            return;
        }

        $settings = $this->getPost('settings', []);
        $userId = $this->getUserId();

        try {
            $this->db->beginTransaction();

            // Delete existing settings
            $this->db->query("
                DELETE FROM notification_settings
                WHERE user_id = ?
            ")
            ->bind(1, $userId)
            ->execute();

            // Insert new settings
            foreach ($settings as $eventType => $config) {
                $this->db->query("
                    INSERT INTO notification_settings (
                        user_id,
                        event_type,
                        email_enabled,
                        whatsapp_enabled
                    ) VALUES (?, ?, ?, ?)
                ")
                ->bind(1, $userId)
                ->bind(2, $eventType)
                ->bind(3, !empty($config['email']))
                ->bind(4, !empty($config['whatsapp']))
                ->execute();
            }

            $this->db->commit();
            $this->setFlash('success', 'Settings saved successfully');

        } catch (Exception $e) {
            $this->db->rollback();
            $this->setFlash('error', 'Failed to save settings');
        }

        $this->redirect('notifications/settings');
    }

    /**
     * Process notification queue
     * Called by cron job
     */
    public function processQueue() {
        if (!$this->isCliRequest()) {
            die('This script can only be run from the command line');
        }

        $processed = $this->notificationModel->processQueue();
        echo "Processed {$processed} notifications\n";
    }

    /**
     * Save email configuration
     * @param array $config Configuration data
     */
    private function saveEmailConfig($config) {
        $this->db->query("
            UPDATE email_config
            SET status = 'inactive'
            WHERE status = 'active'
        ")->execute();

        $this->db->query("
            INSERT INTO email_config (
                driver,
                host,
                port,
                username,
                password,
                encryption,
                from_address,
                from_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")
        ->bind(1, $config['driver'])
        ->bind(2, $config['host'])
        ->bind(3, $config['port'])
        ->bind(4, $config['username'])
        ->bind(5, $config['password'])
        ->bind(6, $config['encryption'])
        ->bind(7, $config['from_address'])
        ->bind(8, $config['from_name'])
        ->execute();
    }

    /**
     * Save WhatsApp configuration
     * @param array $config Configuration data
     */
    private function saveWhatsAppConfig($config) {
        $this->db->query("
            UPDATE whatsapp_config
            SET status = 'inactive'
            WHERE status = 'active'
        ")->execute();

        $this->db->query("
            INSERT INTO whatsapp_config (
                provider,
                api_key,
                api_secret,
                sender_number,
                webhook_url
            ) VALUES (?, ?, ?, ?, ?)
        ")
        ->bind(1, $config['provider'])
        ->bind(2, $config['api_key'])
        ->bind(3, $config['api_secret'])
        ->bind(4, $config['sender_number'])
        ->bind(5, $config['webhook_url'])
        ->execute();
    }
}
