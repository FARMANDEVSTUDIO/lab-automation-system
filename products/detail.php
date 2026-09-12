<?php
declare(strict_types=1);
// Product detail — lifecycle tracking, CPRI approval, test assignment (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$pdo = Database::getConnection();
$wsId = auth_workspace_id();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { redirect('products/index.php'); }

// Fetch product (needed for POST processing)
$productStmt = $pdo->prepare('SELECT p.*, d.name AS dept_name, u.name AS registered_by_name FROM products p LEFT JOIN departments d ON p.department_id = d.id LEFT JOIN users u ON p.registered_by = u.id WHERE p.id = :id AND p.workspace_id = :ws');
$productStmt->execute(['id' => $id, 'ws' => $wsId]);
$product = $productStmt->fetch();
if (!$product) {
    $pageTitle = 'Product Not Found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div style="padding:60px;text-align:center;"><h2>Product not found</h2><a href="' . url('products/index.php') . '" class="btn btn-primary">Back to Products</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle POST actions BEFORE any HTML output
if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'edit') {
        if (can('edit_product')) {
            $model = trim($_POST['model'] ?? '');
            $deptId = (int) ($_POST['department_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            if ($model && $deptId) {
                $stmt = $pdo->prepare('UPDATE products SET spec_model = :model, department_id = :dept, notes = :notes WHERE id = :id AND workspace_id = :ws');
                $stmt->execute(['model' => $model, 'dept' => $deptId, 'notes' => $notes ?: null, 'id' => $id, 'ws' => $wsId]);
                audit_log($pdo, auth_id(), 'product.update', 'product', $id, null, ['spec_model' => $model]);
                flash_success('Product updated.');
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            } else {
                flash_error('Model and department are required.');
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            }
        }
    }

    if ($action === 'cpri_decision' && can('cpri_approve')) {
        $decision = $_POST['cpri_decision'] ?? '';
        $cpriNotes = trim($_POST['cpri_notes'] ?? '');
        if ($product['status'] === 'pending_cpri_approval' && in_array($decision, ['approve', 'reject'])) {
            $newStatus = $decision === 'approve' ? 'released' : 'rejected';
            $pdo->prepare('UPDATE products SET status = :s WHERE id = :id AND workspace_id = :ws')
                ->execute(['s' => $newStatus, 'id' => $id, 'ws' => $wsId]);

            $certNum = $decision === 'approve' ? 'CPRI-' . date('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . '-A' : null;
            $pdo->prepare('INSERT INTO cpri_records (product_id, decision, decided_by, reason, certificate_number) VALUES (:pid, :dec, :uid, :reason, :cert)')
                ->execute(['pid' => $id, 'dec' => $decision === 'approve' ? 'approved' : 'rejected', 'uid' => auth_id(), 'reason' => $cpriNotes ?: null, 'cert' => $certNum]);

            audit_log($pdo, auth_id(), 'cpri.' . $decision, 'product', $id, ['status' => 'pending_cpri_approval'], ['status' => $newStatus, 'notes' => $cpriNotes]);
            if ($product['registered_by']) {
                create_notification($pdo, (int) $product['registered_by'], "CPRI {$decision}d", "Product {$product['spec_model']} has been CPRI {$decision}d.", $decision === 'approve' ? 'success' : 'error', BASE_URL . '/products/detail.php?id=' . $id);
            }
            flash_success("Product CPRI {$decision}d.");
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
    }

    if ($action === 'assign_test') {
        if (can('assign_test')) {
            $typeId = (int) ($_POST['testing_type_id'] ?? 0);
            $testerId = (int) ($_POST['tester_id'] ?? 0);
            $dueDate = $_POST['due_date'] ?? '';
            if ($typeId && $testerId && $dueDate) {
                $attemptNum = (int) $product['attempt_count'] + 1;
                $stmt = $pdo->prepare('INSERT INTO testing_records (workspace_id, product_id, testing_type_id, department_id, tester_id, assigned_by, status, attempt_number, due_date) VALUES (:ws, :pid, :tid, :dept, :tester, :assigned_by, :status, :attempt, :due)');
                $stmt->execute([
                    'ws' => $wsId, 'pid' => $id, 'tid' => $typeId, 'dept' => $product['department_id'],
                    'tester' => $testerId, 'assigned_by' => auth_id(), 'status' => 'assigned', 'attempt' => $attemptNum, 'due' => $dueDate,
                ]);
                $trId = (int) $pdo->lastInsertId();
                if ($product['status'] === 'registered') {
                    $pdo->prepare('UPDATE products SET status = :s WHERE id = :id AND workspace_id = :ws')->execute(['s' => 'assigned', 'id' => $id, 'ws' => $wsId]);
                }
                audit_log($pdo, auth_id(), 'testing.assign', 'testing_record', $trId, null, ['product' => $product['spec_model'], 'tester_id' => $testerId]);
                create_notification($pdo, $testerId, 'Test assignment', "You have been assigned to test {$product['spec_model']}.", 'assignment', BASE_URL . '/testing/detail.php?id=' . $trId);
                flash_success('Test assigned successfully.');
                header('Location: ' . $_SERVER['REQUEST_URI']); exit;
            }
        }
    }
}

// Re-fetch product after potential POST changes
$productStmt->execute(['id' => $id, 'ws' => $wsId]);
$product = $productStmt->fetch();

// Now include header (safe — all redirects happened above)
$pageTitle = $product['spec_model'] . ' — ' . $product['serial_number'];
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Products', 'url' => url('products/index.php')],
    ['label' => $product['serial_number'], 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';

// Fetch testing records for this product
$testRecords = $pdo->prepare("
    SELECT tr.*, tt.name AS test_type_name, u.name AS tester_name
    FROM testing_records tr
    JOIN testing_types tt ON tr.testing_type_id = tt.id
    LEFT JOIN users u ON tr.tester_id = u.id
    WHERE tr.product_id = :pid AND tr.workspace_id = :ws
    ORDER BY tr.created_at DESC
");
$testRecords->execute(['pid' => $id, 'ws' => $wsId]);
$testRecords = $testRecords->fetchAll();

// Audit trail for this product
$auditTrail = $pdo->prepare("
    SELECT al.*, u.name AS actor_name
    FROM audit_log al
    LEFT JOIN users u ON al.actor_id = u.id
    WHERE al.workspace_id = :ws
      AND ((al.entity_type = 'product' AND al.entity_id = :pid)
       OR (al.entity_type = 'testing_record' AND al.entity_id IN (SELECT id FROM testing_records WHERE product_id = :pid2 AND workspace_id = :ws2)))
    ORDER BY al.created_at DESC LIMIT 10
");
$auditTrail->execute(['ws' => $wsId, 'pid' => $id, 'pid2' => $id, 'ws2' => $wsId]);
$auditTrail = $auditTrail->fetchAll();

// Attachments
$attachments = $pdo->prepare("SELECT a.*, u.name AS uploader_name FROM attachments a LEFT JOIN users u ON a.uploaded_by = u.id WHERE a.attachable_type = 'product' AND a.attachable_id = :pid ORDER BY a.uploaded_at DESC");
$attachments->execute(['pid' => $id]);
$attachments = $attachments->fetchAll();

// CPRI Records
$cpriRecords = $pdo->prepare("SELECT c.*, u.name AS decided_by_name FROM cpri_records c LEFT JOIN users u ON c.decided_by = u.id WHERE c.product_id = :pid ORDER BY c.decided_at DESC");
$cpriRecords->execute(['pid' => $id]);
$cpriRecords = $cpriRecords->fetchAll();

// Lifecycle stage
$statusStages = ['registered','assigned','in_testing','submitted_for_review','pending_cpri_approval','released'];
$stageIndex = array_search($product['status'], $statusStages);
if ($stageIndex === false) $stageIndex = -1;
$stageLabels = ['Registered','Assigned','In Testing','Review','CPRI Approval','Released'];

// Data for modals
$deptListStmt = $pdo->prepare("SELECT id, name FROM departments WHERE workspace_id = :ws AND status = 'active' ORDER BY name");
$deptListStmt->execute(['ws' => $wsId]);
$deptList = $deptListStmt->fetchAll();

$typeListStmt = $pdo->prepare("SELECT id, name FROM testing_types WHERE workspace_id = :ws AND status = 'active' ORDER BY name");
$typeListStmt->execute(['ws' => $wsId]);
$typeList = $typeListStmt->fetchAll();

$testerListStmt = $pdo->prepare("SELECT id, name FROM users WHERE workspace_id = :ws AND role IN ('lab_technician','testing_engineer') AND status = 'active' ORDER BY name");
$testerListStmt->execute(['ws' => $wsId]);
$testerList = $testerListStmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <a href="<?= url('products/index.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:8px;">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
      Back to Products
    </a>
    <div class="d-flex align-center gap-12" style="margin-bottom:4px;">
      <div style="width:44px;height:44px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="22" height="22" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M10 2L17 6v8l-7 4-7-4V6l7-4z"/><path d="M10 10l7-4M10 10v8M10 10L3 6"/></svg>
      </div>
      <div>
        <div class="d-flex align-center gap-8">
          <h1 class="page-title" style="font-size:1.5rem;"><?= e($product['spec_model']) ?></h1>
          <?= status_badge($product['status']) ?>
        </div>
        <p class="page-subtitle" style="font-family:var(--font-mono);font-size:0.8125rem;margin-top:2px;"><?= e($product['serial_number']) ?> · <?= e($product['dept_name'] ?? '—') ?> · Registered <?= format_date($product['created_at']) ?></p>
      </div>
    </div>
  </div>
  <div class="page-actions">
    <?php if (can('edit_product')): ?>
    <button class="btn btn-secondary" onclick="openModal('editProductModal')">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M11 2l3 3-9 9H2v-3L11 2z"/></svg>
      Edit
    </button>
    <?php endif; ?>
    <?php if (can('assign_test')): ?>
    <button class="btn btn-primary" onclick="openModal('assignTestModal')">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      Assign Test
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Lifecycle Timeline -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-body" style="padding:20px 24px;">
    <div class="lifecycle-steps">
      <?php foreach ($stageLabels as $i => $label): ?>
      <div class="lifecycle-step <?= $i < $stageIndex ? 'completed' : ($i === $stageIndex ? 'current' : '') ?>">
        <div class="lifecycle-step-dot" style="font-size:0.6875rem;font-weight:700;">
          <?php if ($i < $stageIndex): ?>
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="2.5,6 5,8.5 9.5,3.5"/></svg>
          <?php else: echo $i + 1; endif; ?>
        </div>
        <span class="lifecycle-step-label"><?= $label ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($product['status'] === 'pending_cpri_approval' && can('cpri_approve')): ?>
<div class="card" style="margin-bottom:24px;border-left:3px solid var(--warning);">
  <div class="card-header">
    <h3 class="card-title">CPRI Approval Required</h3>
    <span class="status-badge badge-warning"><span class="status-dot"></span>Pending</span>
  </div>
  <div class="card-body">
    <p style="font-size:0.875rem;color:var(--ink-soft);margin-bottom:16px;">All engineering tests have passed. Review the product's testing history and provide your CPRI decision.</p>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="cpri_decision">
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-control" name="cpri_notes" rows="2" placeholder="Optional approval/rejection notes…"></textarea>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" name="cpri_decision" value="approve" class="btn btn-success" onclick="return confirm('Approve CPRI certification for this product?')">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3,8 6.5,11.5 13,4.5"/></svg>
          Approve CPRI
        </button>
        <button type="submit" name="cpri_decision" value="reject" class="btn btn-danger" onclick="return confirm('Reject CPRI certification? The product will need rework.')">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="4" x2="12" y2="12"/><line x1="12" y1="4" x2="4" y2="12"/></svg>
          Reject
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="tabs">
  <button class="tab active" data-tab="tab-overview">Overview</button>
  <button class="tab" data-tab="tab-tests">Test Assignments <span style="background:var(--accent-tint);color:var(--accent);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:6px;"><?= count($testRecords) ?></span></button>
  <button class="tab" data-tab="tab-attachments">Attachments</button>
  <button class="tab" data-tab="tab-audit">Audit Trail</button>
</div>

<!-- Overview Tab -->
<div class="tab-panel active" id="tab-overview">
  <div class="detail-grid">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Product Details</h3></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:140px 1fr;gap:12px 16px;font-size:0.875rem;">
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Serial Number</span>
          <span class="text-mono" style="font-weight:600;"><?= e($product['serial_number']) ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Model / Spec</span>
          <span><?= e($product['spec_model']) ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Department</span>
          <span><?= e($product['dept_name'] ?? '—') ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Status</span>
          <span><?= status_badge($product['status']) ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Attempt Count</span>
          <span class="text-mono"><?= (int) $product['attempt_count'] ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Registered By</span>
          <span><?= e($product['registered_by_name'] ?? '—') ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Registered At</span>
          <span><?= format_datetime($product['created_at']) ?></span>
          <span class="text-caption" style="text-transform:none;font-size:0.8125rem;color:var(--ink-faint);">Manufactured</span>
          <span><?= $product['manufactured_at'] ? format_date($product['manufactured_at']) : '—' ?></span>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Notes</h3></div>
      <div class="card-body">
        <p style="color:var(--ink-soft);font-size:0.875rem;line-height:1.7;"><?= $product['notes'] ? e($product['notes']) : '<span class="text-faint">No notes.</span>' ?></p>
      </div>
      <?php if (!empty($cpriRecords)): ?>
      <div class="card-header" style="border-top:1px solid var(--line);"><h3 class="card-title">CPRI History</h3></div>
      <div class="card-body" style="padding:0;">
        <?php foreach ($cpriRecords as $cr): ?>
        <div style="padding:12px 24px;border-bottom:1px solid var(--line);font-size:0.8125rem;">
          <div class="d-flex align-center gap-8">
            <?= status_badge($cr['decision'] === 'approved' ? 'approved' : 'rejected') ?>
            <span>by <?= e($cr['decided_by_name'] ?? '—') ?></span>
            <span class="text-faint">· <?= format_datetime($cr['decided_at']) ?></span>
          </div>
          <?php if ($cr['certificate_number']): ?>
          <div style="margin-top:4px;font-family:var(--font-mono);color:var(--success);font-size:0.75rem;">Certificate: <?= e($cr['certificate_number']) ?></div>
          <?php endif; ?>
          <?php if ($cr['reason']): ?>
          <div style="margin-top:4px;color:var(--ink-soft);"><?= e($cr['reason']) ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Tests Tab -->
<div class="tab-panel" id="tab-tests">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Test Type</th>
          <th>Tester</th>
          <th>Status</th>
          <th>Attempt</th>
          <th>Due Date</th>
          <th>Submitted</th>
          <th class="col-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($testRecords)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--ink-faint);padding:24px;">No tests assigned yet.</td></tr>
        <?php else: ?>
        <?php foreach ($testRecords as $r): ?>
        <tr>
          <td><strong><?= e($r['test_type_name']) ?></strong></td>
          <td><?= e($r['tester_name'] ?? '—') ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="col-num">#<?= (int) $r['attempt_number'] ?></td>
          <td class="text-soft" style="font-size:0.8125rem;"><?= $r['due_date'] ? format_date($r['due_date']) : '—' ?></td>
          <td class="text-soft" style="font-size:0.8125rem;"><?= $r['submitted_at'] ? time_ago($r['submitted_at']) : '—' ?></td>
          <td class="col-actions">
            <a href="<?= url('testing/detail.php?id=' . $r['id']) ?>" class="btn btn-sm btn-ghost">Open</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Attachments Tab -->
<div class="tab-panel" id="tab-attachments">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>File Name</th><th>Type</th><th>Size</th><th>Uploaded By</th><th>Date</th></tr></thead>
      <tbody>
        <?php if (empty($attachments)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">No attachments yet.</td></tr>
        <?php else: ?>
        <?php foreach ($attachments as $att): ?>
        <tr>
          <td><?= e($att['file_name']) ?></td>
          <td class="text-soft"><?= e(strtoupper($att['file_type'] ?? '')) ?></td>
          <td class="col-num"><?= $att['file_size'] ? round($att['file_size'] / 1024) . ' KB' : '—' ?></td>
          <td class="text-soft"><?= e($att['uploader_name'] ?? '—') ?></td>
          <td class="text-soft" style="font-size:0.8125rem;"><?= format_date($att['uploaded_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Audit Trail Tab -->
<div class="tab-panel" id="tab-audit">
  <div class="card">
    <div class="card-body" style="padding:0;">
      <?php if (empty($auditTrail)): ?>
      <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No audit entries yet.</div>
      <?php else: ?>
      <?php foreach ($auditTrail as $a): ?>
      <div style="display:flex;gap:14px;padding:14px 24px;border-bottom:1px solid var(--line);">
        <div class="activity-avatar"><?= e(user_initials($a['actor_name'] ?? 'System')) ?></div>
        <div style="flex:1;">
          <div style="font-size:0.8125rem;"><strong><?= e($a['actor_name'] ?? 'System') ?></strong> <?= e(str_replace('.', ' ', $a['action'])) ?></div>
          <div style="font-size:0.75rem;color:var(--ink-faint);margin-top:2px;"><?= format_datetime($a['created_at']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (can('edit_product')): ?>
<!-- Edit Product Modal -->
<div class="modal-backdrop" id="editProductModal" hidden>
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Edit Product</h3>
      <button class="modal-close" onclick="closeModal('editProductModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit">
    <div class="modal-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Serial Number</label>
          <input type="text" class="form-control" value="<?= e($product['serial_number']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Model / Specification <span class="required">*</span></label>
          <input type="text" class="form-control" name="model" value="<?= e($product['spec_model']) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Department <span class="required">*</span></label>
          <select class="form-control" name="department_id" required>
            <?php foreach ($deptList as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $d['id'] == $product['department_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <input type="text" class="form-control" value="<?= e(status_label($product['status'])) ?>" disabled>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea class="form-control" rows="3" name="notes"><?= e($product['notes'] ?? '') ?></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (can('assign_test')): ?>
<!-- Assign Test Modal -->
<div class="modal-backdrop" id="assignTestModal" hidden>
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">Assign Test</h3>
      <button class="modal-close" onclick="closeModal('assignTestModal')">&times;</button>
    </div>
    <form method="post" action="" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="assign_test">
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Product</label>
        <input type="text" class="form-control" value="<?= e($product['spec_model']) ?> (<?= e($product['serial_number']) ?>)" disabled>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Test Type <span class="required">*</span></label>
          <select class="form-control" name="testing_type_id" required>
            <option value="">Select test type…</option>
            <?php foreach ($typeList as $t): ?>
            <option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Assign to Tester <span class="required">*</span></label>
          <select class="form-control" name="tester_id" required>
            <option value="">Select tester…</option>
            <?php foreach ($testerList as $u): ?>
            <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Due Date <span class="required">*</span></label>
        <input type="date" class="form-control" name="due_date" min="<?= date('Y-m-d') ?>" required>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeModal('assignTestModal')">Cancel</button>
      <button type="submit" class="btn btn-primary">Assign Test</button>
    </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
