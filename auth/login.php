<?php
declare(strict_types=1);
// Login page — floating labels, rate limiting, remember me (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$email = $_COOKIE['remember_email'] ?? '';

if (is_post()) {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $result = login_user($email, $password);
        if ($result['success']) {
            if ($remember) {
                setcookie('remember_email', $email, time() + 86400 * 30, '/', '', false, true);
            } else {
                setcookie('remember_email', '', time() - 3600, '/', '', false, true);
            }
            $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/dashboard/index.php';
            unset($_SESSION['redirect_after_login']);
            if (!str_starts_with($redirect, BASE_URL)) {
                $redirect = BASE_URL . '/dashboard/index.php';
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['error'];
        }
    }
}
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
<title>Sign In &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
  .auth-page {
    position: relative;
    background: var(--paper);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
  }
  .auth-page::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 340px;
    background: var(--charcoal);
    z-index: 0;
  }
  :root[data-theme="light"] .auth-page::before {
    background: linear-gradient(180deg, #E4D9CF 0%, var(--paper) 100%);
  }
  .auth-page::after {
    content: '';
    position: absolute;
    top: 200px; left: 50%;
    transform: translateX(-50%);
    width: 480px; height: 480px;
    background: radial-gradient(circle, rgba(166,35,28,0.04) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
  }
  :root[data-theme="light"] .auth-page::after {
    background: radial-gradient(circle, rgba(140,29,23,0.03) 0%, transparent 70%);
  }
  .auth-wrap {
    position: relative;
    z-index: 1;
    padding: 48px 24px 48px;
    max-width: 420px;
    width: 100%;
  }
  .auth-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(242,232,220,0.55);
    font-size: 0.8125rem;
    margin-bottom: 28px;
    text-decoration: none;
    transition: color var(--duration) var(--ease);
  }
  .auth-back:hover { color: rgba(242,232,220,0.92); }
  :root[data-theme="light"] .auth-back { color: var(--ink-faint); }
  :root[data-theme="light"] .auth-back:hover { color: var(--accent); }

  .auth-card {
    background: var(--paper-raised);
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-e2);
    padding: 40px 36px;
    animation: authCardIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
  }
  @keyframes authCardIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @media (prefers-reduced-motion: reduce) {
    .auth-card { animation: none; }
  }

  .auth-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
  }
  .auth-brand-name {
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 1rem;
    color: var(--ink);
  }
  .auth-title {
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 1.375rem;
    color: var(--ink);
    margin-bottom: 6px;
    letter-spacing: -0.01em;
  }
  .auth-subtitle {
    font-size: 0.8125rem;
    color: var(--ink-faint);
    margin-bottom: 28px;
    line-height: 1.5;
  }

  /* Floating label form fields */
  .auth-field {
    position: relative;
    margin-bottom: 20px;
  }
  .auth-field input {
    display: block;
    width: 100%;
    padding: 14px 14px 8px;
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    background: var(--paper);
    color: var(--ink);
    font-family: var(--font-body);
    font-size: 0.875rem;
    line-height: 1.5;
    outline: none;
    transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease);
  }
  .auth-field input:hover {
    border-color: var(--line-strong);
  }
  .auth-field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-tint);
  }
  .auth-field input::placeholder { color: transparent; }
  .auth-field label {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8125rem;
    color: var(--ink-placeholder);
    pointer-events: none;
    transition: all 0.2s var(--ease);
    background: transparent;
    padding: 0 2px;
  }
  .auth-field input:focus + label,
  .auth-field input:not(:placeholder-shown) + label {
    top: 6px;
    transform: translateY(0);
    font-size: 0.625rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--accent);
  }
  .auth-field input:not(:focus):not(:placeholder-shown) + label {
    color: var(--ink-faint);
  }
  .auth-field input:-webkit-autofill + label {
    top: 6px; transform: translateY(0);
    font-size: 0.625rem; font-weight: 600;
    letter-spacing: 0.04em; text-transform: uppercase;
    color: var(--ink-faint);
  }
  .auth-field input:-webkit-autofill:focus + label {
    color: var(--accent);
  }

  .password-field { position: relative; }
  .password-field input { padding-right: 44px; }
  .password-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--ink-faint); cursor: pointer;
    padding: 4px; border-radius: var(--radius-xs); transition: color var(--duration) var(--ease);
    display: flex; align-items: center; justify-content: center;
  }
  .password-toggle:hover { color: var(--ink-soft); }

  .auth-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
  }

  .auth-check {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.8125rem;
    color: var(--ink-soft);
  }
  .auth-check input {
    width: 16px; height: 16px;
    accent-color: var(--accent);
    cursor: pointer;
  }

  .auth-submit {
    width: 100%;
    padding: 12px 24px;
    background: var(--accent);
    color: var(--ink-on-accent);
    border: 1px solid var(--accent);
    border-radius: var(--radius-md);
    font-family: var(--font-body);
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all var(--duration) var(--ease);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }
  .auth-submit:hover { background: var(--accent-hover); border-color: var(--accent-hover); box-shadow: 0 2px 8px rgba(166,35,28,0.3); }
  .auth-submit:active { transform: scale(0.98); }
  .auth-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

  .login-error {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    background: var(--fail-tint);
    border: 1px solid rgba(194,91,78,0.2);
    color: var(--fail);
    font-size: 0.8125rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: errorSlideIn 0.3s var(--ease-out);
  }
  @keyframes errorSlideIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .flash-success-msg {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    background: var(--success-tint);
    border: 1px solid rgba(111,163,131,0.2);
    color: var(--success);
    font-size: 0.8125rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .auth-divider {
    height: 1px;
    background: var(--line);
    margin: 24px 0;
  }

  .auth-footer-text {
    text-align: center;
    font-size: 0.8125rem;
    color: var(--ink-faint);
    line-height: 1.7;
  }
  .auth-footer-text a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
  }
  .auth-footer-text a:hover { text-decoration: underline; }
  .auth-footer-text small {
    display: block;
    font-size: 0.75rem;
    margin-top: 2px;
  }

  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

  @media (max-width: 480px) {
    .auth-card { padding: 32px 24px; }
    .auth-wrap { padding: 32px 16px; }
  }
