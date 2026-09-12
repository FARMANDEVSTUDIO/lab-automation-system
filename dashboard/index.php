<?php
declare(strict_types=1);
// Dashboard — role-based stats and activity feed (Farman)
$pageTitle = 'Dashboard';
$breadcrumbs = [['label' => 'Dashboard', 'url' => '']];
require_once __DIR__ . '/../includes/header.php';

$role = auth_role();
$uid  = auth_id();

/* ==========================================================================
   Shared icon fragments — inner SVG markup, dropped into stroke-based
   <svg> wrappers at render time. Purely decorative, no escaping needed.
   ========================================================================== */
$statIcons = [
    'box'      => '<path d="M10 2L17 6v8l-7 4-7-4V6l7-4z"/><path d="M10 10l7-4M10 10v8M10 10L3 6"/>',
    'flask'    => '<path d="M7 2v5l-4 7a1.5 1.5 0 001.3 2.2h11.4a1.5 1.5 0 001.3-2.2l-4-7V2"/><line x1="5" y1="2" x2="15" y2="2"/>',
    'clock'    => '<circle cx="10" cy="10" r="7"/><polyline points="10,6 10,10 13,12"/>',
    'check'    => '<polyline points="4,10 8,14 16,6"/>',
    'users'    => '<circle cx="7.5" cy="6.5" r="3"/><path d="M2 17c0-3.3 2.5-6 5.5-6s5.5 2.7 5.5 6"/><circle cx="15" cy="7" r="2.2"/><path d="M13.6 11.3c2 .4 3.4 2.3 3.4 5.7"/>',
    'alert'    => '<circle cx="10" cy="10" r="7"/><line x1="10" y1="6.5" x2="10" y2="10.8"/><circle cx="10" cy="13.6" r="0.6" fill="currentColor"/>',
    'calendar' => '<rect x="3" y="4" width="14" height="13" rx="1.5"/><line x1="3" y1="8" x2="17" y2="8"/><line x1="7" y1="2" x2="7" y2="5"/><line x1="13" y1="2" x2="13" y2="5"/>',
    'target'   => '<circle cx="10" cy="10" r="7"/><circle cx="10" cy="10" r="3.6"/><circle cx="10" cy="10" r="0.6" fill="currentColor"/>',
    'history'  => '<path d="M3.6 6.8A7 7 0 1110 17"/><polyline points="10,6 10,10 13,12"/><polyline points="2,4 3.6,6.8 6.4,5.3"/>',
];

$qaIcons = [
    'plus'     => '<line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/>',
    'testing'  => '<path d="M6 2v4l-3 5.5a1.2 1.2 0 001 1.7h8a1.2 1.2 0 001-1.7L10 6V2"/><line x1="4" y1="2" x2="12" y2="2"/>',
    'reports'  => '<path d="M3 14V4a2 2 0 012-2h6a2 2 0 012 2v10"/><line x1="6" y1="7" x2="6" y2="11"/><line x1="8" y1="5" x2="8" y2="11"/><line x1="10" y1="9" x2="10" y2="11"/>',
    'users'    => '<circle cx="8" cy="6" r="2.5"/><path d="M2.5 14c0-3 2.5-5.5 5.5-5.5s5.5 2.5 5.5 5.5"/>',
    'settings' => '<circle cx="8" cy="8" r="2"/><path d="M8 2.5v2M8 11.5v2M2.5 8h2M11.5 8h2M4.4 4.4l1.4 1.4M10.2 10.2l1.4 1.4M4.4 11.6l1.4-1.4M10.2 5.8l1.4-1.4"/>',
    'types'    => '<rect x="3" y="3" width="10" height="3" rx="1"/><rect x="3" y="8" width="10" height="3" rx="1"/>',
    'audit'    => '<path d="M4 2h6l3 3v9a1 1 0 01-1 1H4a1 1 0 01-1-1V3a1 1 0 011-1z"/><path d="M10 2v3h3"/><line x1="5.5" y1="8" x2="10.5" y2="8"/><line x1="5.5" y1="10.5" x2="9" y2="10.5"/>',
];

/* ==========================================================================
   Small render helpers shared across role blocks.
   ========================================================================== */
function audit_action_label(string $action): string
{
    $map = [
        'product.register'    => 'registered',
        'product.update'      => 'updated',
        'testing.assign'      => 'assigned test for',
        'testing.start'       => 'started testing',
        'testing.submit'      => 'submitted measurements for',
        'testing.pass'        => 'approved test for',
        'testing.fail'        => 'failed test for',
        'cpri.approve'        => 'CPRI approved',
        'cpri.reject'         => 'CPRI rejected',
        'auth.login'          => 'logged in',
        'user.login'          => 'logged in',
        'auth.logout'         => 'logged out',
        'testing_type.update' => 'updated testing type',
        'attachment.delete'   => 'removed an attachment from',
    ];
    return $map[$action] ?? str_replace(['.', '_'], ' ', $action);
}

