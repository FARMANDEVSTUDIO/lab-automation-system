<?php
declare(strict_types=1);
// Products listing — intake pipeline, lifecycle status, filters (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Products';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Products', 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';

$wsId = auth_workspace_id();

$statusFilter = $_GET['status'] ?? '';
$deptFilter   = $_GET['department'] ?? '';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

// Stats
$statsAllStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE workspace_id = :ws');
$statsAllStmt->execute(['ws' => $wsId]);
$statsTestingStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE workspace_id = :ws AND status = 'in_testing'");
$statsTestingStmt->execute(['ws' => $wsId]);
$statsReviewStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE workspace_id = :ws AND status = 'submitted_for_review'");
$statsReviewStmt->execute(['ws' => $wsId]);
$statsApprovedStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE workspace_id = :ws AND status IN ('approved','released')");
$statsApprovedStmt->execute(['ws' => $wsId]);
$statsFailedStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE workspace_id = :ws AND status IN ('failed_pending_rework','rejected')");
$statsFailedStmt->execute(['ws' => $wsId]);

$stats = [
    'all'     => (int) $statsAllStmt->fetchColumn(),
    'testing' => (int) $statsTestingStmt->fetchColumn(),
    'review'  => (int) $statsReviewStmt->fetchColumn(),
    'approved'=> (int) $statsApprovedStmt->fetchColumn(),
    'failed'  => (int) $statsFailedStmt->fetchColumn(),
];

// Departments for filter
$deptListStmt = $pdo->prepare("SELECT id, name FROM departments WHERE workspace_id = :ws AND status = 'active' ORDER BY name");
$deptListStmt->execute(['ws' => $wsId]);
$deptList = $deptListStmt->fetchAll();

// Build WHERE
$where = [];
$params = [];

$where[] = 'p.workspace_id = :ws';
$params['ws'] = $wsId;

if ($statusFilter !== '') {
    $where[] = 'p.status = :status';
    $params['status'] = $statusFilter;
}
if ($deptFilter !== '') {
    $where[] = 'p.department_id = :dept';
    $params['dept'] = $deptFilter;
}
if ($search !== '') {
    $where[] = '(p.serial_number LIKE :q1 OR p.spec_model LIKE :q2)';
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
    SELECT p.*, d.name AS department, u.name AS registered_by_name
    FROM products p
    LEFT JOIN departments d ON p.department_id = d.id
    LEFT JOIN users u ON p.registered_by = u.id
    {$whereSql}
    ORDER BY p.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M10 2L17 6v8l-7 4-7-4V6l7-4z"/><path d="M10 10l7-4M10 10v8M10 10L3 6"/></svg>
      </div>
      <div>
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle" style="margin-top:2px;">Manage product intake and lifecycle tracking</p>
      </div>
    </div>
  </div>
  <?php if (can('register_product')): ?>
  <div class="page-actions">
    <a href="<?= url('products/create.php') ?>" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      Register Product
    </a>
  </div>
  <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="stat-grid stat-grid-5" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px;">
  <div class="stat-tile" style="padding:16px 20px;"><span class="stat-label">All Products</span><span class="stat-value" style="font-size:1.5rem;" data-count-to="<?= $stats['all'] ?>"><?= $stats['all'] ?></span></div>
  <div class="stat-tile" style="padding:16px 20px;"><span class="stat-label">In Testing</span><span class="stat-value" style="font-size:1.5rem;color:var(--warning);" data-count-to="<?= $stats['testing'] ?>"><?= $stats['testing'] ?></span></div>
  <div class="stat-tile" style="padding:16px 20px;"><span class="stat-label">Under Review</span><span class="stat-value" style="font-size:1.5rem;color:var(--info);" data-count-to="<?= $stats['review'] ?>"><?= $stats['review'] ?></span></div>
  <div class="stat-tile" style="padding:16px 20px;"><span class="stat-label">Approved</span><span class="stat-value" style="font-size:1.5rem;color:var(--success);" data-count-to="<?= $stats['approved'] ?>"><?= $stats['approved'] ?></span></div>
  <div class="stat-tile" style="padding:16px 20px;"><span class="stat-label">Failed</span><span class="stat-value" style="font-size:1.5rem;color:var(--fail);" data-count-to="<?= $stats['failed'] ?>"><?= $stats['failed'] ?></span></div>
</div>

<!-- Filters -->
<form method="get" action="" class="filter-bar">
  <div class="filter-search">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
    <input type="text" name="q" class="form-control" placeholder="Search by serial, model…" value="<?= e($search) ?>">
  </div>
  <select name="status" class="form-control" style="width:180px;" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <?php
    $statuses = ['registered','assigned','in_testing','submitted_for_review','pending_cpri_approval','approved','released','failed_pending_rework','on_hold','rejected'];
    foreach ($statuses as $s): ?>
    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="department" class="form-control" style="width:180px;" onchange="this.form.submit()">
    <option value="">All Departments</option>
    <?php foreach ($deptList as $d): ?>
    <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<!-- Products Table -->
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th class="sortable">Serial Number</th>
        <th class="sortable">Model / Spec</th>
        <th class="sortable">Department</th>
        <th class="sortable">Status</th>
        <th class="sortable">Attempts</th>
        <th>Registered By</th>
        <th class="sortable">Date</th>
        <th class="col-actions">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($products)): ?>
      <tr><td colspan="8" style="text-align:center;color:var(--ink-faint);padding:24px;">No products found.</td></tr>
      <?php else: ?>
      <?php foreach ($products as $p): ?>
      <tr>
        <td class="col-mono">
          <a href="<?= url('products/detail.php?id=' . $p['id']) ?>" style="font-weight:600;"><?= e($p['serial_number']) ?></a>
        </td>
        <td><?= e($p['spec_model']) ?></td>
        <td><?= e($p['department'] ?? '—') ?></td>
        <td><?= status_badge($p['status']) ?></td>
        <td class="col-num"><?= (int) $p['attempt_count'] ?></td>
        <td class="text-soft"><?= e($p['registered_by_name'] ?? '—') ?></td>
        <td class="text-soft" style="font-size:0.8125rem;white-space:nowrap;"><?= format_date($p['created_at']) ?></td>
        <td class="col-actions">
          <a href="<?= url('products/detail.php?id=' . $p['id']) ?>" class="btn btn-sm btn-ghost">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
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
