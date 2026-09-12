<?php
declare(strict_types=1);
// Testing detail — measurements, review, attachments (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$pdo = Database::getConnection();
$wsId = auth_workspace_id();

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { redirect('testing/index.php'); }

// Fetch record (needed for POST processing)
$recordStmt = $pdo->prepare("
    SELECT tr.*, p.serial_number, p.spec_model, p.id AS product_id,
           tt.name AS test_type_name,
           d.name AS dept_name,
           u.name AS tester_name, u.role AS tester_role,
           ru.name AS reviewer_name
    FROM testing_records tr
    JOIN products p ON tr.product_id = p.id
    JOIN testing_types tt ON tr.testing_type_id = tt.id
    LEFT JOIN departments d ON tr.department_id = d.id
    LEFT JOIN users u ON tr.tester_id = u.id
    LEFT JOIN users ru ON tr.reviewed_by = ru.id
    WHERE tr.id = :id AND tr.workspace_id = :ws
");
$recordStmt->execute(['id' => $id, 'ws' => $wsId]);
$record = $recordStmt->fetch();

if (!$record) {
    $pageTitle = 'Record Not Found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div style="padding:60px;text-align:center;"><h2>Record not found</h2><a href="' . url('testing/index.php') . '" class="btn btn-primary">Back to Testing</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$role = auth_role();

// Handle POST actions BEFORE any HTML output
if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $isAssignedTester = (int) $record['tester_id'] === auth_id();

    if ($action === 'save_measurements' && (can('record_measurements') && $isAssignedTester || $role === ROLE_ADMINISTRATOR)) {
        $values = $_POST['values'] ?? [];
        $paramIds = $_POST['param_ids'] ?? [];
        $instruments = $_POST['instruments'] ?? [];

        foreach ($paramIds as $i => $paramId) {
            $val = trim($values[$i] ?? '');
            if ($val === '') continue;

            $paramStmt = $pdo->prepare('SELECT * FROM testing_type_parameters WHERE id = :pid');
            $paramStmt->execute(['pid' => $paramId]);
            $param = $paramStmt->fetch();

            $inTolerance = 1;
            if ($param) {
                $numVal = (float) $val;
                if ($param['min_value'] !== null && $numVal < (float) $param['min_value']) $inTolerance = 0;
                if ($param['max_value'] !== null && $numVal > (float) $param['max_value']) $inTolerance = 0;
            }

            $existing = $pdo->prepare('SELECT id FROM measurements WHERE testing_record_id = :trid AND parameter_id = :pid AND locked_at IS NULL');
            $existing->execute(['trid' => $id, 'pid' => $paramId]);
            $existingId = $existing->fetchColumn();

            if ($existingId) {
                $pdo->prepare('UPDATE measurements SET recorded_value = :val, instrument = :inst, in_tolerance = :tol, recorded_at = NOW() WHERE id = :id')
                    ->execute(['val' => $val, 'inst' => $instruments[$i] ?? $param['required_instrument'] ?? null, 'tol' => $inTolerance, 'id' => $existingId]);
            } else {
                $pdo->prepare('INSERT INTO measurements (testing_record_id, parameter_id, recorded_value, unit, instrument, in_tolerance, recorded_by, recorded_at) VALUES (:trid, :pid, :val, :unit, :inst, :tol, :uid, NOW())')
                    ->execute(['trid' => $id, 'pid' => $paramId, 'val' => $val, 'unit' => $param['unit'] ?? null, 'inst' => $instruments[$i] ?? $param['required_instrument'] ?? null, 'tol' => $inTolerance, 'uid' => auth_id()]);
            }
        }

        if ($record['status'] === 'assigned') {
            $pdo->prepare("UPDATE testing_records SET status = 'in_testing', started_at = COALESCE(started_at, NOW()) WHERE id = :id AND workspace_id = :ws")->execute(['id' => $id, 'ws' => $wsId]);
        }

        flash_success('Measurements saved.');
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'submit_for_review' && (can('submit_test') && $isAssignedTester || $role === ROLE_ADMINISTRATOR)) {
        $pdo->prepare("UPDATE testing_records SET status = 'submitted_for_review', submitted_at = NOW() WHERE id = :id AND workspace_id = :ws")->execute(['id' => $id, 'ws' => $wsId]);
        $otherActive = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE product_id = :pid AND workspace_id = :ws AND id != :id AND status IN ('assigned','in_testing')");
        $otherActive->execute(['pid' => $record['product_id'], 'ws' => $wsId, 'id' => $id]);
        if ((int) $otherActive->fetchColumn() === 0) {
            $pdo->prepare("UPDATE products SET status = 'submitted_for_review' WHERE id = :pid AND workspace_id = :ws")->execute(['pid' => $record['product_id'], 'ws' => $wsId]);
        }
        audit_log($pdo, auth_id(), 'testing.submit', 'testing_record', $id, null, ['product' => $record['spec_model'], 'status' => 'submitted_for_review']);

        if ($record['assigned_by']) {
            create_notification($pdo, (int) $record['assigned_by'], 'Test submitted for review', "{$record['tester_name']} submitted measurements for {$record['spec_model']}.", 'warning', BASE_URL . '/testing/detail.php?id=' . $id);
        }

        flash_success('Submitted for review.');
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'upload_attachment' && (can('record_measurements') && $isAssignedTester || $role === ROLE_ADMINISTRATOR)) {
        $allowed = ['pdf','doc','docx','xls','xlsx','csv','jpg','jpeg','png','txt'];
        $maxSize = 10 * 1024 * 1024;
        if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                flash_error('File type not allowed. Accepted: ' . implode(', ', $allowed));
            } elseif ($file['size'] > $maxSize) {
                flash_error('File too large. Maximum 10MB.');
            } else {
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $dest = __DIR__ . '/../uploads/' . $safeName;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $pdo->prepare('INSERT INTO attachments (attachable_type, attachable_id, file_name, file_path, file_type, file_size, uploaded_by) VALUES (:type, :aid, :name, :path, :ftype, :size, :uid)')
                        ->execute(['type' => 'testing_record', 'aid' => $id, 'name' => $file['name'], 'path' => 'uploads/' . $safeName, 'ftype' => $file['type'], 'size' => $file['size'], 'uid' => auth_id()]);
                    audit_log($pdo, auth_id(), 'attachment.upload', 'testing_record', $id, null, ['file' => $file['name']]);
                    flash_success('File uploaded.');
                } else {
                    flash_error('Upload failed. Please try again.');
                }
            }
        } elseif (!empty($_FILES['attachment'])) {
            flash_error('Upload error. Please try again.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'delete_attachment' && (can('record_measurements') && $isAssignedTester || $role === ROLE_ADMINISTRATOR)) {
        $attId = (int) ($_POST['attachment_id'] ?? 0);
        $att = $pdo->prepare('SELECT * FROM attachments WHERE id = :id AND attachable_type = :t AND attachable_id = :aid');
        $att->execute(['id' => $attId, 't' => 'testing_record', 'aid' => $id]);
        $att = $att->fetch();
        if ($att && ($att['uploaded_by'] == auth_id() || $role === 'administrator')) {
            $filePath = __DIR__ . '/../' . $att['file_path'];
            if (file_exists($filePath)) @unlink($filePath);
            $pdo->prepare('DELETE FROM attachments WHERE id = :id')->execute(['id' => $attId]);
            audit_log($pdo, auth_id(), 'attachment.delete', 'testing_record', $id, null, ['file' => $att['file_name']]);
            flash_success('Attachment removed.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'review_decision' && can('review_test') && $record['status'] === 'submitted_for_review') {
        $decision = $_POST['decision'] ?? '';
        $reason = trim($_POST['review_reason'] ?? '');
        if (in_array($decision, ['pass', 'fail'])) {
            $newStatus = $decision === 'pass' ? 'passed' : 'failed';
            $pdo->prepare("UPDATE testing_records SET status = :status, reviewed_by = :uid, review_decision = :dec, review_reason = :reason, reviewed_at = NOW() WHERE id = :id AND workspace_id = :ws")
                ->execute(['status' => $newStatus, 'uid' => auth_id(), 'dec' => $decision, 'reason' => $reason ?: null, 'id' => $id, 'ws' => $wsId]);

            $pdo->prepare("UPDATE measurements SET locked_at = NOW() WHERE testing_record_id = :id AND locked_at IS NULL")->execute(['id' => $id]);

            if ($decision === 'pass') {
                $otherPending = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE product_id = :pid AND workspace_id = :ws AND id != :id AND status NOT IN ('passed','failed')");
                $otherPending->execute(['pid' => $record['product_id'], 'ws' => $wsId, 'id' => $id]);
                if ((int) $otherPending->fetchColumn() === 0) {
                    $pdo->prepare("UPDATE products SET status = 'pending_cpri_approval' WHERE id = :pid AND workspace_id = :ws")->execute(['pid' => $record['product_id'], 'ws' => $wsId]);
                }
            } else {
                $pdo->prepare("UPDATE products SET status = 'failed_pending_rework', attempt_count = attempt_count + 1 WHERE id = :pid AND workspace_id = :ws")->execute(['pid' => $record['product_id'], 'ws' => $wsId]);
            }

            audit_log($pdo, auth_id(), 'testing.' . $decision, 'testing_record', $id, null, ['product' => $record['spec_model'], 'decision' => $decision]);
            create_notification($pdo, (int) $record['tester_id'], "Test {$decision}ed", "Your test for {$record['spec_model']} was {$decision}ed.", $decision === 'pass' ? 'success' : 'error', BASE_URL . '/testing/detail.php?id=' . $id);

            flash_success("Test marked as {$decision}.");
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
    }
}

// Re-fetch record after potential POST changes
$recordStmt->execute(['id' => $id, 'ws' => $wsId]);
$record = $recordStmt->fetch();

// Now include header (safe — all redirects happened above)
$pageTitle = 'TR-' . str_pad((string) $record['id'], 3, '0', STR_PAD_LEFT) . ' — ' . $record['test_type_name'];
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Testing Records', 'url' => url('testing/index.php')],
    ['label' => 'TR-' . str_pad((string) $record['id'], 3, '0', STR_PAD_LEFT), 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';

// Fetch parameters and measurements
$params = $pdo->prepare("
    SELECT ttp.*, m.recorded_value, m.in_tolerance, m.instrument AS recorded_instrument, m.locked_at
    FROM testing_type_parameters ttp
    LEFT JOIN measurements m ON m.parameter_id = ttp.id AND m.testing_record_id = :trid
    WHERE ttp.testing_type_id = :ttid
    ORDER BY ttp.sort_order ASC
");
$params->execute(['trid' => $id, 'ttid' => $record['testing_type_id']]);
$params = $params->fetchAll();

$recordedCount = count(array_filter($params, fn($p) => $p['recorded_value'] !== null));
$totalParams = count($params);

// Activity feed
$activity = $pdo->prepare("
    SELECT al.action, al.created_at, u.name AS actor_name
    FROM audit_log al
    LEFT JOIN users u ON al.actor_id = u.id
    WHERE al.entity_type = 'testing_record' AND al.entity_id = :id AND al.workspace_id = :ws
    ORDER BY al.created_at DESC LIMIT 10
");
$activity->execute(['id' => $id, 'ws' => $wsId]);
$activity = $activity->fetchAll();

// Attachments
$attachments = $pdo->prepare("SELECT a.*, u.name AS uploader_name FROM attachments a LEFT JOIN users u ON a.uploaded_by = u.id WHERE a.attachable_type = 'testing_record' AND a.attachable_id = :id ORDER BY a.uploaded_at DESC");
$attachments->execute(['id' => $id]);
$attachments = $attachments->fetchAll();

$isAssignedTesterView = (int) $record['tester_id'] === auth_id();
$canModify = (can('record_measurements') && $isAssignedTesterView) || $role === ROLE_ADMINISTRATOR;
$canRecord = $canModify && in_array($record['status'], ['assigned','in_testing']);
$canSubmit = $canModify && $record['status'] === 'in_testing' && $recordedCount === $totalParams && $totalParams > 0;
$canReview = can('review_test') && $record['status'] === 'submitted_for_review';
$canAttach = $canModify && in_array($record['status'], ['assigned','in_testing','submitted_for_review']);
?>

<!-- Record Header -->
<div class="page-header">
  <div class="page-header-left">
    <a href="<?= url('testing/index.php') ?>" class="btn btn-ghost btn-sm" style="margin-bottom:8px;">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
      Back to Testing Records
    </a>
    <div class="d-flex align-center gap-12" style="margin-bottom:4px;">
      <div style="width:44px;height:44px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="22" height="22" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M7 2v5l-4 7a1.5 1.5 0 001.3 2.2h11.4a1.5 1.5 0 001.3-2.2l-4-7V2"/><line x1="5" y1="2" x2="15" y2="2"/></svg>
      </div>
      <div>
        <div class="d-flex align-center gap-8">
          <span class="text-mono" style="font-weight:700;font-size:1rem;color:var(--ink-faint);">TR-<?= str_pad((string) $record['id'], 3, '0', STR_PAD_LEFT) ?></span>
          <?= status_badge($record['status']) ?>
        </div>
        <h1 class="page-title" style="font-size:1.5rem;"><?= e($record['test_type_name']) ?></h1>
        <p class="page-subtitle" style="margin-top:2px;">
          <a href="<?= url('products/detail.php?id=' . $record['product_id']) ?>"><?= e($record['spec_model']) ?></a>
          · <?= e($record['serial_number']) ?>
          · Assigned to <?= e($record['tester_name'] ?? '—') ?>
          · Attempt #<?= (int) $record['attempt_number'] ?>
        </p>
      </div>
    </div>
  </div>
  <div class="page-actions">
    <?php if ($canSubmit): ?>
    <form method="post" action="" style="display:inline;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="submit_for_review">
      <button type="submit" class="btn btn-primary" onclick="return confirm('Submit all recorded measurements for review? This action cannot be undone.')">Submit for Review</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="testing-detail-layout">

  <!-- Main Column -->
  <div>

    <!-- Measurement Grid -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Measurements</h3>
        <div class="d-flex align-center gap-8">
          <span class="text-caption"><?= $recordedCount ?> of <?= $totalParams ?> recorded</span>
          <div style="width:100px;height:6px;background:var(--line);border-radius:3px;overflow:hidden;">
            <div style="width:<?= $totalParams > 0 ? round(($recordedCount / $totalParams) * 100) : 0 ?>%;height:100%;background:var(--success);border-radius:3px;"></div>
          </div>
        </div>
      </div>
      <?php if ($totalParams === 0): ?>
      <div class="card-body" style="text-align:center;padding:32px 24px;">
        <div style="color:var(--ink-faint);font-size:0.875rem;">
          <svg width="40" height="40" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4;margin-bottom:8px;"><circle cx="20" cy="20" r="16"/><line x1="20" y1="12" x2="20" y2="22"/><circle cx="20" cy="27" r="1.5" fill="currentColor"/></svg>
          <p style="font-weight:600;margin-bottom:4px;">No parameters defined</p>
          <p>This testing type has no parameters configured. An administrator must add parameters to "<strong><?= e($record['test_type_name']) ?></strong>" before measurements can be recorded.</p>
          <?php if ($role === ROLE_ADMINISTRATOR): ?>
          <a href="<?= url('admin/testing-types.php') ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">Manage Testing Types</a>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
      <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_measurements">
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Parameter</th>
                <th class="col-num">Min</th>
                <th class="col-num">Nominal</th>
                <th class="col-num">Max</th>
                <th class="col-num" style="width:120px;">Recorded Value</th>
                <th>Unit</th>
                <th>Instrument</th>
                <th class="text-center">Tolerance</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($params as $i => $pm): ?>
              <tr>
                <td><strong><?= e($pm['name']) ?></strong></td>
                <td class="col-num text-mono"><?= $pm['min_value'] !== null ? rtrim(rtrim(number_format((float)$pm['min_value'], 4), '0'), '.') : '—' ?></td>
                <td class="col-num text-mono"><?= $pm['nominal_value'] !== null ? rtrim(rtrim(number_format((float)$pm['nominal_value'], 4), '0'), '.') : '—' ?></td>
                <td class="col-num text-mono"><?= $pm['max_value'] !== null ? rtrim(rtrim(number_format((float)$pm['max_value'], 4), '0'), '.') : '—' ?></td>
                <td>
                  <input type="hidden" name="param_ids[]" value="<?= (int) $pm['id'] ?>">
                  <input type="hidden" name="instruments[]" value="<?= e($pm['required_instrument'] ?? '') ?>">
                  <input type="number" class="form-control" name="values[]"
                    style="text-align:right;font-family:var(--font-mono);padding:6px 10px;<?php
                      if ($pm['recorded_value'] !== null) {
                        echo $pm['in_tolerance'] ? 'border-color:var(--success);background:var(--success-tint);' : 'border-color:var(--fail);background:var(--fail-tint);';
                      }
                    ?>"
                    value="<?= $pm['recorded_value'] !== null ? e(rtrim(rtrim(number_format((float)$pm['recorded_value'], 4), '0'), '.')) : '' ?>"
                    placeholder="—" step="0.0001"
                    <?= !$canRecord ? 'disabled' : ($pm['locked_at'] ? 'readonly tabindex="-1"' : '') ?>>
                </td>
                <td class="text-mono text-soft"><?= e($pm['unit'] ?? '') ?></td>
                <td class="text-soft" style="font-size:0.8125rem;"><?= e($pm['required_instrument'] ?? '—') ?></td>
                <td class="text-center">
                  <?php if ($pm['recorded_value'] !== null): ?>
                    <?php if ($pm['in_tolerance']): ?>
                      <span style="color:var(--success);"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="4,9 7.5,12.5 14,5.5"/></svg></span>
                    <?php else: ?>
                      <span style="color:var(--fail);"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="5" x2="13" y2="13"/><line x1="13" y1="5" x2="5" y2="13"/></svg></span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color:var(--ink-faint);">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($canRecord): ?>
        <div style="padding:12px 24px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;">
          <button type="submit" class="btn btn-primary">Save Measurements</button>
        </div>
        <?php endif; ?>
      </form>
      <?php endif; ?>
    </div>

    <!-- Attachments -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Attachments</h3>
        <span class="text-caption"><?= count($attachments) ?> file<?= count($attachments) !== 1 ? 's' : '' ?></span>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($attachments)): ?>
        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No attachments uploaded yet.</div>
        <?php else: ?>
        <?php foreach ($attachments as $att):
          $sizeKb = round(($att['file_size'] ?? 0) / 1024, 1);
          $sizeLabel = $sizeKb >= 1024 ? round($sizeKb / 1024, 1) . ' MB' : $sizeKb . ' KB';
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 24px;border-bottom:1px solid var(--line);">
          <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:var(--accent-tint);color:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10 2H5a1.5 1.5 0 00-1.5 1.5v9A1.5 1.5 0 005 14h6a1.5 1.5 0 001.5-1.5V5L10 2z"/><polyline points="10,2 10,5 12.5,5"/></svg>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:500;font-size:0.8125rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($att['file_name']) ?></div>
            <div style="font-size:0.6875rem;color:var(--ink-faint);"><?= $sizeLabel ?> · <?= e($att['uploader_name'] ?? '') ?> · <?= time_ago($att['uploaded_at']) ?></div>
          </div>
          <a href="<?= url('api/download.php?id=' . $att['id']) ?>" download="<?= e($att['file_name']) ?>" class="btn btn-sm btn-ghost" title="Download">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 11v2h10v-2"/><polyline points="8,2 8,9"/><polyline points="5,6 8,9 11,6"/></svg>
          </a>
          <?php if ($canModify && ($att['uploaded_by'] == auth_id() || $role === ROLE_ADMINISTRATOR)): ?>
          <form method="post" action="" style="margin:0;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_attachment">
            <input type="hidden" name="attachment_id" value="<?= (int) $att['id'] ?>">
            <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--fail);" onclick="return confirm('Remove this attachment?')" title="Delete">
              <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="3,5 13,5"/><path d="M5.5 5V3.5a1 1 0 011-1h3a1 1 0 011 1V5"/><path d="M4 5l.7 8.4a1 1 0 001 .9h4.6a1 1 0 001-.9L12 5"/></svg>
            </button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($canAttach): ?>
        <form method="post" action="" enctype="multipart/form-data" style="padding:12px 24px;display:flex;align-items:center;gap:8px;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="upload_attachment">
          <input type="file" name="attachment" required class="form-control" style="font-size:0.8125rem;padding:6px 10px;">
          <button type="submit" class="btn btn-sm btn-secondary">Upload</button>
        </form>
        <div style="padding:0 24px 12px;font-size:0.6875rem;color:var(--ink-faint);">PDF, DOC, XLS, CSV, JPG, PNG, TXT · Max 10MB</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Attempt History -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Attempt History</h3>
      </div>
      <div class="card-body" style="padding:0;">
        <div style="display:flex;align-items:center;gap:12px;padding:14px 24px;border-bottom:1px solid var(--line);background:var(--accent-tint);">
          <span style="font-weight:700;font-size:0.75rem;color:var(--accent);background:var(--paper-raised);padding:2px 8px;border-radius:var(--radius-sm);">Current</span>
          <span style="font-weight:600;font-size:0.8125rem;">Attempt #<?= (int) $record['attempt_number'] ?></span>
          <span style="flex:1;"></span>
          <?= status_badge($record['status']) ?>
          <span class="text-faint" style="font-size:0.75rem;"><?= $record['started_at'] ? 'Started ' . format_date($record['started_at']) : 'Not started' ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Sidebar -->
  <div>

    <!-- Record Info -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">Details</h3></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:14px;font-size:0.8125rem;">
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Product</div>
            <a href="<?= url('products/detail.php?id=' . $record['product_id']) ?>" style="font-weight:600;"><?= e($record['spec_model']) ?></a>
            <div class="text-mono text-faint" style="font-size:0.75rem;"><?= e($record['serial_number']) ?></div>
          </div>
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Test Type</div>
            <div style="font-weight:500;"><?= e($record['test_type_name']) ?></div>
          </div>
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Department</div>
            <div><?= e($record['dept_name'] ?? '—') ?></div>
          </div>
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Assigned Tester</div>
            <div class="d-flex align-center gap-8">
              <span class="user-avatar" style="width:28px;height:28px;font-size:0.625rem;"><?= e(user_initials($record['tester_name'] ?? '')) ?></span>
              <div>
                <div style="font-weight:600;"><?= e($record['tester_name'] ?? '—') ?></div>
                <div class="text-faint" style="font-size:0.6875rem;"><?= e(role_label($record['tester_role'] ?? '')) ?></div>
              </div>
            </div>
          </div>
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Due Date</div>
            <?php if ($record['due_date']):
              $daysLeft = (int)(new DateTimeImmutable())->diff(new DateTimeImmutable($record['due_date']))->format('%r%a');
              $dueColor = $daysLeft < 0 ? 'var(--fail)' : ($daysLeft <= 2 ? 'var(--warning)' : 'inherit');
            ?>
            <div style="font-weight:500;color:<?= $dueColor ?>;"><?= format_date($record['due_date']) ?></div>
            <?php else: ?><div>—</div><?php endif; ?>
          </div>
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Created</div>
            <div class="text-soft"><?= format_datetime($record['created_at']) ?></div>
          </div>
          <?php if ($record['reviewed_by']): ?>
          <div>
            <div class="text-faint" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px;">Reviewed By</div>
            <div><?= e($record['reviewer_name'] ?? '—') ?> · <?= format_datetime($record['reviewed_at']) ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Review Panel -->
    <?php if ($canReview): ?>
    <div class="card">
      <div class="card-header"><h3 class="card-title">Review Decision</h3></div>
      <div class="card-body">
        <form method="post" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="review_decision">
          <div class="form-group">
            <label class="form-label">Reason / Notes</label>
            <textarea class="form-control" name="review_reason" rows="2" placeholder="Optional reviewer notes…"></textarea>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <button type="submit" name="decision" value="pass" class="btn btn-success w-100" onclick="return confirm('Mark this test as PASSED?')">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3,8 6.5,11.5 13,4.5"/></svg>
              Pass
            </button>
            <button type="submit" name="decision" value="fail" class="btn btn-danger w-100" onclick="return confirm('Mark this test as FAILED? This will trigger rework.')">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="4" x2="12" y2="12"/><line x1="12" y1="4" x2="4" y2="12"/></svg>
              Fail
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php elseif ($record['status'] !== 'submitted_for_review'): ?>
    <div class="card">
      <div class="card-header"><h3 class="card-title">Review Decision</h3></div>
      <div class="card-body">
        <?php if ($record['review_decision']): ?>
        <div style="text-align:center;padding:8px 0;">
          <?= status_badge($record['review_decision']) ?>
          <?php if ($record['review_reason']): ?>
          <p style="font-size:0.8125rem;color:var(--ink-soft);margin-top:8px;"><?= e($record['review_reason']) ?></p>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <p style="font-size:0.8125rem;color:var(--ink-faint);">Record all measurements and submit for review first.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Activity -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">Activity</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($activity)): ?>
        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No activity yet.</div>
        <?php else: ?>
        <ul class="activity-list" style="padding:0 24px;">
          <?php foreach ($activity as $act): ?>
          <li class="activity-item">
            <div class="activity-avatar"><?= e(user_initials($act['actor_name'] ?? 'System')) ?></div>
            <div class="activity-body">
              <div class="activity-text"><strong><?= e($act['actor_name'] ?? 'System') ?></strong> <?= e(str_replace('.', ' ', $act['action'])) ?></div>
              <div class="activity-meta"><?= time_ago($act['created_at']) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
