<?php
declare(strict_types=1);
// Email delivery — SMTP send, OTP emails, invitation emails (Naveera)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mail.php';

// ---------------------------------------------------------------------------
// Lightweight SMTP client — supports STARTTLS (587) and direct SSL (465)
// ---------------------------------------------------------------------------

function smtp_send_raw(string $to, string $subject, string $htmlBody, ?int $overridePort = null, ?string $overrideEncryption = null, ?array $config = null): array
{
    if (MAIL_DEV_MODE) {
        return mail_log_to_file($to, $subject, $htmlBody);
    }

    $host       = $config['host']         ?? SMTP_HOST;
    $port       = $overridePort           ?? ($config['port']       ?? SMTP_PORT);
    $encryption = $overrideEncryption     ?? ($config['encryption'] ?? SMTP_ENCRYPTION);
    $username   = $config['username']     ?? SMTP_USERNAME;
    $password   = $config['password']     ?? SMTP_PASSWORD;
    $fromAddr   = $config['from_address'] ?? MAIL_FROM_ADDRESS;
    $fromName   = $config['from_name']    ?? MAIL_FROM_NAME;
    $label      = $config['label']        ?? 'primary';

    $prefix = ($encryption === 'ssl') ? 'ssl://' : '';
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ]);

    $socket = @stream_socket_client(
        $prefix . $host . ':' . $port,
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT,
        $ctx
    );

    if (!$socket) {
        error_log("[Mail] SMTP [{$label}] connect failed ({$port}/{$encryption}): {$errstr} ({$errno})");
        return ['success' => false, 'error' => "Could not connect to mail server on port {$port}."];
    }

    stream_set_timeout($socket, 30);

    $greeting = smtp_read($socket);
    if (!str_starts_with($greeting, '220')) {
        fclose($socket);
        return ['success' => false, 'error' => 'Mail server rejected connection.'];
    }

    $serverName = gethostname() ?: 'localhost';

    if (!smtp_command($socket, "EHLO {$serverName}", 250)) {
        fclose($socket);
        return ['success' => false, 'error' => 'EHLO failed.'];
    }

    if ($encryption === 'tls') {
        if (!smtp_command($socket, 'STARTTLS', 220)) {
            fclose($socket);
            return ['success' => false, 'error' => 'STARTTLS not supported by server.'];
        }
        $tlsOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
        if (!$tlsOk) {
            fclose($socket);
            return ['success' => false, 'error' => 'TLS negotiation failed.'];
        }
        if (!smtp_command($socket, "EHLO {$serverName}", 250)) {
            fclose($socket);
            return ['success' => false, 'error' => 'EHLO after STARTTLS failed.'];
        }
    }

    if ($username !== '') {
        if (!smtp_command($socket, 'AUTH LOGIN', 334)) {
            fclose($socket);
            return ['success' => false, 'error' => 'AUTH LOGIN not accepted.'];
        }
        if (!smtp_command($socket, base64_encode($username), 334)) {
            fclose($socket);
            return ['success' => false, 'error' => 'SMTP username rejected.'];
        }
        if (!smtp_command($socket, base64_encode($password), 235)) {
            fclose($socket);
            return ['success' => false, 'error' => 'SMTP authentication failed. Check credentials.'];
        }
    }

    if (!smtp_command($socket, 'MAIL FROM:<' . $fromAddr . '>', 250)) {
        fclose($socket);
        return ['success' => false, 'error' => 'Sender rejected.'];
    }

    if (!smtp_command($socket, "RCPT TO:<{$to}>", 250)) {
        fclose($socket);
        return ['success' => false, 'error' => 'Recipient rejected.'];
    }

    if (!smtp_command($socket, 'DATA', 354)) {
        fclose($socket);
        return ['success' => false, 'error' => 'DATA command rejected.'];
    }

    $messageId = '<' . bin2hex(random_bytes(16)) . '@' . (gethostname() ?: 'labauto.local') . '>';
    $headers  = "From: {$fromName} <{$fromAddr}>\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "Message-ID: {$messageId}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";
    $headers .= "X-Mailer: LabAutomation/1.0\r\n";
    $headers .= "\r\n";
    $headers .= chunk_split(base64_encode($htmlBody));
    $headers .= "\r\n.\r\n";

    fwrite($socket, $headers);
    $dataResp = smtp_read($socket);
    if (!str_starts_with($dataResp, '250')) {
        fclose($socket);
        return ['success' => false, 'error' => 'Message delivery failed.'];
    }

    smtp_command($socket, 'QUIT', 221);
    fclose($socket);

    return ['success' => true, 'method' => "smtp_{$label}"];
}

