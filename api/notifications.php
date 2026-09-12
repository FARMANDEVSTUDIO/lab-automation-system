<?php
declare(strict_types=1);
// Notifications API — mark read, fetch unread count (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$csrfToken || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$pdo = Database::getConnection();
$wsId = auth_workspace_id();
$action = $_POST['action'] ?? '';

if ($action === 'mark_read') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['error' => 'Missing id']);
        exit;
    }
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid AND workspace_id = :ws")
        ->execute(['id' => $id, 'uid' => auth_id(), 'ws' => $wsId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0 AND workspace_id = :ws")
        ->execute(['uid' => auth_id(), 'ws' => $wsId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
