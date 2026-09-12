<?php
declare(strict_types=1);

/**
 * Shared helper functions — escaping, CSRF, flash messages, pagination,
 * URL helpers, asset versioning, audit logging.
 *
 * @author Farman
 */

require_once __DIR__ . '/../config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset(string $path): string
{
    $filePath = __DIR__ . '/../assets/' . ltrim($path, '/');
    $version = file_exists($filePath) ? filemtime($filePath) : '';
    return BASE_URL . '/assets/' . ltrim($path, '/') . ($version ? '?v=' . $version : '');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function input(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function flash_success(string $message): void
{
    flash('success', $message);
}

function flash_error(string $message): void
{
    flash('error', $message);
}

function time_ago(string $datetime): string
{
    $now = new DateTimeImmutable();
    $then = new DateTimeImmutable($datetime);
    $diff = $now->getTimestamp() - $then->getTimestamp();

    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return $then->format('M j, Y');
}

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if ($date === null || $date === '') return '—';
    return (new DateTimeImmutable($date))->format($format);
}

function format_datetime(?string $datetime): string
{
    if ($datetime === null || $datetime === '') return '—';
    return (new DateTimeImmutable($datetime))->format('M j, Y · H:i');
}

function status_badge(string $status): string
{
    $map = [
        'registered'             => ['label' => 'Registered',       'class' => 'badge-info'],
        'assigned'               => ['label' => 'Assigned',         'class' => 'badge-info'],
        'in_testing'             => ['label' => 'In Testing',       'class' => 'badge-warning'],
        'submitted_for_review'   => ['label' => 'Under Review',     'class' => 'badge-warning'],
        'passed'                 => ['label' => 'Passed',           'class' => 'badge-success'],
        'failed'                 => ['label' => 'Failed',           'class' => 'badge-fail'],
        'failed_pending_rework'  => ['label' => 'Rework',           'class' => 'badge-fail'],
        'pending_cpri_approval'  => ['label' => 'Pending CPRI',     'class' => 'badge-warning'],
        'approved'               => ['label' => 'Approved',         'class' => 'badge-success'],
        'released'               => ['label' => 'Released',         'class' => 'badge-success'],
        'on_hold'                => ['label' => 'On Hold',          'class' => 'badge-hold'],
        'rejected'               => ['label' => 'Rejected',         'class' => 'badge-fail'],
        'cancelled'              => ['label' => 'Cancelled',        'class' => 'badge-hold'],
        'active'                 => ['label' => 'Active',           'class' => 'badge-success'],
        'inactive'               => ['label' => 'Inactive',         'class' => 'badge-hold'],
        'archived'               => ['label' => 'Archived',         'class' => 'badge-hold'],
        'pass'                   => ['label' => 'Pass',             'class' => 'badge-success'],
        'fail'                   => ['label' => 'Fail',             'class' => 'badge-fail'],
    ];

    $entry = $map[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'badge-hold'];
    return '<span class="status-badge ' . $entry['class'] . '"><span class="status-dot"></span>' . e($entry['label']) . '</span>';
}

function status_label(string $status): string
{
    $map = [
        'registered' => 'Registered', 'assigned' => 'Assigned', 'in_testing' => 'In Testing',
        'submitted_for_review' => 'Under Review', 'passed' => 'Passed', 'failed' => 'Failed',
        'failed_pending_rework' => 'Rework', 'pending_cpri_approval' => 'Pending CPRI',
        'approved' => 'Approved', 'released' => 'Released', 'on_hold' => 'On Hold',
        'rejected' => 'Rejected', 'cancelled' => 'Cancelled',
    ];
    return $map[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function role_label(string $role): string
{
    $map = [
        'administrator'    => 'Administrator',
        'testing_engineer' => 'Testing Engineer',
        'lab_technician'   => 'Lab Technician',
        'quality_manager'  => 'Quality Manager',
        'auditor'          => 'Auditor',
    ];
    return $map[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function user_initials(?string $name): string
{
    if ($name === null || $name === '') return '?';
    $parts = explode(' ', trim($name));
    if (count($parts) === 1) return strtoupper(substr($parts[0], 0, 2));
    return strtoupper($parts[0][0] . end($parts)[0]);
}

function paginate(PDO $pdo, string $countSql, string $dataSql, array $params, int $page = 1, int $perPage = 25): array
{
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    $dataStmt = $pdo->prepare($dataSql . " LIMIT {$perPage} OFFSET {$offset}");
    $dataStmt->execute($params);
    $rows = $dataStmt->fetchAll();

    return [
        'data'        => $rows,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
    ];
}

function pagination_html(int $page, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) return '';

    $sep = (str_contains($baseUrl, '?') && strlen($baseUrl) > 1 && $baseUrl !== '?') ? '&' : (str_contains($baseUrl, '?') ? '' : '?');
    $link = fn(int $p) => $baseUrl . $sep . 'page=' . $p;

    $html = '<nav class="pagination-wrap" aria-label="Page navigation"><ul class="pagination">';

    $html .= '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $link($page - 1) . '">&laquo;</a></li>';

    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $link(1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $link($i) . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $link($totalPages) . '">' . $totalPages . '</a></li>';
    }

    $html .= '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $link($page + 1) . '">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

function unread_notification_count(PDO $pdo, int $userId): int
{
    $wsId = auth_workspace_id();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE workspace_id = :ws AND user_id = :uid AND is_read = 0');
    $stmt->execute(['ws' => $wsId, 'uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

function validate_human_name(string $name): ?string
{
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') return 'Name is required.';
    if (strlen($name) < 2) return 'Name must be at least 2 characters.';
    if (strlen($name) > 120) return 'Name must not exceed 120 characters.';
    if (preg_match('/^\d+$/', $name)) return 'Name cannot be numbers only.';
    if (preg_match('/^[^A-Za-z]+$/', $name)) return 'Name must contain at least one letter.';
    if (!preg_match('/^[A-Za-z\s.\-\']+$/', $name)) return 'Name may only contain letters, spaces, hyphens, dots, and apostrophes.';
    return null;
}

function validate_password(string $password): ?string
{
    if (strlen($password) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) return 'Password must include at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) return 'Password must include at least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) return 'Password must include at least one number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return 'Password must include at least one special character.';
    return null;
}

function secure_hash(string $password): string
{
    $pepper = env('PASSWORD_PEPPER', '');
    $peppered = hash_hmac('sha256', $password, $pepper);
    return password_hash($peppered, PASSWORD_BCRYPT, ['cost' => 12]);
}

function secure_verify(string $password, string $hash): bool
{
    $pepper = env('PASSWORD_PEPPER', '');
    $peppered = hash_hmac('sha256', $password, $pepper);
    if (password_verify($peppered, $hash)) {
        return true;
    }
    return password_verify($password, $hash);
}

function generate_invitation_token(): string
{
    return bin2hex(random_bytes(32));
}

function create_invitation(PDO $pdo, int $userId, int $createdBy, int $expiryHours = 72): string
{
    $wsId = auth_workspace_id();

    $pdo->prepare('UPDATE invitation_tokens SET used_at = NOW() WHERE user_id = :uid AND workspace_id = :ws AND used_at IS NULL')
        ->execute(['uid' => $userId, 'ws' => $wsId]);

    $token = generate_invitation_token();
    $interval = (int) $expiryHours;
    $stmt = $pdo->prepare("INSERT INTO invitation_tokens (workspace_id, user_id, token, expires_at, created_by) VALUES (:ws, :uid, :token, DATE_ADD(NOW(), INTERVAL {$interval} HOUR), :created_by)");
    $stmt->execute(['ws' => $wsId, 'uid' => $userId, 'token' => $token, 'created_by' => $createdBy]);
    return $token;
}

function verify_invitation_token(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare('SELECT it.*, u.name, u.email, u.role FROM invitation_tokens it JOIN users u ON it.user_id = u.id WHERE it.token = :token AND it.used_at IS NULL AND it.expires_at > NOW() LIMIT 1');
    $stmt->execute(['token' => $token]);
    return $stmt->fetch() ?: null;
}

function consume_invitation_token(PDO $pdo, string $token, ?int $workspaceId = null): void
{
    $stmt = $pdo->prepare('SELECT user_id, workspace_id FROM invitation_tokens WHERE token = :token LIMIT 1');
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();

    if ($row) {
        $pdo->prepare('UPDATE invitation_tokens SET used_at = NOW() WHERE user_id = :uid AND workspace_id = :ws AND used_at IS NULL')
            ->execute(['uid' => $row['user_id'], 'ws' => $row['workspace_id']]);
    } else {
        $pdo->prepare('UPDATE invitation_tokens SET used_at = NOW() WHERE token = :token')->execute(['token' => $token]);
    }
}
