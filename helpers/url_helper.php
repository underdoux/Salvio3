<?php
/**
 * URL Helper Functions
 * Contains functions for URL manipulation and generation
 */

if (!function_exists('url')) {
    /**
     * Generate a URL for the application
     * 
     * @param string $path The path to append to the base URL
     * @return string The complete URL
     */
    function url($path = '') {
        // Get the base URL from config or construct it
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . '/Salvio3';
        
        // Clean up the path
        $path = trim($path, '/');
        
        // Return the complete URL
        return $path ? $baseUrl . '/' . $path : $baseUrl;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for an asset
     * 
     * @param string $path The path to the asset
     * @return string The complete asset URL
     */
    function asset($path) {
        return url('assets/' . trim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to another URL
     * 
     * @param string $path The path to redirect to
     * @return void
     */
    function redirect($path) {
        header('Location: ' . url($path));
        exit;
    }
}
