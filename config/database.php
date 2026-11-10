<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'barbershop_system';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Log error but don't show details to user
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact administrator.");
}

// Function to add system logs
function addLog($action, $description = '') {
    global $pdo;
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip_address]);
    } catch(PDOException $e) {
        // Don't die on log error, just continue
        error_log("Log error: " . $e->getMessage());
    }
}

// Function to validate barber data
function validateBarberData($data) {
    $errors = [];
    
    if (empty(trim($data['first_name']))) {
        $errors[] = 'First name is required';
    }
    
    if (empty(trim($data['last_name']))) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($data['hired_date'])) {
        $errors[] = 'Hired date is required';
    }
    
    if (empty($data['wage_per_day']) || $data['wage_per_day'] < 0) {
        $errors[] = 'Valid daily wage is required';
    }
    
    return $errors;
}

// Function to validate inventory data
function validateInventoryData($data) {
    $errors = [];
    
    if (empty(trim($data['item_number']))) {
        $errors[] = 'Item number is required';
    }
    
    if (empty(trim($data['name']))) {
        $errors[] = 'Item name is required';
    }
    
    if (empty($data['quantity']) || $data['quantity'] < 0) {
        $errors[] = 'Valid quantity is required';
    }
    
    if (empty($data['reorder_level']) || $data['reorder_level'] < 0) {
        $errors[] = 'Valid reorder level is required';
    }
    
    return $errors;
}
?>