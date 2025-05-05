<?php

/**
 * URL Helper Functions
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
        $base_url = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
        $base_url .= '://' . $_SERVER['HTTP_HOST'];
        $base_url .= str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
        
        // Remove trailing slashes
        $base_url = rtrim($base_url, '/');
        $path = ltrim($path, '/');
        
        return $base_url . '/' . $path;
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to another URL
     *
     * @param string $path The path to redirect to
     * @return void
     */
    function redirect($path = '') {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a URL for an asset
     *
     * @param string $path The path to the asset
     * @return string The complete URL for the asset
     */
    function asset($path = '') {
        return url('assets/' . ltrim($path, '/'));
    }
}
