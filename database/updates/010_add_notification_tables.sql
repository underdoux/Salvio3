-- Add phone column to users table if not exists
ALTER TABLE users
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email;

-- Notification Templates table
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('email', 'whatsapp') NOT NULL,
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    variables JSON NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Queue table
CREATE TABLE notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    type ENUM('email', 'whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    variables JSON NULL,
    status ENUM('pending', 'processing', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    priority TINYINT NOT NULL DEFAULT 0,
    attempts INT NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status, scheduled_at),
    INDEX idx_template (template_id),
    FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification History table
CREATE TABLE notification_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    type ENUM('email', 'whatsapp') NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_queue (queue_id),
    INDEX idx_recipient (recipient),
    INDEX idx_sent_at (sent_at),
    FOREIGN KEY (queue_id) REFERENCES notification_queue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Settings table
CREATE TABLE notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    email_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    whatsapp_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_event (user_id, event_type),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WhatsApp Configuration table
CREATE TABLE whatsapp_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider ENUM('twilio', 'wablas', 'fonnte') NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    api_secret VARCHAR(255) NULL,
    sender_number VARCHAR(20) NOT NULL,
    webhook_url VARCHAR(255) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (provider, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Configuration table
CREATE TABLE email_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver ENUM('smtp', 'sendmail', 'mailgun') NOT NULL,
    host VARCHAR(255) NULL,
    port INT NULL,
    username VARCHAR(255) NULL,
    password VARCHAR(255) NULL,
    encryption ENUM('tls', 'ssl') NULL,
    from_address VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_driver (driver, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add notification-related columns to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS email_notifications BOOLEAN NOT NULL DEFAULT TRUE AFTER email,
ADD COLUMN IF NOT EXISTS whatsapp_notifications BOOLEAN NOT NULL DEFAULT TRUE AFTER phone,
ADD COLUMN IF NOT EXISTS notification_preferences JSON NULL COMMENT 'User notification preferences';

-- Insert default notification templates
INSERT INTO notification_templates 
(name, type, subject, content, variables, created_by)
SELECT 
    t.name, 
    t.type, 
    t.subject, 
    t.content,
    t.variables,
    u.id as created_by
FROM (
    SELECT 'Low Stock Alert' as name, 'email' as type, 
           'Low Stock Alert: {{product_name}}' as subject,
           'Dear Admin,\n\nProduct {{product_name}} (SKU: {{sku}}) is running low on stock.\nCurrent stock: {{current_stock}}\nMinimum stock: {{min_stock}}\n\nPlease reorder soon.' as content,
           '{"product_name":"string","sku":"string","current_stock":"number","min_stock":"number"}' as variables
    UNION ALL
    SELECT 'Payment Due Reminder', 'whatsapp', NULL,
           'Dear {{customer_name}},\nThis is a reminder that payment of {{amount}} for invoice {{invoice_number}} is due on {{due_date}}.\nPlease process the payment to avoid any service interruption.',
           '{"customer_name":"string","amount":"number","invoice_number":"string","due_date":"date"}'
    UNION ALL
    SELECT 'Order Confirmation', 'email', 'Order Confirmation #{{order_number}}',
           'Dear {{customer_name}},\n\nThank you for your order #{{order_number}}.\nTotal amount: {{total_amount}}\n\nWe will process your order shortly.',
           '{"customer_name":"string","order_number":"string","total_amount":"number"}'
    UNION ALL
    SELECT 'Stock Receipt', 'email', 'Stock Receipt for PO #{{po_number}}',
           'Stock receipt has been processed for Purchase Order #{{po_number}}.\nSupplier: {{supplier_name}}\nTotal items: {{total_items}}\n\nPlease review the receipt details.',
           '{"po_number":"string","supplier_name":"string","total_items":"number"}'
) t
CROSS JOIN users u
WHERE u.username = 'admin'
LIMIT 1;

-- Insert default email configuration
INSERT INTO email_config 
(driver, host, port, encryption, from_address, from_name) VALUES
('smtp', 'smtp.mailtrap.io', 2525, 'tls', 'no-reply@example.com', 'System Notification');

-- Create indexes for common queries
CREATE INDEX IF NOT EXISTS idx_notification_queue_status ON notification_queue(status, priority, scheduled_at);
CREATE INDEX IF NOT EXISTS idx_notification_history_type ON notification_history(type, sent_at);
CREATE INDEX IF NOT EXISTS idx_user_notifications ON users(email_notifications, whatsapp_notifications);