function smtp_command($socket, string $cmd, int $expectedCode): bool
{
    fwrite($socket, $cmd . "\r\n");
    $response = smtp_read($socket);
    return str_starts_with($response, (string) $expectedCode);
}

function smtp_read($socket): string
{
    $response = '';
    while ($line = @fgets($socket, 1024)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function mail_log_to_file(string $to, string $subject, string $htmlBody): array
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/mail_' . date('Y-m-d') . '.log';
    $entry  = str_repeat('=', 60) . "\n";
    $entry .= "DATE:    " . date('Y-m-d H:i:s') . "\n";
    $entry .= "TO:      {$to}\n";
    $entry .= "SUBJECT: {$subject}\n";
    $entry .= str_repeat('-', 60) . "\n";
    $entry .= strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)) . "\n";
    $entry .= str_repeat('=', 60) . "\n\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    error_log("[Mail-Dev] Email to {$to} logged to {$logFile}");
    return ['success' => false, 'error' => 'Email delivery is not configured. Email logged to file for development.', 'logged' => true, 'log_file' => $logFile];
}

// ---------------------------------------------------------------------------
// Brevo (Sendinblue) HTTP API — works on hosts that block SMTP
// ---------------------------------------------------------------------------

function mail_brevo_api(string $to, string $subject, string $htmlBody): array
{
    $apiKey = env('BREVO_API_KEY', '');
    if ($apiKey === '') {
        return ['success' => false, 'error' => 'Brevo API key not configured.'];
    }

    $payload = json_encode([
        'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM_ADDRESS],
        'to'          => [['email' => $to]],
        'replyTo'     => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM_ADDRESS],
        'subject'     => $subject,
        'htmlContent' => $htmlBody,
        'headers'     => ['X-Mailin-Tag' => 'transactional'],
    ]);

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("[Mail] Brevo cURL error: {$curlErr}");
            return ['success' => false, 'error' => "Brevo connection failed: {$curlErr}"];
        }
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'method' => 'brevo'];
        }
        error_log("[Mail] Brevo API {$httpCode}: {$response}");
        return ['success' => false, 'error' => "Brevo API error ({$httpCode})"];
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\napi-key: {$apiKey}\r\nAccept: application/json\r\n",
            'content' => $payload,
            'timeout' => 15,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $response = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $ctx);
    if ($response !== false) {
        return ['success' => true, 'method' => 'brevo'];
    }

    error_log("[Mail] Brevo file_get_contents failed");
    return ['success' => false, 'error' => 'Brevo API request failed.'];
}

// ---------------------------------------------------------------------------
// High-level email senders
// ---------------------------------------------------------------------------

function wrap_email_footer(string $htmlBody): string
{
    $footer = '<div style="text-align:center;margin-top:20px;padding:12px;font-size:11px;color:#9ca3af;line-height:1.5;">'
        . APP_NAME . ' &mdash; Automated laboratory testing &amp; quality management.<br>'
        . 'This is a transactional email sent because of an action on your account.'
        . '</div>';
    return str_replace('</body>', $footer . '</body>', $htmlBody);
}

