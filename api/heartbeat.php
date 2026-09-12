<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['active' => false, 'reason' => 'not_authenticated']);
    exit;
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => auth_id()]);
$status = $stmt->fetchColumn();

if ($status !== 'active') {
    session_destroy();
    echo json_encode(['active' => false, 'reason' => 'account_inactive']);
    exit;
}

echo json_encode(['active' => true]);
