<?php
declare(strict_types=1);
// OTP generation, storage, verification, resend logic (Naveera)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

const OTP_LENGTH               = 6;
const OTP_EXPIRY_MINUTES       = 10;
const OTP_MAX_ATTEMPTS         = 5;
const OTP_RESEND_COOLDOWN_SEC  = 60;
const OTP_MAX_SENDS            = 5;

function generate_otp(): string
{
    return str_pad((string) random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
}

function store_otp(PDO $pdo, string $email, string $otp, string $purpose, ?string $referenceToken = null): int
{
    $pdo->prepare('UPDATE email_verifications SET verified_at = NOW() WHERE email = :e AND purpose = :p AND verified_at IS NULL')
        ->execute(['e' => $email, 'p' => $purpose]);

    $hash = password_hash($otp, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO email_verifications (email, otp_hash, purpose, reference_token, expires_at, last_sent_at, send_count)
         VALUES (:email, :hash, :purpose, :ref, DATE_ADD(NOW(), INTERVAL ' . OTP_EXPIRY_MINUTES . ' MINUTE), NOW(), 1)'
    );
    $stmt->execute([
        'email'   => $email,
        'hash'    => $hash,
        'purpose' => $purpose,
        'ref'     => $referenceToken,
    ]);

    return (int) $pdo->lastInsertId();
}

function verify_otp(PDO $pdo, string $email, string $otp, string $purpose): array
{
    $stmt = $pdo->prepare(
        'SELECT *, (expires_at <= NOW()) AS is_expired FROM email_verifications
         WHERE email = :e AND purpose = :p AND verified_at IS NULL
         ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute(['e' => $email, 'p' => $purpose]);
    $record = $stmt->fetch();

    if (!$record) {
        return ['success' => false, 'error' => 'No pending verification found. Please request a new code.'];
    }

    if ((int) $record['is_expired']) {
        return ['success' => false, 'error' => 'Verification code has expired. Please request a new code.', 'expired' => true];
    }

    if ((int) $record['attempts'] >= OTP_MAX_ATTEMPTS) {
        return ['success' => false, 'error' => 'Too many failed attempts. Please request a new code.', 'max_attempts' => true];
    }

    if (!password_verify($otp, $record['otp_hash'])) {
        $pdo->prepare('UPDATE email_verifications SET attempts = attempts + 1 WHERE id = :id')
            ->execute(['id' => $record['id']]);
        $remaining = OTP_MAX_ATTEMPTS - (int) $record['attempts'] - 1;
        $msg = 'Invalid verification code.';
        if ($remaining > 0) {
            $msg .= " {$remaining} attempt(s) remaining.";
        }
        return ['success' => false, 'error' => $msg];
    }

    $pdo->prepare('UPDATE email_verifications SET verified_at = NOW() WHERE id = :id')
        ->execute(['id' => $record['id']]);

    return ['success' => true, 'record' => $record];
}

function can_resend_otp(PDO $pdo, string $email, string $purpose): array
{
    $stmt = $pdo->prepare(
        'SELECT *, TIMESTAMPDIFF(SECOND, last_sent_at, NOW()) AS elapsed_sec FROM email_verifications
         WHERE email = :e AND purpose = :p AND verified_at IS NULL
         ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute(['e' => $email, 'p' => $purpose]);
    $record = $stmt->fetch();

    if (!$record) {
        return ['can_resend' => true, 'cooldown' => 0];
    }

    if ((int) $record['send_count'] >= OTP_MAX_SENDS) {
        return ['can_resend' => false, 'error' => 'Maximum resend limit reached. Please try again later.', 'cooldown' => 0];
    }

    $elapsed = (int) $record['elapsed_sec'];

    if ($elapsed < OTP_RESEND_COOLDOWN_SEC) {
        return ['can_resend' => false, 'cooldown' => OTP_RESEND_COOLDOWN_SEC - $elapsed];
    }

    return ['can_resend' => true, 'cooldown' => 0, 'record' => $record];
}

function resend_otp(PDO $pdo, string $email, string $purpose): array
{
    $check = can_resend_otp($pdo, $email, $purpose);
    if (!($check['can_resend'] ?? false)) {
        return $check + ['success' => false];
    }

    $otp  = generate_otp();
    $hash = password_hash($otp, PASSWORD_BCRYPT);

    if (isset($check['record'])) {
        $stmt = $pdo->prepare(
            'UPDATE email_verifications
             SET otp_hash = :hash, expires_at = DATE_ADD(NOW(), INTERVAL ' . OTP_EXPIRY_MINUTES . ' MINUTE),
                 attempts = 0, last_sent_at = NOW(), send_count = send_count + 1
             WHERE id = :id AND send_count < ' . OTP_MAX_SENDS . '
               AND TIMESTAMPDIFF(SECOND, last_sent_at, NOW()) >= ' . OTP_RESEND_COOLDOWN_SEC
        );
        $stmt->execute(['hash' => $hash, 'id' => $check['record']['id']]);
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Please wait before requesting a new code.', 'cooldown' => OTP_RESEND_COOLDOWN_SEC];
        }
    } else {
        store_otp($pdo, $email, $otp, $purpose);
    }

    return ['success' => true, 'otp' => $otp];
}

function mask_email(string $email): string
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return '***';
    }
    $local  = $parts[0];
    $domain = $parts[1];
    if (strlen($local) <= 2) {
        return $local[0] . '***@' . $domain;
    }
    return $local[0] . str_repeat('*', min(5, strlen($local) - 2)) . substr($local, -1) . '@' . $domain;
}

function cleanup_expired_pending_signups(PDO $pdo): void
{
    $pdo->prepare('DELETE FROM pending_signups WHERE expires_at < NOW()')->execute();
}

function cleanup_expired_verifications(PDO $pdo): void
{
    $pdo->prepare('DELETE FROM email_verifications WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)')->execute();
}
