<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/mail.php';

header('Content-Type: text/plain; charset=UTF-8');

$apiKey = env('BREVO_API_KEY', '');
echo "Brevo API Key: " . ($apiKey ? substr($apiKey, 0, 12) . '***' : '(not set)') . "\n\n";

echo "Sending test email via Brevo HTTP API to fzlab.automation@gmail.com...\n";
$result = mail_brevo_api(
    'fzlab.automation@gmail.com',
    'Brevo Test - Lab Automation ' . date('H:i:s'),
    '<h2>Brevo API Test</h2><p>If you received this, Brevo HTTP API is working! Sent at ' . date('Y-m-d H:i:s') . '</p>'
);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
