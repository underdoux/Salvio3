<?php
/**
 * Maintenance Mode Configuration
 */
return [
    // Enable/disable maintenance mode
    'enabled' => false,

    // Maintenance mode settings
    'settings' => [
        // Duration in seconds (default: 3 hours)
        'duration' => 3 * 60 * 60,

        // Custom maintenance message
        'message' => 'We are currently performing scheduled maintenance. Please try again later.',

        // Allowed IP addresses that can access the site during maintenance
        'allowed_ips' => [
            '127.0.0.1',    // localhost
            '::1'           // localhost IPv6
        ],

        // Bypass key for accessing site during maintenance
        // Access using: yourdomain.com?maintenance=your_bypass_key
        'bypass_key' => null,

        // Routes that are accessible during maintenance
        'allowed_routes' => [
            'auth/login',
            'auth/logout',
            'error/*'
        ],

        // Email notifications
        'notifications' => [
            // Enable email notifications
            'enabled' => true,

            // Email addresses to notify when maintenance mode is enabled/disabled
            'emails' => [
                // 'admin@example.com'
            ],

            // Notification templates
            'templates' => [
                'enabled' => [
                    'subject' => '[{app_name}] Maintenance Mode Enabled',
                    'body' => "Maintenance mode has been enabled.\n\nDetails:\n- Start Time: {start_time}\n- End Time: {end_time}\n- Duration: {duration}\n- Message: {message}"
                ],
                'disabled' => [
                    'subject' => '[{app_name}] Maintenance Mode Disabled',
                    'body' => "Maintenance mode has been disabled.\n\nDetails:\n- Start Time: {start_time}\n- End Time: {end_time}\n- Duration: {duration}"
                ]
            ]
        ],

        // Logging
        'logging' => [
            // Enable logging of maintenance mode events
            'enabled' => true,

            // Log file path (relative to LOG_PATH)
            'file' => 'maintenance.log',

            // Events to log
            'events' => [
                'enabled' => true,     // Log when maintenance mode is enabled
                'disabled' => true,    // Log when maintenance mode is disabled
                'access' => true,      // Log access attempts during maintenance
                'bypass' => true       // Log successful bypasses
            ]
        ],

        // Response settings
        'response' => [
            // HTTP status code to return during maintenance
            'status_code' => 503,

            // Response headers
            'headers' => [
                'Retry-After' => 3600,
                'Cache-Control' => 'no-cache, private'
            ],

            // Content type for maintenance page
            'content_type' => 'text/html; charset=UTF-8'
        ],

        // Assets accessible during maintenance
        'accessible_assets' => [
            'css' => [
                'auth.css',
                'theme.css'
            ],
            'js' => [
                'theme.js'
            ],
            'images' => [
                'maintenance.svg',
                'logo.png'
            ]
        ],

        // Countdown timer settings
        'countdown' => [
            // Show countdown timer on maintenance page
            'enabled' => true,

            // Update frequency in seconds
            'refresh_interval' => 60,

            // Format for displaying remaining time
            'format' => [
                'days' => '%d days',
                'hours' => '%h hours',
                'minutes' => '%m minutes',
                'seconds' => '%s seconds'
            ]
        ],

        // Notification form settings
        'notification_form' => [
            // Enable email notification form on maintenance page
            'enabled' => true,

            // Store subscribed emails in this file (relative to CONFIG_PATH)
            'storage' => 'maintenance_subscribers.json',

            // Maximum number of email subscriptions
            'max_subscribers' => 1000,

            // Email verification
            'verify_email' => true,

            // Thank you message
            'success_message' => 'Thank you! We will notify you when the system is back online.'
        ],

        // Social media links
        'social_links' => [
            'twitter' => 'https://twitter.com/youraccount',
            'facebook' => 'https://facebook.com/youraccount',
            'instagram' => 'https://instagram.com/youraccount'
        ],

        // Cache settings
        'cache' => [
            // Clear application cache when entering/exiting maintenance mode
            'clear_on_change' => true,

            // Cache maintenance mode status
            'enabled' => true,

            // Cache lifetime in seconds
            'lifetime' => 60
        ],

        // Cleanup settings
        'cleanup' => [
            // Clear temporary files
            'temp_files' => true,

            // Clear cache files
            'cache_files' => true,

            // Clear session files
            'session_files' => false,

            // Clear log files
            'log_files' => false
        ]
    ]
];
