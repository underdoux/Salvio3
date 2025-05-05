<?php
/**
 * URL Helper Functions
 */

if (!function_exists('base_url')) {
    /**
     * Get base URL of the application
     */
    function base_url($path = '') {
        $base_url = sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            dirname($_SERVER['SCRIPT_NAME'])
        );
        return rtrim($base_url, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL for given path
     */
    function url($path = '') {
        return base_url($path);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate URL for asset file
     */
    function asset($path) {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to given URL
     */
    function redirect($path) {
        header('Location: ' . base_url($path));
        exit();
    }
}
