<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    // Get sidebar stats
    $daily_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
    $monthly_sales = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())")->fetch();
    $today_count = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetch();
    
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
        'today_count' => $today_count['total'],
        'user_name' => $_SESSION['user_name'] ?? 'Guest',
        'user_position' => $user_position
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => "Error loading sidebar stats: " . $e->getMessage()
    ]);
}