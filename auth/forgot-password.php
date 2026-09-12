<?php
declare(strict_types=1);
// Forgot password — email-based password reset request (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mail.php';

$error = '';
$success = '';
$email = '';
$resetLink = '';

if (is_post()) {
    require_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active') {
            $token = create_password_reset($pdo, (int) $user['id']);
            $mailResult = send_password_reset_email($user['email'], $user['name'], $token);
            if ($mailResult['success']) {
                $success = 'Password reset link sent! Check your inbox. If not found, please check your Spam/Junk folder.';
                $email = '';
            } else {
                $resetLink = rtrim(APP_URL, '/') . '/auth/reset-password.php?token=' . urlencode($token);
                $success = 'Email delivery is unavailable on this server. Use the button below to reset your password.';
                $email = '';
            }
        } else {
            $success = 'If an account with that email exists, we have sent a password reset link. Please check your inbox.';
            $email = '';
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
<title>Forgot Password &middot; <?= e(APP_NAME) ?></title>
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

  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

  @media (max-width: 480px) {
    .auth-card { padding: 32px 24px; }
    .auth-wrap { padding: 32px 16px; }
  }
</style>
</head>
<body class="app-public auth-page">

<div class="auth-wrap">
  <a href="<?= url('auth/login.php') ?>" class="auth-back">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="9,3 5,7 9,11"/></svg>
    Back to sign in
  </a>

  <div class="auth-card">
    <div class="auth-brand">
      <svg width="36" height="36" viewBox="0 0 28 28" fill="none">
        <rect width="28" height="28" rx="7" fill="var(--accent)"/>
        <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
      </svg>
      <span class="auth-brand-name"><?= e(APP_NAME) ?></span>
    </div>

    <h1 class="auth-title">Forgot password?</h1>
    <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>

    <?php if ($success): ?>
    <div class="flash-success-msg">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,8 7,11 12,5"/></svg>
      <?= e($success) ?>
    </div>
    <?php if ($resetLink): ?>
    <a href="<?= e($resetLink) ?>" class="auth-submit" style="text-decoration:none;text-align:center;margin-bottom:20px;">
      Reset My Password
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M6 2l6 6-6 6"/></svg>
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="login-error">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><line x1="5.5" y1="5.5" x2="10.5" y2="10.5"/><line x1="10.5" y1="5.5" x2="5.5" y2="10.5"/></svg>
      <?= e($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post" action="" novalidate id="forgotForm">
      <?= csrf_field() ?>

      <div class="auth-field">
        <input type="email" id="email" name="email" autofocus autocomplete="email"
               placeholder="Email" value="<?= e($email) ?>" required>
        <label for="email">Email address</label>
      </div>

      <button type="submit" class="auth-submit" id="forgotBtn">
        Send Reset Link
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 4l6 4 6-4"/><rect x="1" y="3" width="14" height="10" rx="2"/></svg>
      </button>
    </form>
    <?php endif; ?>

    <div class="auth-divider"></div>

    <div class="auth-footer-text">
      Remember your password? <a href="<?= url('auth/login.php') ?>">Sign in</a>
    </div>
  </div>
</div>

<?php if (!$success): ?>
<script>
document.getElementById('forgotForm').addEventListener('submit', function() {
  var btn = document.getElementById('forgotBtn');
  btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span> Sending…';
  btn.disabled = true;
});
</script>
<?php endif; ?>
</body>
</html>
