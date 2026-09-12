<?php
declare(strict_types=1);
// Search — full-text search across products, tests, users (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Search';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Search', 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';

$wsId = auth_workspace_id();
$query = trim($_GET['q'] ?? '');
$products = [];
$records = [];
$users = [];

if ($query !== '' && strlen($query) >= 2) {
    $like = '%' . $query . '%';

    $products = $pdo->prepare("
        SELECT p.id, p.serial_number, p.spec_model, p.status, d.name AS dept_name
        FROM products p
        LEFT JOIN departments d ON p.department_id = d.id
        WHERE (p.serial_number LIKE :q OR p.spec_model LIKE :q2) AND p.workspace_id = :ws
        ORDER BY p.created_at DESC LIMIT 10
    ");
    $products->execute(['q' => $like, 'q2' => $like, 'ws' => $wsId]);
    $products = $products->fetchAll();

    $records = $pdo->prepare("
        SELECT tr.id, tr.status, tt.name AS test_type_name, p.spec_model, u.name AS tester_name
        FROM testing_records tr
        JOIN products p ON tr.product_id = p.id
        JOIN testing_types tt ON tr.testing_type_id = tt.id
        LEFT JOIN users u ON tr.tester_id = u.id
        WHERE (p.spec_model LIKE :q OR tt.name LIKE :q2 OR p.serial_number LIKE :q3) AND tr.workspace_id = :ws
        ORDER BY tr.created_at DESC LIMIT 10
    ");
    $records->execute(['q' => $like, 'q2' => $like, 'q3' => $like, 'ws' => $wsId]);
    $records = $records->fetchAll();

    if (can('manage_users')) {
        $users = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.role, u.status, d.name AS dept_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE (u.name LIKE :q OR u.email LIKE :q2) AND u.workspace_id = :ws
            ORDER BY u.name LIMIT 10
        ");
        $users->execute(['q' => $like, 'q2' => $like, 'ws' => $wsId]);
        $users = $users->fetchAll();
    }
}

$totalResults = count($products) + count($records) + count($users);

function highlight(string $text, string $query): string {
    if ($query === '') return e($text);
    $escaped = e($text);
    $pattern = '/' . preg_quote(e($query), '/') . '/i';
    return preg_replace($pattern, '<mark style="background:var(--warning-tint);padding:0 2px;border-radius:2px;">$0</mark>', $escaped);
}
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><line x1="13.5" y1="13.5" x2="17" y2="17"/></svg>
      </div>
      <div>
        <h1 class="page-title">Search Results</h1>
        <?php if ($query !== ''): ?>
        <p class="page-subtitle" style="margin-top:2px;">Showing results for "<strong><?= e($query) ?></strong>" · <?= $totalResults ?> result<?= $totalResults !== 1 ? 's' : '' ?> found</p>
        <?php else: ?>
        <p class="page-subtitle" style="margin-top:2px;">Enter a search term to find products, testing records, and users</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Search Input -->
<div style="max-width:600px;margin-bottom:24px;">
  <form method="get" action="">
    <div class="filter-search" style="max-width:none;">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
      <input type="text" class="form-control" name="q" placeholder="Search products, testing records, users…" value="<?= e($query) ?>" style="padding-left:36px;font-size:1rem;padding:10px 12px 10px 36px;" autofocus>
    </div>
  </form>
</div>

<?php if ($query !== '' && $totalResults === 0): ?>
<div style="text-align:center;padding:48px 24px;">
  <p style="font-size:0.9375rem;font-weight:600;color:var(--ink-soft);">No results found</p>
  <p style="font-size:0.8125rem;color:var(--ink-faint);">Try adjusting your search term.</p>
</div>
<?php elseif ($totalResults > 0): ?>

<!-- Facet Tabs -->
<div class="tabs" style="margin-bottom:20px;">
  <button class="tab active" onclick="filterFacet('all')">All <span style="background:var(--hold-tint);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;color:var(--hold);"><?= $totalResults ?></span></button>
  <?php if ($products): ?>
  <button class="tab" onclick="filterFacet('products')">Products <span style="background:var(--hold-tint);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;color:var(--hold);"><?= count($products) ?></span></button>
  <?php endif; ?>
  <?php if ($records): ?>
  <button class="tab" onclick="filterFacet('testing')">Testing Records <span style="background:var(--hold-tint);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;color:var(--hold);"><?= count($records) ?></span></button>
  <?php endif; ?>
  <?php if ($users): ?>
  <button class="tab" onclick="filterFacet('users')">Users <span style="background:var(--hold-tint);padding:1px 7px;border-radius:var(--radius-full);font-size:0.6875rem;font-weight:700;margin-left:4px;color:var(--hold);"><?= count($users) ?></span></button>
  <?php endif; ?>
</div>

<div style="display:flex;flex-direction:column;gap:12px;">

  <?php if ($products): ?>
  <div class="text-caption facet-group" data-facet="products" style="margin-bottom:4px;margin-top:8px;">Products</div>
  <?php foreach ($products as $p): ?>
  <a href="<?= url('products/detail.php?id=' . $p['id']) ?>" class="card card-hover facet-item" data-facet="products" style="text-decoration:none;">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;gap:16px;">
      <div style="width:36px;height:36px;border-radius:var(--radius-md);background:var(--accent-tint);color:var(--accent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 2L17 6v8l-7 4-7-4V6l7-4z"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;color:var(--ink);"><?= highlight($p['spec_model'], $query) ?></div>
        <div style="font-size:0.75rem;color:var(--ink-faint);"><?= highlight($p['serial_number'], $query) ?> · <?= e($p['dept_name'] ?? '—') ?></div>
      </div>
      <?= status_badge($p['status']) ?>
    </div>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($records): ?>
  <div class="text-caption facet-group" data-facet="testing" style="margin-bottom:4px;margin-top:20px;">Testing Records</div>
  <?php foreach ($records as $r): ?>
  <a href="<?= url('testing/detail.php?id=' . $r['id']) ?>" class="card card-hover facet-item" data-facet="testing" style="text-decoration:none;">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;gap:16px;">
      <div style="width:36px;height:36px;border-radius:var(--radius-md);background:var(--warning-tint);color:var(--warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 2v5l-4 7a1.5 1.5 0 001.3 2.2h11.4a1.5 1.5 0 001.3-2.2l-4-7V2"/></svg>
      </div>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;color:var(--ink);"><?= highlight($r['test_type_name'], $query) ?> — <?= highlight($r['spec_model'], $query) ?></div>
        <div style="font-size:0.75rem;color:var(--ink-faint);">TR-<?= str_pad((string) $r['id'], 3, '0', STR_PAD_LEFT) ?> · <?= e($r['tester_name'] ?? '—') ?></div>
      </div>
      <?= status_badge($r['status']) ?>
    </div>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($users): ?>
  <div class="text-caption facet-group" data-facet="users" style="margin-bottom:4px;margin-top:20px;">Users</div>
  <?php foreach ($users as $u): ?>
  <div class="card facet-item" data-facet="users">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;gap:16px;">
      <span class="user-avatar" style="width:36px;height:36px;font-size:0.75rem;"><?= e(user_initials($u['name'])) ?></span>
      <div style="flex:1;min-width:0;">
        <div style="font-weight:600;color:var(--ink);"><?= highlight($u['name'], $query) ?></div>
        <div style="font-size:0.75rem;color:var(--ink-faint);"><?= e(role_label($u['role'])) ?> · <?= e($u['dept_name'] ?? 'Cross-department') ?></div>
      </div>
      <?= status_badge($u['status']) ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>
<?php endif; ?>

<script>
function filterFacet(facet) {
  document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('active'); });
  event.target.closest('.tab').classList.add('active');
  document.querySelectorAll('.facet-group, .facet-item').forEach(function(el) {
    el.style.display = (facet === 'all' || el.getAttribute('data-facet') === facet) ? '' : 'none';
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
