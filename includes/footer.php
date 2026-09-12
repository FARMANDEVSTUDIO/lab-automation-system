<?php // Footer — search modal, toast container, JS loader (Arsalan) ?>
<?php if ($requireAuth && $currentUser): ?>
    </main><!-- /.app-content -->
  </div><!-- /.app-main -->
</div><!-- /.app-shell -->
<?php endif; ?>

<!-- Search Modal (Ctrl+K) -->
<div class="search-modal-backdrop" id="searchModal" hidden>
  <div class="search-modal">
    <div class="search-modal-input-wrap">
      <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="7" cy="7" r="4.5"/><line x1="10.5" y1="10.5" x2="14" y2="14"/></svg>
      <input type="text" class="search-modal-input" id="searchInput" placeholder="Search products, records, users…" autocomplete="off">
      <kbd class="search-shortcut">Esc</kbd>
    </div>
    <div class="search-modal-results" id="searchResults">
      <div class="search-empty">Type to search across all modules</div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer">
  <?php if ($msg = flash('success')): ?>
  <div class="toast toast-success" role="alert">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polyline points="4,9 7.5,12.5 14,5.5"/></svg>
    <span><?= e($msg) ?></span>
    <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
  </div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
  <div class="toast toast-error" role="alert">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="9" cy="9" r="7"/><line x1="9" y1="6" x2="9" y2="10"/><circle cx="9" cy="12.5" r="0.5" fill="currentColor"/></svg>
    <span><?= e($msg) ?></span>
    <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
  </div>
  <?php endif; ?>
</div>

<script>var BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<script src="<?= asset('js/app.js') ?>"></script>

<?php if ($requireAuth && $currentUser): ?>
<!-- Account Status Heartbeat -->
<div id="accountInactiveOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:99999;align-items:center;justify-content:center;">
  <div style="background:var(--paper-raised,#191314);border:1px solid var(--line,#2C2224);border-radius:12px;padding:40px 36px;max-width:420px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.6);">
    <div style="width:56px;height:56px;border-radius:50%;background:rgba(220,38,38,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <h2 style="color:var(--ink,#F2E8DC);font-size:1.25rem;margin:0 0 10px;font-family:var(--font-display,sans-serif);">Account Inactive</h2>
    <p style="color:var(--ink-soft,#D0C4BB);font-size:0.875rem;line-height:1.6;margin:0 0 24px;">Your account has been deactivated by an administrator. Please contact your administrator for assistance.</p>
    <a href="<?= url('auth/login.php') ?>" style="display:inline-block;background:var(--accent,#A6231C);color:#fff;text-decoration:none;padding:10px 28px;border-radius:6px;font-weight:600;font-size:0.875rem;">Go to Sign In</a>
  </div>
</div>
<script>
(function(){
  var hbInterval = setInterval(function(){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', BASE_URL + '/api/heartbeat.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function(){
      if (xhr.readyState === 4 && xhr.status === 200) {
        try {
          var r = JSON.parse(xhr.responseText);
          if (!r.active) {
            clearInterval(hbInterval);
            var ov = document.getElementById('accountInactiveOverlay');
            if (ov) ov.style.display = 'flex';
            document.body.style.overflow = 'hidden';
          }
        } catch(e){}
      } else if (xhr.readyState === 4 && xhr.status === 401) {
        clearInterval(hbInterval);
        window.location.href = BASE_URL + '/auth/login.php';
      }
    };
    xhr.send();
  }, 15000);
})();
</script>
<?php endif; ?>
<?php if (!empty($extraJs)): ?>
<?php foreach ((array) $extraJs as $js): ?>
<script src="<?= asset('js/' . $js) ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
