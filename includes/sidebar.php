<?php
declare(strict_types=1);
// Sidebar navigation — role-based menu items, theme toggle (Arsalan)

$role = $currentUser['role'] ?? null;
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';

function nav_is_active(string $pattern, string $currentPath): bool
{
    return str_contains($currentPath, $pattern);
}

$isAdmin      = $role === ROLE_ADMINISTRATOR;
$isEngineer   = $role === ROLE_TESTING_ENGINEER;
$isTechnician = $role === ROLE_LAB_TECHNICIAN;
$isQM         = $role === ROLE_QUALITY_MANAGER;
$isAuditor    = $role === ROLE_AUDITOR;

$canSeeProducts     = in_array($role, [ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER, ROLE_QUALITY_MANAGER, ROLE_AUDITOR, ROLE_LAB_TECHNICIAN], true);
$canSeeTesting      = true;
$canSeeTestingTypes = can('manage_testing_types');
$canSeeDepartments  = can('manage_departments');
$canSeeReports      = in_array($role, [ROLE_ADMINISTRATOR, ROLE_TESTING_ENGINEER, ROLE_QUALITY_MANAGER, ROLE_AUDITOR], true);
$canSeeAudit        = can('view_audit_log');
$canManageUsers     = can('manage_users');
?>
<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
        <rect width="28" height="28" rx="7" fill="var(--accent)"/>
        <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="white" opacity=".9"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="white" opacity=".9"/>
      </svg>
    </div>
    <span class="brand-text">Lab Automation</span>
  </div>

  <nav class="sidebar-nav" aria-label="Main navigation">
    <!-- Workspace -->
    <div class="nav-group">
      <div class="nav-group-label">Workspace</div>
      <a class="nav-link <?= nav_is_active('/dashboard/', $currentPath) ? 'active' : '' ?>" href="<?= url('dashboard/index.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="7" height="7" rx="1.5"/><rect x="11" y="2" width="7" height="4" rx="1.5"/><rect x="2" y="11" width="7" height="4" rx="1.5"/><rect x="11" y="8" width="7" height="7" rx="1.5"/></svg>
        <span class="nav-label">Dashboard</span>
      </a>
    </div>

    <!-- Operations -->
    <div class="nav-group">
      <div class="nav-group-label">Operations</div>
      <?php if ($canSeeProducts): ?>
      <a class="nav-link <?= nav_is_active('/products/', $currentPath) ? 'active' : '' ?>" href="<?= url('products/index.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2L17 6v8l-7 4-7-4V6l7-4z"/><path d="M10 10l7-4"/><path d="M10 10v8"/><path d="M10 10L3 6"/></svg>
        <span class="nav-label">Products</span>
      </a>
      <?php endif; ?>
      <a class="nav-link <?= nav_is_active('/testing/', $currentPath) ? 'active' : '' ?>" href="<?= url('testing/index.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 2v5l-4 7a1.5 1.5 0 001.3 2.2h11.4a1.5 1.5 0 001.3-2.2l-4-7V2"/><line x1="5" y1="2" x2="15" y2="2"/><path d="M8.5 11a2 2 0 004 0"/></svg>
        <span class="nav-label">Testing Records</span>
      </a>
    </div>

    <!-- Configuration -->
    <?php if ($canSeeTestingTypes || $canSeeDepartments): ?>
    <div class="nav-group">
      <div class="nav-group-label">Configuration</div>
      <?php if ($canSeeTestingTypes): ?>
      <a class="nav-link <?= nav_is_active('/admin/testing-types', $currentPath) ? 'active' : '' ?>" href="<?= url('admin/testing-types.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12v3H4V4z"/><path d="M4 9h12v3H4V9z"/><path d="M4 14h8v3H4v-3z"/></svg>
        <span class="nav-label">Testing Types</span>
      </a>
      <?php endif; ?>
      <?php if ($canSeeDepartments): ?>
      <a class="nav-link <?= nav_is_active('/admin/departments', $currentPath) ? 'active' : '' ?>" href="<?= url('admin/departments.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="6" height="6" rx="1"/><rect x="12" y="7" width="6" height="6" rx="1"/><rect x="7" y="2" width="6" height="6" rx="1"/><line x1="10" y1="8" x2="10" y2="12"/><line x1="5" y1="10" x2="8" y2="10"/><line x1="12" y1="10" x2="15" y2="10"/></svg>
        <span class="nav-label">Departments</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Insights -->
    <?php if ($canSeeReports || $canSeeAudit): ?>
    <div class="nav-group">
      <div class="nav-group-label">Insights</div>
      <?php if ($canSeeReports): ?>
      <a class="nav-link <?= nav_is_active('/reports/', $currentPath) ? 'active' : '' ?>" href="<?= url('reports/index.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17V5a2 2 0 012-2h10a2 2 0 012 2v12"/><line x1="7" y1="9" x2="7" y2="14"/><line x1="10" y1="7" x2="10" y2="14"/><line x1="13" y1="11" x2="13" y2="14"/></svg>
        <span class="nav-label">Reports</span>
      </a>
      <?php endif; ?>
      <?php if ($canSeeAudit): ?>
      <a class="nav-link <?= nav_is_active('/admin/audit-log', $currentPath) ? 'active' : '' ?>" href="<?= url('admin/audit-log.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H5a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7l-5-5z"/><polyline points="12,2 12,7 17,7"/><line x1="7" y1="10" x2="13" y2="10"/><line x1="7" y1="13" x2="11" y2="13"/></svg>
        <span class="nav-label">Audit Log</span>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Administration (Admin only) -->
    <?php if ($canManageUsers): ?>
    <div class="nav-group">
      <div class="nav-group-label">Administration</div>
      <a class="nav-link <?= nav_is_active('/admin/users', $currentPath) ? 'active' : '' ?>" href="<?= url('admin/users.php') ?>">
        <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="6" r="3.5"/><path d="M3 18c0-3.87 3.13-7 7-7s7 3.13 7 7"/><path d="M16 6l1.5 1.5L16 9"/></svg>
        <span class="nav-label">Users</span>
      </a>
    </div>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a class="nav-link" href="<?= url('guide/index.php') ?>">
      <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7.5"/><path d="M7.5 7.5a2.5 2.5 0 015 0c0 1.5-2.5 2-2.5 3.5"/><circle cx="10" cy="14" r="0.5" fill="currentColor"/></svg>
      <span class="nav-label">Guide</span>
    </a>
    <?php if ($isAdmin): ?>
    <a class="nav-link <?= nav_is_active('/admin/settings', $currentPath) ? 'active' : '' ?>" href="<?= url('admin/settings.php') ?>">
      <svg class="nav-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="2.5"/><path d="M16.2 12.5a1.2 1.2 0 00.24 1.32l.04.04a1.46 1.46 0 11-2.06 2.06l-.04-.04a1.2 1.2 0 00-1.32-.24 1.2 1.2 0 00-.73 1.1v.12a1.46 1.46 0 01-2.92 0v-.06a1.2 1.2 0 00-.78-1.1 1.2 1.2 0 00-1.32.24l-.04.04a1.46 1.46 0 11-2.06-2.06l.04-.04a1.2 1.2 0 00.24-1.32 1.2 1.2 0 00-1.1-.73h-.12a1.46 1.46 0 010-2.92h.06a1.2 1.2 0 001.1-.78 1.2 1.2 0 00-.24-1.32l-.04-.04A1.46 1.46 0 117.2 4.65l.04.04a1.2 1.2 0 001.32.24h.06a1.2 1.2 0 00.73-1.1v-.12a1.46 1.46 0 012.92 0v.06a1.2 1.2 0 00.73 1.1 1.2 1.2 0 001.32-.24l.04-.04a1.46 1.46 0 112.06 2.06l-.04.04a1.2 1.2 0 00-.24 1.32v.06a1.2 1.2 0 001.1.73h.12a1.46 1.46 0 010 2.92h-.06a1.2 1.2 0 00-1.1.73z"/></svg>
      <span class="nav-label">Settings</span>
    </a>
    <?php endif; ?>
    <button type="button" class="nav-link sidebar-collapse-btn" id="sidebarCollapseToggle" aria-label="Collapse sidebar">
      <svg class="nav-icon collapse-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="13,4 7,10 13,16"/></svg>
      <span class="nav-label">Collapse</span>
    </button>
  </div>
</aside>