function send_mail(string $to, string $subject, string $htmlBody): array
{
    $htmlBody = wrap_email_footer($htmlBody);

    if (MAIL_DEV_MODE) {
        return mail_log_to_file($to, $subject, $htmlBody);
    }

    // 1. Brevo HTTP API (bypasses SMTP blocking on shared hosts)
    $result = mail_brevo_api($to, $subject, $htmlBody);
    if ($result['success']) return $result;
    if (env('BREVO_API_KEY') !== '') {
        error_log("[Mail] Brevo failed: {$result['error']}");
    }

    // 2. Gmail 1 — Primary SMTP (587/TLS)
    $result = smtp_send_raw($to, $subject, $htmlBody, null, null, ['label' => 'gmail1']);
    if ($result['success']) return $result;
    error_log("[Mail] Gmail 1 (587/TLS) failed: {$result['error']}");

    // 3. Gmail 1 — Primary SMTP (465/SSL)
    if (SMTP_HOST !== '' && SMTP_PORT !== 465) {
        $result = smtp_send_raw($to, $subject, $htmlBody, 465, 'ssl', ['label' => 'gmail1-ssl']);
        if ($result['success']) return $result;
        error_log("[Mail] Gmail 1 (465/SSL) failed: {$result['error']}");
    }

    // 4. Gmail 2 — Backup SMTP (587/TLS)
    if (SMTP_USERNAME_BACKUP !== '') {
        $backupConfig = [
            'host'         => SMTP_HOST_BACKUP,
            'port'         => SMTP_PORT_BACKUP,
            'username'     => SMTP_USERNAME_BACKUP,
            'password'     => SMTP_PASSWORD_BACKUP,
            'encryption'   => SMTP_ENCRYPTION_BACKUP,
            'from_address' => SMTP_USERNAME_BACKUP,
            'from_name'    => MAIL_FROM_NAME,
            'label'        => 'gmail2',
        ];

        $result = smtp_send_raw($to, $subject, $htmlBody, null, null, $backupConfig);
        if ($result['success']) return $result;
        error_log("[Mail] Gmail 2 (587/TLS) failed: {$result['error']}");

        // 5. Gmail 2 — Backup SMTP (465/SSL)
        if (SMTP_PORT_BACKUP !== 465) {
            $result = smtp_send_raw($to, $subject, $htmlBody, 465, 'ssl', $backupConfig);
            if ($result['success']) return $result;
            error_log("[Mail] Gmail 2 (465/SSL) failed: {$result['error']}");
        }
    }

    // 6. PHP mail() — last resort
    $result = mail_php_fallback($to, $subject, $htmlBody);
    if ($result['success']) return $result;
    error_log("[Mail] PHP mail() failed: {$result['error']}");

    return ['success' => false, 'error' => 'All email delivery methods failed.'];
}

function mail_php_fallback(string $to, string $subject, string $htmlBody): array
{
    $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM_ADDRESS . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: LabAutomation/1.0\r\n";

    $sent = @mail($to, $subject, $htmlBody, $headers);
    if ($sent) {
        return ['success' => true, 'method' => 'php_mail'];
    }

    error_log("[Mail] PHP mail() failed for {$to}");
    return ['success' => false, 'error' => 'PHP mail() failed.'];
}

function send_otp_email(string $to, string $name, string $otp, string $purpose): array
{
    $purposeLabel = $purpose === 'admin_signup'
        ? 'You are creating an Administrator account for ' . APP_NAME . '.'
        : 'You are activating your account on ' . APP_NAME . '.';

    $subject = 'Your verification code — ' . APP_NAME;

    $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
  <div style="background:#1C1416;padding:24px 28px;">
    <span style="color:#ffffff;font-size:16px;font-weight:700;letter-spacing:-0.01em;">Lab Automation System</span>
  </div>
  <div style="padding:28px 28px 32px;">
    <p style="color:#15171B;font-size:15px;margin:0 0 6px;font-weight:600;">Verify your email address</p>
    <p style="color:#53575F;font-size:13px;line-height:1.6;margin:0 0 20px;">
      Hello {$name}, {$purposeLabel}
    </p>
    <p style="color:#53575F;font-size:13px;margin:0 0 12px;">Your verification code is:</p>
    <div style="background:#f5f6f7;border:1px solid #e2e4e8;border-radius:8px;padding:20px;text-align:center;margin:0 0 20px;">
      <span style="font-family:monospace;font-size:32px;font-weight:700;letter-spacing:8px;color:#15171B;">{$otp}</span>
    </div>
    <p style="color:#53575F;font-size:13px;line-height:1.6;margin:0 0 6px;">
      This code expires in <strong>10 minutes</strong>.
    </p>
    <p style="color:#53575F;font-size:13px;line-height:1.6;margin:0 0 0;">
      If you did not request this, you can safely ignore this email.
    </p>
    <hr style="border:none;border-top:1px solid #e2e4e8;margin:24px 0 16px;">
    <p style="color:#7B7F87;font-size:11px;line-height:1.5;margin:0;">
      Never share this verification code with anyone. Lab Automation staff will never ask for your code.
    </p>
  </div>
</div>
</body></html>
HTML;

    return send_mail($to, $subject, $html);
}

