<?php
declare(strict_types=1);

/**
 * Reusable page header component. (Arsalan)
 *
 * Usage:
 *   $headerTitle = 'Products';
 *   $headerSubtitle = 'Manage product intake and lifecycle';
 *   $headerActions = '<a href="..." class="btn btn-primary">+ Register Product</a>';
 *   require __DIR__ . '/../components/page_header.php';
 */

$headerTitle    = $headerTitle    ?? 'Page';
$headerSubtitle = $headerSubtitle ?? '';
$headerActions  = $headerActions  ?? '';
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><?= e($headerTitle) ?></h1>
    <?php if ($headerSubtitle): ?>
    <p class="page-subtitle"><?= e($headerSubtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if ($headerActions): ?>
  <div class="page-actions">
    <?= $headerActions ?>
  </div>
  <?php endif; ?>
</div>
