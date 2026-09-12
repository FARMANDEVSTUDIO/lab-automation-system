<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== LAB AUTOMATION EMAIL DIAGNOSTICS ===\n\n";

echo "1. ENV CHECK\n";
echo "   SMTP_HOST: " . (SMTP_HOST ?: '(empty)') . "\n";
echo "   SMTP_PORT: " . SMTP_PORT . "\n";
echo "   SMTP_ENCRYPTION: " . (SMTP_ENCRYPTION ?: '(empty)') . "\n";
echo "   SMTP_USERNAME: " . (SMTP_USERNAME ? substr(SMTP_USERNAME, 0, 6) . '***' : '(empty)') . "\n";
echo "   SMTP_PASSWORD: " . (SMTP_PASSWORD ? str_repeat('*', 8) . ' (' . strlen(SMTP_PASSWORD) . ' chars)' : '(empty)') . "\n";
echo "   MAIL_FROM: " . MAIL_FROM_ADDRESS . "\n";
echo "   MAIL_DEV_MODE: " . (MAIL_DEV_MODE ? 'YES (no SMTP host!)' : 'No') . "\n";
echo "   APP_URL: " . APP_URL . "\n\n";

echo "2. NETWORK TEST - Port 587 (TLS)\n";
$ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
$s587 = @stream_socket_client('smtp.gmail.com:587', $e1, $es1, 10, STREAM_CLIENT_CONNECT, $ctx);
if ($s587) {
    $g = @fgets($s587, 512);
    echo "   CONNECTED! Greeting: " . trim($g) . "\n";
    fclose($s587);
} else {
    echo "   FAILED: {$es1} (errno {$e1})\n";
}

echo "\n3. NETWORK TEST - Port 465 (SSL)\n";
$s465 = @stream_socket_client('ssl://smtp.gmail.com:465', $e2, $es2, 10, STREAM_CLIENT_CONNECT, $ctx);
if ($s465) {
    $g = @fgets($s465, 512);
    echo "   CONNECTED! Greeting: " . trim($g) . "\n";
    fclose($s465);
} else {
    echo "   FAILED: {$es2} (errno {$e2})\n";
}

echo "\n4. PHP mail() TEST\n";
$mailTest = @mail(
    SMTP_USERNAME ?: 'test@test.com',
    'Test from Lab Automation',
    'This is a test email from the diagnostic script.',
    "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\nContent-Type: text/plain; charset=UTF-8\r\n"
);
echo "   mail() returned: " . ($mailTest ? 'TRUE (accepted for delivery)' : 'FALSE (failed)') . "\n";

echo "\n5. PHP FUNCTIONS CHECK\n";
$disabled = ini_get('disable_functions');
echo "   stream_socket_client: " . (function_exists('stream_socket_client') ? 'OK' : 'DISABLED') . "\n";
echo "   mail: " . (function_exists('mail') && strpos($disabled, 'mail') === false ? 'OK' : 'DISABLED') . "\n";
echo "   fsockopen: " . (function_exists('fsockopen') && strpos($disabled, 'fsockopen') === false ? 'OK' : 'DISABLED') . "\n";

echo "\n6. DISABLED FUNCTIONS\n";
$df = $disabled ?: '(none)';
echo "   " . wordwrap($df, 80, "\n   ") . "\n";

echo "\n=== DONE ===\n";
