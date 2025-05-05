-- Commission Rates table
CREATE TABLE commission_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    category_id INT NULL,
    product_id INT NULL,
    rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_category (category_id),
    INDEX idx_product (product_id),
    CONSTRAINT fk_commission_rate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_rate_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_commission_rate_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Price History table
CREATE TABLE price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    old_price DECIMAL(15,2) NOT NULL,
    new_price DECIMAL(15,2) NOT NULL,
    change_type ENUM('regular', 'promotion', 'bulk') NOT NULL DEFAULT 'regular',
    reason TEXT NOT NULL,
    user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    CONSTRAINT fk_price_history_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_price_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
