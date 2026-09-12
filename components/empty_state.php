<?php
declare(strict_types=1);

/**
 * Laboratory-themed empty state component. (Arsalan)
 *
 * Usage:
 *   $emptyIcon    = '<svg ...>';   // optional — custom icon SVG
 *   $emptyTitle   = 'No products registered';
 *   $emptyMessage = 'Register your first product to begin testing.';
 *   $emptyAction  = '<a href="..." class="btn btn-primary btn-sm">Register Product</a>';  // optional
 *   $emptyContext = 'products';    // optional — one of: products, testing, reports, notifications,
 *                                  //   search, types, parameters, attachments, users, audit
 *   require __DIR__ . '/../components/empty_state.php';
 */

$emptyIcon    = $emptyIcon    ?? '';
$emptyTitle   = $emptyTitle   ?? 'Nothing here yet';
$emptyMessage = $emptyMessage ?? '';
$emptyAction  = $emptyAction  ?? '';
$emptyContext = $emptyContext  ?? '';

$contextIcons = [
    'products' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4l10 4v8c0 6-4.2 10-10 12-5.8-2-10-6-10-12V8l10-4z"/><path d="M11 15l3 3 7-7" opacity="0.5"/></svg>',
    'testing' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v8l-6 11a2.5 2.5 0 002.2 3.5h15.6a2.5 2.5 0 002.2-3.5L20 12V4"/><line x1="10" y1="4" x2="22" y2="4"/><circle cx="15" cy="20" r="1.5" fill="currentColor" stroke="none" opacity="0.4"/><circle cx="20" cy="18" r="1" fill="currentColor" stroke="none" opacity="0.3"/></svg>',
    'reports' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h12l6 6v16a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z"/><path d="M20 4v6h6"/><line x1="10" y1="16" x2="22" y2="16" opacity="0.4"/><line x1="10" y1="20" x2="18" y2="20" opacity="0.3"/><line x1="10" y1="24" x2="15" y2="24" opacity="0.2"/></svg>',
    'notifications' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4a8 8 0 018 8c0 4 2 6 2 10H6c0-4 2-6 2-10a8 8 0 018-8z"/><path d="M13 24c0 1.5 1.3 3 3 3s3-1.5 3-3" opacity="0.5"/></svg>',
    'search' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="14" cy="14" r="8"/><line x1="20" y1="20" x2="27" y2="27" stroke-width="2.5"/><line x1="10" y1="12" x2="18" y2="12" opacity="0.3"/><line x1="10" y1="16" x2="15" y2="16" opacity="0.2"/></svg>',
    'types' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="20" height="6" rx="2"/><rect x="6" y="14" width="20" height="6" rx="2" opacity="0.6"/><rect x="6" y="22" width="20" height="6" rx="2" opacity="0.3"/></svg>',
    'parameters' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M16 4v6M16 22v6M4 16h6M22 16h6"/><circle cx="16" cy="16" r="6"/><circle cx="16" cy="16" r="2" opacity="0.4" fill="currentColor" stroke="none"/></svg>',
    'attachments' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16l-8 8a4 4 0 01-5.6-5.6l10-10a2.8 2.8 0 014 4L13 21"/></svg>',
    'users' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="10" r="4"/><path d="M4 26c0-4.4 3.6-8 8-8s8 3.6 8 8"/><circle cx="22" cy="11" r="3" opacity="0.5"/><path d="M22 18c3.3 0 6 2.7 6 6" opacity="0.5"/></svg>',
    'audit' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h10l6 6v16a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z"/><path d="M18 4v6h6"/><line x1="10" y1="16" x2="22" y2="16" opacity="0.3"/><line x1="10" y1="20" x2="18" y2="20" opacity="0.2"/></svg>',
];

$iconSvg = $emptyIcon;
if (!$iconSvg && $emptyContext && isset($contextIcons[$emptyContext])) {
    $iconSvg = '<div class="empty-state-icon">' . $contextIcons[$emptyContext] . '</div>';
} elseif (!$iconSvg) {
    $iconSvg = '<div class="empty-state-icon"><svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="20" height="20" rx="3"/><line x1="13" y1="16" x2="19" y2="16"/><line x1="16" y1="13" x2="16" y2="19"/></svg></div>';
}
?>
<div class="empty-state">
  <?= $iconSvg ?>
  <h3 class="empty-state-title"><?= e($emptyTitle) ?></h3>
  <?php if ($emptyMessage): ?>
  <p class="empty-state-text"><?= e($emptyMessage) ?></p>
  <?php endif; ?>
  <?php if ($emptyAction): ?>
  <?= $emptyAction ?>
  <?php endif; ?>
</div>
