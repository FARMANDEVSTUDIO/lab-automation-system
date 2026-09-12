<?php
declare(strict_types=1);
// Email verification — 6-digit OTP input with auto-advance (Naveera)
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

$sessionToken = $_SESSION['pending_signup_token'] ?? '';
$email        = $_SESSION['pending_signup_email'] ?? '';
$mailStatus   = $_SESSION['signup_mail_status'] ?? '';

if ($sessionToken === '' || $email === '') {
    header('Location: ' . BASE_URL . '/auth/signup.php');
    exit;
}

$pending = $pdo->prepare('SELECT * FROM pending_signups WHERE session_token = :t AND email = :e AND expires_at > NOW() LIMIT 1');
$pending->execute(['t' => $sessionToken, 'e' => $email]);
$signup = $pending->fetch();

if (!$signup) {
    unset($_SESSION['pending_signup_token'], $_SESSION['pending_signup_email'], $_SESSION['signup_mail_status']);
    flash_error('Your registration session has expired. Please start over.');
    header('Location: ' . BASE_URL . '/auth/signup.php');
    exit;
}

$errors = [];
$verified = false;

// Handle OTP verification
if (is_post()) {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $action = !$errors ? ($_POST['action'] ?? 'verify') : '';

    if ($action === 'resend') {
        $nonceKey = 'resend_nonce_signup';
        $submittedNonce = $_POST['resend_nonce'] ?? '';
        if ($submittedNonce !== '' && $submittedNonce === ($_SESSION[$nonceKey] ?? '')) {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        $_SESSION[$nonceKey] = bin2hex(random_bytes(8));

        $resendResult = resend_otp($pdo, $email, 'admin_signup');
        if (!empty($resendResult['success'])) {
            $mailResult = send_otp_email($email, $signup['full_name'], $resendResult['otp'], 'admin_signup');
            if ($mailResult['success']) {
                $_SESSION['signup_mail_status'] = 'sent';
                unset($_SESSION['signup_otp_fallback']);
                flash_success('A new verification code has been sent.');
            } else {
                $_SESSION['signup_mail_status'] = 'failed';
                $_SESSION['signup_otp_fallback'] = $resendResult['otp'];
                flash_success('New code generated (see below).');
            }
        } else {
            $cooldown = $resendResult['cooldown'] ?? 0;
            if ($cooldown > 0) {
                flash_error("Please wait {$cooldown} seconds before requesting a new code.");
            } else {
                flash_error($resendResult['error'] ?? 'Could not resend code.');
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'change_email') {
        $pdo->prepare('DELETE FROM pending_signups WHERE session_token = :t')->execute(['t' => $sessionToken]);
        $pdo->prepare('UPDATE email_verifications SET verified_at = NOW() WHERE email = :e AND purpose = :p AND verified_at IS NULL')
            ->execute(['e' => $email, 'p' => 'admin_signup']);
        unset($_SESSION['pending_signup_token'], $_SESSION['pending_signup_email'], $_SESSION['signup_mail_status']);
        header('Location: ' . BASE_URL . '/auth/signup.php');
        exit;
    }

    // Verify OTP
    $otpInput = trim($_POST['otp'] ?? '');
    if ($otpInput === '' || strlen($otpInput) !== 6 || !ctype_digit($otpInput)) {
        $errors[] = 'Please enter a valid 6-digit verification code.';
    }

    if (!$errors) {
        $result = verify_otp($pdo, $email, $otpInput, 'admin_signup');
        if ($result['success']) {
            $verified = true;
        } else {
            $errors[] = $result['error'];
        }
    }

    if ($verified) {
        // Email verified — create the real workspace + admin account
        $pdo->beginTransaction();
        try {
            $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($signup['workspace_name']));
            $slug = trim($slug, '-');
            if ($slug === '') $slug = 'workspace';

            $baseSlug = substr($slug, 0, 80);
            $finalSlug = $baseSlug;
            $attempt = 0;
            while (true) {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM workspaces WHERE slug = :s');
                $chk->execute(['s' => $finalSlug]);
                if ((int) $chk->fetchColumn() === 0) break;
                $attempt++;
                $finalSlug = $baseSlug . '-' . $attempt;
            }

            $dupCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
            $dupCheck->execute(['e' => $email]);
            if ((int) $dupCheck->fetchColumn() > 0) {
                $pdo->rollBack();
                $errors[] = 'An account with this email was created while you were verifying. Please sign in.';
                $pdo->prepare('DELETE FROM pending_signups WHERE session_token = :t')->execute(['t' => $sessionToken]);
                unset($_SESSION['pending_signup_token'], $_SESSION['pending_signup_email'], $_SESSION['signup_mail_status']);
                flash_error($errors[0]);
                header('Location: ' . BASE_URL . '/auth/login.php');
                exit;
            }

            $wsStmt = $pdo->prepare('INSERT INTO workspaces (name, slug, status, created_at, updated_at) VALUES (:name, :slug, :status, NOW(), NOW())');
            $wsStmt->execute([
                'name'   => $signup['workspace_name'],
                'slug'   => $finalSlug,
                'status' => 'active',
            ]);
            $wsId = (int) $pdo->lastInsertId();

            $userStmt = $pdo->prepare(
                'INSERT INTO users (workspace_id, name, email, email_verified, password_hash, role, status, created_at, updated_at)
                 VALUES (:ws, :name, :email, 1, :hash, :role, :status, NOW(), NOW())'
            );
            $userStmt->execute([
                'ws'     => $wsId,
                'name'   => $signup['full_name'],
                'email'  => $signup['email'],
                'hash'   => $signup['password_hash'],
                'role'   => ROLE_ADMINISTRATOR,
                'status' => 'active',
            ]);
            $userId = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE workspaces SET owner_id = :uid WHERE id = :ws')
                ->execute(['uid' => $userId, 'ws' => $wsId]);

            $defaults = [
                'max_login_attempts'      => '5',
                'lockout_duration_minutes' => '15',
                'session_timeout_minutes'  => '480',
            ];
            $settingStmt = $pdo->prepare('INSERT INTO settings (workspace_id, setting_key, setting_value) VALUES (:ws, :k, :v)');
            foreach ($defaults as $k => $v) {
                $settingStmt->execute(['ws' => $wsId, 'k' => $k, 'v' => $v]);
            }

            audit_log($pdo, $userId, 'workspace.create', 'workspace', $wsId, null, [
                'name' => $signup['workspace_name'],
                'slug' => $finalSlug,
            ], $wsId);

            audit_log($pdo, $userId, 'admin.signup', 'user', $userId, null, [
                'name'           => $signup['full_name'],
                'email'          => $signup['email'],
                'role'           => ROLE_ADMINISTRATOR,
                'email_verified' => true,
            ], $wsId);

            $pdo->prepare('DELETE FROM pending_signups WHERE session_token = :t')->execute(['t' => $sessionToken]);

            $pdo->commit();

            unset($_SESSION['pending_signup_token'], $_SESSION['pending_signup_email'], $_SESSION['signup_mail_status']);

            $loginResult = login_user($signup['email'], '');
            // login_user needs the real password but we have the hash. Do manual session setup.
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'            => $userId,
                'name'          => $signup['full_name'],
                'email'         => $signup['email'],
                'role'          => ROLE_ADMINISTRATOR,
                'department_id' => null,
                'avatar'        => null,
                'workspace_id'  => $wsId,
            ];
            $_SESSION['login_time'] = time();

            audit_log($pdo, $userId, 'auth.login', 'user', $userId, null, null, $wsId);

            flash_success('Welcome to ' . APP_NAME . '! Your workspace "' . $signup['workspace_name'] . '" is ready.');
            header('Location: ' . BASE_URL . '/dashboard/index.php');
            exit;
        } catch (\Throwable $ex) {
            $pdo->rollBack();
            error_log('Signup finalize error: ' . $ex->getMessage());
            $errors[] = 'An unexpected error occurred. Please try again.';
        }
    }
}

// Check cooldown for resend
$cooldownInfo = can_resend_otp($pdo, $email, 'admin_signup');
$cooldownSec  = $cooldownInfo['cooldown'] ?? 0;

$flashSuccess = flash('success');
$flashError = flash('error');
$maskedEmail = mask_email($email);
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
<title>Verify Email &middot; <?= e(APP_NAME) ?></title>
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

  .auth-icon-wrap {
    width: 56px; height: 56px; border-radius: var(--radius-lg);
    background: var(--accent-tint); border: 1px solid rgba(166,35,28,0.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 20px; color: var(--accent-strong);
  }
  .auth-title {
    font-family: var(--font-display); font-weight: 700;
    font-size: 1.25rem; color: var(--ink); margin-bottom: 6px;
    letter-spacing: -0.01em;
  }
  .auth-subtitle {
    font-size: 0.8125rem; color: var(--ink-faint);
    margin-bottom: 28px; line-height: 1.6;
  }
  .auth-subtitle strong { color: var(--ink); }

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
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.8125rem; margin-top: 20px;
    padding-top: 20px; border-top: 1px solid var(--line);
  }
  .otp-actions a, .otp-actions button {
    color: var(--accent); background: none; border: none; cursor: pointer;
    font-size: 0.8125rem; text-decoration: none; font-weight: 500; padding: 0;
    font-family: var(--font-body);
  }
  .otp-actions a:hover, .otp-actions button:hover { text-decoration: underline; }
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
  .flash-warning { background: var(--warning-tint); border: 1px solid rgba(199,154,87,0.2); color: var(--warning); }
  .mail-status {
    padding: 12px 16px; border-radius: var(--radius-md); font-size: 0.75rem;
    margin-bottom: 20px; line-height: 1.6;
  }
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
  <a href="<?= url('auth/signup.php') ?>" class="auth-back" onclick="return confirm('Going back will cancel your current registration. Continue?');">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="9,3 5,7 9,11"/></svg>
    Back to registration
  </a>

  <div class="auth-card">
    <div class="auth-icon-wrap">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 4L12 13 2 4"/></svg>
    </div>
    <h1 class="auth-title">Verify your email</h1>
    <p class="auth-subtitle">
      We sent a 6-digit verification code to<br>
      <strong><?= e($maskedEmail) ?></strong>
    </p>

    <?php if ($mailStatus === 'failed' && !empty($_SESSION['signup_otp_fallback'])): ?>
    <div class="mail-status" style="background:var(--warning-tint);color:var(--warning);text-align:center;">
      <strong>Email delivery unavailable.</strong> Your verification code is:<br>
      <span style="font-family:var(--font-mono,monospace);font-size:1.5rem;font-weight:700;letter-spacing:6px;display:inline-block;margin-top:8px;color:var(--ink);"><?= e($_SESSION['signup_otp_fallback']) ?></span>
    </div>
    <?php elseif ($mailStatus === 'logged'): ?>
    <div class="mail-status" style="background:var(--warning-tint);color:var(--warning);">
      <strong>Development mode:</strong> SMTP is not configured. The verification code has been logged to the server's <code>/logs/</code> directory.
    </div>
    <?php elseif ($mailStatus === 'failed'): ?>
    <div class="mail-status" style="background:var(--fail-tint);color:var(--fail);">
      <strong>Email could not be sent.</strong> Please try resending the code.
    </div>
    <?php endif; ?>

    <?php if ($flashSuccess): ?>
    <div class="flash-msg flash-success"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
    <div class="flash-msg" style="background:var(--fail-tint);color:var(--fail);"><?= e($flashError) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="login-error">
      <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="" id="otpForm" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="verify">
      <input type="hidden" name="otp" id="otpHidden" value="">

      <div class="otp-input-wrap">
        <?php for ($i = 0; $i < 6; $i++): ?>
        <input type="text" maxlength="1" class="otp-digit" data-idx="<?= $i ?>" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
        <?php endfor; ?>
      </div>

      <button type="submit" class="auth-submit" id="verifyBtn">
        Verify Email
      </button>
    </form>

    <div class="otp-actions">
      <form method="post" action="" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="resend">
        <input type="hidden" name="resend_nonce" value="<?= e($_SESSION['resend_nonce_signup'] ?? '') ?>">
        <button type="submit" id="resendBtn" <?= $cooldownSec > 0 ? 'disabled' : '' ?>>
          <?= $cooldownSec > 0 ? "Resend in {$cooldownSec}s" : 'Resend code' ?>
        </button>
      </form>
      <form method="post" action="" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_email">
        <button type="submit" onclick="return confirm('This will discard your current registration. Continue?');">Wrong email?</button>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  var digits = document.querySelectorAll('.otp-digit');
  var hidden = document.getElementById('otpHidden');

  function collectOtp() {
    var val = '';
    digits.forEach(function(d) { val += d.value; });
    hidden.value = val;
  }

  digits.forEach(function(input, idx) {
    input.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 1);
      collectOtp();
      if (this.value && idx < 5) digits[idx + 1].focus();
      if (hidden.value.length === 6) document.getElementById('verifyBtn').focus();
    });
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && !this.value && idx > 0) {
        digits[idx - 1].focus();
        digits[idx - 1].value = '';
        collectOtp();
      }
    });
    input.addEventListener('paste', function(e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      for (var i = 0; i < text.length && i < 6; i++) {
        digits[i].value = text[i];
      }
      collectOtp();
      if (text.length >= 6) document.getElementById('verifyBtn').focus();
      else if (text.length > 0) digits[Math.min(text.length, 5)].focus();
    });
  });

  digits[0].focus();

  // Cooldown timer — initialized from server-side value
  var cooldown = <?= $cooldownSec ?>;
  var resendBtn = document.getElementById('resendBtn');
  var cooldownTimer = null;

  function startCooldownTimer(secs) {
    cooldown = secs;
    if (cooldownTimer) clearInterval(cooldownTimer);
    if (cooldown <= 0) {
      resendBtn.disabled = false;
      resendBtn.textContent = 'Resend code';
      return;
    }
    resendBtn.disabled = true;
    resendBtn.textContent = 'Resend in ' + cooldown + 's';
    cooldownTimer = setInterval(function() {
      cooldown--;
      if (cooldown <= 0) {
        clearInterval(cooldownTimer);
        cooldownTimer = null;
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend code';
      } else {
        resendBtn.textContent = 'Resend in ' + cooldown + 's';
      }
    }, 1000);
  }

  startCooldownTimer(cooldown);

  // Multi-tab sync via BroadcastChannel
  var otpChannel = null;
  try { otpChannel = new BroadcastChannel('lab_otp_sync'); } catch(e) {}
  if (otpChannel) {
    otpChannel.onmessage = function(ev) {
      if (ev.data && ev.data.type === 'otp_resent') {
        startCooldownTimer(ev.data.cooldown || 60);
      }
      if (ev.data && ev.data.type === 'otp_verified') {
        location.reload();
      }
    };
    resendBtn.closest('form').addEventListener('submit', function() {
      otpChannel.postMessage({ type: 'otp_resent', cooldown: 60 });
    });
  }

  document.getElementById('otpForm').addEventListener('submit', function() {
    collectOtp();
    if (otpChannel) otpChannel.postMessage({ type: 'otp_verified' });
    var btn = document.getElementById('verifyBtn');
    btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;vertical-align:middle;"></span> Verifying…';
    btn.disabled = true;
  });
})();
</script>
</body>
</html>
