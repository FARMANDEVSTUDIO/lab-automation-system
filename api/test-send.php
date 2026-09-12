<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/mail.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "Sending test email via SMTP to " . SMTP_USERNAME . "...\n";

$result = smtp_send_raw(
    SMTP_USERNAME,
    'Test Email - Lab Automation ' . date('H:i:s'),
    '<h2>Test Email</h2><p>If you see this, SMTP is working! Sent at ' . date('Y-m-d H:i:s') . '</p>'
);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
