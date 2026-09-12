<?php
declare(strict_types=1);
// Product registration form — serial number, model, department (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Register Product';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Products', 'url' => url('products/index.php')],
    ['label' => 'Register', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER);

$pdo = Database::getConnection();
$wsId = auth_workspace_id();
$errors = $_SESSION['_form_errors'] ?? [];
$old = $_SESSION['_form_old'] ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_form_old']);

if (is_post()) {
    require_csrf();
    $serial   = strtoupper(trim($_POST['serial_number'] ?? ''));
    $model    = trim($_POST['model'] ?? '');
    $deptId   = (int) ($_POST['department_id'] ?? 0);
    $mfgDate  = $_POST['manufactured_date'] ?? '';
    $notes    = trim($_POST['notes'] ?? '');

    $old = compact('serial', 'model', 'deptId', 'mfgDate', 'notes');

    if ($serial === '') $errors[] = 'Serial number is required.';
    elseif (strlen($serial) < 3 || strlen($serial) > 50) $errors[] = 'Serial number must be 3-50 characters.';
    elseif (!preg_match('/[A-Za-z]/', $serial)) $errors[] = 'Serial number must contain at least one letter.';
    elseif (!preg_match('/^[A-Za-z0-9\-_\/]+$/', $serial)) $errors[] = 'Serial number may only contain letters, numbers, hyphens, underscores, and slashes.';
    if ($model === '')  $errors[] = 'Model / specification is required.';
    elseif (strlen($model) < 2 || strlen($model) > 200) $errors[] = 'Model must be 2-200 characters.';
    elseif (!preg_match('/[A-Za-z]/', $model)) $errors[] = 'Model must contain at least one letter.';
    if ($deptId === 0)  $errors[] = 'Department is required.';

    if (!$errors) {
        $dup = $pdo->prepare('SELECT COUNT(*) FROM products WHERE serial_number = :sn AND workspace_id = :ws');
        $dup->execute(['sn' => $serial, 'ws' => $wsId]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors[] = 'A product with this serial number already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO products (workspace_id, serial_number, spec_model, department_id, manufactured_at, status, registered_by, notes) VALUES (:ws, :sn, :model, :dept, :mfg, :status, :uid, :notes)');
        $stmt->execute([
            'ws' => $wsId, 'sn' => $serial, 'model' => $model, 'dept' => $deptId,
            'mfg' => $mfgDate ?: null, 'status' => 'registered', 'uid' => auth_id(),
            'notes' => $notes ?: null,
        ]);
        $newId = (int) $pdo->lastInsertId();
        audit_log($pdo, auth_id(), 'product.register', 'product', $newId, null, ['serial_number' => $serial, 'spec_model' => $model]);
        create_notification($pdo, auth_id(), 'Product registered', "You registered {$model} ({$serial}).", 'success', BASE_URL . '/products/detail.php?id=' . $newId);

        flash_success("Product {$serial} registered successfully.");
        header('Location: ' . url('products/detail.php?id=' . $newId));
        exit;
    }

    $_SESSION['_form_errors'] = $errors;
    $_SESSION['_form_old'] = $old;
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$deptListStmt = $pdo->prepare("SELECT id, name FROM departments WHERE workspace_id = :ws AND status = 'active' ORDER BY name");
$deptListStmt->execute(['ws' => $wsId]);
$deptList = $deptListStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <a href="<?= url('products/index.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:8px;">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
      Back to Products
    </a>
    <div class="d-flex align-center gap-12">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><line x1="10" y1="4" x2="10" y2="16"/><line x1="4" y1="10" x2="16" y2="10"/></svg>
      </div>
      <div>
        <h1 class="page-title">Register Product</h1>
        <p class="page-subtitle" style="margin-top:2px;">Add a new product to the testing pipeline</p>
      </div>
    </div>
  </div>
</div>

<div style="max-width:720px;">
  <?php if ($errors): ?>
  <div style="padding:12px 16px;border-radius:var(--radius-md);background:var(--fail-tint);color:var(--fail);font-size:0.8125rem;margin-bottom:20px;">
    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body" style="padding:32px;">
      <form method="post" action="" novalidate>
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="serial">Serial Number <span class="required">*</span></label>
            <input type="text" class="form-control" id="serial" name="serial_number"
                   placeholder="e.g. FZ-2026-0463" style="text-transform:uppercase;"
                   value="<?= e($old['serial'] ?? '') ?>" required data-validate="required">
            <span class="form-hint">Unique identifier. Uppercase letters, numbers, hyphens.</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="model">Model / Specification <span class="required">*</span></label>
            <input type="text" class="form-control" id="model" name="model"
                   placeholder="e.g. Transformer T-400kV"
                   value="<?= e($old['model'] ?? '') ?>" required data-validate="required">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="department">Department <span class="required">*</span></label>
            <select class="form-control" id="department" name="department_id" required>
              <option value="">Select department…</option>
              <?php foreach ($deptList as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($old['deptId'] ?? 0) == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="manufactured_date">Manufactured Date</label>
            <input type="date" class="form-control" id="manufactured_date" name="manufactured_date"
                   max="<?= date('Y-m-d') ?>" value="<?= e($old['mfgDate'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="notes">Notes</label>
          <textarea class="form-control" id="notes" name="notes" rows="3"
                    placeholder="Priority level, customer info, special requirements…"><?= e($old['notes'] ?? '') ?></textarea>
          <div class="char-count"><span id="notesCount"><?= strlen($old['notes'] ?? '') ?></span> / 500</div>
        </div>

        <div style="border-top:1px solid var(--line);padding-top:20px;margin-top:8px;display:flex;justify-content:flex-end;gap:8px;">
          <a href="<?= url('products/index.php') ?>" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Register Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('notes').addEventListener('input', function() {
  var count = this.value.length;
  var el = document.getElementById('notesCount');
  el.textContent = count;
  el.parentElement.className = 'char-count' + (count > 450 ? (count > 500 ? ' over' : ' warn') : '');
});
document.getElementById('serial').addEventListener('input', function() {
  this.value = this.value.toUpperCase();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
