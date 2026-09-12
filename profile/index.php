<?php
declare(strict_types=1);
// Profile — personal info, password change, active sessions (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth();
$pageTitle = 'Profile';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Profile', 'url' => ''],
];

$pdo = Database::getConnection();
$wsId = auth_workspace_id();
$errors = [];
$success = '';

if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim(preg_replace('/\s+/', ' ', $_POST['full_name'] ?? ''));
        $nameErr = validate_human_name($name);
        if ($nameErr) {
            $errors[] = $nameErr;
        }
        if (!$errors) {
            $_SESSION['user']['name'] = $name;
            $pdo->prepare("UPDATE users SET name = :name WHERE id = :id AND workspace_id = :ws")
                ->execute(['name' => $name, 'id' => auth_id(), 'ws' => $wsId]);
            audit_log($pdo, auth_id(), 'user.profile_update', 'user', auth_id(), null, ['name' => $name]);
            flash_success('Profile updated.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $userStmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id AND workspace_id = :ws');
        $userStmt->execute(['id' => auth_id(), 'ws' => $wsId]);
        $hash = $userStmt->fetchColumn();

        if (!secure_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (($passErr = validate_password($newPass)) !== null) {
            $errors[] = $passErr;
        } elseif ($newPass !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id AND workspace_id = :ws")
                ->execute(['hash' => secure_hash($newPass), 'id' => auth_id(), 'ws' => $wsId]);
            audit_log($pdo, auth_id(), 'user.password_change', 'user', auth_id());
            flash_success('Password changed successfully.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
    }
}

// Re-fetch user data after potential update
$userStmt = $pdo->prepare("SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = :id AND u.workspace_id = :ws");
$userStmt->execute(['id' => auth_id(), 'ws' => $wsId]);
$user = $userStmt->fetch();

// Last login from audit log
$lastLogin = $pdo->prepare("SELECT created_at FROM audit_log WHERE actor_id = :uid AND action = 'auth.login' AND workspace_id = :ws ORDER BY created_at DESC LIMIT 1 OFFSET 1");
$lastLogin->execute(['uid' => auth_id(), 'ws' => $wsId]);
$lastLoginDate = $lastLogin->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="7" r="4"/><path d="M3 18c0-4 3.1-7 7-7s7 3 7 7"/></svg>
      </div>
      <div>
        <h1 class="page-title">Profile</h1>
        <p class="page-subtitle" style="margin-top:2px;">Manage your account settings</p>
      </div>
    </div>
  </div>
</div>

<?php if ($errors): ?>
<div style="padding:12px 16px;border-radius:var(--radius-md);background:var(--fail-tint);color:var(--fail);font-size:0.8125rem;margin-bottom:20px;max-width:800px;">
  <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="profile-layout">

  <!-- Profile Card -->
  <div>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:32px 24px;">
        <div style="width:80px;height:80px;border-radius:var(--radius-full);background:var(--accent);color:var(--ink-on-accent);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;margin:0 auto 16px;">
          <?= e(user_initials($user['name'])) ?>
        </div>
        <h3 style="font-size:1.125rem;margin-bottom:4px;"><?= e($user['name']) ?></h3>
        <div style="font-size:0.8125rem;color:var(--ink-soft);margin-bottom:4px;"><?= e(role_label($user['role'])) ?></div>
        <div style="font-size:0.75rem;color:var(--ink-faint);"><?= e($user['email']) ?></div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:8px;text-align:left;">
          <div class="d-flex justify-between" style="font-size:0.8125rem;">
            <span class="text-faint">Department</span>
            <span><?= e($user['dept_name'] ?? 'Cross-department') ?></span>
          </div>
          <div class="d-flex justify-between" style="font-size:0.8125rem;">
            <span class="text-faint">Status</span>
            <?= status_badge($user['status']) ?>
          </div>
          <div class="d-flex justify-between" style="font-size:0.8125rem;">
            <span class="text-faint">Member since</span>
            <span><?= format_date($user['created_at']) ?></span>
          </div>
          <div class="d-flex justify-between" style="font-size:0.8125rem;">
            <span class="text-faint">Last login</span>
            <span><?= $lastLoginDate ? time_ago($lastLoginDate) : 'Current session' ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Settings Panels -->
  <div style="display:flex;flex-direction:column;gap:24px;">

    <!-- Personal Information -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Personal Information</h3>
      </div>
      <div class="card-body">
        <form method="post" action="" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_profile">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name <span class="required">*</span></label>
              <input type="text" class="form-control" name="full_name" value="<?= e($user['name']) ?>" required minlength="3" data-validate="required|alpha_space|minlength:3">
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
              <span class="form-hint">Contact an administrator to change your email</span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" value="<?= e(role_label($user['role'])) ?>" disabled>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;padding-top:12px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Change Password</h3>
      </div>
      <div class="card-body">
        <form method="post" action="" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label class="form-label">Current Password <span class="required">*</span></label>
            <input type="password" class="form-control" name="current_password" placeholder="Enter current password" required data-validate="required">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password <span class="required">*</span></label>
              <input type="password" class="form-control" name="new_password" id="new_password"
                     placeholder="Min 8 chars, uppercase, number, symbol" required data-validate="required|password">
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password <span class="required">*</span></label>
              <input type="password" class="form-control" name="confirm_password"
                     placeholder="Repeat new password" required data-validate="required|match:new_password">
            </div>
          </div>
          <div class="form-hint" style="margin-bottom:12px;">
            Password must be at least 8 characters with uppercase, number, and special character.
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Active Sessions -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Active Sessions</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <div style="display:flex;align-items:center;gap:14px;padding:14px 24px;">
          <div style="width:36px;height:36px;border-radius:var(--radius-md);background:var(--success-tint);color:var(--success);display:flex;align-items:center;justify-content:center;">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="14" height="10" rx="2"/><line x1="5" y1="16" x2="13" y2="16"/></svg>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:0.8125rem;">Current session · <span style="color:var(--success);font-size:0.75rem;">Active now</span></div>
            <div style="font-size:0.75rem;color:var(--ink-faint);"><?= e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?> · Started <?= format_datetime(date('Y-m-d H:i:s', (int) ($_SESSION['login_time'] ?? time()))) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
