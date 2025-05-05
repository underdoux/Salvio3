<?php
/**
 * Base Controller Class
 * All controllers should extend this class
 */
abstract class Controller {
    protected $view;
    protected $auth;
    protected $session;
    protected $db;

    public function __construct() {
        $this->view = new View();
        $this->auth = Auth::getInstance();
        $this->session = Session::getInstance();
        $this->db = Database::getInstance();
        
        // Load common helpers using Helper class
        Helper::load(['url']);
    }

    /**
     * Require authentication for all actions
     */
    protected function requireAuth() {
        if (!$this->auth->check()) {
            $this->setFlash('error', 'Please login to continue');
            $this->redirect('auth/login');
        }
    }

    /**
     * Require admin role
     */
    protected function requireAdmin() {
        $this->requireAuth();
        if (!$this->auth->isAdmin()) {
            $this->setFlash('error', 'Access denied');
            $this->redirect('dashboard');
        }
    }

    /**
     * Get query parameter
     */
    protected function getQuery($key, $default = null) {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Get post parameter
     */
    protected function getPost($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * Check if request is POST
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Set flash message
     */
    protected function setFlash($type, $message) {
        $this->session->setFlash($type, $message);
    }

    /**
     * Redirect to URL
     */
    /**
     * Load additional helper files
     */
    protected function loadHelpers($helpers) {
        Helper::load($helpers);
    }

    protected function redirect($url) {
        header('Location: ' . url($url));
        exit;
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrf() {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        return $token;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf() {
        $token = $this->getPost('csrf_token');
        $storedToken = $this->session->get('csrf_token');
        $this->session->remove('csrf_token');
        return $token && $storedToken && hash_equals($token, $storedToken);
    }

    /**
     * Log activity
     */
    protected function logActivity($type, $description) {
        if ($this->auth->check()) {
            $this->db->query("
                INSERT INTO activity_logs 
                (user_id, type, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ")
            ->bind(1, $this->auth->user()['id'])
            ->bind(2, $type)
            ->bind(3, $description)
            ->bind(4, $_SERVER['REMOTE_ADDR'])
            ->bind(5, $_SERVER['HTTP_USER_AGENT'])
            ->execute();
        }
    }

    /**
     * Get pagination data
     */
    protected function getPagination($total, $page, $limit) {
        $lastPage = ceil($total / $limit);
        $page = max(1, min($page, $lastPage));
        
        return [
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => ($page - 1) * $limit + 1,
            'to' => min($page * $limit, $total),
            'has_more' => $page < $lastPage
        ];
    }

    /**
     * Handle file upload
     */
    protected function handleUpload($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png']) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        $filename = uniqid() . '.' . $extension;
        $path = 'uploads/' . $directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new Exception('Failed to move uploaded file');
        }

        return $filename;
    }

    /**
     * Send JSON response
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send error response
     */
    protected function error($message, $status = 400) {
        $this->json(['error' => $message], $status);
    }

    /**
     * Send success response
     */
    protected function success($data = [], $message = 'Success') {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
}
