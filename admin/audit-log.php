<?php
declare(strict_types=1);
// Audit log — immutable activity trail with JSON payload viewer (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Audit Log';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Audit Log', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR, ROLE_AUDITOR, ROLE_QUALITY_MANAGER, ROLE_TESTING_ENGINEER);
$wsId = auth_workspace_id();
require_once __DIR__ . '/../includes/header.php';

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$actionFilter = $_GET['action'] ?? '';
$entityFilter = $_GET['entity'] ?? '';
$actorFilter  = $_GET['actor'] ?? '';
$dateFrom     = $_GET['from'] ?? '';
$dateTo       = $_GET['to'] ?? '';
$search       = trim($_GET['q'] ?? '');

$where = ['al.workspace_id = :ws'];
$params = ['ws' => $wsId];

if ($actionFilter !== '') {
    $where[] = 'al.action LIKE :action';
    $params['action'] = '%' . $actionFilter . '%';
}
if ($entityFilter !== '') {
    $where[] = 'al.entity_type = :entity';
    $params['entity'] = $entityFilter;
}
if ($actorFilter !== '') {
    $where[] = 'al.actor_id = :actor';
    $params['actor'] = $actorFilter;
}
if ($dateFrom !== '') {
    $where[] = 'al.created_at >= :datefrom';
    $params['datefrom'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[] = 'al.created_at <= :dateto';
    $params['dateto'] = $dateTo . ' 23:59:59';
}
if ($search !== '') {
    $where[] = '(al.action LIKE :q1 OR al.entity_type LIKE :q2 OR u.name LIKE :q3)';
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
    $params['q3'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log al LEFT JOIN users u ON al.actor_id = u.id {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
    SELECT al.*, u.name AS actor_name
    FROM audit_log al
    LEFT JOIN users u ON al.actor_id = u.id
    {$whereSql}
    ORDER BY al.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$entries = $stmt->fetchAll();

// Users for filter
$userListStmt = $pdo->prepare("SELECT id, name FROM users WHERE workspace_id = :ws ORDER BY name");
$userListStmt->execute(['ws' => $wsId]);
$userList = $userListStmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M4 2h8l4 4v10a2 2 0 01-2 2H4a2 2 0 01-2-2V4a2 2 0 012-2z"/><path d="M12 2v4h4"/><line x1="6" y1="10" x2="14" y2="10"/><line x1="6" y1="13" x2="11" y2="13"/></svg>
      </div>
      <div>
        <h1 class="page-title">Audit Log</h1>
        <p class="page-subtitle" style="margin-top:2px;">Immutable record of every system mutation</p>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<form method="get" action="" class="filter-bar">
  <div class="filter-search">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
    <input type="text" name="q" class="form-control" placeholder="Search by actor, action, entity…" value="<?= e($search) ?>">
  </div>
  <select name="entity" class="form-control" style="width:160px;" onchange="this.form.submit()">
    <option value="">All Entities</option>
    <option value="product" <?= $entityFilter === 'product' ? 'selected' : '' ?>>Product</option>
    <option value="testing_record" <?= $entityFilter === 'testing_record' ? 'selected' : '' ?>>Testing Record</option>
    <option value="testing_type" <?= $entityFilter === 'testing_type' ? 'selected' : '' ?>>Testing Type</option>
    <option value="user" <?= $entityFilter === 'user' ? 'selected' : '' ?>>User</option>
    <option value="department" <?= $entityFilter === 'department' ? 'selected' : '' ?>>Department</option>
    <option value="cpri_record" <?= $entityFilter === 'cpri_record' ? 'selected' : '' ?>>CPRI Record</option>
  </select>
  <select name="actor" class="form-control" style="width:140px;" onchange="this.form.submit()">
    <option value="">All Users</option>
    <?php foreach ($userList as $u): ?>
    <option value="<?= $u['id'] ?>" <?= $actorFilter == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="from" class="form-control" style="width:150px;" value="<?= e($dateFrom) ?>">
  <span class="text-faint" style="font-size:0.8125rem;">to</span>
  <input type="date" name="to" class="form-control" style="width:150px;" value="<?= e($dateTo) ?>">
</form>

<!-- Audit Table -->
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th class="sortable">ID</th>
        <th class="sortable">Timestamp</th>
        <th>Actor</th>
        <th class="sortable">Action</th>
        <th>Entity</th>
        <th>IP Address</th>
        <th class="col-actions"></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($entries)): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--ink-faint);padding:24px;">No audit entries found.</td></tr>
      <?php else: ?>
      <?php foreach ($entries as $a): ?>
      <tr>
        <td class="col-mono text-faint" style="font-size:0.75rem;">#<?= $a['id'] ?></td>
        <td class="text-mono" style="font-size:0.8125rem;white-space:nowrap;"><?= format_datetime($a['created_at']) ?></td>
        <td>
          <div class="d-flex align-center gap-8">
            <span class="user-avatar" style="width:24px;height:24px;font-size:0.5625rem;"><?= e(user_initials($a['actor_name'] ?? 'System')) ?></span>
            <?= e($a['actor_name'] ?? 'System') ?>
          </div>
        </td>
        <td>
          <span style="padding:2px 8px;border-radius:var(--radius-sm);font-size:0.75rem;font-weight:500;
            <?php
            $actionColor = match(true) {
              str_contains($a['action'], 'approve') || str_contains($a['action'], 'pass') || str_contains($a['action'], 'create') => 'background:var(--success-tint);color:var(--success);',
              str_contains($a['action'], 'fail') || str_contains($a['action'], 'reject') || str_contains($a['action'], 'delete') => 'background:var(--fail-tint);color:var(--fail);',
              str_contains($a['action'], 'submit') || str_contains($a['action'], 'start') => 'background:var(--warning-tint);color:var(--warning);',
              str_contains($a['action'], 'assign') || str_contains($a['action'], 'update') => 'background:var(--info-tint);color:var(--info);',
              default => 'background:var(--hold-tint);color:var(--hold);',
            };
            echo $actionColor;
            ?>">
            <?= e(str_replace('.', ' ', $a['action'])) ?>
          </span>
        </td>
        <td>
          <span class="text-soft"><?= e($a['entity_type']) ?></span>
          <span class="text-mono text-faint" style="font-size:0.6875rem;"> #<?= (int) $a['entity_id'] ?></span>
        </td>
        <td class="text-mono text-faint" style="font-size:0.75rem;"><?= e($a['ip_address'] ?? '—') ?></td>
        <td class="col-actions">
          <button class="btn btn-sm btn-ghost" onclick="inspectAudit(<?= (int) $a['id'] ?>, <?= e(json_encode($a['actor_name'] ?? 'System')) ?>, <?= e(json_encode(str_replace('.', ' ', $a['action']))) ?>, <?= e(json_encode($a['entity_type'])) ?>, <?= (int) $a['entity_id'] ?>, <?= e(json_encode($a['created_at'])) ?>, <?= e(json_encode($a['ip_address'] ?? '')) ?>, <?= e(json_encode($a['before_json'])) ?>, <?= e(json_encode($a['after_json'])) ?>)">Inspect</button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="pagination-wrap" style="margin-top:16px;">
  <?php
    $qp = $_GET;
    unset($qp['page']);
    $qs = http_build_query($qp);
    $baseUrl = $qs !== '' ? '?' . $qs : '?';
  ?>
  <?= pagination_html($page, $totalPages, $baseUrl) ?>
</nav>
<?php endif; ?>

<div style="text-align:center;margin-top:12px;">
  <span class="text-faint" style="font-size:0.75rem;">Audit records are immutable and cannot be deleted &middot; <?= number_format($totalRows) ?> total entries</span>
</div>

<div class="modal-backdrop" id="auditInspectModal" hidden>
  <div class="modal" style="max-width:540px;">
    <div class="modal-header">
      <h3 class="modal-title">Audit Entry Details</h3>
      <button class="modal-close" onclick="closeModal('auditInspectModal')">&times;</button>
    </div>
    <div class="modal-body" id="auditInspectBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('auditInspectModal')">Close</button>
    </div>
  </div>
</div>

<script>
function inspectAudit(id, actor, action, entity, entityId, time, ip, beforeJson, afterJson) {
  var body = document.getElementById('auditInspectBody');
  var payload = '';
  try {
    if (afterJson && afterJson !== 'null') {
      payload = JSON.stringify(JSON.parse(afterJson), null, 2);
    } else if (beforeJson && beforeJson !== 'null') {
      payload = JSON.stringify(JSON.parse(beforeJson), null, 2);
    } else {
      payload = '(no payload)';
    }
  } catch(e) { payload = afterJson || beforeJson || '(no payload)'; }

  body.innerHTML =
    '<div style="display:flex;flex-direction:column;gap:12px;">' +
      '<div style="display:grid;grid-template-columns:120px 1fr;gap:8px 16px;font-size:0.8125rem;">' +
        '<span class="text-faint">Entry ID</span><span class="col-mono" style="font-weight:600;">#' + id + '</span>' +
        '<span class="text-faint">Timestamp</span><span>' + time + '</span>' +
        '<span class="text-faint">Actor</span><span style="font-weight:500;">' + actor + '</span>' +
        '<span class="text-faint">Action</span><span style="font-weight:500;">' + action + '</span>' +
        '<span class="text-faint">Entity Type</span><span>' + entity + '</span>' +
        '<span class="text-faint">Entity ID</span><span class="col-mono">#' + entityId + '</span>' +
        '<span class="text-faint">Source IP</span><span class="col-mono">' + (ip || '—') + '</span>' +
      '</div>' +
      '<div style="margin-top:8px;padding-top:12px;border-top:1px solid var(--line);">' +
        '<div class="text-caption" style="margin-bottom:8px;">Change Payload</div>' +
        '<pre style="background:var(--charcoal);color:var(--ink-soft);padding:12px 16px;border-radius:var(--radius-md);font-size:0.75rem;font-family:var(--font-mono);overflow-x:auto;line-height:1.6;border:1px solid var(--line);">' +
          payload.replace(/</g, '&lt;').replace(/>/g, '&gt;') +
        '</pre>' +
      '</div>' +
    '</div>';
  openModal('auditInspectModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
