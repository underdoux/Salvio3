<?php
/**
 * Maintenance Mode Helper
 * Handles system maintenance mode functionality
 */
class MaintenanceMode {
    private static $instance = null;
    private $configFile;
    private $config;
    private $defaultConfig = [
        'enabled' => false,
        'start_time' => null,
        'end_time' => null,
        'message' => 'System is under maintenance. Please try again later.',
        'allowed_ips' => [],
        'bypass_key' => null,
        'notification_emails' => []
    ];

    private function __construct() {
        $this->configFile = CONFIG_PATH . '/maintenance.json';
        $this->loadConfig();
    }

    /**
     * Get MaintenanceMode instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load maintenance configuration
     */
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $content = file_get_contents($this->configFile);
            $this->config = json_decode($content, true) ?: $this->defaultConfig;
        } else {
            $this->config = $this->defaultConfig;
            $this->saveConfig();
        }
    }

    /**
     * Save maintenance configuration
     */
    private function saveConfig() {
        $dir = dirname($this->configFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    /**
     * Enable maintenance mode
     */
    public function enable($duration = 3600, $message = null) {
        $this->config['enabled'] = true;
        $this->config['start_time'] = date('Y-m-d H:i:s');
        $this->config['end_time'] = date('Y-m-d H:i:s', time() + $duration);
        
        if ($message !== null) {
            $this->config['message'] = $message;
        }

        $this->saveConfig();
        $this->notifyAdmins('enabled');
    }

    /**
     * Disable maintenance mode
     */
    public function disable() {
        $this->config['enabled'] = false;
        $this->config['start_time'] = null;
        $this->config['end_time'] = null;
        
        $this->saveConfig();
        $this->notifyAdmins('disabled');
    }

    /**
     * Check if system is in maintenance mode
     */
    public function isEnabled() {
        if (!$this->config['enabled']) {
            return false;
        }

        // Check if maintenance period has expired
        if ($this->config['end_time'] && strtotime($this->config['end_time']) < time()) {
            $this->disable();
            return false;
        }

        return true;
    }

    /**
     * Check if current request should be allowed
     */
    public function shouldAllow() {
        if (!$this->isEnabled()) {
            return true;
        }

        // Check for bypass key in URL
        if (isset($_GET['maintenance']) && 
            $this->config['bypass_key'] && 
            $_GET['maintenance'] === $this->config['bypass_key']) {
            return true;
        }

        // Check if user's IP is allowed
        $ip = $_SERVER['REMOTE_ADDR'];
        if (in_array($ip, $this->config['allowed_ips'])) {
            return true;
        }

        return false;
    }

    /**
     * Add IP to allowed list
     */
    public function allowIP($ip) {
        if (!in_array($ip, $this->config['allowed_ips'])) {
            $this->config['allowed_ips'][] = $ip;
            $this->saveConfig();
        }
    }

    /**
     * Remove IP from allowed list
     */
    public function removeIP($ip) {
        $key = array_search($ip, $this->config['allowed_ips']);
        if ($key !== false) {
            unset($this->config['allowed_ips'][$key]);
            $this->config['allowed_ips'] = array_values($this->config['allowed_ips']);
            $this->saveConfig();
        }
    }

    /**
     * Set bypass key
     */
    public function setBypassKey($key) {
        $this->config['bypass_key'] = $key;
        $this->saveConfig();
    }

    /**
     * Get maintenance message
     */
    public function getMessage() {
        return $this->config['message'];
    }

    /**
     * Set maintenance message
     */
    public function setMessage($message) {
        $this->config['message'] = $message;
        $this->saveConfig();
    }

    /**
     * Get time remaining
     */
    public function getTimeRemaining() {
        if (!$this->isEnabled() || !$this->config['end_time']) {
            return 0;
        }

        $remaining = strtotime($this->config['end_time']) - time();
        return max(0, $remaining);
    }

    /**
     * Get maintenance status
     */
    public function getStatus() {
        return [
            'enabled' => $this->isEnabled(),
            'start_time' => $this->config['start_time'],
            'end_time' => $this->config['end_time'],
            'message' => $this->config['message'],
            'time_remaining' => $this->getTimeRemaining(),
            'allowed_ips' => $this->config['allowed_ips']
        ];
    }

    /**
     * Set notification emails
     */
    public function setNotificationEmails(array $emails) {
        $this->config['notification_emails'] = array_filter($emails, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        $this->saveConfig();
    }

    /**
     * Notify administrators
     */
    private function notifyAdmins($action) {
        if (empty($this->config['notification_emails'])) {
            return;
        }

        $subject = sprintf(
            '[%s] Maintenance Mode %s',
            APP_NAME,
            ucfirst($action)
        );

        $message = sprintf(
            "Maintenance mode has been %s.\n\nDetails:\n- Start Time: %s\n- End Time: %s\n- Message: %s\n",
            $action,
            $this->config['start_time'] ?? 'N/A',
            $this->config['end_time'] ?? 'N/A',
            $this->config['message']
        );

        foreach ($this->config['notification_emails'] as $email) {
            mail($email, $subject, $message);
        }
    }

    /**
     * Handle maintenance mode check
     */
    public function handle() {
        if ($this->isEnabled() && !$this->shouldAllow()) {
            // Store original URL for redirect after maintenance
            if (!empty($_SERVER['REQUEST_URI'])) {
                $_SESSION['maintenance_redirect'] = $_SERVER['REQUEST_URI'];
            }

            // Show maintenance page
            require_once 'controllers/ErrorController.php';
            $controller = new ErrorController();
            $controller->maintenance();
            exit;
        }

        // Check for redirect after maintenance
        if (!$this->isEnabled() && isset($_SESSION['maintenance_redirect'])) {
            $redirect = $_SESSION['maintenance_redirect'];
            unset($_SESSION['maintenance_redirect']);
            header('Location: ' . $redirect);
            exit;
        }
    }
}
