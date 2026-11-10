<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Get all dashboard data
    $daily_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
    $monthly_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch();
    $total_barbers = $pdo->query("SELECT COUNT(*) as count FROM barbers WHERE is_active = 1")->fetch();
    $low_stock_items = $pdo->query("SELECT COUNT(*) as count FROM inventory WHERE quantity <= reorder_level")->fetch();
    
    $today_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_transactions,
            COUNT(DISTINCT barber_id) as barbers_active,
            COALESCE(SUM(amount), 0) as total_revenue
        FROM transactions 
        WHERE DATE(transaction_date) = CURDATE()
    ")->fetch();
    
    $recent_transactions = $pdo->query("
        SELECT t.*, b.first_name, b.last_name, s.name as service_name 
        FROM transactions t 
        JOIN barbers b ON t.barber_id = b.id 
        JOIN services s ON t.service_id = s.id 
        ORDER BY t.transaction_date DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $low_stock = $pdo->query("
        SELECT name, quantity, reorder_level 
        FROM inventory 
        WHERE quantity <= reorder_level 
        ORDER BY quantity ASC 
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $weekly_sales = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $day_name = date('D', strtotime("-$i days"));
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = ?");
        $stmt->execute([$date]);
        $day_sales = $stmt->fetch();
        
        $weekly_sales[] = [
            'day' => $day_name,
            'sales' => $day_sales['total']
        ];
    }
    
   // Get user info if logged in
    $user_position = '';
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT position FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $user_position = $user['position'] ?? '';
    }
    
    echo json_encode([
        'success' => true,
        'daily_sales' => $daily_sales['total'],
        'monthly_sales' => $monthly_sales['total'],
        'total_barbers' => $total_barbers['count'],
        'low_stock_items' => $low_stock_items['count'],
        'today_stats' => $today_stats,
        'recent_transactions' => $recent_transactions,
        'low_stock' => $low_stock,
        'weekly_sales' => $weekly_sales,
        'user_name' => $_SESSION['user_name'] ?? 'Guest',
        'user_position' => $user_position
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => "Error loading dashboard data: " . $e->getMessage()
    ]);
}