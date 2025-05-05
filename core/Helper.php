<?php
/**
 * Helper Loader Class
 * Centralizes helper function loading
 */
class Helper {
    private static $loaded = [];
    
    /**
     * Load helper files
     * @param string|array $helpers Helper names to load
     * @return void
     */
    public static function load($helpers) {
        if (!is_array($helpers)) {
            $helpers = [$helpers];
        }
        
        foreach ($helpers as $helper) {
            if (!isset(self::$loaded[$helper])) {
                $helperFile = dirname(__DIR__) . "/helpers/{$helper}_helper.php";
                if (file_exists($helperFile)) {
                    require_once $helperFile;
                    self::$loaded[$helper] = true;
                } else {
                    error_log("Helper file not found: {$helperFile}");
                }
            }
        }
    }
}
