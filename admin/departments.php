<?php
declare(strict_types=1);
// Departments — CRUD with usage tracking (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Departments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Configuration', 'url' => '#'],
    ['label' => 'Departments', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR);

$pdo = Database::getConnection();
$wsId = auth_workspace_id();

// Handle POST actions
if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';

    $validRoles = ['administrator', 'testing_engineer', 'lab_technician', 'quality_manager', 'auditor'];

    if ($action === 'add_dept' && can('manage_departments')) {
        $name = trim($_POST['dept_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name === '' || strlen($name) < 2 || strlen($name) > 100) {
            flash_error('Department name must be 2-100 characters.');
        } elseif (preg_match('/[^A-Za-z0-9\s\-\/&()]/', $name)) {
            flash_error('Department name contains invalid characters.');
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE LOWER(name) = LOWER(:n) AND workspace_id = :ws');
            $dup->execute(['n' => $name, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) {
                flash_error('A department with this name already exists.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO departments (workspace_id, name, description) VALUES (:ws, :name, :desc)');
                $stmt->execute(['ws' => $wsId, 'name' => $name, 'desc' => $desc ?: null]);
                $newId = (int) $pdo->lastInsertId();
                audit_log($pdo, auth_id(), 'department.create', 'department', $newId, null, ['name' => $name]);
                flash_success("Department \"{$name}\" created.");
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'edit_dept' && can('manage_departments')) {
        $id = (int) ($_POST['dept_id'] ?? 0);
        $name = trim($_POST['dept_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        if (!$id || $name === '' || strlen($name) < 2) {
            flash_error('Department name is required (min 2 characters).');
        } elseif (!in_array($status, ['active', 'inactive'])) {
            flash_error('Invalid status.');
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE LOWER(name) = LOWER(:n) AND id != :id AND workspace_id = :ws');
            $dup->execute(['n' => $name, 'id' => $id, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) {
                flash_error('Another department with this name already exists.');
            } else {
                $pdo->prepare('UPDATE departments SET name = :name, description = :desc, status = :status WHERE id = :id AND workspace_id = :ws')
                    ->execute(['name' => $name, 'desc' => $desc ?: null, 'status' => $status, 'id' => $id, 'ws' => $wsId]);
                audit_log($pdo, auth_id(), 'department.update', 'department', $id, null, ['name' => $name]);
                flash_success('Department updated.');
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'add_user' && can('manage_users')) {
        require_once __DIR__ . '/../includes/mail.php';
        $name = trim(preg_replace('/\s+/', ' ', $_POST['full_name'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        $deptId = (int) ($_POST['department_id'] ?? 0);
        $err = null;

        $addUserRoles = [ROLE_TESTING_ENGINEER, ROLE_LAB_TECHNICIAN, ROLE_QUALITY_MANAGER, ROLE_AUDITOR];

        $nameErr = validate_human_name($name);
        if ($nameErr) $err = $nameErr;
        elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'A valid email address is required.';
        elseif (!in_array($role, $addUserRoles, true)) $err = 'Please select a valid role.';

        if (!$err) {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e AND workspace_id = :ws');
            $dup->execute(['e' => $email, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) $err = 'A user with this email already exists in this workspace.';
        }

        if (!$err) {
            $globalDup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
            $globalDup->execute(['e' => $email]);
            if ((int) $globalDup->fetchColumn() > 0) $err = 'This email is already registered in another workspace.';
        }

        if ($err) {
            flash_error($err);
        } else {
            $tempHash = password_hash(bin2hex(random_bytes(20)), PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO users (workspace_id, name, email, password_hash, role, department_id, status, invitation_status) VALUES (:ws, :name, :email, :hash, :role, :dept, :status, :inv)')
                ->execute(['ws' => $wsId, 'name' => $name, 'email' => $email, 'hash' => $tempHash, 'role' => $role, 'dept' => $deptId ?: null, 'status' => 'inactive', 'inv' => 'pending']);
            $newId = (int) $pdo->lastInsertId();

            $token = create_invitation($pdo, $newId, auth_id());
            $deptName = '—';
            if ($deptId) {
                $dStmt = $pdo->prepare('SELECT name FROM departments WHERE id = :id AND workspace_id = :ws');
                $dStmt->execute(['id' => $deptId, 'ws' => $wsId]);
                $deptName = $dStmt->fetchColumn() ?: '—';
            }
            $mailResult = send_invitation_email($email, $name, $role, $deptName, auth_user()['name'], $token);

            audit_log($pdo, auth_id(), 'user.invite', 'user', $newId, null, ['name' => $name, 'email' => $email, 'role' => $role]);

            if ($mailResult['success']) {
                flash_success("Invitation sent to \"{$name}\" ({$email}).");
            } elseif (!empty($mailResult['logged'])) {
                flash_error("User created, but email delivery is not configured. Invitation logged to server. Configure SMTP to send real emails.");
            } else {
                flash_error("User created, but the invitation email could not be sent. You can resend from the Users page.");
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'edit_user' && can('manage_users')) {
        $id = (int) ($_POST['user_id'] ?? 0);
        $name = trim(preg_replace('/\s+/', ' ', $_POST['full_name'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $err = null;
        if (!$id) $err = 'Invalid user.';
        if (!$err) { $nameErr = validate_human_name($name); if ($nameErr) $err = $nameErr; }
        if (!$err && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) $err = 'A valid email address is required.';
        if (!$err && !in_array($role, $validRoles, true)) $err = 'Please select a valid role.';
        if (!$err && !in_array($status, ['active', 'inactive'], true)) $err = 'Invalid status.';

        if (!$err) {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e AND id != :id AND workspace_id = :ws');
            $dup->execute(['e' => $email, 'id' => $id, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) $err = 'Another user with this email already exists.';
        }

        if ($err) {
            flash_error($err);
        } else {
            $deptIdForUser = (int) ($_POST['department_id'] ?? 0);
            $pdo->prepare('UPDATE users SET name = :name, email = :email, role = :role, department_id = :dept, status = :status WHERE id = :id AND workspace_id = :ws')
                ->execute(['name' => $name, 'email' => $email, 'role' => $role, 'dept' => $deptIdForUser ?: null, 'status' => $status, 'id' => $id, 'ws' => $wsId]);
            audit_log($pdo, auth_id(), 'user.update', 'user', $id, null, ['name' => $name, 'role' => $role]);
            flash_success('User updated.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

// Fetch departments with stats
$deptStmt = $pdo->prepare("
    SELECT d.*,
           (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.role IN ('lab_technician','testing_engineer') AND u.status = 'active') AS tester_count,
           (SELECT COUNT(*) FROM testing_records tr WHERE tr.department_id = d.id AND tr.status IN ('assigned','in_testing')) AS active_tests
    FROM departments d
    WHERE d.workspace_id = :ws
    ORDER BY d.status ASC, d.name ASC
");
$deptStmt->execute(['ws' => $wsId]);
$departments = $deptStmt->fetchAll();

// Fetch all users
$usersStmt = $pdo->prepare("
    SELECT u.*, d.name AS dept_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.workspace_id = :ws
    ORDER BY u.name ASC
");
$usersStmt->execute(['ws' => $wsId]);
$users = $usersStmt->fetchAll();

// Dept list for selects
$deptListStmt = $pdo->prepare("SELECT id, name FROM departments WHERE status = 'active' AND workspace_id = :ws ORDER BY name");
$deptListStmt->execute(['ws' => $wsId]);
$deptList = $deptListStmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="14" height="14" rx="2"/><line x1="3" y1="10" x2="17" y2="10"/><line x1="10" y1="3" x2="10" y2="10"/></svg>
      </div>
      <div>
        <h1 class="page-title">Departments</h1>
        <p class="page-subtitle" style="margin-top:2px;">Manage lab departments and their testing teams</p>
      </div>
    </div>
  </div>
  <?php if (can('manage_departments')): ?>
  <div class="page-actions">
    <button type="button" class="btn btn-primary" onclick="openModal('addDeptModal')">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      Add Department
    </button>
  </div>
  <?php endif; ?>
</div>

<!-- Department Cards Grid -->
<div class="dept-cards-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
  <?php foreach ($departments as $dept):
    $deptInitials = '';
    $words = explode(' ', $dept['name']);
    foreach ($words as $w) { if ($w) $deptInitials .= strtoupper($w[0]); }
  ?>
  <div class="card card-hover dept-card" style="cursor:pointer;">
    <div class="card-body">
      <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;">
        <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8125rem;letter-spacing:0.02em;flex-shrink:0;">
          <?= e($deptInitials) ?>
        </div>
        <div style="min-width:0;flex:1;">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <h3 style="font-size:1rem;font-weight:600;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($dept['name']) ?></h3>
            <?= status_badge($dept['status']) ?>
          </div>
          <?php if (!empty($dept['description'])): ?>
          <p class="text-soft" style="font-size:0.75rem;margin:4px 0 0;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= e($dept['description']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding-top:16px;border-top:1px solid var(--line);">
        <div style="text-align:center;">
          <div style="font-weight:700;font-size:1.25rem;font-variant-numeric:tabular-nums;color:var(--ink);"><?= (int) $dept['tester_count'] ?></div>
          <div style="font-size:0.6875rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.06em;">Testers</div>
        </div>
        <div style="text-align:center;">
          <div style="font-weight:700;font-size:1.25rem;font-variant-numeric:tabular-nums;color:var(--warning);"><?= (int) $dept['active_tests'] ?></div>
          <div style="font-size:0.6875rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.06em;">Active Tests</div>
        </div>
      </div>
    </div>
    <?php if (can('manage_departments')): ?>
    <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
      <button type="button" class="btn btn-sm btn-ghost" onclick="event.stopPropagation();editDept(<?= (int) $dept['id'] ?>, <?= e(json_encode($dept['name'])) ?>, <?= e(json_encode($dept['description'] ?? '')) ?>, '<?= e($dept['status']) ?>')">Edit</button>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Users Table -->
<div style="margin-top:32px;">
  <div class="d-flex align-center justify-between" style="margin-bottom:16px;">
    <h2 style="font-size:1.25rem;">All Users</h2>
    <?php if (can('manage_users')): ?>
    <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('addTesterModal')">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      Add User
    </button>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th class="sortable">Name</th>
          <th class="sortable">Email</th>
          <th class="sortable">Role</th>
          <th class="sortable">Department</th>
          <th class="sortable">Status</th>
          <th class="sortable">Last Login</th>
          <th class="col-actions"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-center gap-10">
              <span class="user-avatar" style="width:32px;height:32px;font-size:0.6875rem;"><?= e(user_initials($u['name'])) ?></span>
              <strong><?= e($u['name']) ?></strong>
            </div>
          </td>
          <td class="text-mono text-soft" style="font-size:0.8125rem;"><?= e($u['email']) ?></td>
          <td><?= e(role_label($u['role'])) ?></td>
          <td><?= e($u['dept_name'] ?? '—') ?></td>
          <td><?= status_badge($u['status']) ?></td>
          <td class="text-soft" style="font-size:0.8125rem;"><?= $u['last_login_at'] ? time_ago($u['last_login_at']) : 'Never' ?></td>
          <td class="col-actions">
            <?php if (can('manage_users')): ?>
            <button type="button" class="btn btn-sm btn-ghost" onclick="editUser(<?= (int) $u['id'] ?>, <?= e(json_encode($u['name'])) ?>, <?= e(json_encode($u['email'])) ?>, '<?= e($u['role']) ?>', '<?= e($u['status']) ?>')">Edit</button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Department Modal -->
<div class="modal-backdrop" id="addDeptModal" hidden>
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Add Department</h3>
      <button type="button" class="modal-close" onclick="closeModal('addDeptModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_dept">
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Department Name <span class="required">*</span></label>
        <input type="text" class="form-control" name="dept_name" placeholder="e.g. Temperature Lab" required data-validate="required|minlength:2">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" rows="2" name="description" placeholder="Brief description" data-validate="maxlength:500"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('addDeptModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Create Department</button>
    </div>
    </form>
  </div>
</div>

<!-- Edit Department Modal -->
<div class="modal-backdrop" id="editDeptModal" hidden>
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Edit Department</h3>
      <button type="button" class="modal-close" onclick="closeModal('editDeptModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_dept">
      <input type="hidden" name="dept_id" id="editDeptId">
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Department Name <span class="required">*</span></label>
        <input type="text" class="form-control" name="dept_name" id="editDeptName" required data-validate="required|minlength:2">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" rows="2" name="description" id="editDeptDesc" data-validate="maxlength:500"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select class="form-control" name="status" id="editDeptStatus">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('editDeptModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal-backdrop" id="editUserModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Edit User</h3>
      <button type="button" class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_user">
      <input type="hidden" name="user_id" id="editUserId">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="full_name" id="editUserName" required data-validate="required|alpha_space|minlength:3">
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" class="form-control" name="email" id="editUserEmail" required data-validate="required|email">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role <span class="required">*</span></label>
          <select class="form-control" name="role" id="editUserRole" required data-validate="required">
            <option value="administrator">Administrator</option>
            <option value="testing_engineer">Testing Engineer</option>
            <option value="lab_technician">Lab Technician</option>
            <option value="quality_manager">Quality Manager</option>
            <option value="auditor">Auditor</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status" id="editUserStatus">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
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

<!-- Add User Modal -->
<div class="modal-backdrop" id="addTesterModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Add User</h3>
      <button type="button" class="modal-close" onclick="closeModal('addTesterModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_user">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="full_name" placeholder="e.g. Maria Santos" required data-validate="required|alpha_space|minlength:3">
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" class="form-control" name="email" placeholder="e.g. m.santos@labauto.local" required data-validate="required|email">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role <span class="required">*</span></label>
          <select class="form-control" name="role" required data-validate="required">
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
            <option value="">No department</option>
            <?php foreach ($deptList as $d): ?>
            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="padding:12px 16px;border-radius:var(--radius-md);background:var(--info-tint);font-size:0.8125rem;color:var(--info);line-height:1.5;margin-top:8px;">
        An invitation email will be sent. The user will set their own password.
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('addTesterModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Send Invitation</button>
    </div>
    </form>
  </div>
</div>

<script>
function editDept(id, name, desc, status) {
  document.getElementById('editDeptId').value = id;
  document.getElementById('editDeptName').value = name;
  document.getElementById('editDeptDesc').value = desc;
  document.getElementById('editDeptStatus').value = status;
  openModal('editDeptModal');
}
function editUser(id, name, email, role, status) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editUserName').value = name;
  document.getElementById('editUserEmail').value = email;
  document.getElementById('editUserRole').value = role;
  document.getElementById('editUserStatus').value = status;
  openModal('editUserModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
