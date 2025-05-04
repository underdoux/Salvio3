<?php
class Controller {
    // Load model
    protected function model($model) {
        require_once 'models/' . $model . '.php';
        return new $model();
    }
    
    // Load view
    protected function view($view, $data = []) {
        if(file_exists('views/' . $view . '.php')) {
            require_once 'views/' . $view . '.php';
        } else {
            die('View does not exist');
        }
    }
    
    // Check if user is logged in
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check user role
    protected function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    // Require login
    protected function requireLogin() {
        if(!$this->isLoggedIn()) {
            $_SESSION['message'] = 'Please log in first';
            $this->redirect('auth');
        }
    }
    
    // Require specific role
    protected function requireRole($role) {
        if(!$this->hasRole($role)) {
            $_SESSION['message'] = 'Unauthorized access';
            $this->redirect('dashboard');
        }
    }
    
    // Flash message helper
    protected function flash($message, $type = 'success') {
        if(!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    // Redirect helper
    protected function redirect($url) {
        header('location: ' . BASE_URL . '/' . $url);
        exit;
    }
    
    // Clean input data
    protected function sanitizeInput($data) {
        $input = [];
        foreach($data as $key => $value) {
            $input[$key] = is_array($value) ? 
                          $this->sanitizeInput($value) : 
                          htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }
    
    // Get POST data
    protected function getPost() {
        return $this->sanitizeInput($_POST);
    }
    
    // Get GET data
    protected function getQuery() {
        return $this->sanitizeInput($_GET);
    }
}
