-- Investors table
CREATE TABLE investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    initial_capital DECIMAL(15,2) NOT NULL,
    current_capital DECIMAL(15,2) NOT NULL,
    ownership_percentage DECIMAL(5,2) NOT NULL,
    join_date DATE NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    bank_name VARCHAR(100) NULL,
    bank_account VARCHAR(50) NULL,
    bank_holder VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_join_date (join_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Capital Transactions table
CREATE TABLE capital_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    type ENUM('investment', 'withdrawal', 'profit_share', 'loss') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    transaction_date DATE NOT NULL,
    reference_number VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_investor (investor_id),
    INDEX idx_type (type),
    INDEX idx_date (transaction_date),
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profit Calculations table
CREATE TABLE profit_calculations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_revenue DECIMAL(15,2) NOT NULL,
    total_costs DECIMAL(15,2) NOT NULL,
    gross_profit DECIMAL(15,2) NOT NULL,
    net_profit DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'finalized', 'distributed') NOT NULL DEFAULT 'draft',
    calculated_by INT NOT NULL,
    finalized_at TIMESTAMP NULL,
    distributed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_period (period_start, period_end),
    INDEX idx_status (status),
    FOREIGN KEY (calculated_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profit Distribution table
CREATE TABLE profit_distributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calculation_id INT NOT NULL,
    investor_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    payment_date DATE NULL,
    payment_reference VARCHAR(50) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_calculation (calculation_id),
    INDEX idx_investor (investor_id),
    INDEX idx_status (status),
    FOREIGN KEY (calculation_id) REFERENCES profit_calculations(id) ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cost Categories table
CREATE TABLE cost_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    type ENUM('fixed', 'variable') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Costs table
CREATE TABLE costs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT NULL,
    cost_date DATE NOT NULL,
    recurring BOOLEAN NOT NULL DEFAULT FALSE,
    recurring_type ENUM('daily', 'weekly', 'monthly', 'yearly') NULL,
    recurring_end_date DATE NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_date (cost_date),
    INDEX idx_recurring (recurring),
    FOREIGN KEY (category_id) REFERENCES cost_categories(id) ON DELETE NO ACTION,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for common queries
CREATE INDEX idx_profit_calc_period ON profit_calculations(period_start, period_end, status);
CREATE INDEX idx_capital_trans_date ON capital_transactions(transaction_date, type);
CREATE INDEX idx_costs_category_date ON costs(category_id, cost_date);

-- Insert default cost categories
INSERT INTO cost_categories (name, type, description) VALUES
('Rent', 'fixed', 'Monthly rental costs'),
('Utilities', 'variable', 'Electricity, water, internet, etc.'),
('Salaries', 'fixed', 'Employee salaries and wages'),
('Marketing', 'variable', 'Marketing and advertising expenses'),
('Supplies', 'variable', 'Office and operational supplies'),
('Maintenance', 'variable', 'Equipment and facility maintenance'),
('Insurance', 'fixed', 'Business insurance costs'),
('Miscellaneous', 'variable', 'Other operational costs');

-- Add profit-related columns to sales table
ALTER TABLE sales
ADD COLUMN cost_of_goods DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER final_amount,
ADD COLUMN gross_profit DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER cost_of_goods,
ADD COLUMN net_profit DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER gross_profit;

-- Add profit calculation trigger for sales
CREATE TRIGGER calculate_sale_profit BEFORE INSERT ON sales
FOR EACH ROW
SET NEW.cost_of_goods = (
    SELECT COALESCE(SUM(si.quantity * p.purchase_price), 0)
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = NEW.id
),
NEW.gross_profit = NEW.final_amount - NEW.cost_of_goods,
NEW.net_profit = NEW.gross_profit - NEW.discount_amount;