function render_attention_items(array $items): void
{
    if (!$items) return;
    ?>
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Needs Your Attention</h3>
        <span class="status-badge badge-warning"><span class="status-dot"></span><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></span>
      </div>
      <div class="card-body" style="padding:0;">
        <?php foreach ($items as $item): ?>
        <div class="attention-item" onclick="location.href='<?= $item['url'] ?>'">
          <div class="attention-icon" style="background:var(--<?= $item['color'] ?>-tint);color:var(--<?= $item['color'] ?>);">
            <?php if ($item['icon'] === 'review' || $item['icon'] === 'due'): ?>
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="9" cy="9" r="7"/><polyline points="9,5 9,9 12,11"/></svg>
            <?php elseif ($item['icon'] === 'approve'): ?>
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 9l3 3 7-7"/></svg>
            <?php elseif ($item['icon'] === 'failed'): ?>
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="9" cy="9" r="7"/><line x1="6.5" y1="6.5" x2="11.5" y2="11.5"/><line x1="11.5" y1="6.5" x2="6.5" y2="11.5"/></svg>
            <?php elseif ($item['icon'] === 'hold'): ?>
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="5" y="4" width="3" height="10" rx="1"/><rect x="10" y="4" width="3" height="10" rx="1"/></svg>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:0.8125rem;"><?= $item['title'] ?></div>
            <div style="font-size:0.75rem;color:var(--ink-faint);"><?= $item['subtitle'] ?></div>
          </div>
          <span class="status-badge <?= $item['badge_class'] ?>"><span class="status-dot"></span><?= $item['badge'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

function render_activity_feed(array $feed, string $title = 'Activity'): void
{
    ?>
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><?= e($title) ?></h3>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($feed)): ?>
        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No recent activity.</div>
        <?php else: ?>
        <ul class="activity-list" style="padding:0 24px;">
          <?php foreach ($feed as $act):
            $verb = audit_action_label($act['action']);
            $after = $act['after_json'] ? json_decode($act['after_json'], true) : [];
            $target = $after['serial_number'] ?? $after['spec_model'] ?? $after['product'] ?? $after['name'] ?? $after['email'] ?? '';
          ?>
          <li class="activity-item">
            <div class="activity-avatar"><?= e(user_initials($act['actor_name'] ?? 'System')) ?></div>
            <div class="activity-body">
              <div class="activity-text">
                <strong><?= e($act['actor_name'] ?? 'System') ?></strong> <?= e($verb) ?>
                <?php if ($target): ?>
                  <span style="font-weight:500;"><?= e($target) ?></span>
                <?php endif; ?>
              </div>
              <div class="activity-meta"><?= e(time_ago($act['created_at'])) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

function render_quick_actions(array $actions, array $icons): void
{
    ?>
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:6px;">
        <?php if (empty($actions)): ?>
        <div style="padding:8px 0;color:var(--ink-faint);font-size:0.8125rem;">No actions available.</div>
        <?php endif; ?>
        <?php foreach ($actions as $a): ?>
        <a href="<?= $a['url'] ?>" class="btn btn-secondary w-100" style="justify-content:flex-start;">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><?= $icons[$a['icon']] ?></svg>
          <?= e($a['label']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}

/* ==========================================================================
   Role-specific data
   ========================================================================== */
$stats          = [];
$attentionItems = [];
$quickActions   = [];
$pageActionUrl  = null; // header CTA (administrator only)
$wsId           = auth_workspace_id();

if ($role === ROLE_ADMINISTRATOR) {

    $totalUsersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE workspace_id = :ws");
    $totalUsersStmt->execute(['ws' => $wsId]);
    $totalUsers = (int) $totalUsersStmt->fetchColumn();

    $totalProductsStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE workspace_id = :ws");
    $totalProductsStmt->execute(['ws' => $wsId]);
    $totalProducts = (int) $totalProductsStmt->fetchColumn();

    $activeTestsStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status IN ('assigned','in_testing') AND workspace_id = :ws");
    $activeTestsStmt->execute(['ws' => $wsId]);
    $activeTests = (int) $activeTestsStmt->fetchColumn();

    $pendingReviewStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status = 'submitted_for_review' AND workspace_id = :ws");
    $pendingReviewStmt->execute(['ws' => $wsId]);
    $pendingReview = (int) $pendingReviewStmt->fetchColumn();

    $stats = [
        ['label' => 'Total Users',    'value' => $totalUsers,    'icon' => 'users', 'color' => 'accent'],
        ['label' => 'Total Products', 'value' => $totalProducts, 'icon' => 'box',   'color' => 'info'],
        ['label' => 'Active Tests',   'value' => $activeTests,   'icon' => 'flask', 'color' => 'warning'],
        ['label' => 'Pending Review', 'value' => $pendingReview, 'icon' => 'clock', 'color' => 'fail'],
    ];

    // Needs Attention — reviews, CPRI approvals, failed tests, due soon, on hold
    $stmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, tt.name AS test_name, u.name AS tester_name, tr.submitted_at
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        JOIN users u ON tr.tester_id = u.id
        WHERE tr.status = 'submitted_for_review' AND tr.workspace_id = :ws
        ORDER BY tr.submitted_at ASC LIMIT 3
    ");
    $stmt->execute(['ws' => $wsId]);
    foreach ($stmt as $row) {
        $attentionItems[] = [
            'icon' => 'review', 'color' => 'warning',
            'title' => e($row['spec_model']) . ' — measurements submitted',
            'subtitle' => e($row['tester_name']) . ' · ' . e($row['test_name']) . ' · ' . time_ago($row['submitted_at']),
            'badge' => 'Review', 'badge_class' => 'badge-warning',
            'url' => url('testing/detail.php?id=' . $row['id']),
        ];
    }

    $stmt = $pdo->prepare("
        SELECT id, spec_model, updated_at FROM products
        WHERE status = 'pending_cpri_approval' AND workspace_id = :ws ORDER BY updated_at ASC LIMIT 3
    ");
    $stmt->execute(['ws' => $wsId]);
    foreach ($stmt as $row) {
        $attentionItems[] = [
            'icon' => 'approve', 'color' => 'info',
            'title' => e($row['spec_model']) . ' — CPRI approval needed',
            'subtitle' => 'All tests passed · Pending QM decision · ' . time_ago($row['updated_at']),
            'badge' => 'Approve', 'badge_class' => 'badge-info',
            'url' => url('products/detail.php?id=' . $row['id']),
        ];
    }

    $stmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, tt.name AS test_name, tr.attempt_number
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        WHERE tr.status = 'failed' AND tr.workspace_id = :ws ORDER BY tr.reviewed_at DESC LIMIT 3
    ");
    $stmt->execute(['ws' => $wsId]);
    foreach ($stmt as $row) {
        $attentionItems[] = [
            'icon' => 'failed', 'color' => 'fail',
            'title' => e($row['spec_model']) . ' — rework required',
            'subtitle' => 'Failed ' . e($row['test_name']) . ' (attempt ' . $row['attempt_number'] . ')',
            'badge' => 'Failed', 'badge_class' => 'badge-fail',
            'url' => url('testing/detail.php?id=' . $row['id']),
        ];
    }

    $stmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, tr.due_date FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        WHERE tr.status IN ('assigned','in_testing') AND tr.due_date IS NOT NULL
          AND tr.due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND tr.workspace_id = :ws
        ORDER BY tr.due_date ASC LIMIT 3
    ");
    $stmt->execute(['ws' => $wsId]);
    $dueSoon = $stmt->fetchAll();
    if ($dueSoon) {
        $labels = array_map(fn($r) => e($r['spec_model']) . ' (' . date('M j', strtotime($r['due_date'])) . ')', $dueSoon);
        $attentionItems[] = [
            'icon' => 'due', 'color' => 'warning',
            'title' => count($dueSoon) . ' test(s) approaching due date',
            'subtitle' => implode(' · ', $labels),
            'badge' => 'Due soon', 'badge_class' => 'badge-warning',
            'url' => url('testing/index.php'),
        ];
    }

    $stmt = $pdo->prepare("SELECT id, spec_model, updated_at FROM products WHERE status = 'on_hold' AND workspace_id = :ws ORDER BY updated_at ASC LIMIT 3");
    $stmt->execute(['ws' => $wsId]);
    foreach ($stmt as $row) {
        $attentionItems[] = [
            'icon' => 'hold', 'color' => 'hold',
            'title' => e($row['spec_model']) . ' — on hold',
            'subtitle' => 'Since ' . date('M j', strtotime($row['updated_at'])),
            'badge' => 'On Hold', 'badge_class' => 'badge-hold',
            'url' => url('products/detail.php?id=' . $row['id']),
        ];
    }
    $attentionItems = array_slice($attentionItems, 0, 6);

    $recentProductsStmt = $pdo->prepare("
        SELECT p.id, p.serial_number, p.spec_model, p.status, p.created_at, d.name AS department
        FROM products p LEFT JOIN departments d ON p.department_id = d.id
        WHERE p.workspace_id = :ws
        ORDER BY p.created_at DESC LIMIT 5
    ");
    $recentProductsStmt->execute(['ws' => $wsId]);
    $recentProducts = $recentProductsStmt->fetchAll();

    $departmentsStmt = $pdo->prepare("
        SELECT d.id, d.name, d.description, d.status,
               (SELECT COUNT(*) FROM testing_records tr WHERE tr.department_id = d.id AND tr.status IN ('assigned','in_testing')) AS active_tests
        FROM departments d WHERE d.status = 'active' AND d.workspace_id = :ws ORDER BY d.name ASC
    ");
    $departmentsStmt->execute(['ws' => $wsId]);
    $departments = $departmentsStmt->fetchAll();

    $activityFeedStmt = $pdo->prepare("
        SELECT al.action, al.entity_type, al.entity_id, al.after_json, al.created_at, u.name AS actor_name
        FROM audit_log al LEFT JOIN users u ON al.actor_id = u.id
        WHERE al.workspace_id = :ws
        ORDER BY al.created_at DESC LIMIT 8
    ");
    $activityFeedStmt->execute(['ws' => $wsId]);
    $activityFeed = $activityFeedStmt->fetchAll();

    if (can('register_product')) $quickActions[] = ['url' => url('products/create.php'), 'icon' => 'plus', 'label' => 'Register New Product'];
    $quickActions[] = ['url' => url('testing/index.php'), 'icon' => 'testing', 'label' => 'View Testing Queue'];
    if (can('view_reports')) $quickActions[] = ['url' => url('reports/index.php'), 'icon' => 'reports', 'label' => 'Generate Report'];
    if (can('manage_users')) $quickActions[] = ['url' => url('admin/users.php'), 'icon' => 'users', 'label' => 'Manage Users'];
    if (can('manage_settings')) $quickActions[] = ['url' => url('admin/settings.php'), 'icon' => 'settings', 'label' => 'System Settings'];

    if (can('register_product')) $pageActionUrl = url('products/create.php');

} elseif ($role === ROLE_TESTING_ENGINEER) {

    $assignedTestsStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status = 'assigned' AND workspace_id = :ws");
    $assignedTestsStmt->execute(['ws' => $wsId]);
    $assignedTests = (int) $assignedTestsStmt->fetchColumn();

    $activeTestsStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status = 'in_testing' AND workspace_id = :ws");
    $activeTestsStmt->execute(['ws' => $wsId]);
    $activeTests = (int) $activeTestsStmt->fetchColumn();

    $pendingReviewStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status = 'submitted_for_review' AND workspace_id = :ws");
    $pendingReviewStmt->execute(['ws' => $wsId]);
    $pendingReview = (int) $pendingReviewStmt->fetchColumn();

    $passedCountStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE review_decision = 'pass' AND workspace_id = :ws");
    $passedCountStmt->execute(['ws' => $wsId]);
    $passedCount = (int) $passedCountStmt->fetchColumn();

    $decidedCountStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE review_decision IS NOT NULL AND workspace_id = :ws");
    $decidedCountStmt->execute(['ws' => $wsId]);
    $decidedCount = (int) $decidedCountStmt->fetchColumn();
    $firstPassYield = $decidedCount > 0 ? round($passedCount / $decidedCount * 100, 1) : 0;

    $stats = [
        ['label' => 'Assigned Tests',   'value' => $assignedTests,  'icon' => 'clock', 'color' => 'info'],
        ['label' => 'Active Tests',     'value' => $activeTests,    'icon' => 'flask', 'color' => 'warning'],
        ['label' => 'Pending Review',   'value' => $pendingReview,  'icon' => 'alert', 'color' => 'fail'],
        ['label' => 'First-Pass Yield', 'value' => $firstPassYield, 'icon' => 'check', 'color' => 'success', 'suffix' => '%'],
    ];

    $stmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, tt.name AS test_name, u.name AS tester_name, tr.submitted_at
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        JOIN users u ON tr.tester_id = u.id
        WHERE tr.status = 'submitted_for_review' AND tr.workspace_id = :ws
        ORDER BY tr.submitted_at ASC LIMIT 4
    ");
    $stmt->execute(['ws' => $wsId]);
    foreach ($stmt as $row) {
        $attentionItems[] = [
            'icon' => 'review', 'color' => 'warning',
            'title' => e($row['spec_model']) . ' — measurements submitted',
            'subtitle' => e($row['tester_name']) . ' · ' . e($row['test_name']) . ' · ' . time_ago($row['submitted_at']),
            'badge' => 'Review', 'badge_class' => 'badge-warning',
            'url' => url('testing/detail.php?id=' . $row['id']),
        ];
    }

    $stmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, tt.name AS test_name, tr.attempt_number
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        WHERE tr.status = 'failed' AND tr.workspace_id = :ws ORDER BY tr.reviewed_at DESC LIMIT 3
    ");
    $stmt->execute(['ws' => $wsId]);
    foreach ($stmt as $row) {
        $attentionItems[] = [
            'icon' => 'failed', 'color' => 'fail',
            'title' => e($row['spec_model']) . ' — rework required',
            'subtitle' => 'Failed ' . e($row['test_name']) . ' (attempt ' . $row['attempt_number'] . ')',
            'badge' => 'Failed', 'badge_class' => 'badge-fail',
            'url' => url('testing/detail.php?id=' . $row['id']),
        ];
    }
    $attentionItems = array_slice($attentionItems, 0, 6);

    $myTestsStmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, p.serial_number, tt.name AS test_name, u.name AS tester_name,
               tr.status, tr.due_date, tr.created_at
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        JOIN users u ON tr.tester_id = u.id
        WHERE tr.assigned_by = :uid AND tr.workspace_id = :ws
        ORDER BY tr.created_at DESC LIMIT 8
    ");
    $myTestsStmt->execute(['uid' => $uid, 'ws' => $wsId]);
    $myRecentTests = $myTestsStmt->fetchAll();

    $activityFeedStmt = $pdo->prepare("
        SELECT al.action, al.entity_type, al.entity_id, al.after_json, al.created_at, u.name AS actor_name
        FROM audit_log al LEFT JOIN users u ON al.actor_id = u.id
        WHERE al.workspace_id = :ws
        ORDER BY al.created_at DESC LIMIT 8
    ");
    $activityFeedStmt->execute(['ws' => $wsId]);
    $activityFeed = $activityFeedStmt->fetchAll();

    if (can('register_product')) $quickActions[] = ['url' => url('products/create.php'), 'icon' => 'plus', 'label' => 'Register New Product'];
    $quickActions[] = ['url' => url('testing/index.php'), 'icon' => 'testing', 'label' => 'View Testing Queue'];
    if (can('view_reports')) $quickActions[] = ['url' => url('reports/index.php'), 'icon' => 'reports', 'label' => 'Generate Report'];
    if (can('manage_testing_types')) $quickActions[] = ['url' => url('admin/testing-types.php'), 'icon' => 'types', 'label' => 'Manage Testing Types'];

} elseif ($role === ROLE_LAB_TECHNICIAN) {

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE tester_id = :uid AND status IN ('assigned','in_testing') AND workspace_id = :ws");
    $countStmt->execute(['uid' => $uid, 'ws' => $wsId]);
    $myActiveTests = (int) $countStmt->fetchColumn();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE tester_id = :uid AND status = 'assigned' AND workspace_id = :ws");
    $countStmt->execute(['uid' => $uid, 'ws' => $wsId]);
    $pendingMeasurements = (int) $countStmt->fetchColumn();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE tester_id = :uid AND status = 'submitted_for_review' AND workspace_id = :ws");
    $countStmt->execute(['uid' => $uid, 'ws' => $wsId]);
    $submittedForReview = (int) $countStmt->fetchColumn();

    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM testing_records
        WHERE tester_id = :uid AND status IN ('passed','failed') AND reviewed_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND workspace_id = :ws
    ");
    $countStmt->execute(['uid' => $uid, 'ws' => $wsId]);
    $completedThisMonth = (int) $countStmt->fetchColumn();

    $stats = [
        ['label' => 'My Active Tests',        'value' => $myActiveTests,        'icon' => 'flask',    'color' => 'warning'],
        ['label' => 'Pending Measurements',   'value' => $pendingMeasurements,  'icon' => 'clock',    'color' => 'info'],
        ['label' => 'Submitted for Review',   'value' => $submittedForReview,   'icon' => 'alert',    'color' => 'hold'],
        ['label' => 'Completed This Month',   'value' => $completedThisMonth,   'icon' => 'calendar', 'color' => 'success'],
    ];

    $queueStmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, p.serial_number, tt.name AS test_name, tr.status, tr.due_date, tr.started_at
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        WHERE tr.tester_id = :uid AND tr.status IN ('assigned','in_testing') AND tr.workspace_id = :ws
        ORDER BY (tr.due_date IS NULL), tr.due_date ASC LIMIT 10
    ");
    $queueStmt->execute(['uid' => $uid, 'ws' => $wsId]);
    $myQueue = $queueStmt->fetchAll();

    $quickActions[] = ['url' => url('testing/index.php'), 'icon' => 'testing', 'label' => 'View Testing Queue'];

} elseif ($role === ROLE_QUALITY_MANAGER) {

    $pendingReviewQMStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status = 'submitted_for_review' AND workspace_id = :ws");
    $pendingReviewQMStmt->execute(['ws' => $wsId]);
    $pendingReviewQM = (int) $pendingReviewQMStmt->fetchColumn();

    $failedTestsQMStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE status = 'failed' AND workspace_id = :ws");
    $failedTestsQMStmt->execute(['ws' => $wsId]);
    $failedTestsQM = (int) $failedTestsQMStmt->fetchColumn();

    $passedQMStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE review_decision = 'pass' AND workspace_id = :ws");
    $passedQMStmt->execute(['ws' => $wsId]);
    $passedQM = (int) $passedQMStmt->fetchColumn();

    $decidedQMStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE review_decision IS NOT NULL AND workspace_id = :ws");
    $decidedQMStmt->execute(['ws' => $wsId]);
    $decidedQM = (int) $decidedQMStmt->fetchColumn();
    $passRate = $decidedQM > 0 ? round($passedQM / $decidedQM * 100, 1) : 0;

    $approvedThisMonthStmt = $pdo->prepare("
        SELECT COUNT(*) FROM audit_log WHERE action = 'cpri.approve' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND workspace_id = :ws
    ");
    $approvedThisMonthStmt->execute(['ws' => $wsId]);
    $approvedThisMonth = (int) $approvedThisMonthStmt->fetchColumn();

    $stats = [
        ['label' => 'Pending Review',      'value' => $pendingReviewQM,   'icon' => 'clock',    'color' => 'warning'],
        ['label' => 'Failed Tests',        'value' => $failedTestsQM,     'icon' => 'alert',    'color' => 'fail'],
        ['label' => 'Pass Rate',           'value' => $passRate,          'icon' => 'check',    'color' => 'success', 'suffix' => '%'],
        ['label' => 'Approved This Month', 'value' => $approvedThisMonth, 'icon' => 'calendar', 'color' => 'accent'],
    ];

    $reviewQueueStmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, p.serial_number, tt.name AS test_name, u.name AS tester_name, tr.submitted_at
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        JOIN users u ON tr.tester_id = u.id
        WHERE tr.status = 'submitted_for_review' AND tr.workspace_id = :ws
        ORDER BY tr.submitted_at ASC LIMIT 8
    ");
    $reviewQueueStmt->execute(['ws' => $wsId]);
    $reviewQueue = $reviewQueueStmt->fetchAll();

    $recentDecisionsStmt = $pdo->prepare("
        SELECT tr.id, p.spec_model, tt.name AS test_name, tr.review_decision, tr.reviewed_at, u.name AS reviewer_name
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        LEFT JOIN users u ON tr.reviewed_by = u.id
        WHERE tr.review_decision IS NOT NULL AND tr.workspace_id = :ws
        ORDER BY tr.reviewed_at DESC LIMIT 6
    ");
    $recentDecisionsStmt->execute(['ws' => $wsId]);
    $recentDecisions = $recentDecisionsStmt->fetchAll();

    $qualityOverviewStmt = $pdo->prepare("
        SELECT d.name,
               SUM(CASE WHEN tr.review_decision = 'pass' THEN 1 ELSE 0 END) AS passed,
               SUM(CASE WHEN tr.review_decision = 'fail' THEN 1 ELSE 0 END) AS failed,
               COUNT(*) AS decided
        FROM testing_records tr
        JOIN departments d ON tr.department_id = d.id
        WHERE tr.review_decision IS NOT NULL AND tr.workspace_id = :ws
        GROUP BY d.id, d.name
        ORDER BY decided DESC LIMIT 6
    ");
    $qualityOverviewStmt->execute(['ws' => $wsId]);
    $qualityOverview = $qualityOverviewStmt->fetchAll();

    $quickActions[] = ['url' => url('testing/index.php'), 'icon' => 'testing', 'label' => 'View Testing Queue'];
    if (can('view_reports')) $quickActions[] = ['url' => url('reports/index.php'), 'icon' => 'reports', 'label' => 'Generate Report'];

} elseif ($role === ROLE_AUDITOR) {

    $totalProductsStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE workspace_id = :ws");
    $totalProductsStmt->execute(['ws' => $wsId]);
    $totalProducts = (int) $totalProductsStmt->fetchColumn();

    $totalTestsStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_records WHERE workspace_id = :ws");
    $totalTestsStmt->execute(['ws' => $wsId]);
    $totalTests = (int) $totalTestsStmt->fetchColumn();

    $recentChangesStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND workspace_id = :ws");
    $recentChangesStmt->execute(['ws' => $wsId]);
    $recentChanges = (int) $recentChangesStmt->fetchColumn();

    $activeUsersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active' AND workspace_id = :ws");
    $activeUsersStmt->execute(['ws' => $wsId]);
    $activeUsers = (int) $activeUsersStmt->fetchColumn();

    $stats = [
        ['label' => 'Total Products', 'value' => $totalProducts, 'icon' => 'box',     'color' => 'info'],
        ['label' => 'Total Tests',    'value' => $totalTests,    'icon' => 'flask',   'color' => 'accent'],
        ['label' => 'Recent Changes', 'value' => $recentChanges, 'icon' => 'history', 'color' => 'warning'],
        ['label' => 'Active Users',   'value' => $activeUsers,   'icon' => 'users',   'color' => 'success'],
    ];

    $recentAuditStmt = $pdo->prepare("
        SELECT al.action, al.entity_type, al.entity_id, al.after_json, al.created_at, u.name AS actor_name
        FROM audit_log al LEFT JOIN users u ON al.actor_id = u.id
        WHERE al.workspace_id = :ws
        ORDER BY al.created_at DESC LIMIT 12
    ");
    $recentAuditStmt->execute(['ws' => $wsId]);
    $recentAudit = $recentAuditStmt->fetchAll();

    $deptActiveStmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE status = 'active' AND workspace_id = :ws");
    $deptActiveStmt->execute(['ws' => $wsId]);
    $deptActive = (int) $deptActiveStmt->fetchColumn();

    $deptTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE workspace_id = :ws");
    $deptTotalStmt->execute(['ws' => $wsId]);
    $deptTotal = (int) $deptTotalStmt->fetchColumn();

    $typesActiveStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_types WHERE status = 'active' AND workspace_id = :ws");
    $typesActiveStmt->execute(['ws' => $wsId]);
    $typesActive = (int) $typesActiveStmt->fetchColumn();

    $typesTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM testing_types WHERE workspace_id = :ws");
    $typesTotalStmt->execute(['ws' => $wsId]);
    $typesTotal = (int) $typesTotalStmt->fetchColumn();

    $usersTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE workspace_id = :ws");
    $usersTotalStmt->execute(['ws' => $wsId]);
    $usersTotal = (int) $usersTotalStmt->fetchColumn();

    $productsInPipelineStmt = $pdo->prepare("
        SELECT COUNT(*) FROM products
        WHERE status IN ('registered','assigned','in_testing','submitted_for_review','pending_cpri_approval') AND workspace_id = :ws
    ");
    $productsInPipelineStmt->execute(['ws' => $wsId]);
    $productsInPipeline = (int) $productsInPipelineStmt->fetchColumn();

    $systemOverview = [
        ['label' => 'Departments',        'value' => $deptActive,  'sub' => $deptActive . ' of ' . $deptTotal . ' active'],
        ['label' => 'Testing Types',      'value' => $typesActive, 'sub' => $typesActive . ' of ' . $typesTotal . ' active'],
        ['label' => 'Users',              'value' => $activeUsers, 'sub' => $activeUsers . ' of ' . $usersTotal . ' active'],
        ['label' => 'Products in Pipeline', 'value' => $productsInPipeline, 'sub' => 'not yet released'],
    ];

    $quickActions[] = ['url' => url('testing/index.php'), 'icon' => 'testing', 'label' => 'View Testing Records'];
    if (can('view_reports')) $quickActions[] = ['url' => url('reports/index.php'), 'icon' => 'reports', 'label' => 'View Reports'];
    if (can('view_audit_log')) $quickActions[] = ['url' => url('admin/audit-log.php'), 'icon' => 'audit', 'label' => 'View Audit Log'];
}
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="14" height="14" rx="2"/><line x1="3" y1="8" x2="17" y2="8"/><line x1="8" y1="3" x2="8" y2="17"/></svg>
      </div>
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle" style="margin-top:2px;">Welcome back, <?= e($currentUser['name']) ?> · <span style="color:var(--accent);"><?= role_label($role) ?></span> · <?= date('l, M j, Y') ?></p>
      </div>
    </div>
  </div>
  <?php if ($pageActionUrl): ?>
  <div class="page-actions">
    <a href="<?= $pageActionUrl ?>" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="3" x2="8" y2="13"/><line x1="3" y1="8" x2="13" y2="8"/></svg>
      Register Product
    </a>
  </div>
  <?php endif; ?>
</div>

<!-- KPI Stat Tiles -->
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);">
  <?php foreach ($stats as $i => $s): ?>
  <div class="stat-tile" style="animation:dashCardIn 0.55s var(--ease-out) <?= $i * 0.08 ?>s both;">
    <div class="d-flex align-center justify-between">
      <span class="stat-label"><?= e($s['label']) ?></span>
      <div class="stat-icon-wrap" style="background:var(--<?= $s['color'] ?>-tint);color:var(--<?= $s['color'] ?>);">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><?= $statIcons[$s['icon']] ?></svg>
      </div>
    </div>
    <span class="stat-value"<?= isset($s['suffix']) ? '' : ' data-count-to="' . (int) $s['value'] . '"' ?>><?= $s['value'] ?><?= $s['suffix'] ?? '' ?></span>
  </div>
  <?php endforeach; ?>
</div>

<!-- Main Dashboard Grid -->
<div class="dashboard-grid" style="display:grid; grid-template-columns:1fr 340px; gap:24px;">

  <?php if ($role === ROLE_ADMINISTRATOR): ?>
  <!-- ══════════════════════ ADMINISTRATOR ══════════════════════ -->
  <div style="display:flex;flex-direction:column;gap:24px;min-width:0;">
    <?php render_attention_items($attentionItems); ?>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Products</h3>
        <a href="<?= url('products/index.php') ?>" class="btn btn-sm btn-ghost">View all</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Serial</th><th>Model</th><th>Department</th><th>Status</th><th>Registered</th></tr></thead>
          <tbody>
            <?php if (empty($recentProducts)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">No products registered yet.</td></tr>
            <?php else: foreach ($recentProducts as $p): ?>
            <tr class="cursor-pointer" onclick="location.href='<?= url('products/detail.php?id=' . $p['id']) ?>'">
              <td class="col-mono"><?= e($p['serial_number']) ?></td>
              <td><strong><?= e($p['spec_model']) ?></strong></td>
              <td class="text-soft"><?= e($p['department'] ?? '—') ?></td>
              <td><?= status_badge($p['status']) ?></td>
              <td class="text-faint" style="font-size:0.75rem;"><?= time_ago($p['created_at']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:24px;">
    <?php render_quick_actions($quickActions, $qaIcons); ?>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Departments</h3>
        <?php if (can('manage_departments')): ?>
        <a href="<?= url('admin/departments.php') ?>" class="btn btn-sm btn-ghost">View all</a>
        <?php endif; ?>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($departments)): ?>
        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No active departments.</div>
        <?php else: foreach ($departments as $dept): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 24px;border-bottom:1px solid var(--line);">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--success);flex-shrink:0;"></div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:0.8125rem;"><?= e($dept['name']) ?></div>
            <div style="font-size:0.6875rem;color:var(--ink-faint);"><?= e($dept['description'] ?? '') ?></div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:700;font-size:0.875rem;font-variant-numeric:tabular-nums;"><?= (int) $dept['active_tests'] ?></div>
            <div style="font-size:0.625rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.04em;">active</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <?php render_activity_feed($activityFeed); ?>
  </div>

  <?php elseif ($role === ROLE_TESTING_ENGINEER): ?>
  <!-- ══════════════════════ TESTING ENGINEER ══════════════════════ -->
  <div style="display:flex;flex-direction:column;gap:24px;min-width:0;">
    <?php render_attention_items($attentionItems); ?>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">My Recent Tests</h3>
        <a href="<?= url('testing/index.php') ?>" class="btn btn-sm btn-ghost">View all</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Product</th><th>Test</th><th>Tester</th><th>Status</th><th>Assigned</th></tr></thead>
          <tbody>
            <?php if (empty($myRecentTests)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">You haven't assigned any tests yet.</td></tr>
            <?php else: foreach ($myRecentTests as $t): ?>
            <tr class="cursor-pointer" onclick="location.href='<?= url('testing/detail.php?id=' . $t['id']) ?>'">
              <td><strong><?= e($t['spec_model']) ?></strong><div class="text-faint col-mono" style="font-size:0.6875rem;"><?= e($t['serial_number']) ?></div></td>
              <td class="text-soft"><?= e($t['test_name']) ?></td>
              <td class="text-soft"><?= e($t['tester_name']) ?></td>
              <td><?= status_badge($t['status']) ?></td>
              <td class="text-faint" style="font-size:0.75rem;"><?= time_ago($t['created_at']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:24px;">
    <?php render_quick_actions($quickActions, $qaIcons); ?>
    <?php render_activity_feed($activityFeed); ?>
  </div>

  <?php elseif ($role === ROLE_LAB_TECHNICIAN): ?>
  <!-- ══════════════════════ LAB TECHNICIAN ══════════════════════ -->
  <div style="display:flex;flex-direction:column;gap:24px;min-width:0;">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">My Testing Queue</h3>
        <a href="<?= url('testing/index.php') ?>" class="btn btn-sm btn-ghost">View all</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Product</th><th>Test</th><th>Status</th><th>Due</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($myQueue)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">No tests currently assigned to you.</td></tr>
            <?php else: foreach ($myQueue as $t): ?>
            <tr class="cursor-pointer" onclick="location.href='<?= url('testing/detail.php?id=' . $t['id']) ?>'">
              <td><strong><?= e($t['spec_model']) ?></strong><div class="text-faint col-mono" style="font-size:0.6875rem;"><?= e($t['serial_number']) ?></div></td>
              <td class="text-soft"><?= e($t['test_name']) ?></td>
              <td><?= status_badge($t['status']) ?></td>
              <td class="text-faint" style="font-size:0.75rem;"><?= format_date($t['due_date']) ?></td>
              <td><a href="<?= url('testing/detail.php?id=' . $t['id']) ?>" class="btn btn-sm btn-ghost">Open</a></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:24px;">
    <?php render_quick_actions($quickActions, $qaIcons); ?>
  </div>

  <?php elseif ($role === ROLE_QUALITY_MANAGER): ?>
  <!-- ══════════════════════ QUALITY MANAGER ══════════════════════ -->
  <div style="display:flex;flex-direction:column;gap:24px;min-width:0;">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Review Queue</h3>
        <span class="status-badge badge-warning"><span class="status-dot"></span><?= count($reviewQueue) ?> awaiting</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Product</th><th>Test</th><th>Tester</th><th>Submitted</th><th></th></tr></thead>
          <tbody>
            <?php if (empty($reviewQueue)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--ink-faint);padding:24px;">Nothing waiting on review.</td></tr>
            <?php else: foreach ($reviewQueue as $t): ?>
            <tr class="cursor-pointer" onclick="location.href='<?= url('testing/detail.php?id=' . $t['id']) ?>'">
              <td><strong><?= e($t['spec_model']) ?></strong><div class="text-faint col-mono" style="font-size:0.6875rem;"><?= e($t['serial_number']) ?></div></td>
              <td class="text-soft"><?= e($t['test_name']) ?></td>
              <td class="text-soft"><?= e($t['tester_name']) ?></td>
              <td class="text-faint" style="font-size:0.75rem;"><?= time_ago($t['submitted_at']) ?></td>
              <td><a href="<?= url('testing/detail.php?id=' . $t['id']) ?>" class="btn btn-sm btn-ghost">View</a></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Recent Decisions</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($recentDecisions)): ?>
        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No review decisions recorded yet.</div>
        <?php else: ?>
        <ul class="activity-list" style="padding:0 24px;">
          <?php foreach ($recentDecisions as $d): ?>
          <li class="activity-item">
            <div class="activity-avatar"><?= e(user_initials($d['reviewer_name'] ?? 'System')) ?></div>
            <div class="activity-body">
              <div class="activity-text">
                <strong><?= e($d['reviewer_name'] ?? 'System') ?></strong> reviewed
                <span style="font-weight:500;"><?= e($d['spec_model']) ?></span> · <?= e($d['test_name']) ?>
              </div>
              <div class="activity-meta"><?= status_badge($d['review_decision']) ?> · <?= e(time_ago($d['reviewed_at'])) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:24px;">
    <?php render_quick_actions($quickActions, $qaIcons); ?>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Quality Overview</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if (empty($qualityOverview)): ?>
        <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No reviewed tests yet.</div>
        <?php else: foreach ($qualityOverview as $q):
          $rate = $q['decided'] > 0 ? round(((int) $q['passed'] / (int) $q['decided']) * 100, 1) : 0;
          $dotColor = $rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'fail');
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 24px;border-bottom:1px solid var(--line);">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--<?= $dotColor ?>);flex-shrink:0;"></div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:0.8125rem;"><?= e($q['name']) ?></div>
            <div style="font-size:0.6875rem;color:var(--ink-faint);"><?= (int) $q['passed'] ?> passed · <?= (int) $q['failed'] ?> failed</div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:700;font-size:0.875rem;font-variant-numeric:tabular-nums;"><?= $rate ?>%</div>
            <div style="font-size:0.625rem;color:var(--ink-faint);text-transform:uppercase;letter-spacing:0.04em;">pass rate</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <?php elseif ($role === ROLE_AUDITOR): ?>
  <!-- ══════════════════════ AUDITOR (read-only) ══════════════════════ -->
  <div style="display:flex;flex-direction:column;gap:24px;min-width:0;">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Audit Activity</h3>
        <a href="<?= url('admin/audit-log.php') ?>" class="btn btn-sm btn-ghost">View full log</a>
      </div>
      <div style="overflow-x:auto;">
        <table class="data-table">
          <thead><tr><th>Actor</th><th>Action</th><th>Entity</th><th>When</th></tr></thead>
          <tbody>
            <?php if (empty($recentAudit)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--ink-faint);padding:24px;">No audit activity recorded yet.</td></tr>
            <?php else: foreach ($recentAudit as $a): ?>
            <tr>
              <td><?= e($a['actor_name'] ?? 'System') ?></td>
              <td class="text-soft"><?= e(audit_action_label($a['action'])) ?></td>
              <td class="text-mono" style="font-size:0.75rem;"><?= e($a['entity_type']) ?> #<?= (int) $a['entity_id'] ?></td>
              <td class="text-faint" style="font-size:0.75rem;"><?= time_ago($a['created_at']) ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:24px;">
    <?php render_quick_actions($quickActions, $qaIcons); ?>

    <div class="card">
      <div class="card-header"><h3 class="card-title">System Overview</h3></div>
      <div class="card-body" style="padding:0;">
        <?php foreach ($systemOverview as $m): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px 24px;border-bottom:1px solid var(--line);">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0;"></div>
          <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:0.8125rem;"><?= e($m['label']) ?></div>
            <div style="font-size:0.6875rem;color:var(--ink-faint);"><?= e($m['sub']) ?></div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:700;font-size:0.875rem;font-variant-numeric:tabular-nums;"><?= (int) $m['value'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<style>
@media (max-width: 1024px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 899px) {
  .dashboard-grid {
    display: flex !important;
    flex-direction: column !important;
  }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
