<?php
declare(strict_types=1);

/**
 * Authentication & session management module.
 * Handles login, registration, CSRF, role checks, and session lifecycle.
 *
 * @author Naveera
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function auth_check(): bool
{
    return isset($_SESSION['user']['id']);
}

function auth_id(): int
{
    return (int) ($_SESSION['user']['id'] ?? 0);
}

function auth_role(): string
{
    return $_SESSION['user']['role'] ?? '';
}

function auth_workspace_id(): int
{
    return (int) ($_SESSION['user']['workspace_id'] ?? 0);
}

function require_auth(): void
{
    if (!auth_check()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => auth_id()]);
    $status = $stmt->fetchColumn();

    if ($status !== 'active') {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'account_inactive', 'message' => 'Your account is inactive. Please contact your administrator.']);
            exit;
        }
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash']['error'] = 'Your account has been deactivated. Please contact your administrator.';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

function require_role(string ...$roles): void
{
    require_auth();
    if (!in_array(auth_role(), $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

function require_any_role(array $roles): void
{
    require_auth();
    if (!in_array(auth_role(), $roles, true)) {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

function can(string $permission): bool
{
    $role = auth_role();
    $permissions = [
        'administrator' => [
            'manage_users', 'manage_departments', 'manage_testing_types', 'manage_settings',
            'manage_testers',
            'register_product', 'edit_product', 'delete_product', 'assign_test',
            'view_all_tests', 'review_test', 'cpri_approve',
            'view_reports', 'export_reports', 'view_audit_log',
            'view_notifications',
        ],
        'testing_engineer' => [
            'register_product', 'edit_product', 'assign_test',
            'view_all_tests', 'review_test',
            'view_reports', 'export_reports', 'view_audit_log',
            'manage_testing_types', 'view_notifications',
        ],
        'lab_technician' => [
            'perform_test', 'record_measurements', 'submit_test',
            'view_own_tests', 'view_notifications',
        ],
        'quality_manager' => [
            'cpri_approve', 'cpri_reject',
            'view_all_tests', 'view_reports', 'export_reports',
            'view_audit_log', 'view_notifications',
        ],
        'auditor' => [
            'view_all_tests', 'view_reports', 'export_reports',
            'view_audit_log', 'view_notifications',
        ],
    ];

    $rolePerms = $permissions[$role] ?? [];
    return in_array($permission, $rolePerms, true);
}

function login_user(string $email, string $password): array
{
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'error' => 'Your account has been deactivated. Contact your administrator.'];
    }

    if ($user['locked_until'] && new DateTime($user['locked_until']) > new DateTime()) {
        $remaining = (new DateTime($user['locked_until']))->diff(new DateTime());
        return ['success' => false, 'error' => 'Account locked. Try again in ' . $remaining->i . ' minutes.'];
    }

    if (!secure_verify($password, $user['password_hash'])) {
        $attempts = $user['failed_login_attempts'] + 1;
        $lockUntil = null;

        $maxAttempts = get_setting($pdo, 'max_login_attempts', 5, (int) $user['workspace_id']);
        $lockoutMinutes = get_setting($pdo, 'lockout_duration_minutes', 15, (int) $user['workspace_id']);

        if ($attempts >= (int) $maxAttempts) {
            $lockUntil = (new DateTime())->modify("+{$lockoutMinutes} minutes")->format('Y-m-d H:i:s');
        }

        $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = :attempts, locked_until = :lock WHERE id = :id AND workspace_id = :ws');
        $stmt->execute(['attempts' => $attempts, 'lock' => $lockUntil, 'id' => $user['id'], 'ws' => $user['workspace_id']]);

        $remaining = (int) $maxAttempts - $attempts;
        $msg = 'Invalid email or password.';
        if ($remaining > 0 && $remaining <= 2) {
            $msg .= " {$remaining} attempt(s) remaining.";
        } elseif ($remaining <= 0) {
            $msg = "Account locked for {$lockoutMinutes} minutes due to too many failed attempts.";
        }

        return ['success' => false, 'error' => $msg];
    }

    $stmt = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id AND workspace_id = :ws');
    $stmt->execute(['id' => $user['id'], 'ws' => $user['workspace_id']]);

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'            => $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'role'          => $user['role'],
        'department_id' => $user['department_id'],
        'avatar'        => $user['avatar'],
        'workspace_id'  => $user['workspace_id'],
    ];
    $_SESSION['login_time'] = time();

    audit_log($pdo, (int) $user['id'], 'auth.login', 'user', (int) $user['id']);

    return ['success' => true, 'user' => $_SESSION['user']];
}

function logout_user(): void
{
    if (auth_check()) {
        try {
            $pdo = Database::getConnection();
            audit_log($pdo, auth_id(), 'auth.logout', 'user', auth_id());
        } catch (Exception $e) {
            // Don't block logout on audit failure
        }
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function get_setting(PDO $pdo, string $key, mixed $default = null, ?int $workspaceId = null): mixed
{
    $wsId = $workspaceId ?? auth_workspace_id();
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE workspace_id = :ws AND setting_key = :key LIMIT 1');
    $stmt->execute(['ws' => $wsId, 'key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

function audit_log(PDO $pdo, ?int $actorId, string $action, string $entityType, int $entityId, ?array $before = null, ?array $after = null, ?int $workspaceId = null): void
{
    $wsId = $workspaceId ?? auth_workspace_id();
    $stmt = $pdo->prepare('INSERT INTO audit_log (workspace_id, actor_id, action, entity_type, entity_id, before_json, after_json, ip_address, user_agent, created_at) VALUES (:ws, :actor, :action, :entity_type, :entity_id, :before, :after, :ip, :ua, NOW())');
    $stmt->execute([
        'ws'          => $wsId,
        'actor'       => $actorId,
        'action'      => $action,
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'before'      => $before ? json_encode($before) : null,
        'after'       => $after ? json_encode($after) : null,
        'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'ua'          => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
    ]);
}

function create_notification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', ?string $link = null, ?int $workspaceId = null): void
{
    $wsId = $workspaceId ?? auth_workspace_id();
    $stmt = $pdo->prepare('INSERT INTO notifications (workspace_id, user_id, title, message, type, link, created_at) VALUES (:ws, :uid, :title, :msg, :type, :link, NOW())');
    $stmt->execute([
        'ws'    => $wsId,
        'uid'   => $userId,
        'title' => $title,
        'msg'   => $message,
        'type'  => $type,
        'link'  => $link,
    ]);
}

function create_password_reset(PDO $pdo, int $userId, int $expiryMinutes = 60): string
{
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL')
        ->execute(['uid' => $userId]);

    $token = bin2hex(random_bytes(32));
    $mins = (int) $expiryMinutes;
    $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, DATE_ADD(NOW(), INTERVAL {$mins} MINUTE))")
        ->execute(['uid' => $userId, 'token' => $token]);
    return $token;
}

function verify_password_reset(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare('SELECT pr.*, u.name, u.email, u.workspace_id FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = :token AND pr.used_at IS NULL AND pr.expires_at > NOW() LIMIT 1');
    $stmt->execute(['token' => $token]);
    return $stmt->fetch() ?: null;
}

function consume_password_reset(PDO $pdo, string $token): void
{
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE token = :token')
        ->execute(['token' => $token]);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_token'] ?? '';
    return hash_equals(csrf_token(), $token);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        if (function_exists('flash_error')) {
            flash_error('Session expired. Please try again.');
        }
        $back = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $back);
        exit;
    }
}
