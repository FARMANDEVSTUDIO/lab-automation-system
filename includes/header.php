<?php
declare(strict_types=1);
// Main layout header — HTML skeleton, sidebar, topbar inclusion (Arsalan)

if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$pageTitle   = $pageTitle ?? APP_NAME;
$breadcrumbs = $breadcrumbs ?? [];
$requireAuth = $requireAuth ?? true;

if ($requireAuth) {
    require_auth();
    $currentUser = auth_user();
    $pdo = Database::getConnection();
    $notifCount = unread_notification_count($pdo, (int) $currentUser['id']);

    $notifStmt = $pdo->prepare('SELECT id, title, message, type, link, is_read, created_at FROM notifications WHERE workspace_id = :ws AND user_id = :uid ORDER BY created_at DESC LIMIT 10');
    $notifStmt->execute(['ws' => auth_workspace_id(), 'uid' => $currentUser['id']]);
    $notifications = $notifStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script>
(function(){
  var t = localStorage.getItem('lab_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="<?= $requireAuth ? 'app-authenticated' : 'app-public' ?>">

<?php if ($requireAuth): ?>
<div class="app-shell">
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class="app-main">
    <?php require_once __DIR__ . '/topbar.php'; ?>
    <main class="app-content" id="appContent">
<?php endif; ?>
