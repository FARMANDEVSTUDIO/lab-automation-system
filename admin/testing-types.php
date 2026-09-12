<?php
declare(strict_types=1);
// Testing types — configuration with parameter management (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_role(ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER);
$pdo = Database::getConnection();
$wsId = auth_workspace_id();

// Handle POST actions BEFORE any HTML output
if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && can('manage_testing_types')) {
        $name = trim($_POST['type_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $validCategories = ['Electrical', 'Mechanical', 'Environmental'];

        if ($name === '' || strlen($name) < 2) {
            flash_error('Testing type name is required (min 2 characters).');
        } elseif (!in_array($category, $validCategories, true)) {
            flash_error('Please select a valid category.');
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM testing_types WHERE LOWER(name) = LOWER(:n) AND workspace_id = :ws');
            $dup->execute(['n' => $name, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) {
                flash_error('A testing type with this name already exists.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO testing_types (workspace_id, name, category, description, version, status, created_by) VALUES (:ws, :name, :cat, :desc, 1, :status, :uid)');
                $stmt->execute([
                    'ws' => $wsId, 'name' => $name, 'cat' => $category, 'desc' => $description ?: null,
                    'status' => 'active', 'uid' => auth_id(),
                ]);
                $newId = (int) $pdo->lastInsertId();
                audit_log($pdo, auth_id(), 'testing_type.create', 'testing_type', $newId, null, ['name' => $name, 'category' => $category]);
                flash_success('Testing type created successfully.');
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'edit' && can('manage_testing_types')) {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['type_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $validCategories = ['Electrical', 'Mechanical', 'Environmental'];

        if (!$id || $name === '' || strlen($name) < 2) {
            flash_error('Testing type name is required (min 2 characters).');
        } elseif (!in_array($category, $validCategories, true)) {
            flash_error('Please select a valid category.');
        } elseif (!in_array($status, ['active', 'archived'], true)) {
            flash_error('Invalid status.');
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM testing_types WHERE LOWER(name) = LOWER(:n) AND id != :id AND workspace_id = :ws');
            $dup->execute(['n' => $name, 'id' => $id, 'ws' => $wsId]);
            if ((int) $dup->fetchColumn() > 0) {
                flash_error('Another testing type with this name already exists.');
            } else {
                $old = $pdo->prepare('SELECT * FROM testing_types WHERE id = :id AND workspace_id = :ws');
                $old->execute(['id' => $id, 'ws' => $wsId]);
                $before = $old->fetch();

                $stmt = $pdo->prepare('UPDATE testing_types SET name = :name, category = :cat, description = :desc, status = :status WHERE id = :id AND workspace_id = :ws');
                $stmt->execute(['name' => $name, 'cat' => $category, 'desc' => $description ?: null, 'status' => $status, 'id' => $id, 'ws' => $wsId]);

                audit_log($pdo, auth_id(), 'testing_type.update', 'testing_type', $id,
                    ['name' => $before['name']], ['name' => $name]);
                flash_success('Testing type updated.');
            }
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'add_parameter' && can('manage_testing_types')) {
        $typeId = (int) ($_POST['testing_type_id'] ?? 0);
        $paramName = trim($_POST['param_name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $minVal = $_POST['min_value'] ?? '';
        $nomVal = $_POST['nominal_value'] ?? '';
        $maxVal = $_POST['max_value'] ?? '';
        $instrument = trim($_POST['required_instrument'] ?? '');

        $typeCheck = $pdo->prepare('SELECT id FROM testing_types WHERE id = :id AND workspace_id = :ws');
        $typeCheck->execute(['id' => $typeId, 'ws' => $wsId]);

        if (!$typeCheck->fetch()) {
            flash_error('Invalid testing type.');
        } elseif ($paramName === '' || strlen($paramName) < 2) {
            flash_error('Parameter name is required (min 2 characters).');
        } elseif ($unit === '') {
            flash_error('Unit is required.');
        } else {
            $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM testing_type_parameters WHERE testing_type_id = :tid');
            $sortStmt->execute(['tid' => $typeId]);
            $nextSort = (int) $sortStmt->fetchColumn();

            $pdo->prepare('INSERT INTO testing_type_parameters (testing_type_id, name, unit, min_value, nominal_value, max_value, required_instrument, sort_order) VALUES (:tid, :name, :unit, :min, :nom, :max, :inst, :sort)')
                ->execute([
                    'tid' => $typeId, 'name' => $paramName, 'unit' => $unit,
                    'min' => $minVal !== '' ? (float) $minVal : null,
                    'nom' => $nomVal !== '' ? (float) $nomVal : null,
                    'max' => $maxVal !== '' ? (float) $maxVal : null,
                    'inst' => $instrument ?: null, 'sort' => $nextSort,
                ]);
            audit_log($pdo, auth_id(), 'parameter.create', 'testing_type', $typeId, null, ['param' => $paramName, 'unit' => $unit]);
            flash_success("Parameter \"{$paramName}\" added.");
        }
        header('Location: ' . url('admin/testing-types.php?expand=' . $typeId)); exit;
    }

    if ($action === 'edit_parameter' && can('manage_testing_types')) {
        $paramId = (int) ($_POST['param_id'] ?? 0);
        $paramName = trim($_POST['param_name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $minVal = $_POST['min_value'] ?? '';
        $nomVal = $_POST['nominal_value'] ?? '';
        $maxVal = $_POST['max_value'] ?? '';
        $instrument = trim($_POST['required_instrument'] ?? '');

        $paramCheck = $pdo->prepare('SELECT ttp.*, tt.workspace_id FROM testing_type_parameters ttp JOIN testing_types tt ON ttp.testing_type_id = tt.id WHERE ttp.id = :pid');
        $paramCheck->execute(['pid' => $paramId]);
        $existingParam = $paramCheck->fetch();

        if (!$existingParam || (int) $existingParam['workspace_id'] !== $wsId) {
            flash_error('Parameter not found.');
        } elseif ($paramName === '' || strlen($paramName) < 2) {
            flash_error('Parameter name is required (min 2 characters).');
        } elseif ($unit === '') {
            flash_error('Unit is required.');
        } else {
            $pdo->prepare('UPDATE testing_type_parameters SET name = :name, unit = :unit, min_value = :min, nominal_value = :nom, max_value = :max, required_instrument = :inst WHERE id = :id')
                ->execute([
                    'name' => $paramName, 'unit' => $unit,
                    'min' => $minVal !== '' ? (float) $minVal : null,
                    'nom' => $nomVal !== '' ? (float) $nomVal : null,
                    'max' => $maxVal !== '' ? (float) $maxVal : null,
                    'inst' => $instrument ?: null, 'id' => $paramId,
                ]);
            audit_log($pdo, auth_id(), 'parameter.update', 'testing_type', (int) $existingParam['testing_type_id'], null, ['param' => $paramName]);
            flash_success("Parameter \"{$paramName}\" updated.");
            header('Location: ' . url('admin/testing-types.php?expand=' . $existingParam['testing_type_id'])); exit;
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'delete_parameter' && can('manage_testing_types')) {
        $paramId = (int) ($_POST['param_id'] ?? 0);
        $paramCheck = $pdo->prepare('SELECT ttp.*, tt.workspace_id FROM testing_type_parameters ttp JOIN testing_types tt ON ttp.testing_type_id = tt.id WHERE ttp.id = :pid');
        $paramCheck->execute(['pid' => $paramId]);
        $existingParam = $paramCheck->fetch();

        if (!$existingParam || (int) $existingParam['workspace_id'] !== $wsId) {
            flash_error('Parameter not found.');
        } else {
            $measCount = $pdo->prepare('SELECT COUNT(*) FROM measurements WHERE parameter_id = :pid');
            $measCount->execute(['pid' => $paramId]);
            if ((int) $measCount->fetchColumn() > 0) {
                flash_error('Cannot delete this parameter — it has recorded measurements. Edit it instead.');
            } else {
                $pdo->prepare('DELETE FROM testing_type_parameters WHERE id = :id')->execute(['id' => $paramId]);
                audit_log($pdo, auth_id(), 'parameter.delete', 'testing_type', (int) $existingParam['testing_type_id'], ['param' => $existingParam['name']], null);
                flash_success("Parameter \"{$existingParam['name']}\" deleted.");
            }
        }
        header('Location: ' . url('admin/testing-types.php?expand=' . ($existingParam['testing_type_id'] ?? ''))); exit;
    }
}

$pageTitle = 'Testing Types';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Configuration', 'url' => '#'],
    ['label' => 'Testing Types', 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';

// Fetch testing types with counts
$typesStmt = $pdo->prepare("
    SELECT tt.*,
           u.name AS creator_name,
           (SELECT COUNT(*) FROM testing_type_parameters ttp WHERE ttp.testing_type_id = tt.id) AS param_count,
           (SELECT COUNT(*) FROM testing_records tr WHERE tr.testing_type_id = tt.id) AS record_count
    FROM testing_types tt
    LEFT JOIN users u ON tt.created_by = u.id
    WHERE tt.workspace_id = :ws
    ORDER BY tt.status ASC, tt.name ASC
");
$typesStmt->execute(['ws' => $wsId]);
$testingTypes = $typesStmt->fetchAll();

// Expanded type parameters
$expandId = (int) ($_GET['expand'] ?? 0);
$expandType = null;
$expandParams = [];
if ($expandId) {
    foreach ($testingTypes as $tt) {
        if ($tt['id'] === $expandId) {
            $expandType = $tt;
            break;
        }
    }
    if ($expandType) {
        $paramStmt = $pdo->prepare('SELECT * FROM testing_type_parameters WHERE testing_type_id = :id ORDER BY sort_order ASC');
        $paramStmt->execute(['id' => $expandId]);
        $expandParams = $paramStmt->fetchAll();
    }
}

// Categories for filter
$catStmt = $pdo->prepare("SELECT DISTINCT category FROM testing_types WHERE category IS NOT NULL AND workspace_id = :ws ORDER BY category");
$catStmt->execute(['ws' => $wsId]);
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
  <div class="page-header-left">
    <a href="<?= url('dashboard/index.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:8px;">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
      Back to Dashboard
    </a>
    <div class="d-flex align-center gap-12">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="14" height="5" rx="1.5"/><rect x="3" y="11" width="14" height="5" rx="1.5"/></svg>
      </div>
      <div>
        <h1 class="page-title">Testing Types</h1>
        <p class="page-subtitle" style="margin-top:2px;">Define test definitions, parameters, tolerance bands, and instruments</p>
      </div>
    </div>
  </div>
  <div class="page-actions">
    <?php if (can('manage_testing_types')): ?>
    <button class="btn btn-primary" onclick="openModal('addTypeModal')">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      New Testing Type
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Filters -->
<div class="filter-bar">
  <div class="filter-search">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
    <input type="text" class="form-control" placeholder="Search testing types…">
  </div>
  <select class="form-control" style="width:160px;">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
    <option><?= e($cat) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="form-control" style="width:140px;">
    <option value="">All Status</option>
    <option>Active</option>
    <option>Archived</option>
  </select>
</div>

<!-- Testing Types Table -->
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th class="sortable">Name</th>
        <th class="sortable">Category</th>
        <th class="col-num sortable">Version</th>
        <th class="col-num">Parameters</th>
        <th class="col-num">Records</th>
        <th>Created By</th>
        <th class="sortable">Status</th>
        <th class="sortable">Last Updated</th>
        <th class="col-actions"></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($testingTypes)): ?>
      <tr><td colspan="9" style="text-align:center;color:var(--ink-faint);padding:24px;">No testing types defined.</td></tr>
      <?php else: ?>
      <?php foreach ($testingTypes as $tt): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;
              <?= $tt['category'] === 'Electrical' ? 'background:var(--warning-tint);color:var(--warning);' : ($tt['category'] === 'Mechanical' ? 'background:var(--info-tint);color:var(--info);' : 'background:var(--success-tint);color:var(--success);') ?>">
              <?php if ($tt['category'] === 'Electrical'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="9,2 5,9 8,9 7,14 11,7 8,7 9,2"/></svg>
              <?php elseif ($tt['category'] === 'Mechanical'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="2"/><path d="M8 2v2M8 12v2M2 8h2M12 8h2"/></svg>
              <?php else: ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 14a6 6 0 100-12 6 6 0 000 12z"/><path d="M8 6v4"/></svg>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-weight:600;"><?= e($tt['name']) ?></div>
              <div class="text-faint" style="font-size:0.6875rem;">v<?= (int) $tt['version'] ?></div>
            </div>
          </div>
        </td>
        <td>
          <span style="padding:2px 8px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:500;
            <?= $tt['category'] === 'Electrical' ? 'background:var(--warning-tint);color:var(--warning);' : ($tt['category'] === 'Mechanical' ? 'background:var(--info-tint);color:var(--info);' : 'background:var(--success-tint);color:var(--success);') ?>">
            <?= e($tt['category'] ?? 'Other') ?>
          </span>
        </td>
        <td class="col-num text-mono">v<?= (int) $tt['version'] ?></td>
        <td class="col-num">
          <a href="<?= url('admin/testing-types.php?expand=' . $tt['id']) ?>" style="font-weight:600;<?= (int) $tt['param_count'] === 0 ? 'color:var(--fail);' : '' ?>">
            <?= (int) $tt['param_count'] ?>
          </a>
        </td>
        <td class="col-num"><?= (int) $tt['record_count'] ?></td>
        <td class="text-soft"><?= e($tt['creator_name'] ?? '—') ?></td>
        <td><?= status_badge($tt['status']) ?></td>
        <td class="text-soft" style="font-size:0.8125rem;"><?= format_date($tt['updated_at']) ?></td>
        <td class="col-actions">
          <div class="d-flex gap-4">
            <a href="<?= url('admin/testing-types.php?expand=' . $tt['id']) ?>" class="btn btn-sm btn-ghost" title="View parameters">Params</a>
            <?php if (can('manage_testing_types')): ?>
            <button class="btn btn-sm btn-ghost" onclick="editType(<?= (int) $tt['id'] ?>, <?= e(json_encode($tt['name'])) ?>, <?= e(json_encode($tt['category'] ?? '')) ?>, <?= e(json_encode($tt['description'] ?? '')) ?>, '<?= e($tt['status']) ?>')">Edit</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Expanded Parameters Section -->
<?php if ($expandType): ?>
<div style="margin-top:32px;" id="params-section">
  <div class="card">
    <div class="card-header">
      <div class="d-flex align-center gap-12">
        <h3 class="card-title"><?= e($expandType['name']) ?> — Parameters</h3>
        <span style="font-size:0.75rem;color:var(--ink-faint);background:var(--paper);padding:2px 8px;border-radius:var(--radius-sm);">v<?= (int) $expandType['version'] ?> · <?= ucfirst($expandType['status']) ?></span>
      </div>
      <?php if (can('manage_testing_types')): ?>
      <button class="btn btn-sm btn-primary" onclick="openModal('addParamModal')">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
        Add Parameter
      </button>
      <?php endif; ?>
    </div>
    <?php if (empty($expandParams)): ?>
    <div class="card-body" style="text-align:center;padding:32px 24px;">
      <div style="color:var(--ink-faint);font-size:0.875rem;">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4;margin-bottom:8px;"><circle cx="20" cy="20" r="16"/><line x1="20" y1="12" x2="20" y2="22"/><circle cx="20" cy="27" r="1.5" fill="currentColor"/></svg>
        <p style="font-weight:600;margin-bottom:4px;">No parameters defined</p>
        <p>This testing type has no parameters. Measurements cannot be recorded until at least one parameter is added.</p>
      </div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Parameter</th>
            <th class="col-num">Min</th>
            <th class="col-num">Nominal</th>
            <th class="col-num">Max</th>
            <th>Unit</th>
            <th>Required Instrument</th>
            <?php if (can('manage_testing_types')): ?>
            <th class="col-actions"></th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($expandParams as $i => $param): ?>
          <tr>
            <td class="col-num"><?= $i + 1 ?></td>
            <td><strong><?= e($param['name']) ?></strong></td>
            <td class="col-num text-mono"><?= $param['min_value'] !== null ? rtrim(rtrim(number_format((float) $param['min_value'], 4), '0'), '.') : '—' ?></td>
            <td class="col-num text-mono"><?= $param['nominal_value'] !== null ? rtrim(rtrim(number_format((float) $param['nominal_value'], 4), '0'), '.') : '—' ?></td>
            <td class="col-num text-mono"><?= $param['max_value'] !== null ? rtrim(rtrim(number_format((float) $param['max_value'], 4), '0'), '.') : '—' ?></td>
            <td class="text-mono"><?= e($param['unit'] ?? '—') ?></td>
            <td><?= e($param['required_instrument'] ?? '—') ?></td>
            <?php if (can('manage_testing_types')): ?>
            <td class="col-actions">
              <div class="d-flex gap-4">
                <button class="btn btn-sm btn-ghost" onclick="editParam(<?= (int) $param['id'] ?>, <?= e(json_encode($param['name'])) ?>, <?= e(json_encode($param['unit'] ?? '')) ?>, <?= e(json_encode($param['min_value'])) ?>, <?= e(json_encode($param['nominal_value'])) ?>, <?= e(json_encode($param['max_value'])) ?>, <?= e(json_encode($param['required_instrument'] ?? '')) ?>)">Edit</button>
                <form method="post" action="" style="margin:0;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_parameter">
                  <input type="hidden" name="param_id" value="<?= (int) $param['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--fail);" onclick="return confirm('Delete parameter <?= e($param['name']) ?>? This cannot be undone.')">Delete</button>
                </form>
              </div>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (can('manage_testing_types')): ?>
<!-- Add Parameter Modal -->
<div class="modal-backdrop" id="addParamModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Add Parameter to <?= e($expandType['name']) ?></h3>
      <button class="modal-close" onclick="closeModal('addParamModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_parameter">
      <input type="hidden" name="testing_type_id" value="<?= (int) $expandType['id'] ?>">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label class="form-label">Parameter Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="param_name" placeholder="e.g. Withstand Voltage" required>
        </div>
        <div class="form-group">
          <label class="form-label">Unit <span class="required">*</span></label>
          <input type="text" class="form-control" name="unit" placeholder="e.g. kV, mA, Ω" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Min Value</label>
          <input type="number" class="form-control" name="min_value" step="0.0001" placeholder="—">
        </div>
        <div class="form-group">
          <label class="form-label">Nominal Value</label>
          <input type="number" class="form-control" name="nominal_value" step="0.0001" placeholder="—">
        </div>
        <div class="form-group">
          <label class="form-label">Max Value</label>
          <input type="number" class="form-control" name="max_value" step="0.0001" placeholder="—">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Required Instrument</label>
        <input type="text" class="form-control" name="required_instrument" placeholder="e.g. Megger, Multimeter, HV Tester">
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('addParamModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Add Parameter</button>
    </div>
    </form>
  </div>
</div>

<!-- Edit Parameter Modal -->
<div class="modal-backdrop" id="editParamModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Edit Parameter</h3>
      <button class="modal-close" onclick="closeModal('editParamModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit_parameter">
      <input type="hidden" name="param_id" id="editParamId">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label class="form-label">Parameter Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="param_name" id="editParamName" required>
        </div>
        <div class="form-group">
          <label class="form-label">Unit <span class="required">*</span></label>
          <input type="text" class="form-control" name="unit" id="editParamUnit" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Min Value</label>
          <input type="number" class="form-control" name="min_value" id="editParamMin" step="0.0001" placeholder="—">
        </div>
        <div class="form-group">
          <label class="form-label">Nominal Value</label>
          <input type="number" class="form-control" name="nominal_value" id="editParamNom" step="0.0001" placeholder="—">
        </div>
        <div class="form-group">
          <label class="form-label">Max Value</label>
          <input type="number" class="form-control" name="max_value" id="editParamMax" step="0.0001" placeholder="—">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Required Instrument</label>
        <input type="text" class="form-control" name="required_instrument" id="editParamInst">
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('editParamModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Add Testing Type Modal -->
<div class="modal-backdrop" id="addTypeModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">New Testing Type</h3>
      <button class="modal-close" onclick="closeModal('addTypeModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="type_name" placeholder="e.g. Thermal Shock Test" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category <span class="required">*</span></label>
          <select class="form-control" name="category" required>
            <option value="">Select…</option>
            <option>Electrical</option><option>Mechanical</option><option>Environmental</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" rows="2" name="description" placeholder="Brief description of what this test measures"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('addTypeModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Create Testing Type</button>
    </div>
    </form>
  </div>
</div>

<!-- Edit Testing Type Modal -->
<div class="modal-backdrop" id="editTypeModal" hidden>
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3 class="modal-title">Edit Testing Type</h3>
      <button type="button" class="modal-close" onclick="closeModal('editTypeModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editTypeId">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="type_name" id="editTypeName" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category <span class="required">*</span></label>
          <select class="form-control" name="category" id="editTypeCat" required>
            <option value="">Select…</option>
            <option>Electrical</option><option>Mechanical</option><option>Environmental</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status" id="editTypeStatus">
            <option value="active">Active</option>
            <option value="archived">Archived</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" rows="2" name="description" id="editTypeDesc"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('editTypeModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
    </form>
  </div>
</div>

<script>
function editType(id, name, category, description, status) {
  document.getElementById('editTypeId').value = id;
  document.getElementById('editTypeName').value = name;
  document.getElementById('editTypeCat').value = category;
  document.getElementById('editTypeDesc').value = description;
  document.getElementById('editTypeStatus').value = status;
  openModal('editTypeModal');
}
function editParam(id, name, unit, min, nom, max, inst) {
  document.getElementById('editParamId').value = id;
  document.getElementById('editParamName').value = name;
  document.getElementById('editParamUnit').value = unit;
  document.getElementById('editParamMin').value = min !== null ? min : '';
  document.getElementById('editParamNom').value = nom !== null ? nom : '';
  document.getElementById('editParamMax').value = max !== null ? max : '';
  document.getElementById('editParamInst').value = inst || '';
  openModal('editParamModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
