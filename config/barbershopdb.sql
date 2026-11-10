-- Create database
CREATE DATABASE IF NOT EXISTS barbershop_system;
USE barbershop_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(255) NULL,
    position VARCHAR(50),
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    last_login DATETIME NULL,
    last_failed_login DATETIME NULL,
    login_attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE barbers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barber_number VARCHAR(50) UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    hired_date DATE,
    section VARCHAR(10),
    contact_number VARCHAR(20),
    wage_per_day DECIMAL(10, 2),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE barbers ADD CONSTRAINT unique_barber_number UNIQUE (barber_number);

CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_number VARCHAR(50) UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration INT DEFAULT 30,
    category VARCHAR(50),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Inventory table
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 5,
    unit_price DECIMAL(10,2),
    last_restocked DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions table
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(20) UNIQUE NOT NULL,
    barber_id INT,
    service_id INT,
    amount DECIMAL(10,2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(20) DEFAULT 'cash',
    status VARCHAR(20) DEFAULT 'completed',
    FOREIGN KEY (barber_id) REFERENCES barbers(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- System logs table
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Sales summary view (for reporting)
CREATE VIEW daily_sales AS
SELECT 
    DATE(transaction_date) as sale_date,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount,
    COUNT(DISTINCT barber_id) as barber_count
FROM transactions
WHERE status = 'completed'
GROUP BY DATE(transaction_date);

-- Insert sample data
INSERT INTO barbers (barber_number, first_name, last_name, hired_date, section, contact_number, wage_per_day) VALUES
('01', 'Mark', 'Nario', '2023-11-27', 'A', '09123466789', 500.00),
('02', 'Carl', 'Santos', '2023-11-27', 'B', '0988765422', 550.00),
('03', 'Denzel', 'Candano', '2023-11-27', 'C', '09192837458', 450.00);

-- Insert sample services
INSERT INTO services (service_number, name, description, price, duration, category) VALUES
('SRV001', 'Haircut', 'A neat and clean haircut for a quick everyday grooming.', 70.00, 30, 'Basic'),
('SRV002', 'Haircut Pro', 'A complete grooming service with haircut, hair wash, and wax styling.', 170.00, 45, 'Premium'),
('SRV003', 'Haircut Deluxe', 'Premium grooming session with haircut, hair wash, wax styling, and massage.', 270.00, 60, 'Deluxe');

INSERT INTO inventory (item_number, name, brand, quantity) VALUES
('01', '250g Talc Powder', 'Carrie', 10),
('02', 'Razor Blade', 'Dorco', 20),
('03', '20g Hair Wax', 'Bruno', 15),
('04', '15g Hair Spray', 'Bruno', 15),
('05', 'Combs', 'Brand X', 12),
('06', 'Scissors', 'Coolblades', 8),
('07', 'Trimmer', 'Coolblades', 2),
('08', 'Hair Clippers', 'Fagaci', 6);

INSERT INTO transactions (transaction_number, barber_id, service_id, amount, transaction_date) VALUES
('TRX001', 1, 1, 70.00, '2025-12-08 09:00:00'),
('TRX002', 2, 1, 70.00, '2025-12-08 10:30:00'),
('TRX003', 1, 1, 70.00, '2025-12-08 11:45:00'),
('TRX004', 2, 1, 70.00, '2025-12-08 13:15:00'),
('TRX005', 1, 2, 170.00, '2025-12-08 14:30:00');