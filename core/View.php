<?php
class View {
    private $data = [];
    private $layout = 'default';
    private static $blocks = [];
    private static $blockName = null;
    
    /**
     * Render a view file
     */
    public function render($view, $data = []) {
        // Merge data
        $this->data = array_merge($this->data, $data);
        
        // Extract data to make it available in view
        extract($this->data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = 'views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new Exception("View file '{$view}' not found");
        }
        
        // Get contents and clean buffer
        $content = ob_get_clean();
        
        // Render with layout if not disabled
        if ($this->layout !== false) {
            $layoutFile = 'views/layout/' . $this->layout . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }
    
    /**
     * Set or disable layout
     */
    public function setLayout($layout) {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Add data to view
     */
    public function with($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Start a content block
     */
    public static function start($name) {
        if (self::$blockName !== null) {
            throw new Exception('Cannot nest blocks');
        }
        self::$blockName = $name;
        ob_start();
    }
    
    /**
     * End a content block
     */
    public static function end() {
        if (self::$blockName === null) {
            throw new Exception('No block started');
        }
        self::$blocks[self::$blockName] = ob_get_clean();
        self::$blockName = null;
    }
    
    /**
     * Get content of a block
     */
    public static function block($name) {
        return self::$blocks[$name] ?? '';
    }
    
    /**
     * Include a partial view
     */
    public function partial($view, $data = []) {
        extract(array_merge($this->data, $data));
        
        $partialFile = 'views/partials/' . $view . '.php';
        if (file_exists($partialFile)) {
            require $partialFile;
        } else {
            throw new Exception("Partial view '{$view}' not found");
        }
    }
    
    /**
     * Escape HTML
     */
    public static function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Format date
     */
    public static function formatDate($date, $format = 'd M Y') {
        return date($format, strtotime($date));
    }
    
    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = 'IDR') {
        return number_format($amount, 0, ',', '.') . ' ' . $currency;
    }
    
    /**
     * Generate CSRF token field
     */
    public static function csrf() {
        return '<input type="hidden" name="csrf_token" value="' . Session::getCsrfToken() . '">';
    }
    
    /**
     * Check if current route is active
     */
    public static function isActive($route) {
        $currentRoute = $_GET['url'] ?? '';
        return strpos($currentRoute, $route) === 0 ? 'active' : '';
    }
    
    /**
     * Generate pagination links
     */
    public static function pagination($total, $perPage, $currentPage, $url) {
        $pages = ceil($total / $perPage);
        
        if ($pages <= 1) return '';
        
        $html = '<nav class="pagination">';
        $html .= '<ul>';
        
        // Previous link
        if ($currentPage > 1) {
            $html .= '<li><a href="' . $url . '?page=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $pages; $i++) {
            if ($i == $currentPage) {
                $html .= '<li class="active"><span>' . $i . '</span></li>';
            } else {
                $html .= '<li><a href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Next link
        if ($currentPage < $pages) {
            $html .= '<li><a href="' . $url . '?page=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Flash messages
     */
    public static function flash() {
        $messages = Session::getFlash();
        if (!empty($messages)) {
            $html = '<div class="flash-messages">';
            foreach ($messages as $message) {
                $html .= '<div class="alert alert-' . $message['type'] . '">';
                $html .= self::escape($message['message']);
                $html .= '</div>';
            }
            $html .= '</div>';
            return $html;
        }
        return '';
    }
}