function send_invitation_email(string $toEmail, string $userName, string $role, string $department, string $inviterName, string $token, string $workspaceName = ''): array
{
    $activateUrl = rtrim(APP_URL, '/') . '/auth/activate.php?token=' . urlencode($token);

    $roleLabel = ucwords(str_replace('_', ' ', $role));
    $wsLine = $workspaceName ? "<div style=\"font-size:13px;color:#7B7F87;margin-bottom:4px;\">Organization</div><div style=\"font-weight:600;color:#15171B;margin-bottom:12px;\">{$workspaceName}</div>" : '';

    $subject = "You've been invited to " . APP_NAME;

    $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<div style="max-width:520px;margin:40px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
  <div style="background:#1C1416;padding:24px 28px;">
    <span style="color:#ffffff;font-size:16px;font-weight:700;letter-spacing:-0.01em;">Lab Automation System</span>
  </div>
  <div style="padding:28px 28px 32px;">
    <p style="color:#15171B;font-size:16px;margin:0 0 16px;font-weight:600;">You've been invited</p>
    <p style="color:#53575F;font-size:14px;line-height:1.6;margin:0 0 20px;">
      <strong>{$inviterName}</strong> has invited you to join Lab Automation System.
    </p>
    <div style="background:#f5f6f7;border-radius:6px;padding:16px 20px;margin:0 0 24px;">
      {$wsLine}
      <div style="font-size:13px;color:#7B7F87;margin-bottom:4px;">Role</div>
      <div style="font-weight:600;color:#15171B;margin-bottom:12px;">{$roleLabel}</div>
      <div style="font-size:13px;color:#7B7F87;margin-bottom:4px;">Department</div>
      <div style="font-weight:600;color:#15171B;">{$department}</div>
    </div>
    <p style="color:#53575F;font-size:13px;line-height:1.6;margin:0 0 20px;">
      Click the button below to verify your email and activate your account. You will be asked to create a password.
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
      <tr>
        <td style="background:#A6231C;border-radius:6px;padding:0;">
          <a href="{$activateUrl}" target="_blank" style="display:block;background:#A6231C;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:6px;font-weight:700;font-size:15px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;text-align:center;mso-padding-alt:0;border:1px solid #A6231C;">Activate My Account</a>
        </td>
      </tr>
    </table>
    <p style="color:#7B7F87;font-size:12px;margin:0 0 0;line-height:1.5;">
      This invitation expires in <strong>72 hours</strong>.
    </p>
    <hr style="border:none;border-top:1px solid #e2e4e8;margin:24px 0 16px;">
    <p style="color:#7B7F87;font-size:11px;line-height:1.5;margin:0;">
      Do not share this invitation link. If you did not expect this email, please ignore it.
    </p>
  </div>
</div>
</body></html>
HTML;

    return send_mail($toEmail, $subject, $html);
}

function send_password_reset_email(string $toEmail, string $userName, string $token): array
{
    $resetUrl = rtrim(APP_URL, '/') . '/auth/reset-password.php?token=' . urlencode($token);

    $subject = 'Reset your password — ' . APP_NAME;

    $html = <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
  <div style="background:#1C1416;padding:24px 28px;">
    <span style="color:#ffffff;font-size:16px;font-weight:700;letter-spacing:-0.01em;">Lab Automation System</span>
  </div>
  <div style="padding:28px 28px 32px;">
    <p style="color:#15171B;font-size:16px;margin:0 0 8px;font-weight:600;">Password Reset</p>
    <p style="color:#53575F;font-size:14px;line-height:1.6;margin:0 0 20px;">
      Hello {$userName}, we received a request to reset your password. Click the button below to choose a new one.
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
      <tr>
        <td style="background:#A6231C;border-radius:6px;padding:0;">
          <a href="{$resetUrl}" target="_blank" style="display:block;background:#A6231C;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:6px;font-weight:700;font-size:15px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;text-align:center;mso-padding-alt:0;border:1px solid #A6231C;">Reset My Password</a>
        </td>
      </tr>
    </table>
    <p style="color:#7B7F87;font-size:12px;margin:0 0 0;line-height:1.5;">
      This link expires in <strong>60 minutes</strong>. If you did not request this, you can safely ignore this email.
    </p>
    <hr style="border:none;border-top:1px solid #e2e4e8;margin:24px 0 16px;">
    <p style="color:#7B7F87;font-size:11px;line-height:1.5;margin:0;">
      Do not share this link with anyone. It grants one-time access to change your account password.
    </p>
  </div>
</div>
</body></html>
HTML;

    return send_mail($toEmail, $subject, $html);
}
