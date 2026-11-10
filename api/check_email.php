<?php
require_once '../config/database.php';
require_once '../config/security.php';

header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = sanitize_input($_GET['email']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_hash = hash_identifier($email);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email_hash]);
        $exists = $stmt->fetchColumn() > 0;
    } else {
        $exists = false;
    }

    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['exists' => false]);
}
?>