<?php
declare(strict_types=1);
// Testing records listing — status tabs, filters, pagination (Naveera)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Testing Records';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Testing Records', 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';

$role = auth_role();
$wsId = auth_workspace_id();

// Status counts for tabs
$tabCounts = [];
$countStmtTabs = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM testing_records WHERE workspace_id = :ws GROUP BY status");
$countStmtTabs->execute(['ws' => $wsId]);
$countRows = $countStmtTabs->fetchAll();
foreach ($countRows as $r) $tabCounts[$r['status']] = (int) $r['cnt'];
$totalCount = array_sum($tabCounts);

// Filter dropdowns
$deptStmt = $pdo->prepare("SELECT id, name FROM departments WHERE status = 'active' AND workspace_id = :ws ORDER BY name");
$deptStmt->execute(['ws' => $wsId]);
$deptList = $deptStmt->fetchAll();
$typeStmt = $pdo->prepare("SELECT id, name FROM testing_types WHERE status = 'active' AND workspace_id = :ws ORDER BY name");
$typeStmt->execute(['ws' => $wsId]);
$typeList = $typeStmt->fetchAll();
$testerStmt = $pdo->prepare("SELECT id, name FROM users WHERE role IN ('lab_technician','testing_engineer') AND status = 'active' AND workspace_id = :ws ORDER BY name");
$testerStmt->execute(['ws' => $wsId]);
$testerList = $testerStmt->fetchAll();

