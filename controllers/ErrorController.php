<?php
/**
 * Error Controller
 * Handles error pages and logging
 */
class ErrorController extends Controller {
    /**
     * Show 404 error page
     */
    public function notFound() {
        http_response_code(404);
        
        // Log 404 error
        $this->logError(404, 'Page not found: ' . $_SERVER['REQUEST_URI']);

        // Check if AJAX request
        if ($this->isAjax()) {
            $this->json([
                'error' => true,
                'code' => 404,
                'message' => 'Page not found'
            ], 404);
        }

        $this->view->setLayout(null);
        $this->view->render('errors/404', [
            'title' => '404 Not Found - ' . APP_NAME
        ]);
    }

    /**
     * Show 403 error page
     */
    public function forbidden() {
        http_response_code(403);
        
        // Log 403 error
        $this->logError(403, 'Access denied: ' . $_SERVER['REQUEST_URI']);

        // Check if AJAX request
        if ($this->isAjax()) {
            $this->json([
                'error' => true,
                'code' => 403,
                'message' => 'Access denied'
            ], 403);
        }

        $this->view->setLayout(null);
        $this->view->render('errors/403', [
            'title' => '403 Access Denied - ' . APP_NAME
        ]);
    }

    /**
     * Show 500 error page
     */
    public function serverError($error = null) {
        http_response_code(500);
        
        // Generate unique error ID
        $errorId = date('YmdHis') . '-' . substr(uniqid(), -6);

        // Log error details
        $errorDetails = [
            'id' => $errorId,
            'message' => $error['message'] ?? 'Unknown error',
            'file' => $error['file'] ?? '',
            'line' => $error['line'] ?? '',
            'trace' => $error['trace'] ?? '',
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'user_id' => $this->auth->check() ? $this->auth->user()['id'] : null,
            'post_data' => $_POST,
            'get_data' => $_GET
        ];

        $this->logError(500, json_encode($errorDetails, JSON_PRETTY_PRINT));

        // Check if AJAX request
        if ($this->isAjax()) {
            $this->json([
                'error' => true,
                'code' => 500,
                'message' => DEBUG ? $error['message'] : 'Internal server error',
                'error_id' => $errorId
            ], 500);
        }

        $this->view->setLayout(null);
        $this->view->render('errors/500', [
            'title' => '500 Internal Server Error - ' . APP_NAME,
            'error' => DEBUG ? $error : null,
            'errorId' => $errorId
        ]);
    }

    /**
     * Show maintenance page
     */
    public function maintenance() {
        http_response_code(503);

        // Check if AJAX request
        if ($this->isAjax()) {
            $this->json([
                'error' => true,
                'code' => 503,
                'message' => 'System is under maintenance'
            ], 503);
        }

        $this->view->setLayout(null);
        $this->view->render('errors/maintenance', [
            'title' => 'Maintenance - ' . APP_NAME
        ]);
    }

    /**
     * Log error to file
     */
    private function logError($code, $message) {
        $logFile = LOG_PATH . '/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$code}: {$message}" . PHP_EOL;

        // Create log directory if it doesn't exist
        if (!file_exists(LOG_PATH)) {
            mkdir(LOG_PATH, 0755, true);
        }

        // Append to log file
        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Rotate log file if it's too large
        if (filesize($logFile) > 10 * 1024 * 1024) { // 10MB
            $this->rotateLogFile($logFile);
        }

        // Send error notification if critical
        if ($code === 500) {
            $this->sendErrorNotification($code, $message);
        }
    }

    /**
     * Rotate log file
     */
    private function rotateLogFile($logFile) {
        $maxFiles = 5;
        
        // Remove oldest file if max files reached
        if (file_exists($logFile . '.' . $maxFiles)) {
            unlink($logFile . '.' . $maxFiles);
        }

        // Rotate existing files
        for ($i = $maxFiles - 1; $i >= 1; $i--) {
            if (file_exists($logFile . '.' . $i)) {
                rename($logFile . '.' . $i, $logFile . '.' . ($i + 1));
            }
        }

        // Move current file
        rename($logFile, $logFile . '.1');
    }

    /**
     * Send error notification
     */
    private function sendErrorNotification($code, $message) {
        // TODO: Implement notification system (email, Slack, etc.)
        if (DEBUG) {
            error_log("Error notification would be sent: {$code} - {$message}");
        }
    }

    /**
     * Check if request is AJAX
     */
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
