<?php
declare(strict_types=1);
define('DBAC_ADMIN_SESSION', true);
require dirname(__DIR__) . '/private/bootstrap.php';
require dirname(__DIR__) . '/private/database.php';
require dirname(__DIR__) . '/private/admin_functions.php';
require dirname(__DIR__) . '/private/mail.php';

header('X-Robots-Tag: noindex, nofollow, noarchive');
try {
    $pdo = database($config);
    $accountExists = $pdo->query('SELECT 1 FROM admin_auth WHERE id = 1 LIMIT 1')->fetchColumn() !== false;
    $adminUsername = admin_username($pdo);
    $mailReady = admin_mail_password($pdo, $config) !== null;
} catch (Throwable $exception) {
    error_log('Admin recovery unavailable.');
    http_response_code(503);
    exit('Recovery is temporarily unavailable.');
}

$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_valid($_POST)) {
        http_response_code(400);
        $error = 'The form expired. Refresh this page and try again.';
    } elseif (!$accountExists || !$mailReady) {
        $error = 'Email recovery is not connected yet. Ask the site owner to connect the mailbox in admin settings.';
    } elseif (($_POST['action'] ?? '') === 'send') {
        try {
            $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
            if (!hash_equals($adminUsername, $username)) {
                $error = 'Check the admin ID and try again.';
            } elseif (admin_too_many_attempts($pdo, $config, 'otp_send', 3, 3600)) {
                $error = 'Too many codes requested. Try again in one hour.';
            } else {
                $recent = $pdo->query(
                    'SELECT 1 FROM admin_password_resets WHERE id = 1
                     AND requested_at > UTC_TIMESTAMP() - INTERVAL 1 MINUTE LIMIT 1'
                )->fetchColumn();
                if ($recent !== false) {
                    $error = 'A code was sent recently. Wait one minute before requesting another.';
                } else {
                    admin_record_failure($pdo, $config, 'otp_send');
                    $code = admin_new_otp();
                    $otpHash = admin_otp_hash($config, $code);
                    $save = $pdo->prepare(
                        'INSERT INTO admin_password_resets (id, otp_hash, requested_at, expires_at, failed_attempts)
                         VALUES (1, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP() + INTERVAL 10 MINUTE, 0)
                         ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash), requested_at = UTC_TIMESTAMP(),
                                                 expires_at = UTC_TIMESTAMP() + INTERVAL 10 MINUTE, failed_attempts = 0'
                    );
                    $save->execute([$otpHash]);
                    if (send_recovery_code($pdo, $config, $code)) {
                        $notice = 'An 8-digit code was sent to info@dezignbank.com. It expires in 10 minutes.';
                    } else {
                        $delete = $pdo->prepare('DELETE FROM admin_password_resets WHERE id = 1 AND otp_hash = ?');
                        $delete->execute([$otpHash]);
                        $error = 'Email could not be sent. Try again later or check the mailbox connection in admin settings.';
                    }
                }
            }
        } catch (Throwable $exception) {
            error_log('Admin reset code request failed.');
            http_response_code(503);
            $error = 'Recovery is temporarily unavailable. Try again later.';
        }
    } elseif (($_POST['action'] ?? '') === 'reset') {
        $otp = is_string($_POST['otp'] ?? null) ? trim($_POST['otp']) : '';
        $new = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        if ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (!admin_valid_password($new)) {
            $error = 'Use a password of at least 12 characters and at most 72 bytes.';
        } else {
            try {
                if (admin_too_many_attempts($pdo, $config, 'otp_try')) {
                    $error = 'Too many incorrect codes. Request a new code after 15 minutes.';
                } else {
                    $pdo->beginTransaction();
                    $row = $pdo->query(
                        'SELECT otp_hash, (expires_at > UTC_TIMESTAMP()) AS unexpired, failed_attempts
                         FROM admin_password_resets WHERE id = 1 FOR UPDATE'
                    )->fetch();
                    if ($row === false || (int) $row['unexpired'] !== 1 || (int) $row['failed_attempts'] >= 3) {
                        $pdo->rollBack();
                        $error = 'Code is invalid or expired. Request a new one.';
                    } elseif (!preg_match('/^[0-9]{8}$/D', $otp)
                        || !hash_equals((string) $row['otp_hash'], admin_otp_hash($config, $otp))) {
                        $pdo->exec('UPDATE admin_password_resets SET failed_attempts = failed_attempts + 1 WHERE id = 1');
                        $pdo->commit();
                        admin_record_failure($pdo, $config, 'otp_try');
                        $error = 'Code is invalid or expired. Request a new one after three incorrect attempts.';
                    } else {
                        $hash = password_hash($new, PASSWORD_DEFAULT);
                        $update = $pdo->prepare('UPDATE admin_auth SET password_hash = ? WHERE id = 1');
                        $update->execute([$hash]);
                        $pdo->exec('DELETE FROM admin_password_resets WHERE id = 1');
                        $pdo->commit();
                        admin_clear_failures($pdo, $config, 'login');
                        admin_clear_failures($pdo, $config, 'otp_try');
                        $_SESSION = ['admin_login_notice' => 'Password reset complete. Sign in with the new password.'];
                        session_regenerate_id(true);
                        redirect_to($config, 'admin/');
                    }
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Admin password reset failed.');
                http_response_code(503);
                $error = 'Recovery is temporarily unavailable. Try again later.';
            }
        }
    } else {
        http_response_code(400);
        $error = 'Unknown recovery request.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive"><meta name="theme-color" content="#A65A3A">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/admin.css')) ?>?v=20261002b">
  <title>Reset admin password | DezignBank</title>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="topbar"><div class="topbar-inner">
    <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>"><img src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" alt="" width="31" height="31"><span>DezignBank</span></a>
    <span class="workspace-label">Competition / Admin</span>
  </div></header>
  <main class="recovery-shell" id="main">
    <a class="back-link" href="<?= e(path_url($config, 'admin/')) ?>">← Back to sign in</a>
    <p class="eyebrow">Account recovery</p><h1>Reset your password<span class="heading-dot">.</span></h1>
    <p>Codes go only to <strong>info@dezignbank.com</strong>. Each code has eight digits and expires after 10 minutes.</p>
    <?php if ($error !== ''): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="success" role="status"><?= e($notice) ?></p><?php endif; ?>
    <?php if (!$mailReady): ?><p class="alert">Email recovery is not connected yet. Sign in and connect the Hostinger mailbox from admin settings.</p><?php endif; ?>
    <div class="recovery-grid">
      <section class="settings-card" aria-labelledby="send-title">
        <p class="eyebrow">Step 01</p><h2 id="send-title">Get a code</h2>
        <form method="post" action="<?= e(path_url($config, 'admin/forgot.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="send">
          <label for="username">Admin ID</label><input id="username" name="username" type="text" value="<?= e($adminUsername) ?>" autocomplete="username" required>
          <button class="primary-button" type="submit"<?= $mailReady ? '' : ' disabled' ?>>Send 8-digit code <span aria-hidden="true">→</span></button>
        </form>
      </section>
      <section class="settings-card" aria-labelledby="reset-title">
        <p class="eyebrow">Step 02</p><h2 id="reset-title">Choose a new password</h2>
        <form method="post" action="<?= e(path_url($config, 'admin/forgot.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="reset">
          <label for="otp">8-digit email code</label><input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{8}" minlength="8" maxlength="8" autocomplete="one-time-code" required>
          <label for="new-password">New admin password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
          <label for="confirm-password">Confirm new password</label><input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
          <button class="primary-button" type="submit"<?= $mailReady ? '' : ' disabled' ?>>Reset password <span aria-hidden="true">→</span></button>
        </form>
      </section>
    </div>
  </main>
</body>
</html>
