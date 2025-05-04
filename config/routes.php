<?php
/**
 * Route Configuration
 * 
 * This file defines the routing rules and access permissions for the application.
 * Each route is defined with its controller, allowed methods, and required roles.
 */

return [
    // Public routes (no authentication required)
    'public' => [
        'auth' => ['index', 'login', 'forgot', 'reset'],
        'error' => ['index', 'notFound', 'unauthorized']
    ],
    
    // Routes that require authentication
    'authenticated' => [
        // Admin-only routes
        'admin' => [
            'controller' => 'AdminController',
            'actions' => ['index', 'users', 'settings'],
            'roles' => ['admin']
        ],
        
        // Sales routes
        'sales' => [
            'controller' => 'SalesController',
            'actions' => ['index', 'create', 'view', 'update'],
            'roles' => ['admin', 'sales']
        ],
        
        // Product routes
        'products' => [
            'controller' => 'ProductController',
            'actions' => [
                'index' => ['admin', 'sales'],
                'create' => ['admin'],
                'edit' => ['admin'],
                'delete' => ['admin'],
                'view' => ['admin', 'sales']
            ]
        ],
        
        // Customer routes
        'customers' => [
            'controller' => 'CustomerController',
            'actions' => [
                'index' => ['admin', 'sales'],
                'create' => ['admin', 'sales'],
                'edit' => ['admin', 'sales'],
                'delete' => ['admin'],
                'view' => ['admin', 'sales']
            ]
        ],
        
        // Category routes
        'categories' => [
            'controller' => 'CategoryController',
            'actions' => [
                'index' => ['admin', 'sales'],
                'create' => ['admin'],
                'edit' => ['admin'],
                'delete' => ['admin']
            ]
        ],
        
        // Report routes
        'reports' => [
            'controller' => 'ReportController',
            'actions' => [
                'sales' => ['admin'],
                'inventory' => ['admin'],
                'customers' => ['admin'],
                'commission' => ['admin']
            ]
        ],
        
        // Profile routes (available to all authenticated users)
        'profile' => [
            'controller' => 'ProfileController',
            'actions' => ['index', 'edit', 'password'],
            'roles' => ['admin', 'sales']
        ],
        
        // Dashboard routes
        'dashboard' => [
            'controller' => 'DashboardController',
            'actions' => ['index'],
            'roles' => ['admin', 'sales']
        ]
    ],
    
    // Default routes when no specific route is matched
    'defaults' => [
        'controller' => 'AuthController',
        'action' => 'index'
    ],
    
    // Error routes
    'errors' => [
        '404' => [
            'controller' => 'ErrorController',
            'action' => 'notFound'
        ],
        '403' => [
            'controller' => 'ErrorController',
            'action' => 'unauthorized'
        ]
    ]
];
