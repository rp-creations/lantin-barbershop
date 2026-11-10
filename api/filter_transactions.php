<?php
// api/filter_transactions.php
require_once '../config/database.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
$barber_id = $_GET['barber_id'] ?? '';

try {
    // Build the query
    $query = "
        SELECT t.*, b.first_name, b.last_name, s.name as service_name 
        FROM transactions t 
        JOIN barbers b ON t.barber_id = b.id 
        JOIN services s ON t.service_id = s.id 
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($date) {
        $query .= " AND DATE(t.transaction_date) = ?";
        $params[] = $date;
    }
    
    if ($barber_id) {
        $query .= " AND t.barber_id = ?";
        $params[] = $barber_id;
    }
    
    $query .= " ORDER BY t.transaction_date DESC LIMIT 20";
    
    // Get filtered transactions
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get total count for current filter
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM transactions t 
        WHERE 1=1
    ";
    
    $countParams = [];
    
    if ($date) {
        $countQuery .= " AND DATE(t.transaction_date) = ?";
        $countParams[] = $date;
    }
    
    if ($barber_id) {
        $countQuery .= " AND t.barber_id = ?";
        $countParams[] = $barber_id;
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total_count = $countStmt->fetch()['total'];
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'total_count' => $total_count,
        'showing_count' => count($transactions)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading transactions: ' . $e->getMessage()
    ]);
}
?>