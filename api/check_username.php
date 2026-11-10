<?php
require_once '../config/database.php';
require_once '../config/security.php';

header('Content-Type: application/json');

if (isset($_GET['username'])) {
    $username = sanitize_input($_GET['username']);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $exists = $stmt->fetchColumn() > 0;
    
    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['exists' => false]);
}
?>