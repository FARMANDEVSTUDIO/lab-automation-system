<?php
declare(strict_types=1);
// Testers management — lab technician assignment (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Testers';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Configuration', 'url' => '#'],
    ['label' => 'Testers', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR);
$wsId = auth_workspace_id();
require_once __DIR__ . '/../includes/header.php';

// Fetch testers with stats
$testersStmt = $pdo->prepare("
    SELECT u.*, d.name AS dept_name,
           (SELECT COUNT(*) FROM testing_records tr WHERE tr.tester_id = u.id AND tr.status IN ('assigned','in_testing')) AS active_tests,
           (SELECT COUNT(*) FROM testing_records tr WHERE tr.tester_id = u.id AND tr.review_decision IS NOT NULL) AS completed,
           (SELECT COUNT(*) FROM testing_records tr WHERE tr.tester_id = u.id AND tr.review_decision = 'pass') AS passed
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.role IN ('lab_technician','testing_engineer') AND u.workspace_id = :ws
    ORDER BY u.name ASC
");
$testersStmt->execute(['ws' => $wsId]);
$testers = $testersStmt->fetchAll();

$deptListStmt = $pdo->prepare("SELECT id, name FROM departments WHERE status = 'active' AND workspace_id = :ws ORDER BY name");
$deptListStmt->execute(['ws' => $wsId]);
$deptList = $deptListStmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><circle cx="7" cy="7" r="3"/><path d="M2 17c0-3 2.2-5.5 5-5.5s5 2.5 5 5.5"/><circle cx="15" cy="7" r="2"/><path d="M18 17c0-2.3-1.6-4-3.5-4"/></svg>
      </div>
      <div>
        <h1 class="page-title">Testers</h1>
        <p class="page-subtitle" style="margin-top:2px;">Lab Technicians and Testing Engineers across all departments</p>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="filter-bar" id="testerFilterBar">
  <div class="filter-search">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
    <input type="text" class="form-control" placeholder="Search testers…" id="testerSearch" aria-label="Search testers">
  </div>
  <select class="form-control" style="width:180px;" id="testerDeptFilter" aria-label="Filter by department">
    <option value="">All Departments</option>
    <?php foreach ($deptList as $d): ?>
    <option><?= e($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="form-control" style="width:160px;" id="testerRoleFilter" aria-label="Filter by role">
    <option value="">All Roles</option>
    <option>Lab Technician</option>
    <option>Testing Engineer</option>
  </select>
  <select class="form-control" style="width:140px;" id="testerStatusFilter" aria-label="Filter by status">
    <option value="">All Status</option>
    <option>Active</option>
    <option>Inactive</option>
  </select>
</div>

<!-- Tester Cards -->
<div class="tester-cards-grid" id="testerCardsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
  <?php foreach ($testers as $t):
    $passRate = $t['completed'] > 0 ? round(((int) $t['passed'] / (int) $t['completed']) * 100) : 0;
  ?>
  <div class="card card-hover tester-card" data-name="<?= e(strtolower($t['name'])) ?>" data-dept="<?= e(strtolower($t['dept_name'] ?? '')) ?>" data-role="<?= e(strtolower(role_label($t['role']))) ?>" data-status="<?= e($t['status']) ?>">
    <div class="card-body" style="padding:20px;">
      <div class="d-flex align-center gap-12" style="margin-bottom:16px;">
        <span class="user-avatar" style="width:44px;height:44px;font-size:0.875rem;"><?= e(user_initials($t['name'])) ?></span>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:600;font-size:0.9375rem;"><?= e($t['name']) ?></div>
          <div style="font-size:0.75rem;color:var(--ink-faint);"><?= e(role_label($t['role'])) ?> · <?= e($t['dept_name'] ?? '—') ?></div>
        </div>
        <?= status_badge($t['status']) ?>
      </div>
      <div class="text-mono" style="font-size:0.75rem;color:var(--ink-faint);margin-bottom:12px;"><?= e($t['email']) ?></div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;padding-top:12px;border-top:1px solid var(--line);">
        <div>
          <div style="font-weight:700;font-size:1rem;font-variant-numeric:tabular-nums;"><?= (int) $t['active_tests'] ?></div>
          <div style="font-size:0.6875rem;color:var(--ink-faint);">Active Tests</div>
        </div>
        <div>
          <div style="font-weight:700;font-size:1rem;font-variant-numeric:tabular-nums;"><?= (int) $t['completed'] ?></div>
          <div style="font-size:0.6875rem;color:var(--ink-faint);">Completed</div>
        </div>
        <div>
          <div style="font-weight:700;font-size:1rem;color:var(--success);font-variant-numeric:tabular-nums;"><?= $passRate ?>%</div>
          <div style="font-size:0.6875rem;color:var(--ink-faint);">Pass Rate</div>
        </div>
      </div>
    </div>
    <div class="card-footer" style="display:flex;justify-content:flex-end;gap:8px;">
      <a href="<?= url('testing/index.php?tester=' . $t['id']) ?>" class="btn btn-sm btn-ghost">View Work</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if (empty($testers)): ?>
<div style="text-align:center;padding:48px 24px;">
  <p style="font-size:0.9375rem;font-weight:600;color:var(--ink-soft);">No testers found</p>
  <p style="font-size:0.8125rem;color:var(--ink-faint);">Add users with Lab Technician or Testing Engineer role.</p>
</div>
<?php endif; ?>

<!-- Empty state for filtered cards -->
<div id="testerEmptyState" style="display:none;text-align:center;padding:48px 24px;">
  <p style="font-size:0.9375rem;font-weight:600;color:var(--ink-soft);">No testers found</p>
  <p style="font-size:0.8125rem;color:var(--ink-faint);">Try adjusting your search or filter criteria.</p>
</div>

<script>
(function() {
  var search = document.getElementById('testerSearch');
  var deptFilter = document.getElementById('testerDeptFilter');
  var roleFilter = document.getElementById('testerRoleFilter');
  var statusFilter = document.getElementById('testerStatusFilter');
  var grid = document.getElementById('testerCardsGrid');
  var emptyState = document.getElementById('testerEmptyState');

  function filterCards() {
    var query = (search ? search.value : '').toLowerCase();
    var dept = (deptFilter ? deptFilter.value : '').toLowerCase();
    var role = (roleFilter ? roleFilter.value : '').toLowerCase();
    var status = (statusFilter ? statusFilter.value : '').toLowerCase();
    var cards = grid.querySelectorAll('.tester-card');
    var visible = 0;

    cards.forEach(function(card) {
      var matchName = !query || card.getAttribute('data-name').indexOf(query) !== -1 ||
                      card.querySelector('.text-mono').textContent.toLowerCase().indexOf(query) !== -1;
      var matchDept = !dept || card.getAttribute('data-dept').indexOf(dept) !== -1;
      var matchRole = !role || card.getAttribute('data-role').indexOf(role) !== -1;
      var matchStatus = !status || card.getAttribute('data-status') === status.toLowerCase();
      var show = matchName && matchDept && matchRole && matchStatus;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    emptyState.style.display = visible === 0 ? '' : 'none';
  }

  if (search) search.addEventListener('input', filterCards);
  if (deptFilter) deptFilter.addEventListener('change', filterCards);
  if (roleFilter) roleFilter.addEventListener('change', filterCards);
  if (statusFilter) statusFilter.addEventListener('change', filterCards);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