// Apply filters
$statusFilter = $_GET['status'] ?? '';
$deptFilter   = $_GET['department'] ?? '';
$typeFilter   = $_GET['type'] ?? '';
$testerFilter = $_GET['tester'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where = [];
$params = [];

$where[] = 'tr.workspace_id = :ws';
$params['ws'] = $wsId;

if ($statusFilter !== '') {
    $where[] = 'tr.status = :status';
    $params['status'] = $statusFilter;
}
if ($deptFilter !== '') {
    $where[] = 'tr.department_id = :dept';
    $params['dept'] = $deptFilter;
}
if ($typeFilter !== '') {
    $where[] = 'tr.testing_type_id = :type';
    $params['type'] = $typeFilter;
}
if ($testerFilter !== '') {
    $where[] = 'tr.tester_id = :tester';
    $params['tester'] = $testerFilter;
}
if ($search !== '') {
    $where[] = '(p.serial_number LIKE :q1 OR p.spec_model LIKE :q2 OR tt.name LIKE :q3)';
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
    $params['q3'] = '%' . $search . '%';
}

// Lab technicians see only their own records
if ($role === 'lab_technician') {
    $where[] = 'tr.tester_id = :myid';
    $params['myid'] = auth_id();
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM testing_records tr
    JOIN products p ON tr.product_id = p.id
    JOIN testing_types tt ON tr.testing_type_id = tt.id
    {$whereSql}
");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
    SELECT tr.*, p.serial_number, p.spec_model,
           tt.name AS test_type_name,
           d.name AS dept_name,
           u.name AS tester_name
    FROM testing_records tr
    JOIN products p ON tr.product_id = p.id
    JOIN testing_types tt ON tr.testing_type_id = tt.id
    LEFT JOIN departments d ON tr.department_id = d.id
    LEFT JOIN users u ON tr.tester_id = u.id
    {$whereSql}
    ORDER BY tr.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$records = $stmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M7 2v5l-4 7a1.5 1.5 0 001.3 2.2h11.4a1.5 1.5 0 001.3-2.2l-4-7V2"/><line x1="5" y1="2" x2="15" y2="2"/></svg>
      </div>
      <div>
        <h1 class="page-title">Testing Records</h1>
        <p class="page-subtitle" style="margin-top:2px;">Track testing assignments, measurements, and review status</p>
      </div>
    </div>
  </div>
</div>

<!-- Status Tabs -->
<div class="tabs" style="margin-bottom:20px;">
  <a class="tab <?= $statusFilter === '' ? 'active' : '' ?>" href="?">All <span style="background:var(--hold-tint);color:var(--hold);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;"><?= $totalCount ?></span></a>
  <a class="tab <?= $statusFilter === 'assigned' ? 'active' : '' ?>" href="?status=assigned">Assigned <span style="background:var(--info-tint);color:var(--info);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;"><?= $tabCounts['assigned'] ?? 0 ?></span></a>
  <a class="tab <?= $statusFilter === 'in_testing' ? 'active' : '' ?>" href="?status=in_testing">In Testing <span style="background:var(--warning-tint);color:var(--warning);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;"><?= $tabCounts['in_testing'] ?? 0 ?></span></a>
  <a class="tab <?= $statusFilter === 'submitted_for_review' ? 'active' : '' ?>" href="?status=submitted_for_review">Under Review <span style="background:var(--accent-tint);color:var(--accent);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;"><?= $tabCounts['submitted_for_review'] ?? 0 ?></span></a>
  <a class="tab <?= $statusFilter === 'passed' ? 'active' : '' ?>" href="?status=passed">Passed <span style="background:var(--success-tint);color:var(--success);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;"><?= $tabCounts['passed'] ?? 0 ?></span></a>
  <a class="tab <?= $statusFilter === 'failed' ? 'active' : '' ?>" href="?status=failed">Failed <span style="background:var(--fail-tint);color:var(--fail);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;"><?= $tabCounts['failed'] ?? 0 ?></span></a>
</div>

<!-- Filters -->
<form method="get" action="" class="filter-bar">
  <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
  <div class="filter-search">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
    <input type="text" name="q" class="form-control" placeholder="Search by product serial, test type…" value="<?= e($search) ?>">
  </div>
  <select name="department" class="form-control" style="width:180px;" onchange="this.form.submit()">
    <option value="">All Departments</option>
    <?php foreach ($deptList as $d): ?>
    <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="type" class="form-control" style="width:180px;" onchange="this.form.submit()">
    <option value="">All Test Types</option>
    <?php foreach ($typeList as $t): ?>
    <option value="<?= $t['id'] ?>" <?= $typeFilter == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="tester" class="form-control" style="width:160px;" onchange="this.form.submit()">
    <option value="">All Testers</option>
    <?php foreach ($testerList as $u): ?>
    <option value="<?= $u['id'] ?>" <?= $testerFilter == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<!-- Table -->
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th class="sortable">ID</th>
        <th class="sortable">Product</th>
        <th class="sortable">Test Type</th>
        <th class="sortable">Department</th>
        <th>Tester</th>
        <th class="sortable">Status</th>
        <th class="col-num sortable">Attempt</th>
        <th class="sortable">Due Date</th>
        <th class="col-actions">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($records)): ?>
      <tr><td colspan="9" style="text-align:center;color:var(--ink-faint);padding:24px;">No testing records found.</td></tr>
      <?php else: ?>
      <?php foreach ($records as $r): ?>
      <tr>
        <td class="col-mono" style="font-weight:600;">TR-<?= str_pad((string) $r['id'], 3, '0', STR_PAD_LEFT) ?></td>
        <td>
          <div style="font-weight:500;"><?= e($r['spec_model']) ?></div>
          <div style="font-size:0.75rem;color:var(--ink-faint);font-family:var(--font-mono);"><?= e($r['serial_number']) ?></div>
        </td>
        <td><?= e($r['test_type_name']) ?></td>
        <td><?= e($r['dept_name'] ?? '—') ?></td>
        <td>
          <div class="d-flex align-center gap-8">
            <span class="user-avatar" style="width:24px;height:24px;font-size:0.5625rem;"><?= e(user_initials($r['tester_name'] ?? '')) ?></span>
            <?= e($r['tester_name'] ?? '—') ?>
          </div>
        </td>
        <td><?= status_badge($r['status']) ?></td>
        <td class="col-num">#<?= (int) $r['attempt_number'] ?></td>
        <td class="text-soft" style="font-size:0.8125rem;white-space:nowrap;">
          <?php if ($r['due_date']): ?>
          <?php
          $due = new DateTimeImmutable($r['due_date']);
          $now = new DateTimeImmutable();
          $daysLeft = (int)$now->diff($due)->format('%r%a');
          $dueColor = $daysLeft < 0 ? 'var(--fail)' : ($daysLeft <= 2 ? 'var(--warning)' : 'var(--ink-soft)');
          ?>
          <span style="color:<?= $dueColor ?>;"><?= format_date($r['due_date']) ?></span>
          <?php else: ?>
          —
          <?php endif; ?>
        </td>
        <td class="col-actions">
          <a href="<?= url('testing/detail.php?id=' . $r['id']) ?>" class="btn btn-sm btn-ghost">Open</a>
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
    $baseUrl = '?' . http_build_query($qp);
  ?>
  <?= pagination_html($page, $totalPages, $baseUrl) ?>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
