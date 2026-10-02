<?php
declare(strict_types=1);

function admin_mail_key(array $config): string
{
    return hash_hmac('sha256', 'admin-mail-password-v1', (string) $config['DB_PASSWORD'], true);
}

function admin_mail_configured(PDO $pdo): bool
{
    return $pdo->query('SELECT 1 FROM admin_mail_settings WHERE id = 1 LIMIT 1')->fetchColumn() !== false;
}

function admin_save_mail_password(PDO $pdo, array $config, string $password): void
{
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($password, 'aes-256-gcm', admin_mail_key($config), OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Unable to secure mailbox settings.');
    }
    $query = $pdo->prepare(
        'INSERT INTO admin_mail_settings (id, ciphertext, nonce, auth_tag, updated_at)
         VALUES (1, ?, ?, ?, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE ciphertext = VALUES(ciphertext), nonce = VALUES(nonce),
                                 auth_tag = VALUES(auth_tag), updated_at = UTC_TIMESTAMP()'
    );
    $query->execute([base64_encode($ciphertext), base64_encode($nonce), base64_encode($tag)]);
}

function admin_mail_password(PDO $pdo, array $config): ?string
{
    $row = $pdo->query('SELECT ciphertext, nonce, auth_tag FROM admin_mail_settings WHERE id = 1 LIMIT 1')->fetch();
    if ($row === false) {
        return null;
    }
    $ciphertext = base64_decode((string) $row['ciphertext'], true);
    $nonce = base64_decode((string) $row['nonce'], true);
    $tag = base64_decode((string) $row['auth_tag'], true);
    if ($ciphertext === false || $nonce === false || $tag === false || strlen($nonce) !== 12 || strlen($tag) !== 16) {
        return null;
    }
    $password = openssl_decrypt($ciphertext, 'aes-256-gcm', admin_mail_key($config), OPENSSL_RAW_DATA, $nonce, $tag);
    return $password === false ? null : $password;
}

function admin_send_mail(string $password, string $subject, string $body): bool
{
    require_once __DIR__ . '/vendor/phpmailer/Exception.php';
    require_once __DIR__ . '/vendor/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/SMTP.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->Username = 'info@dezignbank.com';
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 12;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('info@dezignbank.com', 'DezignBank Competition');
        $mail->addAddress('info@dezignbank.com');
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Throwable $exception) {
        error_log('Admin recovery email could not be sent.');
        return false;
    }
}

function send_recovery_code(PDO $pdo, array $config, string $code): bool
{
    $password = admin_mail_password($pdo, $config);
    if ($password === null) {
        return false;
    }
    return admin_send_mail(
        $password,
        'DezignBank admin password reset code',
        "Your 8-digit DezignBank admin reset code is: $code\n\nThis code expires in 10 minutes and can be used once. If you did not request it, ignore this email."
    );
}
