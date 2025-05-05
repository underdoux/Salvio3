-- Suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    company_name VARCHAR(100) NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    contact_person VARCHAR(100) NULL,
    tax_number VARCHAR(50) NULL,
    bank_name VARCHAR(100) NULL,
    bank_account VARCHAR(50) NULL,
    bank_holder VARCHAR(100) NULL,
    credit_limit DECIMAL(15,2) NULL,
    payment_terms INT NULL COMMENT 'Payment terms in days',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_company (company_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Orders table
CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    order_date DATE NOT NULL,
    expected_date DATE NULL,
    delivery_date DATE NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    shipping_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
    order_status ENUM('draft', 'ordered', 'received', 'cancelled') NOT NULL DEFAULT 'draft',
    payment_due_date DATE NULL,
    notes TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier (supplier_id),
    INDEX idx_status (order_status, payment_status),
    INDEX idx_date (order_date),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE NO ACTION,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Order Items table
CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    received_quantity INT NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po (purchase_order_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Payments table
CREATE TABLE supplier_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank', 'credit') NOT NULL,
    reference_number VARCHAR(50) NULL,
    bank_account_id INT NULL,
    notes TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po (purchase_order_id),
    INDEX idx_date (payment_date),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Receipts table
CREATE TABLE stock_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT NOT NULL,
    receipt_date DATE NOT NULL,
    notes TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_po (purchase_order_id),
    INDEX idx_date (receipt_date),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Receipt Items table
CREATE TABLE stock_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_receipt_id INT NOT NULL,
    purchase_order_item_id INT NOT NULL,
    quantity INT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receipt (stock_receipt_id),
    INDEX idx_po_item (purchase_order_item_id),
    FOREIGN KEY (stock_receipt_id) REFERENCES stock_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add supplier-related columns to products table
ALTER TABLE products
ADD COLUMN default_supplier_id INT NULL AFTER supplier_id,
ADD COLUMN supplier_sku VARCHAR(50) NULL AFTER default_supplier_id,
ADD COLUMN last_purchase_price DECIMAL(15,2) NULL AFTER supplier_sku,
ADD COLUMN reorder_point INT NOT NULL DEFAULT 0 AFTER min_stock,
ADD COLUMN reorder_quantity INT NOT NULL DEFAULT 0 AFTER reorder_point,
ADD FOREIGN KEY (default_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- Create trigger to update product purchase price
CREATE TRIGGER update_product_purchase_price 
AFTER INSERT ON purchase_order_items
FOR EACH ROW
UPDATE products 
SET last_purchase_price = NEW.unit_price,
    updated_at = CURRENT_TIMESTAMP
WHERE id = NEW.product_id 
AND (SELECT order_status FROM purchase_orders WHERE id = NEW.purchase_order_id) = 'received';

-- Create trigger to update purchase order totals
CREATE TRIGGER calculate_po_totals 
AFTER INSERT ON purchase_order_items
FOR EACH ROW
UPDATE purchase_orders 
SET subtotal = (
        SELECT SUM(total_amount)
        FROM purchase_order_items
        WHERE purchase_order_id = NEW.purchase_order_id
    ),
    total_amount = (
        SELECT SUM(total_amount)
        FROM purchase_order_items
        WHERE purchase_order_id = NEW.purchase_order_id
    ) + shipping_cost + tax_amount - discount_amount,
    updated_at = CURRENT_TIMESTAMP
WHERE id = NEW.purchase_order_id;

-- Create trigger to update stock and create movement record
CREATE TRIGGER update_stock_on_receipt 
AFTER INSERT ON stock_receipt_items
FOR EACH ROW
UPDATE products p
INNER JOIN purchase_order_items poi ON poi.product_id = p.id
SET p.stock = p.stock + NEW.quantity,
    p.updated_at = CURRENT_TIMESTAMP,
    poi.received_quantity = poi.received_quantity + NEW.quantity
WHERE poi.id = NEW.purchase_order_item_id;

-- Create trigger to record stock movement
CREATE TRIGGER create_stock_movement_on_receipt
AFTER INSERT ON stock_receipt_items
FOR EACH ROW
INSERT INTO stock_movements (
    product_id,
    reference_id,
    reference_type,
    quantity,
    movement_type,
    notes,
    created_at
)
SELECT 
    poi.product_id,
    NEW.stock_receipt_id,
    'receipt',
    NEW.quantity,
    'in',
    'Stock receipt',
    CURRENT_TIMESTAMP
FROM purchase_order_items poi
WHERE poi.id = NEW.purchase_order_item_id;
