<?php
declare(strict_types=1);

/**
 * Permission matrix — role-based access control enforcement. (Farman)
 * Every capability check in the app routes through has_permission().
 */

const PERM_MANAGE_USERS           = 'manage_users';
const PERM_CONFIGURE_DEPARTMENTS  = 'configure_departments';
const PERM_CONFIGURE_TESTING      = 'configure_testing';
const PERM_REGISTER_PRODUCTS      = 'register_products';
const PERM_ASSIGN_TESTING         = 'assign_testing';
const PERM_PERFORM_TESTS          = 'perform_tests';
const PERM_EDIT_MEASUREMENTS      = 'edit_measurements';
const PERM_REVIEW_RECOMMEND       = 'review_recommend';
const PERM_CPRI_APPROVAL          = 'cpri_approval';
const PERM_REJECT_REROUTE         = 'reject_reroute';
const PERM_VIEW_REPORTS           = 'view_reports';
const PERM_EXPORT_REPORTS         = 'export_reports';
const PERM_VIEW_AUDIT             = 'view_audit';
const PERM_SYSTEM_SETTINGS        = 'system_settings';

const PERMISSION_MATRIX = [
    ROLE_ADMINISTRATOR => [
        PERM_MANAGE_USERS          => 'full',
        PERM_CONFIGURE_DEPARTMENTS => 'full',
        PERM_CONFIGURE_TESTING     => 'full',
        PERM_REGISTER_PRODUCTS     => 'full',
        PERM_ASSIGN_TESTING        => 'full',
        PERM_PERFORM_TESTS         => 'override',
        PERM_EDIT_MEASUREMENTS     => 'reason_required',
        PERM_REVIEW_RECOMMEND      => 'full',
        PERM_CPRI_APPROVAL         => 'override',
        PERM_REJECT_REROUTE        => 'full',
        PERM_VIEW_REPORTS          => 'full',
        PERM_EXPORT_REPORTS        => 'full',
        PERM_VIEW_AUDIT            => 'full',
        PERM_SYSTEM_SETTINGS       => 'full',
    ],
    ROLE_TESTING_ENGINEER => [
        PERM_MANAGE_USERS          => false,
        PERM_CONFIGURE_DEPARTMENTS => 'view',
        PERM_CONFIGURE_TESTING     => 'full',
        PERM_REGISTER_PRODUCTS     => 'full',
        PERM_ASSIGN_TESTING        => 'full',
        PERM_PERFORM_TESTS         => 'view',
        PERM_EDIT_MEASUREMENTS     => 'pre_review',
        PERM_REVIEW_RECOMMEND      => 'full',
        PERM_CPRI_APPROVAL         => false,
        PERM_REJECT_REROUTE        => 'initiate',
        PERM_VIEW_REPORTS          => 'full',
        PERM_EXPORT_REPORTS        => 'full',
        PERM_VIEW_AUDIT            => 'own',
        PERM_SYSTEM_SETTINGS       => false,
    ],
    ROLE_LAB_TECHNICIAN => [
        PERM_MANAGE_USERS          => false,
        PERM_CONFIGURE_DEPARTMENTS => false,
        PERM_CONFIGURE_TESTING     => 'view',
        PERM_REGISTER_PRODUCTS     => false,
        PERM_ASSIGN_TESTING        => false,
        PERM_PERFORM_TESTS         => 'assigned',
        PERM_EDIT_MEASUREMENTS     => false,
        PERM_REVIEW_RECOMMEND      => false,
        PERM_CPRI_APPROVAL         => false,
        PERM_REJECT_REROUTE        => false,
        PERM_VIEW_REPORTS          => 'own_dept',
        PERM_EXPORT_REPORTS        => false,
        PERM_VIEW_AUDIT            => 'own',
        PERM_SYSTEM_SETTINGS       => false,
    ],
    ROLE_QUALITY_MANAGER => [
        PERM_MANAGE_USERS          => false,
        PERM_CONFIGURE_DEPARTMENTS => 'view',
        PERM_CONFIGURE_TESTING     => 'view',
        PERM_REGISTER_PRODUCTS     => 'view',
        PERM_ASSIGN_TESTING        => 'view',
        PERM_PERFORM_TESTS         => 'view',
        PERM_EDIT_MEASUREMENTS     => false,
        PERM_REVIEW_RECOMMEND      => 'view',
        PERM_CPRI_APPROVAL         => 'full',
        PERM_REJECT_REROUTE        => 'final',
        PERM_VIEW_REPORTS          => 'full',
        PERM_EXPORT_REPORTS        => 'full',
        PERM_VIEW_AUDIT            => 'full',
        PERM_SYSTEM_SETTINGS       => false,
    ],
    ROLE_AUDITOR => [
        PERM_MANAGE_USERS          => 'view',
        PERM_CONFIGURE_DEPARTMENTS => 'view',
        PERM_CONFIGURE_TESTING     => 'view',
        PERM_REGISTER_PRODUCTS     => 'view',
        PERM_ASSIGN_TESTING        => 'view',
        PERM_PERFORM_TESTS         => 'view',
        PERM_EDIT_MEASUREMENTS     => 'view',
        PERM_REVIEW_RECOMMEND      => 'view',
        PERM_CPRI_APPROVAL         => 'view',
        PERM_REJECT_REROUTE        => 'view',
        PERM_VIEW_REPORTS          => 'full',
        PERM_EXPORT_REPORTS        => 'full',
        PERM_VIEW_AUDIT            => 'full',
        PERM_SYSTEM_SETTINGS       => false,
    ],
];

function has_permission(string $capability, ?string $role = null): string|false
{
    $role = $role ?? current_role();
    if ($role === null) return false;
    return PERMISSION_MATRIX[$role][$capability] ?? false;
}

function can(string $capability, ?string $role = null): bool
{
    $perm = has_permission($capability, $role);
    return $perm !== false && $perm !== 'view';
}

function can_view(string $capability, ?string $role = null): bool
{
    $perm = has_permission($capability, $role);
    return $perm !== false;
}

function require_permission(string $capability): void
{
    if (!can($capability)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en" data-theme="dark"><head><meta charset="UTF-8">'
           . '<title>Access denied · ' . htmlspecialchars(APP_NAME) . '</title>'
           . '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css"></head>'
           . '<body class="app-public"><div style="display:flex;align-items:center;justify-content:center;height:100vh;">'
           . '<div style="text-align:center"><h1 style="font-size:1.25rem;">403 — Access denied</h1>'
           . '<p class="text-soft" style="margin:8px 0 20px;">You don\'t have permission for this action.</p>'
           . '<a class="btn btn-primary" href="' . BASE_URL . '/dashboard/index.php">Back to Dashboard</a>'
           . '</div></div></body></html>';
        exit;
    }
}

function validate_password(string $password): array
{
    $errors = [];
    if (strlen($password) < 8)              $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password))   $errors[] = 'Password must contain an uppercase letter.';
    if (!preg_match('/[0-9]/', $password))   $errors[] = 'Password must contain a number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain a special character.';
    return $errors;
}

function check_session_timeout(): void
{
    if (empty($_SESSION['user']['id'])) return;

    $timeout = 30 * 60;
    $lastActivity = $_SESSION['last_activity'] ?? time();

    if (time() - $lastActivity > $timeout) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        $_SESSION['flash']['error'] = 'Your session has expired. Please sign in again.';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    $_SESSION['last_activity'] = time();
}
