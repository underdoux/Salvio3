<?php
/**
 * Error Handler Helper
 * Handles error logging, notifications, and management
 */
class ErrorHandler {
    private static $instance = null;
    private $logPath;
    private $maxLogSize = 10485760; // 10MB
    private $maxLogFiles = 5;
    private $notificationEmails = [];

    private function __construct() {
        $this->logPath = LOG_PATH . '/error.log';
        $this->ensureLogDirectory();
    }

    /**
     * Get ErrorHandler instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set error handlers
     */
    public function register() {
        // Set error handler
        set_error_handler([$this, 'handleError']);
        
        // Set exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Set shutdown function
        register_shutdown_function([$this, 'handleShutdown']);

        // Enable error logging
        ini_set('log_errors', 1);
        ini_set('error_log', $this->logPath);
    }

    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            // Error reporting is disabled for this error level
            return false;
        }

        $error = [
            'type' => $this->getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        $this->logError($error);

        if ($errno == E_USER_ERROR) {
            $this->displayError(500, $error);
            exit(1);
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ];

        $this->logError($error);
        $this->displayError(500, $error);
    }

    /**
     * Handle fatal errors
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * Log error to file
     */
    public function logError($error) {
        $timestamp = date('Y-m-d H:i:s');
        $errorId = uniqid('err_');
        
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\n",
            $timestamp,
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );

        if (isset($error['trace'])) {
            $logMessage .= "Stack trace:\n";
            foreach ($error['trace'] as $i => $trace) {
                $logMessage .= sprintf(
                    "#%d %s:%d - %s%s%s()\n",
                    $i,
                    $trace['file'] ?? 'unknown',
                    $trace['line'] ?? 0,
                    $trace['class'] ?? '',
                    $trace['type'] ?? '',
                    $trace['function'] ?? ''
                );
            }
        }

        $logMessage .= str_repeat('-', 80) . "\n";

        // Write to log file
        file_put_contents($this->logPath, $logMessage, FILE_APPEND);

        // Check log file size and rotate if needed
        $this->rotateLogIfNeeded();

        // Send notification for critical errors
        if (in_array($error['type'], ['Error', 'ParseError', 'E_ERROR', 'E_USER_ERROR'])) {
            $this->sendErrorNotification($errorId, $error);
        }

        return $errorId;
    }

    /**
     * Display error page
     */
    public function displayError($code, $error = null) {
        http_response_code($code);

        if (php_sapi_name() === 'cli') {
            // Command line interface
            echo "Error {$code}: " . ($error['message'] ?? 'An error occurred') . "\n";
            if (DEBUG && isset($error['trace'])) {
                echo "Stack trace:\n" . print_r($error['trace'], true);
            }
            return;
        }

        // Check if request is AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => true,
                'code' => $code,
                'message' => DEBUG ? ($error['message'] ?? 'An error occurred') : 'An error occurred'
            ]);
            return;
        }

        // Regular web request
        if (file_exists("views/errors/{$code}.php")) {
            include "views/errors/{$code}.php";
        } else {
            include "views/errors/error.php";
        }
    }

    /**
     * Get error type string
     */
    private function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
            default:
                return 'UNKNOWN';
        }
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory() {
        $dir = dirname($this->logPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Rotate log file if needed
     */
    private function rotateLogIfNeeded() {
        if (!file_exists($this->logPath)) {
            return;
        }

        if (filesize($this->logPath) < $this->maxLogSize) {
            return;
        }

        // Remove oldest log file if exists
        if (file_exists($this->logPath . '.' . $this->maxLogFiles)) {
            unlink($this->logPath . '.' . $this->maxLogFiles);
        }

        // Rotate existing log files
        for ($i = $this->maxLogFiles - 1; $i >= 1; $i--) {
            if (file_exists($this->logPath . '.' . $i)) {
                rename(
                    $this->logPath . '.' . $i,
                    $this->logPath . '.' . ($i + 1)
                );
            }
        }

        // Move current log file
        rename($this->logPath, $this->logPath . '.1');
    }

    /**
     * Send error notification
     */
    private function sendErrorNotification($errorId, $error) {
        if (empty($this->notificationEmails)) {
            return;
        }

        $subject = sprintf(
            '[%s] Error %s: %s',
            APP_NAME,
            $errorId,
            $error['message']
        );

        $message = sprintf(
            "Error Details:\n\nID: %s\nType: %s\nMessage: %s\nFile: %s\nLine: %d\n\n",
            $errorId,
            $error['type'],
            $error['message'],
            $error['file'],
            $error['line']
        );

        if (isset($error['trace'])) {
            $message .= "Stack Trace:\n" . print_r($error['trace'], true);
        }

        // Send email
        foreach ($this->notificationEmails as $email) {
            mail($email, $subject, $message);
        }
    }

    /**
     * Set notification emails
     */
    public function setNotificationEmails(array $emails) {
        $this->notificationEmails = array_filter($emails, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }

    /**
     * Set max log size (in bytes)
     */
    public function setMaxLogSize($size) {
        $this->maxLogSize = max(1048576, (int)$size); // Minimum 1MB
    }

    /**
     * Set max log files
     */
    public function setMaxLogFiles($count) {
        $this->maxLogFiles = max(1, (int)$count);
    }

    /**
     * Get error log path
     */
    public function getLogPath() {
        return $this->logPath;
    }

    /**
     * Clear error log
     */
    public function clearLog() {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * Get recent errors
     */
    public function getRecentErrors($limit = 100) {
        if (!file_exists($this->logPath)) {
            return [];
        }

        $errors = [];
        $lines = file($this->logPath);
        $currentError = null;

        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\[(.*?)\] (.*?): (.*)$/', $line, $matches)) {
                if (count($errors) >= $limit) {
                    break;
                }
                $currentError = [
                    'timestamp' => $matches[1],
                    'type' => $matches[2],
                    'message' => $matches[3],
                    'details' => []
                ];
                $errors[] = $currentError;
            } elseif ($currentError !== null && trim($line) !== '') {
                $currentError['details'][] = trim($line);
            }
        }

        return $errors;
    }
}
