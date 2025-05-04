<?php
class ErrorController extends Controller {
    /**
     * Display 404 Not Found error
     */
    public function notFound() {
        http_response_code(404);
        $this->view('404', [
            'title' => '404 Not Found',
            'bodyClass' => 'error-page'
        ]);
    }
    
    /**
     * Display 403 Unauthorized error
     */
    public function unauthorized() {
        http_response_code(403);
        $this->view('errors/403', [
            'title' => '403 Unauthorized',
            'bodyClass' => 'error-page'
        ]);
    }
    
    /**
     * Display 500 Internal Server Error
     */
    public function serverError($exception = null) {
        http_response_code(500);
        
        // Log the error if exception is provided
        if ($exception instanceof Exception) {
            error_log($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }
        
        $this->view('errors/500', [
            'title' => '500 Internal Server Error',
            'bodyClass' => 'error-page',
            'showDetails' => defined('DEBUG_MODE') && DEBUG_MODE === true,
            'exception' => $exception
        ]);
    }
    
    /**
     * Display maintenance mode page
     */
    public function maintenance() {
        http_response_code(503);
        $this->view('errors/maintenance', [
            'title' => 'Under Maintenance',
            'bodyClass' => 'maintenance-page'
        ]);
    }
    
    /**
     * Display database connection error
     */
    public function databaseError($error = '') {
        http_response_code(500);
        $this->view('errors/database', [
            'title' => 'Database Error',
            'bodyClass' => 'error-page',
            'error' => $error
        ]);
    }
}
