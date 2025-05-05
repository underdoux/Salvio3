-- Report Configurations table
CREATE TABLE report_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT NULL,
    parameters JSON NULL,
    schedule VARCHAR(50) NULL COMMENT 'Cron expression for scheduled reports',
    recipients JSON NULL COMMENT 'Email recipients for scheduled reports',
    created_by INT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_type (type),
    INDEX idx_report_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Cache table
CREATE TABLE report_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    configuration_id INT NOT NULL,
    parameters JSON NULL,
    data LONGTEXT NOT NULL,
    format VARCHAR(20) NOT NULL DEFAULT 'json',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_report_config (configuration_id),
    INDEX idx_report_expiry (expires_at),
    FOREIGN KEY (configuration_id) REFERENCES report_configurations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Schedules table
CREATE TABLE report_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    configuration_id INT NOT NULL,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    status ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schedule_config (configuration_id),
    INDEX idx_schedule_next_run (next_run, status),
    FOREIGN KEY (configuration_id) REFERENCES report_configurations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Downloads table
CREATE TABLE report_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    configuration_id INT NOT NULL,
    user_id INT NOT NULL,
    parameters JSON NULL,
    format VARCHAR(20) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_download_config (configuration_id),
    INDEX idx_download_user (user_id),
    FOREIGN KEY (configuration_id) REFERENCES report_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics Events table
CREATE TABLE analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_event_user (user_id),
    INDEX idx_event_date (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics Metrics table
CREATE TABLE analytics_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(50) NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    dimension VARCHAR(50) NULL,
    dimension_value VARCHAR(100) NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name (metric_name, dimension, dimension_value),
    INDEX idx_metric_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin user if not exists
INSERT INTO users (username, password, email, name, role, status)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Admin', 'admin', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Insert default report configurations
INSERT INTO report_configurations 
(name, type, description, parameters, created_by)
SELECT 
    r.name, 
    r.type, 
    r.description, 
    r.parameters,
    u.id as created_by
FROM (
    SELECT 'Sales Summary' as name, 'sales' as type, 'Daily sales summary with product breakdown' as description, 
           '{"period": "daily", "metrics": ["total_sales", "total_items", "average_order"]}' as parameters
    UNION ALL
    SELECT 'Inventory Status', 'inventory', 'Current inventory levels and reorder alerts',
           '{"threshold": "min_stock", "sort": "stock_level"}'
    UNION ALL
    SELECT 'Commission Report', 'commission', 'Sales staff commission calculations',
           '{"period": "monthly", "include_pending": true}'
    UNION ALL
    SELECT 'Profit Analysis', 'financial', 'Profit and loss analysis by period',
           '{"period": "monthly", "include_costs": true}'
    UNION ALL
    SELECT 'Customer Activity', 'customer', 'Customer purchase history and trends',
           '{"period": "monthly", "metrics": ["purchase_frequency", "average_value"]}'
    UNION ALL
    SELECT 'Supplier Performance', 'supplier', 'Supplier delivery and payment analysis',
           '{"metrics": ["delivery_time", "payment_status", "quality_issues"]}'
) r
CROSS JOIN users u
WHERE u.username = 'admin'
LIMIT 1;

-- Create indexes for common report queries
CREATE INDEX IF NOT EXISTS idx_report_sales_date ON sales(created_at);
CREATE INDEX IF NOT EXISTS idx_report_sales_customer ON sales(customer_id, created_at);
CREATE INDEX IF NOT EXISTS idx_report_sales_user ON sales(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_report_product_stock ON products(stock, reorder_point);
CREATE INDEX IF NOT EXISTS idx_report_commission_status ON commission_calculations(status, created_at);
CREATE INDEX IF NOT EXISTS idx_report_supplier_orders ON purchase_orders(supplier_id, order_date);
CREATE INDEX IF NOT EXISTS idx_report_customer_activity ON sales(customer_id, created_at, total_amount);

-- Add report-related columns to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS report_preferences JSON NULL COMMENT 'User report preferences and filters',
ADD COLUMN IF NOT EXISTS last_report_access TIMESTAMP NULL;

-- Add analytics-related columns to products table
ALTER TABLE products
ADD COLUMN IF NOT EXISTS view_count INT NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS purchase_count INT NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_viewed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS trending_score DECIMAL(10,4) NULL;

-- Add analytics-related columns to customers table
ALTER TABLE customers
ADD COLUMN IF NOT EXISTS lifetime_value DECIMAL(15,2) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_purchase_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS purchase_frequency INT NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS churn_risk DECIMAL(5,2) NULL;
