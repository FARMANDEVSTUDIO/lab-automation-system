<?php
declare(strict_types=1);
// Reports — yield analytics, throughput charts, CSV export (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Reports';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Reports', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER, ROLE_QUALITY_MANAGER, ROLE_AUDITOR);

// Handle CSV export (must run before header.php outputs HTML)
$pdo = Database::getConnection();
$wsId = auth_workspace_id();
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');

    if ($exportType === 'yield') {
        header('Content-Disposition: attachment; filename="first_pass_yield_' . date('Ymd') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Date', 'Total Tests', 'Passed First Attempt', 'Yield %']);
        $stmt = $pdo->prepare("
            SELECT DATE(reviewed_at) AS review_date,
                   COUNT(*) AS total,
                   SUM(CASE WHEN review_decision = 'pass' AND attempt_number = 1 THEN 1 ELSE 0 END) AS first_pass
            FROM testing_records
            WHERE reviewed_at IS NOT NULL AND reviewed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND workspace_id = :ws
            GROUP BY DATE(reviewed_at) ORDER BY review_date
        ");
        $stmt->execute(['ws' => $wsId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $pct = $r['total'] > 0 ? round(($r['first_pass'] / $r['total']) * 100, 1) : 0;
            fputcsv($fp, [$r['review_date'], $r['total'], $r['first_pass'], $pct]);
        }
        fclose($fp);
        exit;
    }

    if ($exportType === 'throughput') {
        header('Content-Disposition: attachment; filename="department_throughput_' . date('Ymd') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Department', 'Products In', 'Tests Run', 'Passed', 'Failed', 'Pass Rate %', 'Avg Cycle Days']);
        $stmt = $pdo->prepare("
            SELECT d.name,
                   (SELECT COUNT(*) FROM products p WHERE p.department_id = d.id AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS products_in,
                   COUNT(tr.id) AS tests_run,
                   SUM(CASE WHEN tr.review_decision = 'pass' THEN 1 ELSE 0 END) AS passed,
                   SUM(CASE WHEN tr.review_decision = 'fail' THEN 1 ELSE 0 END) AS failed,
                   CASE WHEN COUNT(tr.id) > 0 THEN ROUND(SUM(CASE WHEN tr.review_decision = 'pass' THEN 1 ELSE 0 END) / COUNT(tr.id) * 100, 1) ELSE 0 END AS pass_rate,
                   ROUND(AVG(CASE WHEN tr.reviewed_at IS NOT NULL AND tr.started_at IS NOT NULL THEN DATEDIFF(tr.reviewed_at, tr.started_at) END), 1) AS avg_cycle
            FROM departments d
            LEFT JOIN testing_records tr ON tr.department_id = d.id AND tr.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE d.status = 'active' AND d.workspace_id = :ws
            GROUP BY d.id, d.name ORDER BY d.name
        ");
        $stmt->execute(['ws' => $wsId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            fputcsv($fp, [$r['name'], $r['products_in'], $r['tests_run'], $r['passed'], $r['failed'], $r['pass_rate'], $r['avg_cycle'] ?? '—']);
        }
        fclose($fp);
        exit;
    }

    if ($exportType === 'audit') {
        header('Content-Disposition: attachment; filename="audit_log_' . date('Ymd') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['ID', 'Timestamp', 'Actor', 'Action', 'Entity Type', 'Entity ID', 'IP Address']);
        $stmt = $pdo->prepare("
            SELECT al.id, al.created_at, u.name AS actor, al.action, al.entity_type, al.entity_id, al.ip_address
            FROM audit_log al LEFT JOIN users u ON al.actor_id = u.id
            WHERE al.workspace_id = :ws
            ORDER BY al.created_at DESC LIMIT 1000
        ");
        $stmt->execute(['ws' => $wsId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            fputcsv($fp, [$r['id'], $r['created_at'], $r['actor'] ?? 'System', $r['action'], $r['entity_type'], $r['entity_id'], $r['ip_address']]);
        }
        fclose($fp);
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

// Real data for First-Pass Yield chart
$yieldStmt = $pdo->prepare("
    SELECT DATE(reviewed_at) AS review_date,
           COUNT(*) AS total,
           SUM(CASE WHEN review_decision = 'pass' AND attempt_number = 1 THEN 1 ELSE 0 END) AS first_pass
    FROM testing_records
    WHERE reviewed_at IS NOT NULL AND reviewed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND workspace_id = :ws
    GROUP BY DATE(reviewed_at)
    ORDER BY review_date
");
$yieldStmt->execute(['ws' => $wsId]);
$yieldRows = $yieldStmt->fetchAll();

$yieldData = [];
$totalTests = 0;
$totalFirstPass = 0;
foreach ($yieldRows as $r) {
    $pct = $r['total'] > 0 ? round(($r['first_pass'] / $r['total']) * 100, 1) : 0;
    $yieldData[] = ['date' => $r['review_date'], 'pct' => $pct, 'total' => (int) $r['total']];
    $totalTests += (int) $r['total'];
    $totalFirstPass += (int) $r['first_pass'];
}
$avgYield = $totalTests > 0 ? round(($totalFirstPass / $totalTests) * 100, 1) : 0;
$bestDay = !empty($yieldData) ? max(array_column($yieldData, 'pct')) : 0;
$worstDay = !empty($yieldData) ? min(array_column($yieldData, 'pct')) : 0;

// Department throughput
$deptStmt = $pdo->prepare("
    SELECT d.name,
           (SELECT COUNT(*) FROM products p WHERE p.department_id = d.id AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS products_in,
           COUNT(tr.id) AS tests_run,
           SUM(CASE WHEN tr.review_decision = 'pass' THEN 1 ELSE 0 END) AS passed,
           SUM(CASE WHEN tr.review_decision = 'fail' THEN 1 ELSE 0 END) AS failed,
           CASE WHEN COUNT(tr.id) > 0 THEN ROUND(SUM(CASE WHEN tr.review_decision = 'pass' THEN 1 ELSE 0 END) / COUNT(tr.id) * 100, 1) ELSE 0 END AS pass_rate,
           ROUND(AVG(CASE WHEN tr.reviewed_at IS NOT NULL AND tr.started_at IS NOT NULL THEN DATEDIFF(tr.reviewed_at, tr.started_at) END), 1) AS avg_cycle
    FROM departments d
    LEFT JOIN testing_records tr ON tr.department_id = d.id AND tr.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE d.status = 'active' AND d.workspace_id = :ws
    GROUP BY d.id, d.name ORDER BY d.name
");
$deptStmt->execute(['ws' => $wsId]);
$deptThroughput = $deptStmt->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M3 16V6a2 2 0 012-2h10a2 2 0 012 2v10"/><line x1="7" y1="10" x2="7" y2="14"/><line x1="10" y1="7" x2="10" y2="14"/><line x1="13" y1="12" x2="13" y2="14"/></svg>
      </div>
      <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle" style="margin-top:2px;">Analytics, insights, and exportable reports</p>
      </div>
    </div>
  </div>
</div>

<!-- Report Templates -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:32px;">
  <?php
  $reportTemplates = [
    ['title' => 'First-Pass Yield', 'desc' => 'Percentage of products passing all tests on the first attempt, trended over time.', 'icon' => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,16 8,10 12,13 18,5"/></svg>', 'color' => 'var(--success)', 'url' => '?export=yield'],
    ['title' => 'Department Throughput', 'desc' => 'Products processed per department per week, with tester utilization rates.', 'icon' => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="12" width="3" height="7"/><rect x="9.5" y="8" width="3" height="11"/><rect x="16" y="4" width="3" height="15"/></svg>', 'color' => 'var(--warning)', 'url' => '?export=throughput'],
    ['title' => 'Audit Trail Export', 'desc' => 'Complete audit log export for compliance review — last 1000 entries.', 'icon' => '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M5 11l4 4 8-8"/><rect x="2" y="2" width="18" height="18" rx="3"/></svg>', 'color' => 'var(--info)', 'url' => '?export=audit'],
  ];
  foreach ($reportTemplates as $rt):
  ?>
  <a href="<?= e($rt['url']) ?>" class="card card-hover" style="cursor:pointer;text-decoration:none;color:inherit;">
    <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
      <div style="width:44px;height:44px;border-radius:var(--radius-md);background:color-mix(in srgb, <?= $rt['color'] ?> 12%, transparent);color:<?= $rt['color'] ?>;display:flex;align-items:center;justify-content:center;">
        <?= $rt['icon'] ?>
      </div>
      <div>
        <h3 style="font-size:0.9375rem;font-weight:600;margin-bottom:4px;"><?= e($rt['title']) ?></h3>
        <p style="font-size:0.8125rem;color:var(--ink-soft);line-height:1.5;"><?= e($rt['desc']) ?></p>
      </div>
      <div style="margin-top:auto;padding-top:8px;">
        <span style="font-size:0.75rem;color:var(--accent);font-weight:500;">Download CSV →</span>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<!-- Inline Report: First-Pass Yield -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h3 class="card-title">First-Pass Yield — Last 30 Days</h3>
    <a href="?export=yield" class="btn btn-sm btn-secondary">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 11v2h10v-2"/><polyline points="8,2 8,9"/><polyline points="5,6 8,9 11,6"/></svg>
      CSV
    </a>
  </div>
  <div class="card-body">
    <?php if (empty($yieldData)): ?>
    <div style="text-align:center;padding:40px;color:var(--ink-faint);font-size:0.875rem;">No reviewed tests in the last 30 days.</div>
    <?php else: ?>
    <div style="display:flex;align-items:flex-end;gap:4px;height:180px;padding-bottom:24px;border-bottom:1px solid var(--line);position:relative;">
      <div style="position:absolute;left:0;bottom:calc(24px + 180px * 0.9 * 0.9);width:100%;display:flex;align-items:center;gap:8px;">
        <span style="font-size:0.5625rem;color:var(--ink-faint);width:20px;">90%</span>
        <div style="flex:1;height:1px;border-top:1px dashed var(--success);opacity:0.4;"></div>
      </div>
      <?php foreach ($yieldData as $yd): ?>
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;">
        <div style="width:100%;height:<?= max(2, $yd['pct'] * 0.9 * (156 / 100)) ?>px;background:<?= $yd['pct'] >= 90 ? 'var(--success)' : 'var(--fail)' ?>;border-radius:2px 2px 0 0;opacity:0.7;min-height:2px;" title="<?= e($yd['date']) ?>: <?= $yd['pct'] ?>% (<?= $yd['total'] ?> tests)"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:20px;">
      <div><span class="stat-label">Average</span><div style="font-family:var(--font-display);font-weight:700;font-size:1.5rem;color:var(--success);"><?= $avgYield ?>%</div></div>
      <div><span class="stat-label">Best Day</span><div style="font-family:var(--font-display);font-weight:700;font-size:1.5rem;"><?= $bestDay ?>%</div></div>
      <div><span class="stat-label">Worst Day</span><div style="font-family:var(--font-display);font-weight:700;font-size:1.5rem;color:var(--fail);"><?= $worstDay ?>%</div></div>
      <div><span class="stat-label">Total Tests</span><div style="font-family:var(--font-display);font-weight:700;font-size:1.5rem;" data-count-to="<?= $totalTests ?>"><?= number_format($totalTests) ?></div></div>
    </div>
  </div>
</div>

<!-- Department Throughput Table -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Department Throughput — This Month</h3>
    <a href="?export=throughput" class="btn btn-sm btn-secondary">
      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 11v2h10v-2"/><polyline points="8,2 8,9"/><polyline points="5,6 8,9 11,6"/></svg>
      CSV
    </a>
  </div>
  <div style="overflow-x:auto;">
    <table class="data-table">
      <thead><tr><th>Department</th><th class="col-num">Products In</th><th class="col-num">Tests Run</th><th class="col-num">Passed</th><th class="col-num">Failed</th><th class="col-num">Pass Rate</th><th class="col-num">Avg Cycle</th></tr></thead>
      <tbody>
        <?php if (empty($deptThroughput)): ?>
        <tr><td colspan="7" style="text-align:center;color:var(--ink-faint);padding:24px;">No data available.</td></tr>
        <?php else: ?>
        <?php foreach ($deptThroughput as $dt): ?>
        <tr>
          <td><strong><?= e($dt['name']) ?></strong></td>
          <td class="col-num"><?= (int) $dt['products_in'] ?></td>
          <td class="col-num"><?= (int) $dt['tests_run'] ?></td>
          <td class="col-num" style="color:var(--success);"><?= (int) $dt['passed'] ?></td>
          <td class="col-num" style="color:var(--fail);"><?= (int) $dt['failed'] ?></td>
          <td class="col-num"><strong><?= $dt['pass_rate'] ?>%</strong></td>
          <td class="col-num"><?= $dt['avg_cycle'] !== null ? $dt['avg_cycle'] . 'd' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
