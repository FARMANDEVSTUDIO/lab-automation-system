<?php
declare(strict_types=1);

/**
 * Session-based auth helpers shared by every protected page. (Naveera)
 * Credential verification and session creation live in auth/login.php (Phase 3);
 * these helpers only read the session that login.php establishes.
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/dashboard/index.php';
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

/**
 * @param string|array $roles One role slug, or an array of allowed role slugs.
 */
function require_role(string|array $roles): void
{
    $roles = is_array($roles) ? $roles : [$roles];
    $user  = current_user();

    if ($user === null || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en" data-theme="dark"><head><meta charset="UTF-8">'
           . '<title>Access denied · ' . htmlspecialchars(APP_NAME) . '</title>'
           . '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/bootstrap.min.css">'
           . '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css"></head>'
           . '<body class="app-public"><div class="d-flex align-items-center justify-content-center vh-100">'
           . '<div class="text-center"><h1 class="h3">403 — Access denied</h1>'
           . '<p class="text-muted">Your role does not have permission to view this page.</p>'
           . '<a class="btn btn-primary" href="' . BASE_URL . '/dashboard.php">Back to Dashboard</a>'
           . '</div></div></body></html>';
        exit;
    }
}

function current_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function is_role(string $role): bool
{
    return current_role() === $role;
}
