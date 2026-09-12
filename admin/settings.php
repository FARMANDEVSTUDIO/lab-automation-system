<?php
declare(strict_types=1);
// Settings — general config, security policy, role matrix (Farman)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Settings';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => url('dashboard/index.php')],
    ['label' => 'Settings', 'url' => ''],
];
require_role(ROLE_ADMINISTRATOR);

$pdo = Database::getConnection();
$wsId = auth_workspace_id();

if (is_post()) {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_general') {
        $companyName = trim($_POST['company_name'] ?? '');
        $systemName = trim($_POST['system_name'] ?? '');
        $timezone = trim($_POST['timezone'] ?? '');
        $dateFormat = trim($_POST['date_format'] ?? '');
        $perPage = (int) ($_POST['records_per_page'] ?? 25);

        $err = null;
        if ($companyName !== '' && strlen($companyName) > 100) $err = 'Company name is too long.';
        if (!$err && $systemName !== '' && strlen($systemName) > 100) $err = 'System name is too long.';
        if (!$err && $timezone !== '' && !in_array($timezone, timezone_identifiers_list())) $err = 'Invalid timezone.';
        if (!$err && $dateFormat !== '' && !in_array($dateFormat, ['Y-m-d','d/m/Y','m/d/Y','d-M-Y','M j, Y'])) $err = 'Invalid date format.';
        if (!$err && ($perPage < 10 || $perPage > 100)) $err = 'Records per page must be between 10 and 100.';

        if ($err) {
            flash_error($err);
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }

        $fields = ['company_name' => $companyName, 'system_name' => $systemName, 'timezone' => $timezone, 'date_format' => $dateFormat, 'records_per_page' => (string) $perPage];
        foreach ($fields as $f => $val) {
            if ($val !== '') {
                $pdo->prepare('INSERT INTO settings (workspace_id, setting_key, setting_value) VALUES (:ws, :k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2')
                    ->execute(['ws' => $wsId, 'k' => $f, 'v' => $val, 'v2' => $val]);
            }
        }
        audit_log($pdo, auth_id(), 'settings.update', 'settings', 0, null, ['section' => 'general']);
        flash_success('General settings saved.');
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }

    if ($action === 'save_security') {
        $ranges = [
            'password_min_length' => [6, 32],
            'max_login_attempts' => [3, 10],
            'lockout_duration_minutes' => [5, 60],
            'session_timeout_minutes' => [15, 480],
        ];
        $err = null;
        foreach ($ranges as $f => $r) {
            $val = (int) ($_POST[$f] ?? 0);
            if ($val < $r[0] || $val > $r[1]) {
                $err = ucwords(str_replace('_', ' ', $f)) . " must be between {$r[0]} and {$r[1]}.";
                break;
            }
        }
        if ($err) {
            flash_error($err);
        } else {
            foreach (array_keys($ranges) as $f) {
                $val = trim($_POST[$f] ?? '');
                if ($val !== '') {
                    $pdo->prepare('INSERT INTO settings (workspace_id, setting_key, setting_value) VALUES (:ws, :k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2')
                        ->execute(['ws' => $wsId, 'k' => $f, 'v' => $val, 'v2' => $val]);
                }
            }
            audit_log($pdo, auth_id(), 'settings.update', 'settings', 0, null, ['section' => 'security']);
            flash_success('Security settings saved.');
        }
        header('Location: ' . $_SERVER['REQUEST_URI']); exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

$s = function(string $key, string $default = '') use ($pdo): string {
    return get_setting($pdo, $key, $default);
};
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="d-flex align-center gap-12 mb-4">
      <div style="width:40px;height:40px;border-radius:var(--radius-md);background:var(--accent-tint);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="var(--accent-strong)" stroke-width="1.5" stroke-linecap="round"><circle cx="10" cy="10" r="3"/><path d="M10 3v2M10 15v2M3 10h2M15 10h2M5.6 5.6l1.4 1.4M13 13l1.4 1.4M5.6 14.4l1.4-1.4M13 7l1.4-1.4"/></svg>
      </div>
      <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle" style="margin-top:2px;">System configuration · Administrator only</p>
      </div>
    </div>
  </div>
</div>

<!-- Settings Tabs -->
<div class="tabs" style="margin-bottom:24px;">
  <button class="tab active" data-tab="tab-general">General</button>
  <button class="tab" data-tab="tab-security">Security & Access</button>
</div>

<!-- General Tab -->
<div class="tab-panel active" id="tab-general">
  <div style="max-width:720px;display:flex;flex-direction:column;gap:24px;">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Organization</h3></div>
      <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_general">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Company Name <span class="required">*</span></label>
              <input type="text" class="form-control" name="company_name" value="<?= e($s('company_name', 'FZ Engineering')) ?>" required data-validate="required|minlength:2">
            </div>
            <div class="form-group">
              <label class="form-label">System Name <span class="required">*</span></label>
              <input type="text" class="form-control" name="system_name" value="<?= e($s('system_name', 'Lab Automation System')) ?>" required data-validate="required|minlength:2">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Timezone</label>
              <select class="form-control" name="timezone">
                <?php $tz = $s('timezone', 'UTC');
                $tzOpts = ['UTC' => 'UTC', 'Asia/Kolkata' => 'Asia/Kolkata (IST)', 'America/New_York' => 'America/New_York (EST)', 'Europe/London' => 'Europe/London (GMT)'];
                foreach ($tzOpts as $v => $label): ?>
                <option value="<?= e($v) ?>" <?= $tz === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Date Format</label>
              <select class="form-control" name="date_format">
                <?php $df = $s('date_format', 'Y-m-d');
                $dfOpts = ['Y-m-d' => date('Y-m-d') . ' (ISO)', 'd/m/Y' => date('d/m/Y'), 'm/d/Y' => date('m/d/Y'), 'M j, Y' => date('M j, Y')];
                foreach ($dfOpts as $v => $label): ?>
                <option value="<?= e($v) ?>" <?= $df === $v ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Records Per Page</label>
            <select class="form-control" style="width:140px;" name="records_per_page">
              <?php $rpp = $s('records_per_page', '25');
              foreach (['10','25','50','100'] as $v): ?>
              <option <?= $rpp === $v ? 'selected' : '' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;justify-content:flex-end;padding-top:12px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Security Tab -->
<div class="tab-panel" id="tab-security">
  <div style="max-width:720px;display:flex;flex-direction:column;gap:24px;">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Security Policy</h3></div>
      <form method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_security">
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Password Min Length</label>
              <input type="number" class="form-control" name="password_min_length" value="<?= e($s('password_min_length', '8')) ?>" min="6" max="32">
            </div>
            <div class="form-group">
              <label class="form-label">Max Login Attempts</label>
              <input type="number" class="form-control" name="max_login_attempts" value="<?= e($s('max_login_attempts', '5')) ?>" min="3" max="10">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Lockout Duration (minutes)</label>
              <input type="number" class="form-control" name="lockout_duration_minutes" value="<?= e($s('lockout_duration_minutes', '15')) ?>" min="5" max="60">
            </div>
            <div class="form-group">
              <label class="form-label">Session Timeout (minutes)</label>
              <input type="number" class="form-control" name="session_timeout_minutes" value="<?= e($s('session_timeout_minutes', '30')) ?>" min="15" max="480">
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;padding-top:12px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title">Role & Access Matrix</h3></div>
      <div class="card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="data-table" style="font-size:0.75rem;">
            <thead>
              <tr><th>Capability</th><th class="text-center">Admin</th><th class="text-center">Engineer</th><th class="text-center">Technician</th><th class="text-center">QM</th><th class="text-center">Auditor</th></tr>
            </thead>
            <tbody>
              <?php
              $perms = [
                ['Manage users & roles','●','—','—','—','—'],
                ['Configure departments','●','○','—','—','—'],
                ['Configure testing types','●','●','—','—','—'],
                ['Register products','●','●','—','—','—'],
                ['Assign testing','●','●','—','—','—'],
                ['Perform tests','●','—','●','—','—'],
                ['Review / recommend','●','●','—','—','—'],
                ['CPRI approval','●','—','—','●','—'],
                ['View reports','●','●','—','●','●'],
                ['Export reports','●','●','—','●','●'],
                ['View audit trail','●','●','—','●','●'],
                ['System settings','●','—','—','—','—'],
              ];
              foreach ($perms as $p): ?>
              <tr>
                <td style="font-weight:500;"><?= $p[0] ?></td>
                <?php for ($i = 1; $i <= 5; $i++):
                  $color = $p[$i] === '●' ? 'var(--success)' : 'var(--line)';
                ?>
                <td class="text-center"><span style="color:<?= $color ?>;"><?= $p[$i] ?></span></td>
                <?php endfor; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 24px;border-top:1px solid var(--line);display:flex;gap:16px;font-size:0.6875rem;color:var(--ink-faint);">
          <span><span style="color:var(--success);">●</span> Full access</span>
          <span><span style="color:var(--line);">—</span> No access</span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
