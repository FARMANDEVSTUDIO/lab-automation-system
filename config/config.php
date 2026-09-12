<?php
declare(strict_types=1);

/**
 * ============================================================================
 * Lab Automation System
 * A web-based laboratory testing and quality management platform
 * ============================================================================
 *
 * Project  : Lab Automation System (Coaching Project)
 * Stack    : PHP 8.x / MySQL / Apache (XAMPP)
 *
 * Team:
 *   Farman         — Team Lead, Architecture & Backend
 *   Arsalan        — Frontend UI, CSS Design System & Reports
 *   Naveera        — Database, Testing Workflow & Auth Module
 *
 * Application bootstrap: environment, constants, and secure session policy.
 * Must be required before any session_start() call in the app.
 * ============================================================================
 */

// Buffer all output from the very first file loaded by every entry point,
// so a header()/redirect anywhere later in the request can never fail with
// "headers already sent" — even if something upstream of includes/header.php
// (which used to be the earliest ob_start() call) emits stray output.
if (!ob_get_level()) {
    ob_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');

// ---- Load .env file (if present) ------------------------------------------
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        $_ENV[$key] = $val;
    }
}
function env(string $key, string $default = ''): string {
    return $_ENV[$key] ?? (getenv($key) ?: $default);
}

// ---- Application constants ---------------------------------------------

define('APP_NAME', 'Lab Automation System');
define('APP_VERSION', '0.1.0');

// BASE_URL — /lab-automation on localhost, empty on production hosting.
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isLocal = in_array($serverName, ['localhost', '127.0.0.1'], true);
define('BASE_URL', $isLocal ? '/lab-automation' : '');

// APP_URL is the full public URL (scheme + host + BASE_URL).
// Set APP_URL in .env for production; auto-detected from server vars otherwise.
$appUrl = env('APP_URL');
if (!$appUrl) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $port = $_SERVER['SERVER_PORT'] ?? '';
    $portSuffix = ($port && $port !== '80' && $port !== '443') ? ':' . $port : '';
    $appUrl = $scheme . '://' . $host . $portSuffix . BASE_URL;
}
define('APP_URL', $appUrl);

// ---- Secure session configuration ---------------------------------------
// Must be set before session_start() is called (see includes/header.php).

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', (string) (60 * 60 * 8)); // 8 hour idle timeout

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_name('lab_automation_session');

// ---- Role constants -------------------------------------------------------
// Mirrors the five roles defined in the PRD (§04).

const ROLE_ADMINISTRATOR   = 'administrator';
const ROLE_TESTING_ENGINEER = 'testing_engineer';
const ROLE_LAB_TECHNICIAN  = 'lab_technician';
const ROLE_QUALITY_MANAGER = 'quality_manager';
const ROLE_AUDITOR         = 'auditor';
