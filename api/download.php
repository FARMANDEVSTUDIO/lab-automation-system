<?php
declare(strict_types=1);
// File download API — secure attachment serving (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!auth_check()) {
    http_response_code(401);
    die('Unauthorized');
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); die('Missing id'); }

$pdo = Database::getConnection();
$stmt = $pdo->prepare(
    "SELECT a.* FROM attachments a
     JOIN testing_records tr ON a.attachable_id = tr.id AND a.attachable_type = 'testing_record'
     WHERE a.id = :id AND tr.workspace_id = :ws"
);
$stmt->execute(['id' => $id, 'ws' => auth_workspace_id()]);
$att = $stmt->fetch();

if (!$att) { http_response_code(404); die('Not found'); }

$filePath = __DIR__ . '/../' . $att['file_path'];
if (!file_exists($filePath)) { http_response_code(404); die('File missing'); }

header('Content-Type: ' . ($att['file_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($att['file_name']) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
