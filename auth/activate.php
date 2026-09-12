<?php
declare(strict_types=1);
// Account activation — invitation token validation and setup (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/mail.php';

if (auth_check()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$pdo = Database::getConnection();
$token = trim($_GET['token'] ?? '');
$invitation = null;
$tokenError = '';
$errors = [];
$step = 'set_password';

if ($token === '') {
    $tokenError = 'No invitation token provided.';
} else {
    $invitation = verify_invitation_token($pdo, $token);
    if (!$invitation) {
        $tokenError = 'This invitation link is invalid, has already been used, or has expired. Please contact your administrator for a new invitation.';
    }
}

$sessionKey = $invitation ? 'activate_email_verified_' . md5($token) : '';

$mailStatus = $_SESSION['activate_mail_status'] ?? '';

if (is_post() && $invitation) {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $action = !$errors ? ($_POST['action'] ?? '') : '';

    // Handle password creation
    if ($action === 'set_password') {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['password_confirm'] ?? '';

            $passErr = validate_password($password);
            if ($passErr) $errors[] = $passErr;

            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (!$errors) {
                $wsId = (int) $invitation['workspace_id'];

                $pdo->prepare(
                    'UPDATE users SET password_hash = :hash, status = :status, email_verified = 1,
                     invitation_status = :inv_status, password_changed_at = NOW(), updated_at = NOW()
                     WHERE id = :id AND workspace_id = :ws'
                )->execute([
                    'hash'       => secure_hash($password),
                    'status'     => 'active',
                    'inv_status' => 'activated',
                    'id'         => $invitation['user_id'],
                    'ws'         => $wsId,
                ]);

                consume_invitation_token($pdo, $token);

                audit_log($pdo, (int) $invitation['user_id'], 'user.activate', 'user', (int) $invitation['user_id'], null, [
                    'name'           => $invitation['name'],
                    'email'          => $invitation['email'],
                    'role'           => $invitation['role'],
                    'email_verified' => true,
                ], $wsId);

                create_notification(
                    $pdo,
                    (int) $invitation['user_id'],
                    'Welcome to ' . APP_NAME,
                    'Your account has been activated. You can now sign in and start working.',
                    'success',
                    null,
                    $wsId
                );

                // Clean up session keys
                unset($_SESSION[$sessionKey], $_SESSION['activate_otp_sent_' . md5($token)], $_SESSION['activate_mail_status']);

                flash_success('Account activated successfully. Please sign in with your new password.');
                header('Location: ' . BASE_URL . '/auth/login.php');
                exit;
            }
    }
}

$flashSuccess = flash('success');
$flashError = flash('error');
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
<title>Activate Account &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
  .auth-page {
    position: relative; background: var(--paper);
    display: flex; align-items: center; justify-content: center; min-height: 100vh;
  }
  .auth-page::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 340px; background: var(--charcoal); z-index: 0;
  }
  :root[data-theme="light"] .auth-page::before {
    background: linear-gradient(180deg, #E4D9CF 0%, var(--paper) 100%);
  }
  .auth-page::after {
    content: '';
    position: absolute; top: 200px; left: 50%;
    transform: translateX(-50%);
    width: 480px; height: 480px;
    background: radial-gradient(circle, rgba(166,35,28,0.04) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
  }
  :root[data-theme="light"] .auth-page::after {
    background: radial-gradient(circle, rgba(140,29,23,0.03) 0%, transparent 70%);
  }
  .auth-wrap {
    position: relative; z-index: 1; padding: 48px 24px;
    max-width: 440px; width: 100%;
  }
  .auth-back {
    display: inline-flex; align-items: center; gap: 6px;
    color: rgba(242,232,220,0.55); font-size: 0.8125rem;
    margin-bottom: 20px; text-decoration: none;
    transition: color var(--duration) var(--ease);
  }
  .auth-back:hover { color: rgba(242,232,220,0.92); }
  :root[data-theme="light"] .auth-back { color: var(--ink-faint); }
  :root[data-theme="light"] .auth-back:hover { color: var(--accent); }
  .auth-card {
    background: var(--paper-raised); border: 1px solid var(--line);
    border-radius: var(--radius-lg); padding: 36px 32px;
    box-shadow: var(--shadow-e2);
    animation: authCardIn 0.5s cubic-bezier(0.16,1,0.3,1) both;
  }
  @keyframes authCardIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @media (prefers-reduced-motion: reduce) { .auth-card { animation: none; } }

  .auth-brand { margin-bottom: 20px; }
  .auth-title {
    font-family: var(--font-display); font-weight: 700;
    font-size: 1.25rem; color: var(--ink); margin-bottom: 6px;
    letter-spacing: -0.01em;
  }
  .auth-subtitle {
    font-size: 0.8125rem; color: var(--ink-faint);
    margin-bottom: 24px; line-height: 1.6;
  }
  .auth-subtitle strong { color: var(--ink); }

  .invite-details {
    background: var(--paper); border: 1px solid var(--line);
    border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 24px;
  }
  .invite-detail-row {
    display: flex; justify-content: space-between; padding: 5px 0;
    font-size: 0.8125rem;
  }
  .invite-detail-label { color: var(--ink-faint); }
  .invite-detail-value { font-weight: 600; color: var(--ink); }
  .step-indicator {
    display: flex; gap: 8px; margin-bottom: 24px;
  }
  .step-dot {
    width: 32px; height: 4px; border-radius: var(--radius-full);
    background: var(--line-strong);
    transition: background var(--duration-slow) var(--ease);
  }
  .step-dot.active { background: var(--accent); }
  .step-dot.done { background: var(--success); }

  .otp-input-wrap {
    display: flex; gap: 10px; justify-content: center; margin-bottom: 24px;
  }
  .otp-input-wrap input {
    width: 48px; height: 56px; text-align: center;
    font-size: 1.375rem; font-weight: 600;
    font-family: var(--font-mono);
    border: 1px solid var(--line-strong);
    border-radius: var(--radius-md); background: var(--paper);
    color: var(--ink); outline: none;
    transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease);
  }
  .otp-input-wrap input:focus {
    border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-tint);
  }

  .auth-submit {
    width: 100%; padding: 12px 24px;
    background: var(--accent); color: var(--ink-on-accent);
    border: 1px solid var(--accent); border-radius: var(--radius-md);
    font-family: var(--font-body); font-weight: 600; font-size: 0.875rem;
    cursor: pointer; transition: all var(--duration) var(--ease);
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .auth-submit:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
  .auth-submit:disabled { opacity: 0.6; cursor: not-allowed; }

  .otp-actions {
    display: flex; justify-content: center; font-size: 0.8125rem;
    margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--line);
  }
  .otp-actions button {
    color: var(--accent); background: none; border: none; cursor: pointer;
    font-size: 0.8125rem; font-weight: 500; padding: 0;
    font-family: var(--font-body);
  }
  .otp-actions button:hover { text-decoration: underline; }
  .otp-actions button:disabled { color: var(--ink-faint); cursor: default; text-decoration: none; }
  .login-error {
    padding: 12px 16px; border-radius: var(--radius-md);
    background: var(--fail-tint); border: 1px solid rgba(194,91,78,0.2);
    color: var(--fail); font-size: 0.8125rem; margin-bottom: 20px;
    animation: errorSlideIn 0.3s var(--ease-out);
  }
  .login-error div { margin-bottom: 3px; }
  .login-error div:last-child { margin-bottom: 0; }
  @keyframes errorSlideIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .flash-msg {
    padding: 12px 16px; border-radius: var(--radius-md);
    font-size: 0.8125rem; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .flash-success { background: var(--success-tint); border: 1px solid rgba(111,163,131,0.2); color: var(--success); }
  .mail-status {
    padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.75rem;
    margin-bottom: 20px; line-height: 1.6;
  }

  /* Floating label fields for password step */
  .auth-field { position: relative; margin-bottom: 18px; }
  .auth-field input {
    display: block; width: 100%;
    padding: 14px 14px 8px;
    border: 1px solid var(--line); border-radius: var(--radius-md);
    background: var(--paper); color: var(--ink);
    font-family: var(--font-body); font-size: 0.875rem;
    line-height: 1.5; outline: none;
    transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease);
  }
  .auth-field input:hover { border-color: var(--line-strong); }
  .auth-field input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-tint); }
  .auth-field input::placeholder { color: transparent; }
  .auth-field label {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    font-size: 0.8125rem; color: var(--ink-placeholder);
    pointer-events: none; transition: all 0.2s var(--ease);
    padding: 0 2px;
  }
  .auth-field input:focus + label,
  .auth-field input:not(:placeholder-shown) + label {
    top: 6px; transform: translateY(0);
    font-size: 0.625rem; font-weight: 600;
    letter-spacing: 0.04em; text-transform: uppercase;
    color: var(--accent);
  }
  .auth-field input:not(:focus):not(:placeholder-shown) + label { color: var(--ink-faint); }
  .auth-field input:-webkit-autofill + label {
    top: 6px; transform: translateY(0);
    font-size: 0.625rem; font-weight: 600;
    letter-spacing: 0.04em; text-transform: uppercase;
    color: var(--ink-faint);
  }
  .auth-field input:-webkit-autofill:focus + label {
    color: var(--accent);
  }
  .auth-field .form-hint {
    margin-top: 4px; font-size: 0.6875rem; color: var(--ink-faint);
  }
  .password-field { position: relative; }
  .password-field input { padding-right: 44px; }
  .password-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--ink-faint); cursor: pointer;
    padding: 4px; border-radius: var(--radius-xs);
    display: flex; align-items: center; transition: color var(--duration) var(--ease);
  }
  .password-toggle:hover { color: var(--ink-soft); }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  @media (max-width: 480px) {
    .auth-card { padding: 28px 20px; }
    .otp-input-wrap input { width: 42px; height: 50px; font-size: 1.125rem; }
    .otp-input-wrap { gap: 6px; }
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
      <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
        <rect width="28" height="28" rx="7" fill="var(--accent)"/>
        <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
      </svg>
    </div>

    <?php if ($tokenError): ?>
    <h1 class="auth-title">Invalid Invitation</h1>
    <p class="auth-subtitle"><?= e($tokenError) ?></p>
    <a href="<?= url('auth/login.php') ?>" class="btn btn-secondary w-100">Go to Sign In</a>

    <?php elseif ($step === 'set_password'): ?>

    <div class="step-indicator">
      <span class="step-dot done"></span>
      <span class="step-dot active"></span>
    </div>

    <h1 class="auth-title">Create your password</h1>
    <p class="auth-subtitle">Email verified. Set a password to activate your account.</p>

    <div class="invite-details">
      <div class="invite-detail-row">
        <span class="invite-detail-label">Name</span>
        <span class="invite-detail-value"><?= e($invitation['name']) ?></span>
      </div>
      <div class="invite-detail-row">
        <span class="invite-detail-label">Email</span>
        <span class="invite-detail-value"><?= e($invitation['email']) ?></span>
      </div>
      <div class="invite-detail-row">
        <span class="invite-detail-label">Role</span>
        <span class="invite-detail-value"><?= e(role_label($invitation['role'])) ?></span>
      </div>
    </div>

    <?php if ($errors): ?>
    <div class="login-error">
      <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="?token=<?= e(urlencode($token)) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="set_password">

      <div class="auth-field password-field">
        <input type="password" id="password" name="password"
               placeholder="Password" required autocomplete="new-password">
        <label for="password">Password</label>
        <button type="button" class="password-toggle" onclick="togglePw('password', this)" aria-label="Show password">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
        </button>
      </div>

      <div class="auth-field password-field" style="margin-bottom:24px;">
        <input type="password" id="password_confirm" name="password_confirm"
               placeholder="Confirm" required autocomplete="new-password">
        <label for="password_confirm">Confirm Password</label>
        <button type="button" class="password-toggle" onclick="togglePw('password_confirm', this)" aria-label="Show password">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
        </button>
      </div>

      <button type="submit" class="auth-submit" id="activateBtn">
        Activate Account
      </button>
    </form>

    <?php endif; ?>
  </div>
</div>

<script>
function togglePw(id, btn) {
  var input = document.getElementById(id);
  var isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.innerHTML = isHidden
    ? '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="2" y1="2" x2="14" y2="14"/><path d="M6.5 6.5a2 2 0 002.8 2.8"/><path d="M1 8s2.5-5 7-5c1 0 1.8.2 2.5.5"/><path d="M15 8s-2.5 5-7 5c-1.2 0-2.2-.3-3-.7"/></svg>'
    : '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>';
}

// Submit spinner
(function() {
  var actBtn = document.getElementById('activateBtn');
  if (actBtn) {
    actBtn.closest('form').addEventListener('submit', function() {
      actBtn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;vertical-align:middle;"></span> Activating…';
      actBtn.disabled = true;
    });
  }
})();
</script>
</body>
</html>
