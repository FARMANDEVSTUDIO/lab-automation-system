<?php
declare(strict_types=1);
// Security policy page — public information display (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title>Security Policy &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
.sec-hero {
  position: relative;
  padding: 64px 0 56px;
  overflow: hidden;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(135deg, rgba(74,139,107,0.06) 0%, transparent 60%);
}
:root[data-theme="light"] .sec-hero {
  background: linear-gradient(135deg, rgba(74,139,107,0.08) 0%, transparent 60%);
}
.sec-hero-inner {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 32px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 48px;
  align-items: center;
}
.sec-hero-visual {
  width: 120px;
  height: 120px;
  border-radius: 24px;
  background: rgba(74,139,107,0.1);
  border: 1px solid rgba(74,139,107,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
:root[data-theme="light"] .sec-hero-visual {
  background: rgba(74,139,107,0.08);
  border-color: rgba(74,139,107,0.12);
}
.sec-hero-visual svg { color: #4A8B6B; }
.sec-hero .info-eyebrow { color: #4A8B6B; }
.sec-hero h1 {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin-bottom: 8px;
}
.sec-hero .info-meta { margin-bottom: 12px; }
.sec-hero .info-lede {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--ink-soft);
  max-width: 520px;
  margin: 0;
}
.sec-measures {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 20px;
}
.sec-measure {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #4A8B6B;
}
.sec-measure-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #4A8B6B;
  flex-shrink: 0;
}
@media (max-width: 640px) {
  .sec-hero-inner { grid-template-columns: 1fr; gap: 24px; }
  .sec-hero-visual { width: 80px; height: 80px; border-radius: 16px; order: -1; }
  .sec-hero h1 { font-size: 1.5rem; }
  .sec-hero { padding: 40px 0 36px; }
  .sec-measures { grid-template-columns: 1fr; }
}
</style>
</head>
<body class="app-public">
<nav class="info-nav">
  <a href="<?= url('index.php') ?>" class="info-nav-brand">
    <svg width="20" height="20" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="7" fill="var(--accent)"/><rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/></svg>
    <?= e(APP_NAME) ?>
  </a>
  <div class="info-nav-actions">
    <a href="<?= url('index.php') ?>" class="info-nav-link">Home</a>
    <a href="<?= url('auth/login.php') ?>" class="btn btn-primary btn-sm">Sign In</a>
  </div>
</nav>

<div class="sec-hero emerge-section">
  <div class="sec-hero-inner" data-emerge>
    <div data-emerge-child>
      <a href="<?= url('index.php') ?>" class="info-back">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
        Back to Home
      </a>
      <div class="info-eyebrow" style="margin-top:20px;">Access Control</div>
      <h1>Security Policy</h1>
      <div class="info-meta">
        <span>Last reviewed: August 2026</span>
      </div>
      <p class="info-lede">How the Lab Automation System protects your data, enforces access control, and maintains operational integrity.</p>
      <div class="sec-measures">
        <div class="sec-measure"><span class="sec-measure-dot"></span>Bcrypt password hashing</div>
        <div class="sec-measure"><span class="sec-measure-dot"></span>CSRF token protection</div>
        <div class="sec-measure"><span class="sec-measure-dot"></span>Role-based access control</div>
        <div class="sec-measure"><span class="sec-measure-dot"></span>Append-only audit log</div>
      </div>
    </div>
    <div class="sec-hero-visual" data-emerge-child>
      <svg width="56" height="56" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="14" y="24" width="28" height="22" rx="3" opacity="0.4"/>
        <path d="M20 24v-6a8 8 0 0116 0v6"/>
        <circle cx="28" cy="35" r="3"/>
        <line x1="28" y1="38" x2="28" y2="41"/>
        <path d="M8 20l4-4M48 20l-4-4M8 40l4 4M48 40l-4 4" opacity="0.2" stroke-width="1"/>
      </svg>
    </div>
  </div>
</div>

<div class="info-page" style="padding-top:40px;">
  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">01</span><h2>Authentication</h2></div>
    <p>The system uses session-based authentication with the following safeguards:</p>
    <ul>
      <li>Passwords are hashed using bcrypt (PASSWORD_BCRYPT) — plaintext passwords are never stored.</li>
      <li>Failed login attempts are tracked per account. After reaching the configured maximum, the account is temporarily locked.</li>
      <li>Session cookies are set with HttpOnly and SameSite=Strict flags to prevent cross-site attacks.</li>
      <li>Sessions use strict mode, preventing session fixation attacks.</li>
      <li>Session IDs are regenerated on login to prevent session hijacking.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">02</span><h2>Authorization</h2></div>
    <p>Role-based access control (RBAC) enforces the principle of least privilege:</p>
    <ul>
      <li>Five roles (Administrator, Testing Engineer, Lab Technician, Quality Manager, Auditor) with granular permissions.</li>
      <li>Server-side authorization checks on every request — UI visibility alone does not control access.</li>
      <li>Direct URL access to restricted pages returns HTTP 403.</li>
      <li>Role values are never trusted from the client side.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">03</span><h2>CSRF Protection</h2></div>
    <p>Every state-changing request (POST) requires a valid CSRF token. Requests without a valid token receive HTTP 419. Tokens are tied to the user's session and verified server-side.</p>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">04</span><h2>Data Protection</h2></div>
    <ul>
      <li>All database queries use PDO prepared statements to prevent SQL injection.</li>
      <li>User input is escaped with htmlspecialchars() before rendering to prevent cross-site scripting (XSS).</li>
      <li>File uploads are stored outside the web-accessible directory with execution disabled via .htaccess.</li>
      <li>PHP error display is disabled in production (display_errors = 0). Errors are logged server-side.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">05</span><h2>Audit Trail</h2></div>
    <p>All significant actions are recorded in an append-only audit log including:</p>
    <ul>
      <li>Actor identity (user ID)</li>
      <li>Action performed</li>
      <li>Before and after state (JSON snapshots)</li>
      <li>IP address and user agent</li>
      <li>Timestamp</li>
    </ul>
    <p>Audit records cannot be modified or deleted through the application interface.</p>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">06</span><h2>Account Management</h2></div>
    <p>There is no public registration for standard roles. All non-administrator accounts are created by an Administrator through the user management interface. This ensures only authorized personnel can access the system.</p>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">07</span><h2>Reporting Security Issues</h2></div>
    <p>If you discover a security vulnerability, please report it immediately to your system administrator or IT department — see the <a href="<?= url('pages/contact.php') ?>">Contact page</a>. Do not publicly disclose security issues before they are resolved.</p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
