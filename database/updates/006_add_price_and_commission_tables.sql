-- Price History table
CREATE TABLE price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    old_price DECIMAL(15,2) NOT NULL,
    new_price DECIMAL(15,2) NOT NULL,
    change_type ENUM('purchase', 'markup', 'discount', 'adjustment') NOT NULL,
    reason TEXT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commission Rates table
CREATE TABLE commission_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    category_id INT NULL,
    product_id INT NULL,
    rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_category (category_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commission Calculations table
CREATE TABLE commission_calculations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    user_id INT NOT NULL,
    commission_rate_id INT NOT NULL,
    sale_amount DECIMAL(15,2) NOT NULL,
    commission_amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'approved', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
    payment_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sale (sale_id),
    INDEX idx_user (user_id),
    INDEX idx_rate (commission_rate_id),
    INDEX idx_status (status),
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE NO ACTION,
    FOREIGN KEY (commission_rate_id) REFERENCES commission_rates(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discount Rules table
CREATE TABLE discount_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(15,2) NOT NULL,
    min_purchase DECIMAL(15,2) NULL COMMENT 'Minimum purchase amount',
    max_discount DECIMAL(15,2) NULL COMMENT 'Maximum discount amount',
    start_date DATE NULL,
    end_date DATE NULL,
    allowed_roles JSON NULL COMMENT 'Array of roles allowed to apply discount',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_date (status, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add discount_rule_id to sale_items table
ALTER TABLE sale_items
ADD COLUMN discount_rule_id INT NULL AFTER discount_amount,
ADD FOREIGN KEY (discount_rule_id) REFERENCES discount_rules(id) ON DELETE SET NULL;

-- Add commission-related columns to users table
ALTER TABLE users
ADD COLUMN commission_eligible BOOLEAN NOT NULL DEFAULT TRUE AFTER role,
ADD COLUMN default_commission_rate DECIMAL(5,2) NULL AFTER commission_eligible;

-- Add price-related columns to products table
ALTER TABLE products
ADD COLUMN min_price DECIMAL(15,2) NULL COMMENT 'Minimum allowed selling price' AFTER selling_price,
ADD COLUMN max_discount_rate DECIMAL(5,2) NULL COMMENT 'Maximum allowed discount percentage' AFTER min_price;

-- Add indexes for common queries
CREATE INDEX idx_price_history_date ON price_history(created_at);
CREATE INDEX idx_commission_calc_date ON commission_calculations(created_at);
CREATE INDEX idx_commission_payment ON commission_calculations(payment_date);
CREATE INDEX idx_discount_rule_status ON discount_rules(status, start_date, end_date);

-- Insert default global commission rate
INSERT INTO commission_rates (user_id, rate)
VALUES (NULL, 5.00);

-- Insert default discount rules
INSERT INTO discount_rules 
(name, type, value, min_purchase, max_discount, allowed_roles, status)
VALUES 
('Standard Discount', 'percentage', 5.00, 1000000.00, 500000.00, '["admin", "sales"]', 'active'),
('Bulk Purchase', 'percentage', 10.00, 5000000.00, 1000000.00, '["admin"]', 'active');
