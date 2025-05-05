<?php
/**
 * View Class
 * Handles view rendering and template management
 */
class View {
    private $layout = 'main';
    private $data = [];
    private $sections = [];
    private $currentSection = null;

    /**
     * Set layout
     * @param string|null $layout Layout name or null for no layout
     */
    public function setLayout($layout) {
        $this->layout = $layout;
    }

    /**
     * Get current layout
     * @return string|null
     */
    public function getLayout() {
        return $this->layout;
    }

    /**
     * Set view data
     * @param array $data Data to pass to view
     */
    public function setData($data) {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Get view data
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Render view
     * @param string $view View name
     * @param array $data Data to pass to view
     */
    public function render($view, $data = []) {
        // Merge data
        $this->setData($data);
        
        // Start output buffering
        ob_start();
        
        // Extract data to make it available in view
        extract($this->data);
        
        // Include view file
        $viewFile = VIEW_PATH . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View {$view} not found");
        }
        
        include $viewFile;
        
        // Get view content
        $this->data['content'] = ob_get_clean();
        
        // Render with layout if set
        if ($this->layout !== null) {
            $layoutFile = VIEW_PATH . '/layout/' . $this->layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new Exception("Layout {$this->layout} not found");
            }
            
            extract($this->data);
            include $layoutFile;
        } else {
            echo $this->data['content'];
        }
    }

    /**
     * Start a section
     * @param string $name Section name
     */
    public function section($name) {
        if ($this->currentSection) {
            throw new Exception('Cannot nest sections');
        }
        
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End current section
     */
    public function endSection() {
        if (!$this->currentSection) {
            throw new Exception('No section started');
        }
        
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    /**
     * Get section content
     * @param string $name Section name
     * @param string $default Default content if section not found
     * @return string
     */
    public function getSection($name, $default = '') {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Include partial view
     * @param string $partial Partial view name
     * @param array $data Data to pass to partial
     */
    public function partial($partial, $data = []) {
        extract(array_merge($this->data, $data));
        
        $partialFile = VIEW_PATH . '/partials/' . $partial . '.php';
        if (!file_exists($partialFile)) {
            throw new Exception("Partial {$partial} not found");
        }
        
        include $partialFile;
    }

    /**
     * Generate CSRF token field
     * @return string HTML input field
     */
    public function csrf() {
        $token = Session::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Format date
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }

    /**
     * Format currency
     * @param float $amount Amount
     * @param string $currency Currency code
     * @return string Formatted currency
     */
    public function formatCurrency($amount, $currency = null) {
        $currency = $currency ?? APP_CURRENCY;
        
        switch ($currency) {
            case 'IDR':
                return 'Rp ' . number_format($amount, 0, ',', '.');
            case 'USD':
                return '$' . number_format($amount, 2);
            default:
                return number_format($amount, 2);
        }
    }

    /**
     * Get active menu class
     * @param string $menu Menu name
     * @param string $activeClass Active class name
     * @return string Class name if active
     */
    public function getActiveMenu($menu, $activeClass = 'active') {
        $url = $_GET['url'] ?? '';
        $currentMenu = explode('/', $url)[0];
        
        return $currentMenu === $menu ? $activeClass : '';
    }

    /**
     * Escape HTML
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate pagination links
     * @param array $pagination Pagination data
     * @return string HTML pagination links
     */
    public function pagination($pagination) {
        if ($pagination['last_page'] <= 1) {
            return '';
        }

        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Previous link
        if ($pagination['current_page'] > 1) {
            $html .= '<li class="page-item">
                        <a class="page-link" href="?page=' . ($pagination['current_page'] - 1) . '">Previous</a>
                     </li>';
        }
        
        // Page links
        for ($i = 1; $i <= $pagination['last_page']; $i++) {
            if ($i == $pagination['current_page']) {
                $html .= '<li class="page-item active">
                            <span class="page-link">' . $i . '</span>
                         </li>';
            } else {
                $html .= '<li class="page-item">
                            <a class="page-link" href="?page=' . $i . '">' . $i . '</a>
                         </li>';
            }
        }
        
        // Next link
        if ($pagination['current_page'] < $pagination['last_page']) {
            $html .= '<li class="page-item">
                        <a class="page-link" href="?page=' . ($pagination['current_page'] + 1) . '">Next</a>
                     </li>';
        }
        
        $html .= '</ul></nav>';
        
        return $html;
    }

    /**
     * Get flash messages HTML
     * @return string HTML flash messages
     */
    public function getFlashMessages() {
        $html = '';
        $types = ['success', 'error', 'info', 'warning'];
        
        foreach ($types as $type) {
            $message = Session::getFlash($type);
            if ($message) {
                $class = $type === 'error' ? 'danger' : $type;
                $html .= '<div class="alert alert-' . $class . ' alert-dismissible fade show" role="alert">
                            ' . $message . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                         </div>';
            }
        }
        
        return $html;
    }
}