</style>
</head>
<body class="app-public auth-page">

<div class="auth-wrap">
  <a href="<?= url('index.php') ?>" class="auth-back">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="9,3 5,7 9,11"/></svg>
    Back to home
  </a>

  <div class="auth-card">
    <div class="auth-brand">
      <svg width="36" height="36" viewBox="0 0 28 28" fill="none">
        <rect width="28" height="28" rx="7" fill="var(--accent)"/>
        <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
      </svg>
      <span class="auth-brand-name"><?= e(APP_NAME) ?></span>
    </div>

    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to your workspace to continue.</p>

    <?php $flashSuccess = flash('success'); ?>
    <?php if ($flashSuccess): ?>
    <div class="flash-success-msg">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,8 7,11 12,5"/></svg>
      <?= e($flashSuccess) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="login-error">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><line x1="5.5" y1="5.5" x2="10.5" y2="10.5"/><line x1="10.5" y1="5.5" x2="5.5" y2="10.5"/></svg>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="" novalidate id="loginForm">
      <?= csrf_field() ?>

      <div class="auth-field">
        <input type="email" id="email" name="email" autofocus autocomplete="username"
               placeholder="Email" value="<?= e($email) ?>" required>
        <label for="email">Email address</label>
      </div>

      <div class="auth-field password-field">
        <input type="password" id="password" name="password"
               placeholder="Password" autocomplete="current-password" required>
        <label for="password">Password</label>
        <button type="button" class="password-toggle" id="pwToggle" aria-label="Show password">
          <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1.5 9s3-5.5 7.5-5.5S16.5 9 16.5 9s-3 5.5-7.5 5.5S1.5 9 1.5 9z"/><circle cx="9" cy="9" r="2.5"/></svg>
        </button>
      </div>

      <div class="auth-options">
        <label class="auth-check">
          <input type="checkbox" name="remember" value="1" <?= !empty($_COOKIE['remember_email']) ? 'checked' : '' ?>>
          Keep me signed in
        </label>
        <a href="<?= url('auth/forgot-password.php') ?>" style="font-size:0.8125rem;color:var(--ink-faint);text-decoration:none;transition:color var(--duration) var(--ease);" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--ink-faint)'">Forgot password?</a>
      </div>

      <button type="submit" class="auth-submit" id="loginBtn">
        Sign In
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="8" x2="13" y2="8"/><polyline points="9,4 13,8 9,12"/></svg>
      </button>
    </form>

    <div class="auth-divider"></div>

    <div class="auth-footer-text">
      Need your own workspace? <a href="<?= url('auth/signup.php') ?>">Create an Administrator account</a>
      <small>Other roles are created by your workspace administrator.</small>
    </div>
  </div>
</div>

<script>
document.getElementById('pwToggle').addEventListener('click', function() {
  var input = document.getElementById('password');
  var isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  this.innerHTML = isHidden
    ? '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="2" y1="2" x2="16" y2="16"/><path d="M7.2 7.2a2.5 2.5 0 003.5 3.5"/><path d="M1.5 9s3-5.5 7.5-5.5c1.2 0 2.2.3 3 .6"/><path d="M16.5 9s-3 5.5-7.5 5.5c-1.4 0-2.6-.4-3.5-.8"/></svg>'
    : '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1.5 9s3-5.5 7.5-5.5S16.5 9 16.5 9s-3 5.5-7.5 5.5S1.5 9 1.5 9z"/><circle cx="9" cy="9" r="2.5"/></svg>';
});
document.getElementById('loginForm').addEventListener('submit', function() {
  var btn = document.getElementById('loginBtn');
  btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span> Signing in…';
  btn.disabled = true;
});
</script>
</body>
</html>
