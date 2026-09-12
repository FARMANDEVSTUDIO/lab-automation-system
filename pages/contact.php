<?php
declare(strict_types=1);
// Contact page — support form and information (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title>Contact Support &middot; <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
.ct-hero {
  position: relative;
  padding: 64px 0 56px;
  overflow: hidden;
  border-bottom: 1px solid var(--line);
  background: linear-gradient(135deg, rgba(184,134,74,0.06) 0%, transparent 60%);
}
:root[data-theme="light"] .ct-hero {
  background: linear-gradient(135deg, rgba(184,134,74,0.08) 0%, transparent 60%);
}
.ct-hero-inner {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 32px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 48px;
  align-items: center;
}
.ct-hero-visual {
  width: 120px;
  height: 120px;
  border-radius: 24px;
  background: rgba(184,134,74,0.1);
  border: 1px solid rgba(184,134,74,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
:root[data-theme="light"] .ct-hero-visual {
  background: rgba(184,134,74,0.08);
  border-color: rgba(184,134,74,0.12);
}
.ct-hero-visual svg { color: #B8864A; }
.ct-hero .info-eyebrow { color: #B8864A; }
.ct-hero h1 {
  font-family: var(--font-display);
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--ink);
  margin-bottom: 8px;
}
.ct-hero .info-lede {
  font-size: 0.9375rem;
  line-height: 1.7;
  color: var(--ink-soft);
  max-width: 520px;
  margin: 0;
}
.ct-channels {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 32px 0;
}
.ct-channel {
  padding: 24px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--paper-raised);
  transition: border-color 0.3s var(--ease), transform 0.3s var(--ease);
}
.ct-channel:hover {
  border-color: var(--line-strong);
  transform: translateY(-2px);
}
.ct-channel-full { grid-column: 1 / -1; }
.ct-channel-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}
.ct-channel-icon.icon-blue { background: rgba(74,124,155,0.12); color: #4A7C9B; }
.ct-channel-icon.icon-amber { background: rgba(184,134,74,0.12); color: #B8864A; }
.ct-channel-icon.icon-green { background: rgba(74,139,107,0.12); color: #4A8B6B; }
.ct-channel-icon.icon-slate { background: rgba(107,122,141,0.12); color: #6B7A8D; }
.ct-channel-icon.icon-red { background: rgba(166,35,28,0.12); color: var(--accent-strong); }
.ct-channel h3 {
  font-family: var(--font-display);
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 8px;
}
.ct-channel p {
  font-size: 0.8125rem;
  line-height: 1.65;
  color: var(--ink-soft);
  margin: 0 0 12px;
}
.ct-channel-action {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ink-faint);
  padding: 5px 12px;
  border-radius: var(--radius-full);
  background: var(--paper);
  border: 1px solid var(--line);
  display: inline-block;
}
@media (max-width: 640px) {
  .ct-hero-inner { grid-template-columns: 1fr; gap: 24px; }
  .ct-hero-visual { width: 80px; height: 80px; border-radius: 16px; order: -1; }
  .ct-hero h1 { font-size: 1.5rem; }
  .ct-hero { padding: 40px 0 36px; }
  .ct-channels { grid-template-columns: 1fr; padding: 28px 20px 0; }
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

<div class="ct-hero emerge-section">
  <div class="ct-hero-inner" data-emerge>
    <div data-emerge-child>
      <a href="<?= url('index.php') ?>" class="info-back">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="10,3 5,8 10,13"/></svg>
        Back to Home
      </a>
      <div class="info-eyebrow" style="margin-top:20px;">Internal Support</div>
      <h1>Contact &amp; Support</h1>
      <p class="info-lede">Need help with the Lab Automation System? This is internal enterprise software — support is routed through your organization, not an external help desk.</p>
    </div>
    <div class="ct-hero-visual" data-emerge-child>
      <svg width="56" height="56" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="10" y="16" width="36" height="24" rx="3" opacity="0.4"/>
        <polyline points="10,18 28,32 46,18"/>
        <circle cx="44" cy="16" r="6" fill="currentColor" opacity="0.25" stroke="none"/>
        <path d="M42 16l2 2 3-3" stroke-width="1.5"/>
      </svg>
    </div>
  </div>
</div>

<div class="ct-channels" data-emerge="0.7">
  <div class="ct-channel" data-emerge-child>
    <div class="ct-channel-icon icon-blue">
      <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="8" cy="5" r="3"/><path d="M2.5 14c0-3 2.46-5.5 5.5-5.5s5.5 2.5 5.5 5.5"/></svg>
    </div>
    <h3>Account &amp; Access</h3>
    <p>Locked account, forgotten password, role changes, or new account requests. Your administrator can reset passwords and manage accounts through the User Management panel.</p>
    <span class="ct-channel-action">Contact your System Administrator</span>
  </div>

  <div class="ct-channel" data-emerge-child>
    <div class="ct-channel-icon icon-amber">
      <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="9" rx="1.5"/><polyline points="2,4 8,8.5 14,4"/></svg>
    </div>
    <h3>Technical Support</h3>
    <p>System errors, pages not loading, database issues, or performance concerns. Provide the error details, your browser, and the steps to reproduce the issue.</p>
    <span class="ct-channel-action">Contact your IT department</span>
  </div>

  <div class="ct-channel" data-emerge-child>
    <div class="ct-channel-icon icon-green">
      <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 4v5M2.5 14h11L8 4 2.5 14z"/><circle cx="8" cy="11.5" r="0.4" fill="currentColor"/></svg>
    </div>
    <h3>Workflow Support</h3>
    <p>Questions about assigning tests, recording measurements, review decisions, or CPRI approval. Check the Guide first — most workflow questions are answered there.</p>
    <span class="ct-channel-action">See the <a href="<?= url('guide/index.php') ?>" style="color:inherit;">Guide</a>, then your team lead</span>
  </div>

  <div class="ct-channel" data-emerge-child>
    <div class="ct-channel-icon icon-slate">
      <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 2v4l3 2"/><circle cx="8" cy="8" r="6"/></svg>
    </div>
    <h3>Feature Requests</h3>
    <p>Suggestions for new features, workflow improvements, or usability enhancements. We review all feedback to prioritize future development.</p>
    <span class="ct-channel-action">Submit via your team lead</span>
  </div>

  <div class="ct-channel ct-channel-full" data-emerge-child>
    <div class="ct-channel-icon icon-red">
      <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M8 1.5l6 2.5v4c0 3.5-2.5 6-6 7.5C4.5 14 2 11.5 2 8V4l6-2.5z"/></svg>
    </div>
    <h3>Security Concerns</h3>
    <p>Suspected unauthorized access, data breaches, or security vulnerabilities. Report immediately — do not publicly disclose security issues before they are resolved. See the <a href="<?= url('pages/security.php') ?>" style="color:inherit;">Security Policy</a> for details on how the system is protected.</p>
    <span class="ct-channel-action">Contact IT Security immediately</span>
  </div>
</div>

<div class="info-page" style="padding-top:16px;">
  <div class="info-section" data-emerge="0.7" style="margin-top: 12px;">
    <div class="info-section-head"><span class="info-section-num">&bull;</span><h2>Before You Contact Support</h2></div>
    <ul>
      <li>Check the <a href="<?= url('guide/index.php') ?>">Guide</a> for answers to common questions.</li>
      <li>Try refreshing the page (Ctrl+Shift+R) to clear cached data.</li>
      <li>Check that your browser is up to date (Chrome, Firefox, Edge recommended).</li>
      <li>Note the exact error message, URL, and steps to reproduce the issue.</li>
      <li>Include your role and which page you were on when the issue occurred.</li>
    </ul>
  </div>

  <div class="info-section" data-emerge="0.7">
    <div class="info-section-head"><span class="info-section-num">&bull;</span><h2>System Information</h2></div>
    <p>When reporting issues, include:</p>
    <ul>
      <li>Application version: <strong>v<?= e(APP_VERSION) ?></strong></li>
      <li>Your browser name and version</li>
      <li>The URL where the issue occurred</li>
      <li>Screenshot of the error (if applicable)</li>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
