<?php
declare(strict_types=1);
// Privacy policy page — public information display (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title>Privacy Policy &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
.priv-hero {
  position: relative;
  padding: 64px 0 56px;
  overflow: hidden;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(135deg, rgba(74,124,155,0.06) 0%, transparent 60%);
}
:root[data-theme="light"] .priv-hero {
  background: linear-gradient(135deg, rgba(74,124,155,0.08) 0%, transparent 60%);
}
.priv-hero-inner {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 32px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 48px;
  align-items: center;
}
.priv-hero-visual {
  width: 120px;
  height: 120px;
  border-radius: 24px;
  background: rgba(74,124,155,0.1);
  border: 1px solid rgba(74,124,155,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
:root[data-theme="light"] .priv-hero-visual {
  background: rgba(74,124,155,0.08);
  border-color: rgba(74,124,155,0.12);
}
.priv-hero-visual svg { color: #4A7C9B; }
.priv-hero .info-eyebrow { color: #4A7C9B; }
.priv-hero h1 {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin-bottom: 8px;
}
.priv-hero .info-meta { margin-bottom: 12px; }
.priv-hero .info-lede {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--ink-soft);
  max-width: 520px;
  margin: 0;
}
@media (max-width: 640px) {
  .priv-hero-inner { grid-template-columns: 1fr; gap: 24px; }
  .priv-hero-visual { width: 80px; height: 80px; border-radius: 16px; order: -1; }
  .priv-hero h1 { font-size: 1.5rem; }
  .priv-hero { padding: 40px 0 36px; }
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

<div class="priv-hero emerge-section">
  <div class="priv-hero-inner" data-emerge>
    <div data-emerge-child>
      <a href="<?= url('index.php') ?>" class="info-back">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
        Back to Home
      </a>
      <div class="info-eyebrow" style="margin-top:20px;">Data Protection</div>
      <h1>Privacy Policy</h1>
      <div class="info-meta">
        <span>Last updated: August 2026</span>
        <span class="info-meta-dot"></span>
        <span>Applies to all workspaces</span>
      </div>
      <p class="info-lede">How we collect, use, and protect your data within the Lab Automation System. Access is limited to authorized employees with administrator-created accounts.</p>
    </div>
    <div class="priv-hero-visual" data-emerge-child>
      <svg width="56" height="56" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M28 6L46 14v12c0 12-8 20-18 24C18 46 10 38 10 26V14L28 6z" opacity="0.4"/>
        <rect x="21" y="22" width="14" height="12" rx="2"/>
        <path d="M24 22v-4a4 4 0 018 0v4"/>
        <circle cx="28" cy="28.5" r="1.5" fill="currentColor"/>
        <line x1="28" y1="30" x2="28" y2="32"/>
      </svg>
    </div>
  </div>
</div>

<div class="info-page" style="padding-top:40px;">
  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">01</span><h2>Data We Collect</h2></div>
    <p>The system collects and processes the following categories of data:</p>
    <ul>
      <li><strong>Account Information:</strong> Name, email address, role, department assignment, and account status. These are provided by your administrator when creating your account.</li>
      <li><strong>Authentication Data:</strong> Password hashes (passwords are never stored in plaintext), login timestamps, failed login attempts, and session information.</li>
      <li><strong>Operational Data:</strong> Product registrations, testing records, measurement data, review decisions, and file attachments uploaded during the testing process.</li>
      <li><strong>Audit Data:</strong> Action logs including the actor, action type, before/after state, IP address, user agent, and timestamp for every significant operation.</li>
      <li><strong>Technical Data:</strong> IP addresses and browser user agent strings are recorded in audit logs and session records for security purposes.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">02</span><h2>How We Use Data</h2></div>
    <ul>
      <li>To authenticate users and enforce role-based access control.</li>
      <li>To manage the testing workflow — product registration, test assignment, measurement recording, and review processes.</li>
      <li>To generate operational reports (yield, throughput, cycle time).</li>
      <li>To maintain an immutable audit trail for regulatory compliance.</li>
      <li>To detect and prevent unauthorized access attempts.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">03</span><h2>Data Storage</h2></div>
    <p>All data is stored in a MySQL/MariaDB database on infrastructure managed by your organization. File attachments are stored on the local server filesystem. No data is transmitted to external services or third parties.</p>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">04</span><h2>Data Retention</h2></div>
    <p>Operational data (products, testing records, measurements) and audit logs are retained indefinitely for compliance purposes. Session data expires according to the configured session lifetime (default: 8 hours of inactivity). Your administrator can configure retention policies through system settings.</p>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">05</span><h2>Your Rights</h2></div>
    <p>As an internal system user, you can:</p>
    <ul>
      <li>View and update your profile information (name).</li>
      <li>Change your password at any time.</li>
      <li>View your active sessions.</li>
      <li>Request your administrator to update your email, role, or department assignment.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">06</span><h2>Contact</h2></div>
    <p>For privacy-related questions or data requests, contact your system administrator or IT department — see the <a href="<?= url('pages/contact.php') ?>">Contact page</a> for guidance on who to reach.</p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
