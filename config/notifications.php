<?php
/**
 * Notification Configuration
 */
return [
    // Email settings
    'email' => [
        'from' => [
            'address' => MAIL_FROM,
            'name' => APP_NAME
        ],
        'reply_to' => MAIL_FROM,
        'template_path' => BASE_PATH . '/views/emails'
    ],

    // Browser notification settings
    'browser' => [
        'default_icon' => 'bell',
        'default_color' => 'primary',
        'auto_dismiss' => 5000, // milliseconds
        'position' => 'top-right',
        'sound' => true
    ],

    // Notification templates
    'templates' => [
        // System notifications
        'system_error' => [
            'title' => 'System Error',
            'message' => 'An error occurred: {message}',
            'icon' => 'exclamation-triangle',
            'color' => 'danger'
        ],
        'system_warning' => [
            'title' => 'System Warning',
            'message' => '{message}',
            'icon' => 'exclamation',
            'color' => 'warning'
        ],
        'system_info' => [
            'title' => 'System Information',
            'message' => '{message}',
            'icon' => 'info-circle',
            'color' => 'info'
        ],
        'maintenance' => [
            'title' => 'System Maintenance',
            'message' => 'System will be under maintenance from {start_time} to {end_time}',
            'icon' => 'tools',
            'color' => 'warning'
        ],

        // User notifications
        'welcome' => [
            'title' => 'Welcome to {app_name}',
            'message' => 'Welcome {name}! Thank you for joining us.',
            'icon' => 'hand-wave',
            'color' => 'success'
        ],
        'password_reset' => [
            'title' => 'Password Reset',
            'message' => 'Your password has been reset successfully.',
            'icon' => 'key',
            'color' => 'success'
        ],
        'login_alert' => [
            'title' => 'New Login',
            'message' => 'New login detected from {location} using {device}',
            'icon' => 'shield-exclamation',
            'color' => 'warning'
        ],

        // Inventory notifications
        'low_stock' => [
            'title' => 'Low Stock Alert',
            'message' => 'Product {product_name} is running low ({current_stock} remaining)',
            'icon' => 'box',
            'color' => 'warning'
        ],
        'out_of_stock' => [
            'title' => 'Out of Stock',
            'message' => 'Product {product_name} is out of stock',
            'icon' => 'box',
            'color' => 'danger'
        ],
        'stock_update' => [
            'title' => 'Stock Updated',
            'message' => '{product_name} stock updated to {new_stock}',
            'icon' => 'box-check',
            'color' => 'success'
        ],

        // Sales notifications
        'new_sale' => [
            'title' => 'New Sale',
            'message' => 'New sale of {amount} by {sales_person}',
            'icon' => 'shopping-cart',
            'color' => 'success'
        ],
        'payment_received' => [
            'title' => 'Payment Received',
            'message' => 'Payment of {amount} received for invoice #{invoice_number}',
            'icon' => 'money-bill',
            'color' => 'success'
        ],
        'payment_overdue' => [
            'title' => 'Payment Overdue',
            'message' => 'Payment for invoice #{invoice_number} is overdue by {days} days',
            'icon' => 'clock',
            'color' => 'danger'
        ],

        // Commission notifications
        'commission_earned' => [
            'title' => 'Commission Earned',
            'message' => 'You earned {amount} commission from sale #{sale_id}',
            'icon' => 'money-bill',
            'color' => 'success'
        ],
        'commission_paid' => [
            'title' => 'Commission Paid',
            'message' => 'Commission of {amount} has been paid to your account',
            'icon' => 'money-bill-transfer',
            'color' => 'success'
        ],

        // Customer notifications
        'new_customer' => [
            'title' => 'New Customer',
            'message' => 'New customer {name} has been registered',
            'icon' => 'user',
            'color' => 'info'
        ],
        'customer_birthday' => [
            'title' => 'Customer Birthday',
            'message' => "It's {name}'s birthday today!",
            'icon' => 'cake-candles',
            'color' => 'info'
        ],

        // Supplier notifications
        'purchase_order' => [
            'title' => 'Purchase Order',
            'message' => 'New purchase order #{po_number} created for {supplier}',
            'icon' => 'file-invoice',
            'color' => 'info'
        ],
        'order_received' => [
            'title' => 'Order Received',
            'message' => 'Order #{po_number} from {supplier} has been received',
            'icon' => 'truck',
            'color' => 'success'
        ],
        'supplier_payment_due' => [
            'title' => 'Supplier Payment Due',
            'message' => 'Payment of {amount} to {supplier} is due in {days} days',
            'icon' => 'clock',
            'color' => 'warning'
        ],

        // Report notifications
        'report_ready' => [
            'title' => 'Report Ready',
            'message' => '{report_name} for {period} is ready for review',
            'icon' => 'file-chart-line',
            'color' => 'info'
        ],
        'sales_target' => [
            'title' => 'Sales Target',
            'message' => '{message}',
            'icon' => 'chart-line',
            'color' => 'info'
        ]
    ],

    // Notification channels
    'channels' => [
        'email' => [
            'enabled' => true,
            'queue' => true,
            'rate_limit' => [
                'attempts' => 3,
                'decay_minutes' => 60
            ]
        ],
        'browser' => [
            'enabled' => true,
            'queue' => false,
            'rate_limit' => [
                'attempts' => 10,
                'decay_minutes' => 1
            ]
        ],
        'database' => [
            'enabled' => true,
            'queue' => false,
            'prune_after_days' => 30
        ]
    ],

    // Queue settings
    'queue' => [
        'default' => 'sync',
        'connections' => [
            'sync' => [
                'driver' => 'sync'
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90
            ]
        ]
    ],

    // Cleanup settings
    'cleanup' => [
        'enabled' => true,
        'schedule' => '0 0 * * *', // Daily at midnight
        'keep_days' => 30,
        'batch_size' => 1000
    ]
];
