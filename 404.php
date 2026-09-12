<?php
declare(strict_types=1);
// 404 Not Found error page with animated illustration (Arsalan)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

http_response_code(404);

$isAuthenticated = false;
if (session_status() === PHP_SESSION_ACTIVE || session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $isAuthenticated = isset($_SESSION['user']['id']);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title>Page Not Found · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
.error-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--paper);
  overflow: hidden;
}
.error-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 48px;
  height: 64px;
  border-bottom: 1px solid var(--line);
  flex-shrink: 0;
  backdrop-filter: blur(12px);
  background: rgba(15,11,12,0.8);
}
:root[data-theme="light"] .error-nav {
  background: rgba(250,243,236,0.85);
}
.error-nav-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1rem;
  color: var(--ink);
  text-decoration: none;
}
.error-nav-brand:hover { color: var(--ink); }

.error-content {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  position: relative;
}

/* Ambient background glow */
.error-content::before {
  content: '';
  position: absolute;
  top: 30%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(166,35,28,0.06) 0%, transparent 70%);
  pointer-events: none;
  animation: ambientPulse 6s ease-in-out infinite;
}
:root[data-theme="light"] .error-content::before {
  background: radial-gradient(circle, rgba(140,29,23,0.04) 0%, transparent 70%);
}
@keyframes ambientPulse {
  0%, 100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1); }
  50%      { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
}

.error-container {
  text-align: center;
  max-width: 520px;
  position: relative;
  z-index: 1;
  animation: errorContentIn 0.8s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes errorContentIn {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.error-visual {
  position: relative;
  margin-bottom: 40px;
  display: inline-block;
}

.error-code {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 8rem;
  line-height: 1;
  color: var(--ink);
  letter-spacing: -0.04em;
  opacity: 0.06;
  user-select: none;
  animation: errorCodeIn 1s cubic-bezier(0.16,1,0.3,1) both;
}
@keyframes errorCodeIn {
  from { opacity: 0; transform: scale(0.9); }
  to   { opacity: 0.06; transform: scale(1); }
}

.error-icon-wrap {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 96px;
  height: 96px;
  border-radius: var(--radius-xl);
  background: var(--accent-tint);
  border: 1px solid rgba(166,35,28,0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: errorIconIn 0.9s cubic-bezier(0.16,1,0.3,1) 0.15s both;
}
@keyframes errorIconIn {
  from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
  to   { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}
.error-icon-wrap svg { color: var(--accent-strong); }

/* Floating particles around the icon */
.error-particle {
  position: absolute;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--accent);
  opacity: 0.15;
}
.error-particle:nth-child(1) {
  top: 15%; left: 25%;
  animation: particleFloat 4s ease-in-out infinite;
}
.error-particle:nth-child(2) {
  top: 25%; right: 20%;
  animation: particleFloat 5s ease-in-out 1s infinite;
  width: 4px; height: 4px;
}
.error-particle:nth-child(3) {
  bottom: 30%; left: 18%;
  animation: particleFloat 4.5s ease-in-out 0.5s infinite;
  width: 5px; height: 5px;
}
.error-particle:nth-child(4) {
  bottom: 20%; right: 25%;
  animation: particleFloat 3.8s ease-in-out 1.5s infinite;
  width: 3px; height: 3px; opacity: 0.1;
}
@keyframes particleFloat {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-8px); }
}

.error-title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.5rem;
  color: var(--ink);
  margin-bottom: 12px;
  letter-spacing: -0.01em;
  animation: errorTextIn 0.7s cubic-bezier(0.16,1,0.3,1) 0.25s both;
}
@keyframes errorTextIn {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.error-desc {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--ink-soft);
  margin-bottom: 32px;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
  animation: errorTextIn 0.7s cubic-bezier(0.16,1,0.3,1) 0.35s both;
}
.error-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
  animation: errorTextIn 0.7s cubic-bezier(0.16,1,0.3,1) 0.45s both;
}
.error-meta {
  margin-top: 48px;
  padding-top: 24px;
  border-top: 1px solid var(--line);
  font-size: 0.75rem;
  color: var(--ink-faint);
  font-family: var(--font-mono);
  animation: errorTextIn 0.7s cubic-bezier(0.16,1,0.3,1) 0.55s both;
}

@media (prefers-reduced-motion: reduce) {
  .error-container,
  .error-code,
  .error-icon-wrap,
  .error-title,
  .error-desc,
  .error-actions,
  .error-meta { animation: none; opacity: 1; transform: none; }
  .error-icon-wrap { transform: translate(-50%, -50%); }
  .error-code { opacity: 0.06; }
  .error-content::before { animation: none; }
  .error-particle { animation: none; }
}

@media (max-width: 768px) {
  .error-nav { padding: 0 20px; }
  .error-code { font-size: 5rem; }
  .error-icon-wrap { width: 72px; height: 72px; }
  .error-title { font-size: 1.25rem; }
  .error-actions { flex-direction: column; align-items: center; }
  .error-actions .btn { width: 200px; }
}
</style>
</head>
<body class="app-public error-page">

<nav class="error-nav">
  <a href="<?= url('') ?>" class="error-nav-brand">
    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
      <rect width="28" height="28" rx="7" fill="var(--accent)"/>
      <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
    </svg>
    <?= e(APP_NAME) ?>
  </a>
  <a href="<?= url('') ?>" class="btn btn-secondary btn-sm">Back to Home</a>
</nav>

<div class="error-content">
  <div class="error-container">
    <div class="error-visual">
      <span class="error-particle"></span>
      <span class="error-particle"></span>
      <span class="error-particle"></span>
      <span class="error-particle"></span>
      <div class="error-code">404</div>
      <div class="error-icon-wrap">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 6v8L10 22.5a2.5 2.5 0 002.2 3.5h15.6a2.5 2.5 0 002.2-3.5L24 14V6"/>
          <line x1="12" y1="6" x2="28" y2="6"/>
          <circle cx="18" cy="20" r="1.5" fill="currentColor" stroke="none"/>
          <circle cx="24" cy="18" r="1" fill="currentColor" stroke="none"/>
          <line x1="14" y1="32" x2="26" y2="32" opacity="0.3"/>
          <line x1="16" y1="35" x2="24" y2="35" opacity="0.15"/>
        </svg>
      </div>
    </div>

    <h1 class="error-title">Test record not found.</h1>
    <p class="error-desc">The page you're looking for doesn't exist in this workspace, may have been moved, or the URL may be incorrect.</p>

    <div class="error-actions">
      <a href="<?= url('') ?>" class="btn btn-primary">Back to Home</a>
      <?php if ($isAuthenticated): ?>
      <a href="<?= url('dashboard/index.php') ?>" class="btn btn-secondary">Go to Dashboard</a>
      <?php else: ?>
      <a href="<?= url('auth/login.php') ?>" class="btn btn-secondary">Sign In</a>
      <?php endif; ?>
    </div>

    <div class="error-meta">
      <?= e($_SERVER['REQUEST_URI'] ?? '/') ?> · <?= date('Y-m-d H:i:s') ?> UTC
    </div>
  </div>
</div>

</body>
</html>
