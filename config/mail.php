<?php
declare(strict_types=1);

/**
 * SMTP / Email delivery configuration. (Naveera)
 *
 * Override via environment variables or edit the defaults below.
 * Never commit real credentials — use env vars in production.
 */

define('SMTP_HOST',       env('SMTP_HOST'));
define('SMTP_PORT',       (int) (env('SMTP_PORT', '587')));
define('SMTP_USERNAME',   env('SMTP_USERNAME'));
define('SMTP_PASSWORD',   env('SMTP_PASSWORD'));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@labauto.local'));
define('MAIL_FROM_NAME',    env('MAIL_FROM_NAME', APP_NAME));

// Backup Gmail SMTP (failover when primary Gmail is blocked/rate-limited)
define('SMTP_HOST_BACKUP',       env('SMTP_HOST_BACKUP', ''));
define('SMTP_PORT_BACKUP',       (int) (env('SMTP_PORT_BACKUP', '587')));
define('SMTP_USERNAME_BACKUP',   env('SMTP_USERNAME_BACKUP', ''));
define('SMTP_PASSWORD_BACKUP',   env('SMTP_PASSWORD_BACKUP', ''));
define('SMTP_ENCRYPTION_BACKUP', env('SMTP_ENCRYPTION_BACKUP', 'tls'));

// When SMTP_HOST is empty, emails are written to /logs/ for local testing
define('MAIL_DEV_MODE', SMTP_HOST === '');
