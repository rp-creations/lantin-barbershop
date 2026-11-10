<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Test - LANTIN</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; margin: 10px 0; padding: 10px; background: #e8f5e9; border-left: 4px solid green; }
        .error { color: red; margin: 10px 0; padding: 10px; background: #ffebee; border-left: 4px solid red; }
        .warning { color: orange; margin: 10px 0; padding: 10px; background: #fff3e0; border-left: 4px solid orange; }
        .info { color: blue; margin: 10px 0; padding: 10px; background: #e3f2fd; border-left: 4px solid blue; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>Database Connection Test</h2>";

try {
    // Test basic connection
    echo "<div class='success'>✓ Database connection successful</div>";
    
    // Test tables
    $tables = ['users', 'barbers', 'services', 'inventory', 'transactions', 'system_logs'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<div class='success'>✓ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>✗ Table '$table' does NOT exist</div>";
            $missing_tables[] = $table;
        }
    }
    
    // Test sample data
    $barbers_count = $pdo->query("SELECT COUNT(*) as count FROM barbers")->fetch()['count'];
    echo "<div class='info'>Barbers in database: $barbers_count</div>";
    
    $services_count = $pdo->query("SELECT COUNT(*) as count FROM services")->fetch()['count'];
    echo "<div class='info'>Services in database: $services_count</div>";
    
    $inventory_count = $pdo->query("SELECT COUNT(*) as count FROM inventory")->fetch()['count'];
    echo "<div class='info'>Inventory items: $inventory_count</div>";
    
    $users_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    echo "<div class='info'>Users in system: $users_count</div>";
    
    // Check for database.sql file
    $sql_file_path = __DIR__ . '/database.sql';
    if (file_exists($sql_file_path)) {
        $sql_content = file_get_contents($sql_file_path);
        echo "<div class='success'>✓ database.sql file found</div>";
    } else {
        echo "<div class='warning'>⚠ database.sql file not found in: $sql_file_path</div>";
        // Provide the SQL content directly
        $sql_content = "-- Create database
CREATE DATABASE IF NOT EXISTS barbershop_system;
USE barbershop_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    position VARCHAR(50),
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Barbers table
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

-- Services table
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

-- Sample data for barbers
INSERT INTO barbers (barber_number, first_name, last_name, hired_date, section, contact_number, wage_per_day) VALUES
('01', 'Mark', 'Nario', '2023-11-27', 'A', '09123466789', 500.00),
('02', 'Carl', 'Santos', '2023-11-27', 'B', '0988765422', 550.00),
('03', 'Denzel', 'Candano', '2023-11-27', 'C', '09192837458', 450.00);

-- Sample data for services
INSERT INTO services (service_number, name, description, price, duration, category) VALUES
('SRV001', 'Haircut', 'A neat and clean haircut for a quick everyday grooming.', 70.00, 30, 'Basic'),
('SRV002', 'Haircut Pro', 'A complete grooming service with haircut, hair wash, and wax styling.', 170.00, 45, 'Premium'),
('SRV003', 'Haircut Deluxe', 'Premium grooming session with haircut, hair wash, wax styling, and massage.', 270.00, 60, 'Deluxe');

-- Sample data for inventory
INSERT INTO inventory (item_number, name, brand, quantity, reorder_level, unit_price) VALUES
('INV001', '250g Talc Powder', 'Carrie', 10, 5, 45.00),
('INV002', 'Razor Blade', 'Dorco', 20, 10, 15.00),
('INV003', '20g Hair Wax', 'Bruno', 15, 5, 120.00),
('INV004', '15g Hair Spray', 'Bruno', 15, 5, 110.00),
('INV005', 'Combs', 'Brand X', 12, 6, 25.00),
('INV006', 'Scissors', 'Coolblades', 8, 4, 180.00),
('INV007', 'Trimmer', 'Coolblades', 2, 2, 2500.00),
('INV008', 'Hair Clippers', 'Fagaci', 6, 3, 3200.00);

-- Sample data for transactions
INSERT INTO transactions (transaction_number, barber_id, service_id, amount, transaction_date) VALUES
('TRX001', 1, 1, 70.00, NOW()),
('TRX002', 2, 1, 70.00, NOW()),
('TRX003', 1, 1, 70.00, NOW()),
('TRX004', 2, 1, 70.00, NOW()),
('TRX005', 1, 2, 170.00, NOW());

-- Create default admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, position, username, password) VALUES
('Admin', 'User', 'admin@barbershop.com', 'Manager', 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');";

        echo "<div class='info'>Here's the SQL content for setting up the database:</div>";
    }
    
    if (!empty($missing_tables)) {
        echo "<h3>Setup Instructions</h3>
        <p>Some tables are missing. Follow these steps:</p>
        <ol>
            <li>Create a database named 'barbershop_system' in your MySQL</li>
            <li>Run the SQL commands below:</li>
        </ol>
        
        <h4>SQL Setup Commands:</h4>
        <pre>" . htmlspecialchars($sql_content) . "</pre>
        
        <p><strong>Note:</strong> The admin password is 'password' (without quotes)</p>
        
        <div style='margin-top: 20px;'>
            <a href='login.php' class='button'>Go to Login</a>
            <a href='javascript:location.reload()' class='button' style='background: #2196F3;'>Test Again</a>
        </div>";
    } else {
        echo "<div class='success' style='font-size: 1.2em; padding: 15px;'>
            <strong>✓ All systems go! Database is properly configured.</strong>
        </div>
        
        <div style='margin-top: 20px;'>
            <a href='login.php' class='button'>Go to Login Page</a>
            <a href='dashboard.php' class='button' style='background: #2196F3;'>Go to Dashboard</a>
        </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>
        <h3>Database Connection Error</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        
        <h4>Troubleshooting Steps:</h4>
        <ol>
            <li>Check if MySQL is running in XAMPP</li>
            <li>Verify database credentials in config/database.php</li>
            <li>Make sure database 'barbershop_system' exists</li>
            <li>Check if MySQL username is 'root' and password is empty (default XAMPP)</li>
        </ol>
        
        <p>Default XAMPP credentials:</p>
        <pre>
Host: localhost
Username: root
Password: (empty)
Database: barbershop_system
        </pre>
        
        <p><strong>Quick Fix:</strong> Open XAMPP Control Panel and start MySQL service.</p>
        
        <div style='margin-top: 20px;'>
            <a href='javascript:location.reload()' class='button'>Test Again</a>
        </div>
    </div>";
}

echo "</div></body></html>";
?>