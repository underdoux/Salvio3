<?php
/**
 * View Class
 * Handles template rendering and view logic
 */
class View {
    private $layout = 'layout/main';
    private $data = [];
    private $sections = [];
    private $currentSection = null;

    public function __construct() {
        // Load common helpers
        $this->loadHelpers(['url']);
    }

    /**
     * Load helper files
     */
    private function loadHelpers($helpers) {
        foreach ($helpers as $helper) {
            $helperFile = dirname(__DIR__) . "/helpers/{$helper}_helper.php";
            if (file_exists($helperFile)) {
                require_once $helperFile;
            } else {
                error_log("Helper file not found: {$helperFile}");
            }
        }
    }

    /**
     * Set layout template
     */
    public function setLayout($layout) {
        $this->layout = $layout;
    }

    /**
     * Disable layout
     */
    public function disableLayout() {
        $this->layout = null;
    }

    /**
     * Set view data
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Get view data
     */
    public function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Render view template
     */
    public function render($view, $data = []) {
        // Merge data
        $this->data = array_merge($this->data, $data);
        
        // Extract data to variables
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include view file
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }
        include $viewFile;
        
        // Get view content
        $content = ob_get_clean();
        
        // Render with layout if set
        if ($this->layout) {
            // Store content in sections array
            $this->sections['content'] = $content;
            
            // Start new output buffer for layout
            ob_start();
            
            // Include layout file
            $layoutFile = __DIR__ . '/../views/' . $this->layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new Exception("Layout file not found: {$layoutFile}");
            }
            include $layoutFile;
            
            // Get final content
            $content = ob_get_clean();
        }
        
        echo $content;
    }

    /**
     * Start a new section
     */
    public function section($name) {
        if ($this->currentSection) {
            throw new Exception("Cannot nest sections");
        }
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End the current section
     */
    public function endSection() {
        if (!$this->currentSection) {
            throw new Exception("No section started");
        }
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    /**
     * Get section content
     */
    public function getSection($name, $default = '') {
        return isset($this->sections[$name]) ? $this->sections[$name] : $default;
    }

    /**
     * Include partial view
     */
    public function partial($view, $data = []) {
        extract(array_merge($this->data, $data));
        include __DIR__ . '/../views/' . $view . '.php';
    }

    /**
     * Escape HTML
     */
    public function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format date
     */
    public function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }

    /**
     * Format currency
     */
    public function formatCurrency($amount, $decimals = 2) {
        return number_format($amount, $decimals, ',', '.');
    }

    /**
     * Generate pagination links
     */
    public function pagination($data, $baseUrl) {
        if ($data['last_page'] <= 1) {
            return '';
        }

        $html = '<nav class="pagination">';
        $html .= '<ul class="pagination-list">';

        // Previous page link
        if ($data['page'] > 1) {
            $html .= '<li><a href="' . $baseUrl . '?page=' . ($data['page'] - 1) . '">&laquo; Previous</a></li>';
        }

        // Page links
        for ($i = 1; $i <= $data['last_page']; $i++) {
            if ($i == $data['page']) {
                $html .= '<li class="active"><span>' . $i . '</span></li>';
            } else {
                $html .= '<li><a href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
            }
        }

        // Next page link
        if ($data['page'] < $data['last_page']) {
            $html .= '<li><a href="' . $baseUrl . '?page=' . ($data['page'] + 1) . '">Next &raquo;</a></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Get CSRF token field
     */
    public function csrf() {
        $token = bin2hex(random_bytes(32));
        Session::getInstance()->set('csrf_token', $token);
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Get flash messages
     */
    public function getFlash() {
        $session = Session::getInstance();
        $flash = $session->getFlash();
        if (!empty($flash)) {
            $html = '<div class="flash-messages">';
            foreach ($flash as $type => $message) {
                $html .= '<div class="alert alert-' . $type . '">' . $message . '</div>';
            }
            $html .= '</div>';
            return $html;
        }
        return '';
    }
}
