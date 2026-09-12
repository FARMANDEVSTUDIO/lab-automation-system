<?php
declare(strict_types=1);
// 403 Forbidden error page (Farman)
$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
(function(){
  var t = localStorage.getItem('lab_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
</script>
<title>403 — Access Denied</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '/lab-automation' ?>/assets/css/style.css">
<style>
.error-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--paper);
  padding: 2rem;
}
.error-card {
  text-align: center;
  max-width: 400px;
}
.error-code {
  font-size: 4rem;
  font-weight: 700;
  color: var(--fail);
  line-height: 1;
  margin-bottom: 8px;
}
.error-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 12px;
}
.error-desc {
  font-size: 0.875rem;
  color: var(--ink-soft);
  margin-bottom: 28px;
  line-height: 1.6;
}
.error-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}
</style>
</head>
<body class="error-page">
<div class="error-card">
  <div class="error-code">403</div>
  <h1 class="error-title">Access Denied</h1>
  <p class="error-desc">You don't have permission to access this page. If you believe this is an error, contact your administrator.</p>
  <div class="error-actions">
    <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    <a href="<?= defined('BASE_URL') ? BASE_URL : '/lab-automation' ?>/dashboard/index.php" class="btn btn-primary">Dashboard</a>
  </div>
</div>
</body>
</html>
