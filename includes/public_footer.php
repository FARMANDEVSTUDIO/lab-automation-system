<?php // Public footer — brand, navigation links, copyright (Arsalan) ?>
<footer class="public-footer">
  <div class="public-footer-inner">
    <div class="public-footer-brand">
      <svg width="18" height="18" viewBox="0 0 28 28" fill="none">
        <rect width="28" height="28" rx="7" fill="var(--accent)"/>
        <rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/>
      </svg>
      FZ Engineering
    </div>
    <div class="public-footer-links">
      <a href="<?= url('index.php') ?>">Home</a>
      <a href="<?= url('auth/login.php') ?>">Sign In</a>
      <a href="<?= url('guide/index.php') ?>">Guide</a>
      <a href="<?= url('pages/privacy.php') ?>">Privacy</a>
      <a href="<?= url('pages/terms.php') ?>">Terms</a>
      <a href="<?= url('pages/security.php') ?>">Security</a>
      <a href="<?= url('pages/contact.php') ?>">Contact</a>
    </div>
    <div class="public-footer-copy">&copy; <?= date('Y') ?> FZ Engineering &middot; v<?= APP_VERSION ?></div>
  </div>
</footer>
