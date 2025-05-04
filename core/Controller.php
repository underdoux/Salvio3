<?php
/**
 * Base Controller Class
 * All controllers extend this class
 */
class Controller {
    protected $view;
    protected $currentUser;

    public function __construct() {
        $this->view = new View();
        $this->currentUser = Auth::user();
    }

    /**
     * Load model
     * @param string $model Model name
     * @return object Model instance
     */
    protected function model($model) {
        if (file_exists('models/' . $model . '.php')) {
            require_once 'models/' . $model . '.php';
            return new $model();
        }
        throw new Exception("Model {$model} not found");
    }

    /**
     * Load view
     * @param string $view View name
     * @param array $data Data to pass to view
     */
    protected function view($view, $data = []) {
        $this->view->render($view, $data);
    }

    /**
     * Redirect to URL
     * @param string $url URL to redirect to
     */
    protected function redirect($url) {
        header('Location: ' . APP_URL . '/' . $url);
        exit;
    }

    /**
     * Set flash message
     * @param string $type Message type (success, error, info, warning)
     * @param string $message Message content
     */
    protected function setFlash($type, $message) {
        Session::setFlash($type, $message);
    }

    /**
     * Get POST data
     * @param string $key POST key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     * @param string $key GET key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if request is POST
     * @return bool
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request is GET
     * @return bool
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Check if request is AJAX
     * @return bool
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Require authentication
     * Redirects to login if not authenticated
     */
    protected function requireAuth() {
        if (!Auth::check()) {
            $this->setFlash('error', 'Please login to continue');
            $this->redirect('auth');
        }
    }

    /**
     * Require admin role
     * Redirects to dashboard if not admin
     */
    protected function requireAdmin() {
        $this->requireAuth();
        
        if (!Auth::hasRole('admin')) {
            $this->setFlash('error', 'Access denied');
            $this->redirect('dashboard');
        }
    }

    /**
     * Get current user
     * @return array|null User data
     */
    protected function getCurrentUser() {
        return $this->currentUser;
    }

    /**
     * Log activity
     * @param string $type Activity type
     * @param string $description Activity description
     */
    protected function logActivity($type, $description) {
        $userId = Auth::id();
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO activity_logs (user_id, type, description, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?)"
        )
        ->bind(1, $userId)
        ->bind(2, $type)
        ->bind(3, $description)
        ->bind(4, $ipAddress)
        ->bind(5, $userAgent)
        ->execute();
    }

    /**
     * Handle file upload
     * @param string $field Form field name
     * @param string $directory Upload directory
     * @param array $allowedTypes Allowed file types
     * @param int $maxSize Maximum file size in bytes
     * @return string|false Filename on success, false on failure
     */
    protected function handleUpload($field, $directory, $allowedTypes = [], $maxSize = 5242880) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $file = $_FILES[$field];
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            $this->setFlash('error', 'File is too large');
            return false;
        }

        // Validate file type
        $fileType = mime_content_type($file['tmp_name']);
        if (!empty($allowedTypes) && !in_array($fileType, $allowedTypes)) {
            $this->setFlash('error', 'Invalid file type');
            return false;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = $directory . '/' . $filename;

        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->setFlash('error', 'Failed to upload file');
            return false;
        }

        return $filename;
    }

    /**
     * Delete file
     * @param string $path File path
     * @return bool
     */
    protected function deleteFile($path) {
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Send JSON response
     * @param mixed $data Response data
     * @param int $status HTTP status code
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Validate CSRF token
     * @return bool
     */
    protected function validateCsrf() {
        $token = $this->getPost('csrf_token');
        return Session::verifyCsrfToken($token);
    }

    /**
     * Generate CSRF token
     * @return string
     */
    protected function generateCsrf() {
        return Session::generateCsrfToken();
    }
}
