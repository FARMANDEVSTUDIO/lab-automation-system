<?php
declare(strict_types=1);
// User management — CRUD, invitations, role assignment (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'User Management';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'User Management', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR);

$pdo = Database::getConnection();
$wsId = auth_workspace_id();
$errors = [];

if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        require_once __DIR__ . '/../includes/mail.php';
        $name     = trim(preg_replace('/\s+/', ' ', $_POST['full_name'] ?? ''));
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $role     = $_POST['role'] ?? '';
        $deptId   = (int) ($_POST['department_id'] ?? 0);

        $validRoles = [ROLE_TESTING_ENGINEER, ROLE_LAB_TECHNICIAN, ROLE_QUALITY_MANAGER, ROLE_AUDITOR];

        $nameErr = validate_human_name($name);
        if ($nameErr) $errors[] = $nameErr;
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (!in_array($role, $validRoles, true)) $errors[] = 'Please select a valid role.';

        if (!$errors) {
            $dup = $pdo->prepare('SELECT id, status, invitation_status FROM users WHERE email = :e AND workspace_id = :ws');
            $dup->execute(['e' => $email, 'ws' => $wsId]);
            $existingUser = $dup->fetch();
            if ($existingUser) {
                if ($existingUser['invitation_status'] === 'pending') {
                    $errors[] = 'This email already has a pending invitation. Use "Resend Invitation" instead.';
                } else {
                    $errors[] = 'A user with this email already exists in this workspace.';
                }
            }

            $globalDup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
            $globalDup->execute(['e' => $email]);
            if ((int) $globalDup->fetchColumn() > 0 && !$existingUser) {
                $errors[] = 'This email is already registered in another workspace.';
            }
        }

        if (!$errors) {
            $tempHash = password_hash(bin2hex(random_bytes(20)), PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (workspace_id, name, email, email_verified, password_hash, role, department_id, status, invitation_status, created_at, updated_at) VALUES (:ws, :name, :email, 0, :hash, :role, :dept, :status, :inv, NOW(), NOW())');
            $stmt->execute([
                'ws'    => $wsId,
                'name'  => $name,
                'email' => $email,
                'hash'  => $tempHash,
                'role'  => $role,
                'dept'  => $deptId ?: null,
                'status' => 'inactive',
                'inv'   => 'pending',
            ]);
            $newId = (int) $pdo->lastInsertId();

            $token = create_invitation($pdo, $newId, auth_id());

            $deptName = '—';
            if ($deptId) {
                $dStmt = $pdo->prepare('SELECT name FROM departments WHERE id = :id AND workspace_id = :ws');
                $dStmt->execute(['id' => $deptId, 'ws' => $wsId]);
                $deptName = $dStmt->fetchColumn() ?: '—';
            }

            $wsStmt = $pdo->prepare('SELECT name FROM workspaces WHERE id = :id');
            $wsStmt->execute(['id' => $wsId]);
            $wsName = $wsStmt->fetchColumn() ?: '';

            $mailResult = send_invitation_email($email, $name, $role, $deptName, auth_user()['name'] ?? '', $token, $wsName);

            audit_log($pdo, auth_id(), 'user.invite', 'user', $newId, null, ['name' => $name, 'email' => $email, 'role' => $role]);
            create_notification($pdo, auth_id(), 'User invited', "Invitation sent to {$name} ({$email}) for role " . role_label($role) . '.', 'success');

            if ($mailResult['success']) {
                flash_success("Invitation sent to {$name} ({$email}). They will verify their email and set their own password.");
            } else {
                $activateUrl = rtrim(APP_URL, '/') . '/auth/activate.php?token=' . urlencode($token);
                flash_error("User created but email could not be sent. Share this activation link manually: {$activateUrl}");
            }
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
    }

    if ($action === 'resend_invitation') {
        require_once __DIR__ . '/../includes/mail.php';
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId) {
            $userStmt = $pdo->prepare('SELECT u.*, d.name AS dept_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = :id AND u.workspace_id = :ws');
            $userStmt->execute(['id' => $userId, 'ws' => $wsId]);
            $targetUser = $userStmt->fetch();

            if ($targetUser && $targetUser['invitation_status'] === 'pending') {
                $pdo->prepare('UPDATE invitation_tokens SET used_at = NOW() WHERE user_id = :uid AND workspace_id = :ws AND used_at IS NULL')
                    ->execute(['uid' => $userId, 'ws' => $wsId]);

                $newToken = create_invitation($pdo, $userId, auth_id());

                $wsStmt = $pdo->prepare('SELECT name FROM workspaces WHERE id = :id');
                $wsStmt->execute(['id' => $wsId]);
                $wsName = $wsStmt->fetchColumn() ?: '';

                $mailResult = send_invitation_email(
                    $targetUser['email'], $targetUser['name'], $targetUser['role'],
                    $targetUser['dept_name'] ?? '—', auth_user()['name'] ?? '', $newToken, $wsName
                );

                audit_log($pdo, auth_id(), 'user.reinvite', 'user', $userId, null, ['email' => $targetUser['email']]);

                if ($mailResult['success']) {
                    flash_success("Invitation resent to {$targetUser['name']}.");
                } else {
                    $activateUrl = rtrim(APP_URL, '/') . '/auth/activate.php?token=' . urlencode($newToken);
                    flash_error("Email could not be sent. Share this activation link: {$activateUrl}");
                }
            } else {
                flash_error('User not found or already activated.');
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'edit_user') {
        $id       = (int) ($_POST['user_id'] ?? 0);
        $name     = trim(preg_replace('/\s+/', ' ', $_POST['full_name'] ?? ''));
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $role     = $_POST['role'] ?? '';
        $deptId   = (int) ($_POST['department_id'] ?? 0);
        $status   = $_POST['status'] ?? 'active';
        $newPass  = trim($_POST['new_password'] ?? '');

        $validRoles = [ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER, ROLE_LAB_TECHNICIAN, ROLE_QUALITY_MANAGER, ROLE_AUDITOR];

        if (!$id) $errors[] = 'Invalid user.';
        $nameErr = validate_human_name($name);
        if ($nameErr) $errors[] = $nameErr;
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
        if (!in_array($role, $validRoles, true)) $errors[] = 'Please select a valid role.';
        if (!in_array($status, ['active', 'inactive'], true)) $errors[] = 'Invalid status.';

        if ($newPass !== '') {
            $passErr = validate_password($newPass);
            if ($passErr) $errors[] = $passErr;
        }

        if (!$errors && $id) {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e AND id != :id AND workspace_id = :ws');
            $dup->execute(['e' => $email, 'id' => $id, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) $errors[] = 'Another user already has this email.';
        }

        if (!$errors && $id) {
            $old = $pdo->prepare('SELECT * FROM users WHERE id = :id AND workspace_id = :ws');
            $old->execute(['id' => $id, 'ws' => $wsId]);
            $before = $old->fetch();

            if (!$before) {
                $errors[] = 'User not found.';
            } else {
                $sql = 'UPDATE users SET name = :name, email = :email, role = :role, department_id = :dept, status = :status, updated_at = NOW()';
                $params = ['name' => $name, 'email' => $email, 'role' => $role, 'dept' => $deptId ?: null, 'status' => $status, 'id' => $id, 'ws' => $wsId];

                if ($newPass !== '') {
                    $sql .= ', password_hash = :hash';
                    $params['hash'] = secure_hash($newPass);
                }

                $sql .= ' WHERE id = :id AND workspace_id = :ws';
                $pdo->prepare($sql)->execute($params);

                audit_log($pdo, auth_id(), 'user.update', 'user', $id,
                    ['name' => $before['name'], 'email' => $before['email'], 'role' => $before['role'], 'status' => $before['status']],
                    ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status]
                );

                if ($before['id'] === auth_id()) {
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['role'] = $role;
                    $_SESSION['user']['department_id'] = $deptId ?: null;
                }

                flash_success("User {$name} updated.");
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            }
        }
    }

    if ($action === 'toggle_status') {
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id && $id !== auth_id()) {
            $user = $pdo->prepare('SELECT id, name, status FROM users WHERE id = :id AND workspace_id = :ws');
            $user->execute(['id' => $id, 'ws' => $wsId]);
            $u = $user->fetch();
            if ($u) {
                $newStatus = $u['status'] === 'active' ? 'inactive' : 'active';
                $pdo->prepare('UPDATE users SET status = :s, updated_at = NOW() WHERE id = :id AND workspace_id = :ws')->execute(['s' => $newStatus, 'id' => $id, 'ws' => $wsId]);
                audit_log($pdo, auth_id(), 'user.status_change', 'user', $id, ['status' => $u['status']], ['status' => $newStatus]);
                flash_success("User {$u['name']} " . ($newStatus === 'active' ? 'activated' : 'deactivated') . '.');
            }
        } else {
            flash_error('Cannot deactivate your own account.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'reset_password') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        $passErr = validate_password($newPass);
        if ($id && !$passErr) {
            $pdo->prepare('UPDATE users SET password_hash = :hash, failed_login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = :id AND workspace_id = :ws')
                ->execute(['hash' => secure_hash($newPass), 'id' => $id, 'ws' => $wsId]);
            audit_log($pdo, auth_id(), 'user.password_reset', 'user', $id);
            $u = $pdo->prepare('SELECT name FROM users WHERE id = :id AND workspace_id = :ws');
            $u->execute(['id' => $id, 'ws' => $wsId]);
            $uName = $u->fetchColumn();
            flash_success("Password reset for {$uName}.");
        } else {
            flash_error($passErr ?: 'Invalid request.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = 'u.workspace_id = :ws';
$params = ['ws' => $wsId];
if ($roleFilter) { $where .= ' AND u.role = :role'; $params['role'] = $roleFilter; }
if ($statusFilter) { $where .= ' AND u.status = :status'; $params['status'] = $statusFilter; }
if ($search) { $where .= ' AND (u.name LIKE :q OR u.email LIKE :q2)'; $params['q'] = "%{$search}%"; $params['q2'] = "%{$search}%"; }

$countSql = "SELECT COUNT(*) FROM users u WHERE {$where}";
$dataSql  = "SELECT u.*, u.invitation_status, d.name AS dept_name,
    (SELECT COUNT(*) FROM testing_records tr WHERE tr.tester_id = u.id AND tr.status IN ('assigned','in_testing')) AS active_tests,
    (SELECT COUNT(*) FROM testing_records tr WHERE tr.tester_id = u.id AND tr.review_decision IS NOT NULL) AS completed_tests
    FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE {$where} ORDER BY u.created_at DESC";

$result = paginate($pdo, $countSql, $dataSql, $params, $page, 15);
$users = $result['data'];

$deptStmt = $pdo->prepare("SELECT id, name FROM departments WHERE status = 'active' AND workspace_id = :ws ORDER BY name");
$deptStmt->execute(['ws' => $wsId]);
$departments = $deptStmt->fetchAll();

$totalUsersStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE workspace_id = :ws');
$totalUsersStmt->execute(['ws' => $wsId]);
$totalUsers = (int) $totalUsersStmt->fetchColumn();

$activeUsersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active' AND workspace_id = :ws");
$activeUsersStmt->execute(['ws' => $wsId]);
$activeUsers = (int) $activeUsersStmt->fetchColumn();

require_once __DIR__ . '/../includes/header.php';

$baseFilterUrl = url('admin/users.php') . '?role=' . urlencode($roleFilter) . '&status=' . urlencode($statusFilter) . '&q=' . urlencode($search);
?>

<?php if ($errors): ?>
<div style="padding:12px 16px;border-radius:var(--radius-md);background:var(--fail-tint);color:var(--fail);font-size:0.8125rem;margin-bottom:20px;">
  <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><circle cx="7.5" cy="6.5" r="3"/><path d="M2 17c0-3.3 2.5-6 5.5-6s5.5 2.7 5.5 6"/><circle cx="15" cy="7" r="2.2"/><path d="M13.6 11.3c2 .4 3.4 2.3 3.4 5.7"/></svg>
      </div>
      <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle" style="margin-top:2px;"><?= $totalUsers ?> users · <?= $activeUsers ?> active</p>
      </div>
    </div>
  </div>
  <div class="page-actions">
    <button class="btn btn-primary" onclick="openModal('addUserModal')">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      Add User
    </button>
  </div>
</div>

<!-- Stats -->
<div class="stat-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
  <?php
  $roleCountsStmt = $pdo->prepare("SELECT role, COUNT(*) as cnt FROM users WHERE workspace_id = :ws GROUP BY role ORDER BY FIELD(role,'administrator','testing_engineer','lab_technician','quality_manager','auditor')");
  $roleCountsStmt->execute(['ws' => $wsId]);
  $roleCounts = $roleCountsStmt->fetchAll();
  $roleMap = [];
  foreach ($roleCounts as $rc) $roleMap[$rc['role']] = (int) $rc['cnt'];
  $roleColors = ['administrator' => 'accent', 'testing_engineer' => 'warning', 'lab_technician' => 'info', 'quality_manager' => 'success', 'auditor' => 'hold'];
  foreach ([ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER, ROLE_LAB_TECHNICIAN, ROLE_QUALITY_MANAGER, ROLE_AUDITOR] as $r):
  ?>
  <div class="stat-tile">
    <span class="stat-label"><?= e(role_label($r)) ?></span>
    <span class="stat-value"><?= $roleMap[$r] ?? 0 ?></span>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px;">
  <form method="get" action="" style="display:flex;gap:10px;flex:1;align-items:center;flex-wrap:wrap;">
    <div class="filter-search">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
      <input type="text" class="form-control" name="q" placeholder="Search users…" value="<?= e($search) ?>">
    </div>
    <select class="form-control" name="role" style="width:180px;" onchange="this.form.submit()">
      <option value="">All Roles</option>
      <option value="administrator" <?= $roleFilter === 'administrator' ? 'selected' : '' ?>>Administrator</option>
      <option value="testing_engineer" <?= $roleFilter === 'testing_engineer' ? 'selected' : '' ?>>Testing Engineer</option>
      <option value="lab_technician" <?= $roleFilter === 'lab_technician' ? 'selected' : '' ?>>Lab Technician</option>
      <option value="quality_manager" <?= $roleFilter === 'quality_manager' ? 'selected' : '' ?>>Quality Manager</option>
      <option value="auditor" <?= $roleFilter === 'auditor' ? 'selected' : '' ?>>Auditor</option>
    </select>
    <select class="form-control" name="status" style="width:140px;" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <?php if ($search || $roleFilter || $statusFilter): ?>
    <a href="<?= url('admin/users.php') ?>" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Users Table -->
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th class="sortable">User</th>
        <th class="sortable">Role</th>
        <th>Department</th>
        <th class="col-num">Active Tests</th>
        <th class="col-num">Completed</th>
        <th class="sortable">Status</th>
        <th class="sortable">Last Login</th>
        <th class="col-actions">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
      <tr><td colspan="8" style="text-align:center;color:var(--ink-faint);padding:24px;">No users found.</td></tr>
      <?php else: ?>
      <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div class="d-flex align-center gap-10">
            <span class="user-avatar" style="width:36px;height:36px;font-size:0.75rem;"><?= e(user_initials($u['name'])) ?></span>
            <div>
              <div style="font-weight:600;font-size:0.8125rem;"><?= e($u['name']) ?></div>
              <div style="font-size:0.6875rem;color:var(--ink-faint);font-family:var(--font-mono);"><?= e($u['email']) ?></div>
            </div>
          </div>
        </td>
        <td>
          <span style="padding:2px 8px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:500;background:var(--<?= $roleColors[$u['role']] ?? 'hold' ?>-tint);color:var(--<?= $roleColors[$u['role']] ?? 'hold' ?>);">
            <?= e(role_label($u['role'])) ?>
          </span>
        </td>
        <td class="text-soft"><?= e($u['dept_name'] ?? '—') ?></td>
        <td class="col-num"><?= (int) $u['active_tests'] ?></td>
        <td class="col-num"><?= (int) $u['completed_tests'] ?></td>
        <td>
          <?php if (($u['invitation_status'] ?? '') === 'pending'): ?>
          <span class="status-badge badge-warning"><span class="status-dot"></span>Pending Invite</span>
          <?php else: ?>
          <?= status_badge($u['status']) ?>
          <?php endif; ?>
        </td>
        <td class="text-soft" style="font-size:0.75rem;"><?= $u['last_login_at'] ? time_ago($u['last_login_at']) : 'Never' ?></td>
        <td class="col-actions">
          <div class="d-flex gap-4">
            <button class="btn btn-sm btn-ghost" onclick="editUser(<?= $u['id'] ?>, <?= e(json_encode($u['name'])) ?>, <?= e(json_encode($u['email'])) ?>, '<?= e($u['role']) ?>', <?= (int) ($u['department_id'] ?? 0) ?>, '<?= e($u['status']) ?>')">Edit</button>
            <?php if (($u['invitation_status'] ?? '') === 'pending'): ?>
            <form method="post" action="" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="resend_invitation">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--info);">Resend</button>
            </form>
            <?php endif; ?>
            <?php if ($u['id'] !== auth_id()): ?>
            <form method="post" action="" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--<?= $u['status'] === 'active' ? 'fail' : 'success' ?>);">
                <?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?>
              </button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?= pagination_html($result['page'], $result['total_pages'], $baseFilterUrl) ?>

<!-- Add User Modal -->
<div class="modal-backdrop" id="addUserModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Add User</h3>
      <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_user">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" class="form-control" name="full_name" required minlength="3"
                   data-validate="required|alpha_space|minlength:3" placeholder="e.g. Rajesh Kumar">
          </div>
          <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" class="form-control" name="email" required
                   data-validate="required|email" placeholder="user@company.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Role <span class="required">*</span></label>
            <select class="form-control" name="role" required>
              <option value="">Select role…</option>
              <option value="testing_engineer">Testing Engineer</option>
              <option value="lab_technician">Lab Technician</option>
              <option value="quality_manager">Quality Manager</option>
              <option value="auditor">Auditor</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select class="form-control" name="department_id">
              <option value="">Cross-department</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="padding:12px 16px;border-radius:var(--radius-md);background:var(--info-tint);font-size:0.8125rem;color:var(--info);line-height:1.5;">
          An invitation email will be sent to the user. They will set their own password when activating their account.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Send Invitation</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal-backdrop" id="editUserModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Edit User</h3>
      <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_user">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <input type="text" class="form-control" name="full_name" id="editUserName" required minlength="3">
          </div>
          <div class="form-group">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" class="form-control" name="email" id="editUserEmail" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Role <span class="required">*</span></label>
            <select class="form-control" name="role" id="editUserRole" required>
              <option value="administrator">Administrator</option>
              <option value="testing_engineer">Testing Engineer</option>
              <option value="lab_technician">Lab Technician</option>
              <option value="quality_manager">Quality Manager</option>
              <option value="auditor">Auditor</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select class="form-control" name="department_id" id="editUserDept">
              <option value="">Cross-department</option>
              <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-control" name="status" id="editUserStatus">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" id="editUserPassword" autocomplete="new-password" placeholder="Leave blank to keep current">
            <span class="form-hint">Only fill this to change the user's password.</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function editUser(id, name, email, role, deptId, status) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editUserName').value = name;
  document.getElementById('editUserEmail').value = email;
  document.getElementById('editUserRole').value = role;
  document.getElementById('editUserDept').value = deptId || '';
  document.getElementById('editUserStatus').value = status;
  var passField = document.getElementById('editUserPassword');
  passField.value = '';
  passField.setAttribute('type', 'text');
  setTimeout(function(){ passField.setAttribute('type', 'password'); passField.value = ''; }, 50);
  openModal('editUserModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
