<?php
declare(strict_types=1);
// Logout — session destroy and redirect (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<script>
(function(){
  var t = localStorage.getItem('lab_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
  window.location.replace(<?= json_encode(BASE_URL . '/auth/login.php') ?>);
})();
</script>
</head>
<body></body>
</html>
