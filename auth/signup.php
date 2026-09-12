<?php
declare(strict_types=1);
// Signup — workspace creation, OTP email verification (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/mail.php';

$pdo = Database::getConnection();
$errors = [];
$name = '';
$email = '';
$workspaceName = '';

if (is_post()) {
    require_csrf();

    $name          = trim(preg_replace('/\s+/', ' ', $_POST['full_name'] ?? ''));
    $email         = strtolower(trim($_POST['email'] ?? ''));
    $workspaceName = trim($_POST['workspace_name'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirm       = $_POST['password_confirm'] ?? '';
    $terms         = $_POST['terms'] ?? '';

    $nameErr = validate_human_name($name);
    if ($nameErr) $errors[] = $nameErr;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if ($workspaceName === '') {
        $errors[] = 'Workspace name is required.';
    } elseif (strlen($workspaceName) < 2 || strlen($workspaceName) > 150) {
        $errors[] = 'Workspace name must be 2-150 characters.';
    } elseif (!preg_match('/^[A-Za-z\s.\-&]+$/', $workspaceName)) {
        $errors[] = 'Workspace name may only contain letters, spaces, dots, and hyphens. No numbers or symbols.';
    }

    $passErr = validate_password($password);
    if ($passErr) $errors[] = $passErr;

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$terms) {
        $errors[] = 'You must accept the Terms of Service.';
    }

    if (!$errors) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
        $dup->execute(['e' => $email]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        cleanup_expired_pending_signups($pdo);
        $pdo->prepare('DELETE FROM pending_signups WHERE email = :e')->execute(['e' => $email]);

        $sessionToken = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            'INSERT INTO pending_signups (session_token, workspace_name, full_name, email, password_hash, expires_at)
             VALUES (:token, :ws, :name, :email, :hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        );
        $stmt->execute([
            'token' => $sessionToken,
            'ws'    => $workspaceName,
            'name'  => $name,
            'email' => $email,
            'hash'  => secure_hash($password),
        ]);

        $otp = generate_otp();
        store_otp($pdo, $email, $otp, 'admin_signup', $sessionToken);
        $mailResult = send_otp_email($email, $name, $otp, 'admin_signup');

        $_SESSION['pending_signup_token'] = $sessionToken;
        $_SESSION['pending_signup_email'] = $email;

        if ($mailResult['success']) {
            $_SESSION['signup_mail_status'] = 'sent';
            unset($_SESSION['signup_otp_fallback']);
        } else {
            $_SESSION['signup_mail_status'] = 'failed';
            $_SESSION['signup_otp_fallback'] = $otp;
        }

        header('Location: ' . BASE_URL . '/auth/verify-email.php');
        exit;
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
<title>Create Account &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
  .auth-page {
    position: relative;
    background: var(--paper);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    min-height: 100vh;
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
    max-width: 480px; width: 100%;
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

  .auth-brand {
    display: flex; align-items: center; gap: 10px; margin-bottom: 24px;
  }
  .auth-brand-name {
    font-family: var(--font-display); font-weight: 700;
    font-size: 1rem; color: var(--ink);
  }
  .auth-title {
    font-family: var(--font-display); font-weight: 700;
    font-size: 1.25rem; color: var(--ink); margin-bottom: 4px;
    letter-spacing: -0.01em;
  }
  .auth-subtitle {
    font-size: 0.8125rem; color: var(--ink-faint);
    margin-bottom: 24px; line-height: 1.5;
  }

  .admin-notice {
    padding: 12px 16px; border-radius: var(--radius-md);
    background: var(--warning-tint); border: 1px solid rgba(199,154,87,0.2);
    font-size: 0.75rem; color: var(--warning);
    margin-bottom: 24px; line-height: 1.6;
  }

  .form-section-label {
    font-size: 0.625rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--ink-faint);
    margin: 24px 0 14px; padding-bottom: 8px;
    border-bottom: 1px solid var(--line);
    display: flex; align-items: center; gap: 8px;
  }
  .form-section-label:first-of-type { margin-top: 0; }
  .form-section-num {
    width: 20px; height: 20px; border-radius: var(--radius-full);
    background: var(--accent-tint); color: var(--accent-strong);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.5625rem; font-weight: 700;
  }

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
    position: absolute; left: 14px; top: 22px; transform: translateY(-50%);
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
  .auth-field input:-webkit-autofill:focus + label { color: var(--accent); }
  .auth-field .form-hint {
    margin-top: 4px; font-size: 0.6875rem;
    color: var(--ink-faint); line-height: 1.4;
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

  .auth-submit {
    width: 100%; padding: 12px 24px;
    background: var(--accent); color: var(--ink-on-accent);
    border: 1px solid var(--accent); border-radius: var(--radius-md);
    font-family: var(--font-body); font-weight: 600; font-size: 0.875rem;
    cursor: pointer; transition: all var(--duration) var(--ease);
    display: flex; align-items: center; justify-content: center; gap: 8px;
  }
  .auth-submit:hover { background: var(--accent-hover); border-color: var(--accent-hover); box-shadow: 0 2px 8px rgba(166,35,28,0.3); }
  .auth-submit:active { transform: scale(0.98); }
  .auth-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

  .auth-terms {
    display: flex; align-items: flex-start; gap: 10px;
    margin-bottom: 24px; cursor: pointer;
  }
  .auth-terms input {
    width: 16px; height: 16px; accent-color: var(--accent);
    cursor: pointer; margin-top: 2px; flex-shrink: 0;
  }
  .auth-terms span {
    font-size: 0.8125rem; color: var(--ink-soft); line-height: 1.5;
  }
  .auth-terms a { color: var(--accent); text-decoration: none; }
  .auth-terms a:hover { text-decoration: underline; }

  .auth-divider { height: 1px; background: var(--line); margin: 20px 0; }

  .auth-footer-text {
    text-align: center; font-size: 0.8125rem; color: var(--ink-faint);
  }
  .auth-footer-text a { color: var(--accent); text-decoration: none; font-weight: 500; }
  .auth-footer-text a:hover { text-decoration: underline; }

  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

  @media (max-width: 480px) {
    .auth-card { padding: 28px 20px; }
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
      <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
        <rect width="28" height="28" rx="7" fill="var(--accent)"/>
        <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
      </svg>
      <span class="auth-brand-name"><?= e(APP_NAME) ?></span>
    </div>

    <h1 class="auth-title">Administrator Registration</h1>
    <p class="auth-subtitle">Create your workspace and administrator account.</p>

    <div class="admin-notice">
      <strong>Administrators only.</strong> Lab Technicians, Testing Engineers, Quality Managers, and Auditors should ask their Administrator for an invitation.
    </div>

    <?php if ($errors): ?>
    <div class="login-error">
      <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="" novalidate id="signupForm">
      <?= csrf_field() ?>

      <div class="form-section-label"><span class="form-section-num">1</span> Organization</div>

      <div class="auth-field">
        <input type="text" id="workspace_name" name="workspace_name"
               placeholder="Workspace" value="<?= e($workspaceName) ?>"
               required autocomplete="organization">
        <label for="workspace_name">Workspace Name</label>
        <div class="form-hint">Your organization or team name.</div>
      </div>

      <div class="form-section-label"><span class="form-section-num">2</span> Personal Information</div>

      <div class="auth-field">
        <input type="text" id="full_name" name="full_name"
               placeholder="Name" value="<?= e($name) ?>"
               required autocomplete="name">
        <label for="full_name">Full Name</label>
      </div>

      <div class="auth-field">
        <input type="email" id="email" name="email"
               placeholder="Email" value="<?= e($email) ?>"
               required autocomplete="email">
        <label for="email">Email Address</label>
      </div>

      <div class="form-section-label"><span class="form-section-num">3</span> Security</div>

      <div class="auth-field password-field">
        <input type="password" id="password" name="password"
               placeholder="Password" required autocomplete="new-password">
        <label for="password">Password</label>
        <button type="button" class="password-toggle" onclick="togglePw('password', this)" aria-label="Show password">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
        </button>
      </div>

      <div class="auth-field password-field">
        <input type="password" id="password_confirm" name="password_confirm"
               placeholder="Confirm" required autocomplete="new-password">
        <label for="password_confirm">Confirm Password</label>
        <button type="button" class="password-toggle" onclick="togglePw('password_confirm', this)" aria-label="Show password">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z"/><circle cx="8" cy="8" r="2"/></svg>
        </button>
      </div>

      <label class="auth-terms">
        <input type="checkbox" name="terms" value="1">
        <span>
          I accept the <a href="<?= url('pages/terms.php') ?>" target="_blank">Terms of Service</a>
          and <a href="<?= url('pages/privacy.php') ?>" target="_blank">Privacy Policy</a>
        </span>
      </label>

      <button type="submit" class="auth-submit" id="signupBtn">
        Continue
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="8" x2="13" y2="8"/><polyline points="9,4 13,8 9,12"/></svg>
      </button>
    </form>

    <div class="auth-divider"></div>

    <div class="auth-footer-text">
      Already have an account? <a href="<?= url('auth/login.php') ?>">Sign in</a>
    </div>
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

document.getElementById('signupForm').addEventListener('submit', function() {
  var btn = document.getElementById('signupBtn');
  btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;vertical-align:middle;"></span> Creating…';
  btn.disabled = true;
});

function showFieldError(input, msg) {
  clearFieldError(input);
  input.style.borderColor = 'var(--fail)';
  var el = document.createElement('div');
  el.className = 'field-live-error';
  el.style.cssText = 'color:var(--fail);font-size:0.75rem;margin-top:4px;';
  el.textContent = msg;
  input.parentNode.appendChild(el);
}
function clearFieldError(input) {
  input.style.borderColor = '';
  var old = input.parentNode.querySelector('.field-live-error');
  if (old) old.remove();
}

document.getElementById('workspace_name').addEventListener('input', function() {
  var v = this.value;
  if (v && /[0-9]/.test(v)) { showFieldError(this, 'Numbers not allowed in workspace name.'); }
  else if (v && /[^A-Za-z\s.\-&]/.test(v)) { showFieldError(this, 'Only letters, spaces, dots and hyphens allowed.'); }
  else { clearFieldError(this); }
});

document.getElementById('full_name').addEventListener('input', function() {
  var v = this.value;
  if (v && /[0-9]/.test(v)) { showFieldError(this, 'Numbers not allowed in name.'); }
  else if (v && /[^A-Za-z\s.\-\']/.test(v)) { showFieldError(this, 'Only letters, spaces, dots and hyphens allowed.'); }
  else { clearFieldError(this); }
});
</script>
</body>
</html>
