<?php
declare(strict_types=1);
// Terms of use page — public legal content (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title>Terms of Use &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
.terms-hero {
  position: relative;
  padding: 64px 0 56px;
  overflow: hidden;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(135deg, rgba(107,122,141,0.06) 0%, transparent 60%);
}
:root[data-theme="light"] .terms-hero {
  background: linear-gradient(135deg, rgba(107,122,141,0.08) 0%, transparent 60%);
}
.terms-hero-inner {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 32px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 48px;
  align-items: center;
}
.terms-hero-visual {
  width: 120px;
  height: 120px;
  border-radius: 24px;
  background: rgba(107,122,141,0.1);
  border: 1px solid rgba(107,122,141,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
:root[data-theme="light"] .terms-hero-visual {
  background: rgba(107,122,141,0.08);
  border-color: rgba(107,122,141,0.12);
}
.terms-hero-visual svg { color: #6B7A8D; }
.terms-hero .info-eyebrow { color: #6B7A8D; }
.terms-hero h1 {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin-bottom: 8px;
}
.terms-hero .info-meta { margin-bottom: 12px; }
.terms-hero .info-lede {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--ink-soft);
  max-width: 520px;
  margin: 0;
}
.terms-toc {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 20px;
}
.terms-toc a {
  font-size: 0.6875rem;
  font-weight: 500;
  color: var(--ink-faint);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  border: 1px solid var(--line);
  background: var(--paper);
  text-decoration: none;
  transition: color var(--duration) var(--ease), border-color var(--duration) var(--ease);
}
.terms-toc a:hover { color: var(--ink); border-color: var(--line-strong); }
@media (max-width: 640px) {
  .terms-hero-inner { grid-template-columns: 1fr; gap: 24px; }
  .terms-hero-visual { width: 80px; height: 80px; border-radius: 16px; order: -1; }
  .terms-hero h1 { font-size: 1.5rem; }
  .terms-hero { padding: 40px 0 36px; }
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

<div class="terms-hero emerge-section">
  <div class="terms-hero-inner" data-emerge>
    <div data-emerge-child>
      <a href="<?= url('index.php') ?>" class="info-back">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
        Back to Home
      </a>
      <div class="info-eyebrow" style="margin-top:20px;">Legal Agreement</div>
      <h1>Terms of Use</h1>
      <div class="info-meta">
        <span>Effective: August 2026</span>
        <span class="info-meta-dot"></span>
        <span>Applies to all workspaces</span>
      </div>
      <p class="info-lede">By accessing the Lab Automation System, you agree to these terms. Access is granted through administrator-created accounts and is subject to your organization's agreements.</p>
      <div class="terms-toc">
        <a href="#authorized-use">Authorized Use</a>
        <a href="#prohibited">Prohibited Actions</a>
        <a href="#accuracy">Data Accuracy</a>
        <a href="#monitoring">Monitoring</a>
        <a href="#suspension">Account Suspension</a>
        <a href="#ip">Intellectual Property</a>
        <a href="#liability">Liability</a>
        <a href="#changes">Changes</a>
      </div>
    </div>
    <div class="terms-hero-visual" data-emerge-child>
      <svg width="56" height="56" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 8h16l8 8v30a2 2 0 01-2 2H16a2 2 0 01-2-2V10a2 2 0 012-2z" opacity="0.4"/>
        <path d="M32 8v8h8"/>
        <line x1="20" y1="22" x2="36" y2="22"/>
        <line x1="20" y1="27" x2="32" y2="27"/>
        <line x1="20" y1="32" x2="36" y2="32"/>
        <line x1="20" y1="37" x2="28" y2="37"/>
        <circle cx="32" cy="40" r="5" opacity="0.3"/>
        <polyline points="30,40 32,42 35,38" stroke-width="1.5"/>
      </svg>
    </div>
  </div>
</div>

<div class="info-page" style="padding-top:40px;">
  <div class="info-section" data-emerge="0.7" id="authorized-use">
    <div class="info-section-head"><span class="info-section-num">01</span><h2>Authorized Use</h2></div>
    <p>You agree to:</p>
    <ul>
      <li>Use the system only for its intended purpose — managing laboratory testing workflows, recording measurements, and generating reports.</li>
      <li>Access only the functions and data authorized for your assigned role.</li>
      <li>Maintain the confidentiality of your login credentials and not share them with others.</li>
      <li>Report any suspected unauthorized access or security incidents immediately to your administrator.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7" id="prohibited">
    <div class="info-section-head"><span class="info-section-num">02</span><h2>Prohibited Actions</h2></div>
    <ul>
      <li>Attempting to access pages, data, or functions beyond your assigned role.</li>
      <li>Modifying, deleting, or tampering with audit log entries.</li>
      <li>Sharing login credentials or allowing others to use your account.</li>
      <li>Uploading malicious files or attempting to exploit system vulnerabilities.</li>
      <li>Falsifying testing data, measurements, or review decisions.</li>
      <li>Using automated tools to scrape or extract data without authorization.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7" id="accuracy">
    <div class="info-section-head"><span class="info-section-num">03</span><h2>Data Accuracy</h2></div>
    <p>Users are responsible for the accuracy of data they enter into the system, including product information, measurements, and review decisions. All entries are logged and attributed to the authenticated user.</p>
  </div>

  <div class="info-section" data-emerge="0.7" id="monitoring">
    <div class="info-section-head"><span class="info-section-num">04</span><h2>Monitoring</h2></div>
    <p>All actions within the system are logged in an immutable audit trail. Your organization reserves the right to review audit logs for compliance, security, and operational purposes.</p>
  </div>

  <div class="info-section" data-emerge="0.7" id="suspension">
    <div class="info-section-head"><span class="info-section-num">05</span><h2>Account Suspension</h2></div>
    <p>Your administrator may suspend or deactivate your account at any time. Accounts are automatically locked after exceeding the configured maximum failed login attempts.</p>
  </div>

  <div class="info-section" data-emerge="0.7" id="ip">
    <div class="info-section-head"><span class="info-section-num">06</span><h2>Intellectual Property</h2></div>
    <p>The Lab Automation System, including its source code, design, and documentation, is the property of FZ Engineering. Testing data and operational records belong to your organization.</p>
  </div>

  <div class="info-section" data-emerge="0.7" id="liability">
    <div class="info-section-head"><span class="info-section-num">07</span><h2>Limitation of Liability</h2></div>
    <p>The system is provided "as is" for internal operational use. While every effort is made to ensure data integrity and system reliability, FZ Engineering is not liable for data loss resulting from hardware failures, misuse, or events beyond reasonable control.</p>
  </div>

  <div class="info-section" data-emerge="0.7" id="changes">
    <div class="info-section-head"><span class="info-section-num">08</span><h2>Changes</h2></div>
    <p>These terms may be updated periodically. Continued use of the system after changes constitutes acceptance of the revised terms. Users will be notified of material changes through system notifications.</p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
