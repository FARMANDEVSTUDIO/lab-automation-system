<?php
declare(strict_types=1);
// Top navigation bar — notifications, search, user dropdown (Arsalan)

$initials = user_initials($currentUser['name']);
$roleBadge = role_label($currentUser['role']);
?>
<header class="app-topbar" id="appTopbar">
  <div class="topbar-left">
    <button type="button" class="topbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="3" y1="5" x2="17" y2="5"/><line x1="3" y1="10" x2="17" y2="10"/><line x1="3" y1="15" x2="17" y2="15"/></svg>
    </button>
    <?php if (!empty($breadcrumbs)): ?>
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb">
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <li class="breadcrumb-item <?= $i === array_key_last($breadcrumbs) ? 'active' : '' ?>">
          <?php if ($i < array_key_last($breadcrumbs) && !empty($crumb['url'])): ?>
            <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
          <?php else: ?>
            <?= e($crumb['label']) ?>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <?php endif; ?>
  </div>

  <div class="topbar-center">
    <button type="button" class="search-trigger" id="searchTrigger" aria-label="Search">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
      <span class="search-placeholder">Search products, records…</span>
      <kbd class="search-shortcut">Ctrl K</kbd>
    </button>
  </div>

  <div class="topbar-right">
    <!-- Notifications -->
    <div class="topbar-notifications" id="notifDropdown">
      <button type="button" class="topbar-icon-btn" aria-label="Notifications" data-dropdown="notifications">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17.5c.83 0 1.5-.67 1.5-1.5h-3c0 .83.67 1.5 1.5 1.5z"/><path d="M15.5 13V9c0-2.76-1.84-5.08-4.36-5.8V2.5a1.14 1.14 0 10-2.28 0v.7C6.34 3.92 4.5 6.24 4.5 9v4l-1.5 1.5v.5h14v-.5L15.5 13z"/></svg>
        <?php if ($notifCount > 0): ?>
        <span class="notif-badge" id="notifBadge"><?= $notifCount ?></span>
        <?php else: ?>
        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu notif-dropdown-menu" id="notifMenu" style="min-width:360px;right:0;">
        <div class="notif-dropdown-header">
          <span class="notif-dropdown-title">Notifications</span>
          <button type="button" id="markAllRead" class="notif-mark-all" style="background:none;border:none;cursor:pointer;color:var(--accent);font-size:0.75rem;font-weight:500;padding:0;">Mark all read</button>
        </div>
        <div class="notif-dropdown-list" id="notifList">
          <?php if (empty($notifications)): ?>
          <div style="padding:24px;text-align:center;color:var(--ink-faint);font-size:0.8125rem;">No notifications</div>
          <?php else: ?>
          <?php foreach ($notifications as $n): ?>
          <div class="notif-item <?= !$n['is_read'] ? 'notif-unread' : '' ?>"
               data-notif-id="<?= $n['id'] ?>"
               data-href="<?= e($n['link'] ?? '#') ?>">
            <div class="notif-icon notif-icon-<?= e($n['type']) ?>">
              <?php if ($n['type'] === 'warning' || $n['type'] === 'assignment'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2L14 13H2L8 2z"/><line x1="8" y1="6" x2="8" y2="9"/><circle cx="8" cy="11" r="0.5" fill="currentColor"/></svg>
              <?php elseif ($n['type'] === 'error'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><line x1="5.5" y1="5.5" x2="10.5" y2="10.5"/><line x1="10.5" y1="5.5" x2="5.5" y2="10.5"/></svg>
              <?php elseif ($n['type'] === 'success'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><polyline points="5,8 7,10 11,6"/></svg>
              <?php else: ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="6"/><line x1="8" y1="5.5" x2="8" y2="8.5"/><circle cx="8" cy="11" r="0.5" fill="currentColor"/></svg>
              <?php endif; ?>
            </div>
            <div class="notif-content">
              <div class="notif-title"><?= e($n['title']) ?></div>
              <div class="notif-message"><?= e($n['message'] ?? '') ?></div>
              <div class="notif-time"><?= e(time_ago($n['created_at'])) ?></div>
            </div>
            <?php if (!$n['is_read']): ?>
            <span class="notif-dot"></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <a href="<?= url('admin/audit-log.php') ?>" class="notif-dropdown-footer">
          View all activity
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="5,3 9,7 5,11"/></svg>
        </a>
      </div>
    </div>

    <!-- User Menu -->
    <div class="topbar-user" id="userDropdown">
      <button type="button" class="user-trigger" data-dropdown="user-menu" aria-label="User menu">
        <span class="user-avatar"><?= e($initials) ?></span>
        <div class="user-info">
          <span class="user-name"><?= e($currentUser['name']) ?></span>
          <span class="user-role"><?= e($roleBadge) ?></span>
        </div>
        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,5.5 7,8.5 10,5.5"/></svg>
      </button>
      <div class="dropdown-menu user-dropdown-menu" id="userMenu">
        <a class="dropdown-item" href="<?= url('profile/index.php') ?>">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="3"/><path d="M2.5 14c0-3 2.46-5.5 5.5-5.5s5.5 2.5 5.5 5.5"/></svg>
          Profile
        </a>
        <button type="button" class="dropdown-item" id="themeToggle">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="8" r="3"/><line x1="8" y1="1" x2="8" y2="3"/><line x1="8" y1="13" x2="8" y2="15"/><line x1="1" y1="8" x2="3" y2="8"/><line x1="13" y1="8" x2="15" y2="8"/></svg>
          <span id="themeLabel">Dark mode</span>
        </button>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item dropdown-item-danger" href="<?= url('auth/logout.php') ?>">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M6 14H3a1 1 0 01-1-1V3a1 1 0 011-1h3"/><polyline points="10,11 14,8 10,5"/><line x1="14" y1="8" x2="6" y2="8"/></svg>
          Sign out
        </a>
      </div>
    </div>
  </div>
</header>
