<?php
declare(strict_types=1);
// User guide — role-aware documentation with scroll-spy nav (Arsalan)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'User Guide';
$loggedIn = !empty($_SESSION['user']['id']);
if ($loggedIn) {
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
        ['label' => 'User Guide', 'url' => ''],
    ];
    require_once __DIR__ . '/../includes/header.php';
    $role = auth_role();
} else {
    $role = '';
}

$pdo = Database::getConnection();
$showAll      = !$loggedIn;
$isAdmin      = $showAll || $role === ROLE_ADMINISTRATOR;
$isEngineer   = $showAll || $role === ROLE_TESTING_ENGINEER;
$isTechnician = $showAll || $role === ROLE_LAB_TECHNICIAN;
$isQM         = $showAll || $role === ROLE_QUALITY_MANAGER;
$isAuditor    = $showAll || $role === ROLE_AUDITOR;

if (!$loggedIn): ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){ var t = localStorage.getItem('lab_theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
<title>User Guide · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<style>
.lp-nav { display:flex;align-items:center;justify-content:space-between;padding:0 48px;height:64px;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:50;backdrop-filter:blur(16px) saturate(1.8);background:rgba(15,11,12,0.88); }
:root[data-theme="light"] .lp-nav { background:rgba(250,243,236,0.88); }
.lp-brand { display:flex;align-items:center;gap:10px;font-family:var(--font-display);font-weight:700;font-size:1rem;color:var(--ink);text-decoration:none; }
.lp-nav-actions { display:flex;align-items:center;gap:8px; }
.lp-nav-link { font-size:0.8125rem;font-weight:500;color:var(--ink-soft);padding:6px 14px;border-radius:var(--radius-sm);transition:color var(--duration) var(--ease),background var(--duration) var(--ease);text-decoration:none; }
.lp-nav-link:hover { color:var(--ink);background:var(--accent-subtle); }
.guide-public-wrap { max-width:1100px;margin:0 auto;padding:40px 48px 60px; }
@media(max-width:768px){ .lp-nav{padding:0 20px;} .guide-public-wrap{padding:24px 20px 40px;} }
</style>
</head>
<body class="app-public">
<nav class="lp-nav">
  <a href="<?= url('') ?>" class="lp-brand">
    <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="7" fill="var(--accent)"/><rect x="11" y="6" width="6" height="1.5" rx=".75" fill="var(--ink-on-accent)" opacity=".92"/><path d="M12.5 7.5h3V12l3 5.5a1 1 0 01-.87 1.5H10.37a1 1 0 01-.87-1.5L12.5 12V7.5z" fill="var(--ink-on-accent)" opacity=".92"/></svg>
    <?= e(APP_NAME) ?>
  </a>
  <div class="lp-nav-actions">
    <a href="<?= url('auth/signup.php') ?>" class="lp-nav-link">Create Workspace</a>
    <a href="<?= url('auth/login.php') ?>" class="btn btn-primary">Sign In</a>
  </div>
</nav>
<div class="guide-public-wrap">
<?php endif; ?>

<style>
.guide-layout { display:grid; grid-template-columns:220px 1fr; gap:32px; align-items:start; }
.guide-nav { position:sticky; top:80px; }
.guide-nav a {
  display:block; padding:8px 14px; font-size:0.8125rem; color:var(--ink-soft);
  border-left:2px solid var(--line); text-decoration:none; transition:all var(--duration) var(--ease);
}
.guide-nav a:hover { color:var(--ink); background:var(--paper); }
.guide-nav a.active { color:var(--accent); border-left-color:var(--accent); font-weight:600; }
.guide-section { scroll-margin-top:80px; }
.guide-section h2 { font-size:1.25rem; margin-bottom:16px; padding-bottom:8px; border-bottom:1px solid var(--line); }
.guide-section h3 { font-size:1rem; margin:20px 0 10px; color:var(--ink); }
.guide-content { display:flex; flex-direction:column; gap:40px; max-width:800px; }
.guide-steps { counter-reset:step; list-style:none; padding:0; }
.guide-steps li {
  counter-increment:step; padding:12px 0 12px 44px; position:relative; border-bottom:1px solid var(--line);
  font-size:0.875rem; line-height:1.6;
}
.guide-steps li::before {
  content:counter(step); position:absolute; left:0; top:12px;
  width:28px; height:28px; border-radius:var(--radius-full); background:var(--accent-tint); color:var(--accent);
  display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem;
}
.guide-steps li:last-child { border-bottom:none; }
.guide-tip {
  padding:14px 18px; border-radius:var(--radius-md); background:var(--info-tint);
  border-left:3px solid var(--info); font-size:0.8125rem; color:var(--ink); line-height:1.6;
}
.guide-warn {
  padding:14px 18px; border-radius:var(--radius-md); background:var(--warning-tint);
  border-left:3px solid var(--warning); font-size:0.8125rem; color:var(--ink); line-height:1.6;
}
.guide-table { width:100%; border-collapse:collapse; font-size:0.8125rem; }
.guide-table th { text-align:left; padding:8px 12px; background:var(--paper); font-weight:600; border-bottom:1px solid var(--line); }
.guide-table td { padding:8px 12px; border-bottom:1px solid var(--line); }
.guide-table td:first-child { font-weight:500; white-space:nowrap; }
.kbd { display:inline-block; padding:2px 6px; font-size:0.6875rem; font-family:var(--font-mono); background:var(--paper); border:1px solid var(--line); border-radius:3px; }
@media (max-width:768px) {
  .guide-layout { grid-template-columns:1fr; }
  .guide-nav { position:static; display:flex; flex-wrap:wrap; gap:4px; margin-bottom:16px; }
  .guide-nav a { border-left:none; border-bottom:2px solid var(--line); padding:6px 12px; }
  .guide-nav a.active { border-bottom-color:var(--accent); }
}
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><path d="M2 4c2-1.5 4-2 6-2s4 .5 6 2c2-1.5 4-2 6-2" transform="scale(0.8) translate(2.5,2.5)"/><path d="M2 4v12c2-1 4-1.5 6-1.5s4 .5 6 1.5V4" transform="scale(0.8) translate(2.5,2.5)"/><line x1="10" y1="4" x2="10" y2="16" transform="scale(0.8) translate(2.5,2.5)"/></svg>
      </div>
      <div>
        <h1 class="page-title">User Guide</h1>
        <p class="page-subtitle" style="margin-top:2px;">How to use the Lab Automation System · <?= $loggedIn ? role_label($role) . ' reference' : 'Complete reference' ?></p>
      </div>
    </div>
  </div>
</div>

<div class="guide-layout">
  <nav class="guide-nav">
    <a href="#getting-started" class="active">Getting Started</a>
    <a href="#navigation">Navigation</a>
    <a href="#dashboard">Dashboard</a>
    <?php if ($isAdmin || $isEngineer): ?>
    <a href="#products">Products</a>
    <?php endif; ?>
    <a href="#testing">Testing</a>
    <?php if ($isAdmin || $isEngineer || $isQM || $isAuditor): ?>
    <a href="#reports">Reports</a>
    <?php endif; ?>
    <?php if ($isAdmin): ?>
    <a href="#user-management">User Management</a>
    <a href="#configuration">Configuration</a>
    <?php endif; ?>
    <a href="#profile">Profile & Security</a>
    <?php if ($isAdmin || $isAuditor || $isQM): ?>
    <a href="#audit">Audit Log</a>
    <?php endif; ?>
    <a href="#tips">Tips & Shortcuts</a>
  </nav>

  <div class="guide-content">

    <!-- Getting Started -->
    <section class="guide-section" id="getting-started">
      <h2>Getting Started</h2>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin-bottom:16px;">
        The Lab Automation System manages the full product testing lifecycle — from registration through measurement, review, and CPRI approval. <?= $loggedIn ? 'Your role as <strong>' . role_label($role) . '</strong> determines which features and data you can access.' : 'Each role has access to specific features and data.' ?>
      </p>

      <h3>Signing In</h3>
      <ol class="guide-steps">
        <li>Navigate to the login page. Your administrator provides your email and initial password.</li>
        <li>Enter your email address and password, then click <strong>Sign In</strong>.</li>
        <li>After your first login, go to <strong>Profile</strong> and change your password immediately.</li>
      </ol>

      <div class="guide-tip" style="margin-top:16px;">
        <strong>Security note:</strong> Accounts are created by administrators only — there is no self-registration. After <?= e(get_setting($pdo, 'max_login_attempts', '5')) ?> failed login attempts, your account will be locked for <?= e(get_setting($pdo, 'lockout_duration_minutes', '15')) ?> minutes. Contact your administrator if you're locked out.
      </div>
    </section>

    <!-- Navigation -->
    <section class="guide-section" id="navigation">
      <h2>Navigation</h2>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin-bottom:16px;">
        The sidebar on the left provides access to all available pages. Items shown depend on your role.
      </p>

      <table class="guide-table">
        <thead><tr><th>Section</th><th>Available To</th><th>Purpose</th></tr></thead>
        <tbody>
          <tr><td>Dashboard</td><td>All roles</td><td>Overview with role-specific statistics and actions</td></tr>
          <tr><td>Products</td><td>Admin, Engineer, QM, Auditor</td><td>Product registration and lifecycle tracking</td></tr>
          <tr><td>Testing</td><td>All roles</td><td>Test assignments, measurements, and review queue</td></tr>
          <?php if ($isAdmin || $isEngineer): ?>
          <tr><td>Configuration</td><td>Admin, Engineer</td><td>Departments, testing types, and test parameters</td></tr>
          <?php endif; ?>
          <tr><td>Reports</td><td>Admin, Engineer, QM, Auditor</td><td>Generate and export testing reports</td></tr>
          <tr><td>Audit Log</td><td>Admin, Auditor, QM</td><td>Immutable record of all system changes</td></tr>
          <?php if ($isAdmin): ?>
          <tr><td>Users</td><td>Admin only</td><td>Create, edit, enable/disable user accounts</td></tr>
          <tr><td>Settings</td><td>Admin only</td><td>System configuration and security policies</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <h3>Top Bar</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        The top bar shows breadcrumb navigation, a global search trigger (<span class="kbd">Ctrl+K</span>), your notification bell with unread count, and a user menu for profile access, theme toggle, and sign out.
      </p>

      <h3>Theme</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Click the moon/sun icon in the user menu to switch between light and dark mode. Your preference is saved and persists across sessions.
      </p>
    </section>

    <!-- Dashboard -->
    <section class="guide-section" id="dashboard">
      <h2>Dashboard</h2>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin-bottom:16px;">
        Your dashboard shows real-time statistics and actions relevant to your role:
      </p>

      <?php if ($isAdmin): ?>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Stat tiles:</strong> Total users, products, active tests, pending reviews</li>
        <li><strong>Needs Attention:</strong> Items requiring review, CPRI approval, failed tests, due-soon tests, on-hold products</li>
        <li><strong>Recent Products:</strong> Latest registered products with status</li>
        <li><strong>Departments:</strong> Active departments with test counts</li>
        <li><strong>Quick Actions:</strong> Register product, view queue, generate report, manage users, settings</li>
        <li><strong>Activity Feed:</strong> System-wide recent activity</li>
      </ul>
      <?php elseif ($isEngineer): ?>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Stat tiles:</strong> Assigned tests, active tests, pending review, first-pass yield</li>
        <li><strong>Needs Attention:</strong> Submitted measurements needing review, failed tests needing rework</li>
        <li><strong>My Recent Tests:</strong> Tests you've assigned, with status tracking</li>
        <li><strong>Quick Actions:</strong> Register product, view queue, generate report, manage testing types</li>
      </ul>
      <?php elseif ($isTechnician): ?>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Stat tiles:</strong> My active tests, pending measurements, submitted for review, completed this month</li>
        <li><strong>My Testing Queue:</strong> All tests assigned to you, sorted by due date</li>
        <li><strong>Quick Actions:</strong> View testing queue</li>
      </ul>
      <?php elseif ($isQM): ?>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Stat tiles:</strong> Pending review, failed tests, pass rate, approved this month</li>
        <li><strong>Review Queue:</strong> Tests waiting for your review decision</li>
        <li><strong>Recent Decisions:</strong> History of pass/fail review outcomes</li>
        <li><strong>Quality Overview:</strong> Pass rate by department</li>
      </ul>
      <?php elseif ($isAuditor): ?>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Stat tiles:</strong> Total products, total tests, recent changes (7 days), active users</li>
        <li><strong>Recent Audit Activity:</strong> Latest audit log entries across the system</li>
        <li><strong>System Overview:</strong> Department, testing type, user, and pipeline counts</li>
        <li><strong>Quick Actions:</strong> View testing records, reports, audit log</li>
      </ul>
      <?php endif; ?>
    </section>

    <!-- Products -->
    <?php if ($isAdmin || $isEngineer): ?>
    <section class="guide-section" id="products">
      <h2>Products</h2>

      <h3>Registering a Product</h3>
      <ol class="guide-steps">
        <li>Click <strong>Register Product</strong> from the dashboard or Products page.</li>
        <li>Enter a unique serial number (auto-uppercased), model/specification, and department.</li>
        <li>Optionally add a manufactured date and notes (priority, customer, special requirements).</li>
        <li>Click <strong>Register Product</strong>. The system checks for duplicate serial numbers.</li>
      </ol>

      <h3>Product Lifecycle</h3>
      <table class="guide-table">
        <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
        <tbody>
          <tr><td>Registered</td><td>Product entered into the system, no tests assigned yet</td></tr>
          <tr><td>Assigned</td><td>At least one test has been assigned to this product</td></tr>
          <tr><td>In Testing</td><td>A technician has started recording measurements</td></tr>
          <tr><td>Submitted for Review</td><td>Measurements completed, awaiting engineer review</td></tr>
          <tr><td>Pending CPRI Approval</td><td>All tests passed, awaiting Quality Manager final approval</td></tr>
          <tr><td>Passed / Released</td><td>Product has passed all tests and received CPRI approval</td></tr>
          <tr><td>Failed</td><td>One or more tests failed review — may need rework</td></tr>
          <tr><td>On Hold</td><td>Product testing temporarily paused</td></tr>
        </tbody>
      </table>

      <h3>Assigning Tests</h3>
      <ol class="guide-steps">
        <li>Open a product's detail page.</li>
        <li>In the <strong>Assign Test</strong> section, select a testing type, tester (lab technician), and optional due date.</li>
        <li>Click <strong>Assign Test</strong>. The tester receives a notification and sees it in their queue.</li>
      </ol>
    </section>
    <?php endif; ?>

    <!-- Testing -->
    <section class="guide-section" id="testing">
      <h2>Testing</h2>

      <?php if ($isTechnician): ?>
      <h3>Recording Measurements</h3>
      <ol class="guide-steps">
        <li>Open your assigned test from the dashboard queue or Testing page.</li>
        <li>Click <strong>Start Testing</strong> if this is a new assignment — this changes status to "In Testing".</li>
        <li>Enter measured values for each parameter. The system shows min/max tolerance bands for reference.</li>
        <li>Optionally upload supporting documents (photos, calibration certificates).</li>
        <li>Click <strong>Save Measurements</strong> to save your progress without submitting.</li>
        <li>When all measurements are complete, click <strong>Submit for Review</strong>. This locks the record and notifies the assigned reviewer.</li>
      </ol>

      <div class="guide-warn" style="margin-top:16px;">
        <strong>Important:</strong> Once submitted for review, you cannot modify measurements. Make sure all values are correct and attachments are uploaded before submitting.
      </div>

      <?php elseif ($isEngineer || $isAdmin): ?>
      <h3>Reviewing Test Results</h3>
      <ol class="guide-steps">
        <li>Open a test record with status "Submitted for Review".</li>
        <li>Review all measured values against the defined tolerance bands (min/nominal/max).</li>
        <li>Check attached documents if relevant.</li>
        <li>Select <strong>Pass</strong> or <strong>Fail</strong> and add review notes explaining your decision.</li>
        <li>Click <strong>Submit Review</strong>. The system updates the product status accordingly.</li>
      </ol>

      <div class="guide-tip" style="margin-top:16px;">
        <strong>Pass:</strong> If all tests for a product pass, the product moves to "Pending CPRI Approval" for the Quality Manager.<br>
        <strong>Fail:</strong> The product is marked as failed. A new test attempt can be assigned for rework.
      </div>

      <?php elseif ($isQM): ?>
      <h3>CPRI Approval</h3>
      <ol class="guide-steps">
        <li>Products that have passed all engineering reviews appear in the CPRI approval queue.</li>
        <li>Open the product detail page to review the complete testing history.</li>
        <li>Approve or reject the product based on quality standards.</li>
        <li>Approved products move to "Released" status. Rejected products return for further review.</li>
      </ol>

      <?php elseif ($isAuditor): ?>
      <h3>Viewing Test Records</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        As an auditor, you have read-only access to all testing records. Use the Testing page to view any record's measurements, review decisions, attachments, and complete history. You can filter by status, department, and date range. Use the Audit Log for a chronological trail of every change made to any record.
      </p>
      <?php endif; ?>

      <h3>Testing Page Features</h3>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Status tabs:</strong> Filter records by status (All, Assigned, In Testing, Submitted, Passed, Failed)</li>
        <li><strong>Search:</strong> Find records by product serial number, model, or test name</li>
        <li><strong>Pagination:</strong> Navigate large result sets with page controls</li>
        <li><strong>Detail view:</strong> Click any record to see full measurements, parameters, and attachments</li>
      </ul>
    </section>

    <!-- Reports -->
    <?php if ($isAdmin || $isEngineer || $isQM || $isAuditor): ?>
    <section class="guide-section" id="reports">
      <h2>Reports</h2>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin-bottom:16px;">
        The Reports page provides summary statistics and allows CSV export of testing data.
      </p>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Summary tiles:</strong> Total tests, pass rate, average completion time</li>
        <li><strong>Filters:</strong> Filter by date range, department, testing type, and status</li>
        <li><strong>CSV Export:</strong> Click <strong>Export CSV</strong> to download filtered data as a spreadsheet-compatible file</li>
      </ul>
      <div class="guide-tip" style="margin-top:16px;">
        Reports reflect real database data. If no records match your filters, the export will contain only headers — this is expected behavior, not an error.
      </div>
    </section>
    <?php endif; ?>

    <!-- User Management (Admin) -->
    <?php if ($isAdmin): ?>
    <section class="guide-section" id="user-management">
      <h2>User Management</h2>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin-bottom:16px;">
        Only administrators can create and manage user accounts. There is no public registration — this is internal enterprise software.
      </p>

      <h3>Creating a User</h3>
      <ol class="guide-steps">
        <li>Go to <strong>Administration → Users</strong> from the sidebar.</li>
        <li>Click <strong>Add User</strong>.</li>
        <li>Enter full name, email, select a role, and optionally assign a department.</li>
        <li>Click <strong>Create User</strong>. The account is created with the default password <code>ChangeMe123!</code>.</li>
        <li>Inform the user of their credentials and instruct them to change their password on first login.</li>
      </ol>

      <h3>Editing a User</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Click the <strong>Edit</strong> button on any user row to modify their name, email, role, department, or status. Changes are audit-logged.
      </p>

      <h3>Enabling / Disabling Accounts</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Toggle a user's status between Active and Inactive. Inactive users cannot sign in. You cannot deactivate your own account.
      </p>

      <h3>Resetting Passwords</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Click <strong>Reset Password</strong> on a user row to set their password back to <code>ChangeMe123!</code>. A notification is sent to the user. All password resets are audit-logged.
      </p>

      <h3>Role Permissions</h3>
      <table class="guide-table">
        <thead><tr><th>Role</th><th>Key Permissions</th></tr></thead>
        <tbody>
          <tr><td>Administrator</td><td>Full access — users, settings, products, testing, reports, audit log</td></tr>
          <tr><td>Testing Engineer</td><td>Register products, assign tests, review results, manage testing types, reports</td></tr>
          <tr><td>Lab Technician</td><td>Perform assigned tests, record measurements, submit for review</td></tr>
          <tr><td>Quality Manager</td><td>CPRI approval/rejection, view reports, quality metrics, audit log</td></tr>
          <tr><td>Auditor</td><td>Read-only access to all data — products, tests, reports, audit log (cannot modify anything)</td></tr>
        </tbody>
      </table>
    </section>
    <?php endif; ?>

    <!-- Configuration (Admin) -->
    <?php if ($isAdmin): ?>
    <section class="guide-section" id="configuration">
      <h2>Configuration</h2>

      <h3>Departments</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Go to <strong>Configuration → Departments</strong> to create, edit, or deactivate lab departments. Each department shows its tester count and active test count. Users and products are assigned to departments for organizational tracking.
      </p>

      <h3>Testing Types</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Go to <strong>Configuration → Testing Types</strong> to define the types of tests your lab performs (Electrical, Mechanical, Environmental). Each testing type has versioned parameters with min/nominal/max tolerance values, units, and required instruments.
      </p>

      <h3>Settings</h3>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>General:</strong> Company name, system name, timezone, date format, records per page</li>
        <li><strong>Security:</strong> Password minimum length, max login attempts, lockout duration, session timeout</li>
        <li><strong>Role Matrix:</strong> Reference table showing which roles have which permissions</li>
      </ul>
    </section>
    <?php endif; ?>

    <!-- Profile -->
    <section class="guide-section" id="profile">
      <h2>Profile & Security</h2>

      <h3>Updating Your Profile</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Click your name in the top-right user menu and select <strong>Profile</strong>. You can update your display name. Email and role changes require an administrator.
      </p>

      <h3>Changing Your Password</h3>
      <ol class="guide-steps">
        <li>Go to your Profile page.</li>
        <li>Scroll to <strong>Change Password</strong>.</li>
        <li>Enter your current password, then your new password twice.</li>
        <li>Password must be at least 8 characters with an uppercase letter, number, and special character.</li>
        <li>Click <strong>Update Password</strong>.</li>
      </ol>

      <h3>Active Sessions</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Your profile page shows your current active session with IP address and login time.
      </p>
    </section>

    <!-- Audit Log -->
    <?php if ($isAdmin || $isAuditor || $isQM): ?>
    <section class="guide-section" id="audit">
      <h2>Audit Log</h2>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;margin-bottom:16px;">
        The audit log is an immutable, append-only record of every change in the system. Entries cannot be deleted or modified.
      </p>
      <ul style="font-size:0.875rem;line-height:1.8;padding-left:20px;">
        <li><strong>Filters:</strong> Search by keyword, filter by entity type, user, or date range</li>
        <li><strong>Inspect:</strong> Click any entry to view the full change payload (before/after JSON snapshots)</li>
        <li><strong>Tracked actions:</strong> Login/logout, product registration/updates, test assignments, measurement submissions, review decisions, CPRI approvals, user management, settings changes</li>
      </ul>
      <?php if ($isAuditor): ?>
      <div class="guide-warn" style="margin-top:16px;">
        <strong>Auditor access is read-only.</strong> You can view all data across the system but cannot create, edit, or delete any records. This is by design to ensure audit independence.
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Tips -->
    <section class="guide-section" id="tips">
      <h2>Tips & Shortcuts</h2>
      <table class="guide-table">
        <thead><tr><th>Shortcut</th><th>Action</th></tr></thead>
        <tbody>
          <tr><td><span class="kbd">Ctrl</span> + <span class="kbd">K</span></td><td>Open global search</td></tr>
          <tr><td><span class="kbd">Esc</span></td><td>Close any open modal or dropdown</td></tr>
          <tr><td>Click table headers</td><td>Sort columns ascending/descending</td></tr>
          <tr><td>Theme toggle (user menu)</td><td>Switch between light and dark mode</td></tr>
        </tbody>
      </table>

      <h3>Notifications</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        The bell icon in the top bar shows your unread notification count. Click to expand and view recent notifications. Click <strong>Mark as read</strong> on individual items to dismiss them. Notifications are generated when tests are assigned to you, reviews complete, products are registered, and other relevant events occur.
      </p>

      <h3>Global Search</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        Press <span class="kbd">Ctrl</span> + <span class="kbd">K</span> or click the search icon to search across products, testing records, and users by serial number, name, or model.
      </p>

      <h3>Need Help?</h3>
      <p style="font-size:0.875rem;color:var(--ink-soft);line-height:1.6;">
        If you encounter issues or need assistance, contact your system administrator or visit the <a href="<?= url('pages/contact.php') ?>" style="color:var(--accent);text-decoration:underline;">Contact IT</a> page.
      </p>
    </section>

  </div>
</div>

<script>
(function(){
  var links = document.querySelectorAll('.guide-nav a');
  var sections = document.querySelectorAll('.guide-section');
  function onScroll() {
    var scrollY = window.scrollY + 120;
    var current = '';
    sections.forEach(function(s) { if (s.offsetTop <= scrollY) current = s.id; });
    links.forEach(function(a) {
      a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();
})();
</script>

<?php if ($loggedIn): ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php else: ?>
</div><!-- /.guide-public-wrap -->
<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
<script>var BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
</body>
</html>
<?php endif; ?>
