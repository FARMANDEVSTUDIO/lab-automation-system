<?php
declare(strict_types=1);

/**
 * Reusable confirm modal component. (Arsalan)
 *
 * Usage:
 *   $modalId = 'deleteModal';
 *   $modalTitle = 'Confirm Deletion';
 *   $modalMessage = 'Are you sure you want to delete this item?';
 *   $modalAction = '/products/delete.php';
 *   $modalBtnText = 'Delete';
 *   $modalBtnClass = 'btn-danger';
 *   $modalHiddenFields = '<input type="hidden" name="id" value="5">';
 *   require __DIR__ . '/../components/confirm_modal.php';
 */

$modalId           = $modalId           ?? 'confirmModal';
$modalTitle        = $modalTitle        ?? 'Confirm';
$modalMessage      = $modalMessage      ?? 'Are you sure?';
$modalAction       = $modalAction       ?? '';
$modalBtnText      = $modalBtnText      ?? 'Confirm';
$modalBtnClass     = $modalBtnClass     ?? 'btn-danger';
$modalHiddenFields = $modalHiddenFields ?? '';
?>
<div class="modal-backdrop" id="<?= e($modalId) ?>" hidden>
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title"><?= e($modalTitle) ?></h3>
      <button type="button" class="modal-close" onclick="document.getElementById('<?= e($modalId) ?>').hidden = true">&times;</button>
    </div>
    <form method="post" action="<?= e($modalAction) ?>">
      <?= csrf_field() ?>
      <?= $modalHiddenFields ?>
      <div class="modal-body">
        <p><?= e($modalMessage) ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('<?= e($modalId) ?>').hidden = true">Cancel</button>
        <button type="submit" class="btn <?= e($modalBtnClass) ?>"><?= e($modalBtnText) ?></button>
      </div>
    </form>
  </div>
</div>
