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
    admin_require_auth($config, $pdo);
    $authHash = (string) $pdo->query('SELECT password_hash FROM admin_auth WHERE id = 1')->fetchColumn();
    $mailReady = admin_mail_password($pdo, $config) !== null;
} catch (Throwable $exception) {
    error_log('Admin settings unavailable.');
    http_response_code(503);
    exit('Admin is temporarily unavailable.');
}

$error = '';
$notice = (string) ($_SESSION['admin_settings_notice'] ?? '');
unset($_SESSION['admin_settings_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_valid($_POST)) {
        http_response_code(400);
        $error = 'The form expired. Refresh this page and try again.';
    } else {
        try {
            if (admin_too_many_attempts($pdo, $config, 'settings')) {
                $error = 'Three incorrect password attempts. Try again in 15 minutes.';
            } else {
                $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
                if (!password_verify($current, $authHash)) {
                    admin_record_failure($pdo, $config, 'settings');
                    $error = admin_too_many_attempts($pdo, $config, 'settings')
                        ? 'Three incorrect password attempts. Try again in 15 minutes.'
                        : 'Current admin password is incorrect.';
                } elseif (($_POST['action'] ?? '') === 'password') {
                    $new = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
                    $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
                    if ($new !== $confirm) {
                        $error = 'New passwords do not match.';
                    } elseif (!admin_valid_password($new)) {
                        $error = 'Use a password of at least 12 characters and at most 72 bytes.';
                    } elseif (password_verify($new, $authHash)) {
                        $error = 'Choose a different password.';
                    } else {
                        $newHash = password_hash($new, PASSWORD_DEFAULT);
                        $query = $pdo->prepare('UPDATE admin_auth SET password_hash = ? WHERE id = 1');
                        $query->execute([$newHash]);
                        admin_clear_failures($pdo, $config, 'settings');
                        admin_complete_login($newHash);
                        $_SESSION['admin_settings_notice'] = 'Admin password changed. Other sessions have been signed out.';
                        redirect_to($config, 'admin/settings.php');
                    }
                } elseif (($_POST['action'] ?? '') === 'mail') {
                    $mailPassword = is_string($_POST['mail_password'] ?? null) ? $_POST['mail_password'] : '';
                    if ($mailPassword === '' || strlen($mailPassword) > 255) {
                        $error = 'Enter the password for info@dezignbank.com.';
                    } elseif (admin_too_many_attempts($pdo, $config, 'mail_test', 3, 3600)) {
                        $error = 'Email setup was tried too often. Try again in one hour.';
                    } else {
                        admin_record_failure($pdo, $config, 'mail_test');
                        if (!admin_send_mail($mailPassword, 'DezignBank admin recovery test',
                            'Email recovery is ready for the DezignBank competition admin. This is a one-time setup test.')) {
                            $error = 'Hostinger did not accept the mailbox login or connection. Check the mailbox password and try again.';
                        } else {
                            admin_save_mail_password($pdo, $config, $mailPassword);
                            $mailReady = true;
                            admin_clear_failures($pdo, $config, 'settings');
                            $_SESSION['admin_settings_notice'] = 'Recovery email connected. A test message was sent to info@dezignbank.com.';
                            redirect_to($config, 'admin/settings.php');
                        }
                    }
                } else {
                    http_response_code(400);
                    $error = 'Unknown settings request.';
                }
            }
        } catch (Throwable $exception) {
            error_log('Admin settings update failed.');
            http_response_code(503);
            $error = 'Settings could not be saved. Try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive"><meta name="theme-color" content="#A65A3A">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/admin.css')) ?>?v=20261002b">
  <title>Admin settings | DezignBank</title>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="topbar"><div class="topbar-inner">
    <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>"><img src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" alt="" width="31" height="31"><span>DezignBank</span></a>
    <div class="topbar-right"><a class="settings-link" href="<?= e(path_url($config, 'admin/')) ?>">← Registrations</a><span class="workspace-label">Competition / Admin</span></div>
  </div></header>
  <main class="settings-shell" id="main">
    <p class="eyebrow">Private workspace</p><h1>Account settings<span class="heading-dot">.</span></h1>
    <p class="settings-intro">Admin ID: <strong>admin</strong>. Keep the admin password separate from your hosting and email passwords.</p>
    <?php if ($error !== ''): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice !== ''): ?><p class="success" role="status"><?= e($notice) ?></p><?php endif; ?>
    <div class="settings-grid">
      <section class="settings-card" aria-labelledby="password-title">
        <p class="eyebrow">Access</p><h2 id="password-title">Change admin password</h2>
        <p>Use this after the temporary password. Your other admin sessions will end.</p>
        <form method="post" action="<?= e(path_url($config, 'admin/settings.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="password">
          <label for="current-password">Current admin password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
          <label for="new-password">New admin password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
          <label for="confirm-password">Confirm new password</label><input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
          <button class="primary-button" type="submit">Save new password <span aria-hidden="true">→</span></button>
        </form>
      </section>
      <section class="settings-card" aria-labelledby="mail-title">
        <p class="eyebrow">Recovery email</p><h2 id="mail-title">Hostinger mailbox</h2>
        <p>Status: <strong><?= $mailReady ? 'Connected' : 'Not connected' ?></strong>. Reset codes go only to <strong>info@dezignbank.com</strong>.</p>
        <form method="post" action="<?= e(path_url($config, 'admin/settings.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="mail">
          <label for="mail-current">Current admin password</label><input id="mail-current" name="current_password" type="password" autocomplete="current-password" required>
          <label for="mail-password">Hostinger mailbox password</label><input id="mail-password" name="mail_password" type="password" autocomplete="off" required>
          <p class="field-note">The password for the info@dezignbank.com mailbox, not your Hostinger account password. A test email is sent before this is saved securely.</p>
          <button class="primary-button" type="submit"><?= $mailReady ? 'Update mailbox connection' : 'Connect mailbox' ?> <span aria-hidden="true">→</span></button>
        </form>
      </section>
    </div>
  </main>
</body>
</html>
