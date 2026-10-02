<?php
declare(strict_types=1);
define('DBAC_ADMIN_SESSION', true);
require dirname(__DIR__) . '/private/bootstrap.php';
require dirname(__DIR__) . '/private/database.php';
require dirname(__DIR__) . '/private/admin_functions.php';

header('X-Robots-Tag: noindex, nofollow, noarchive');

try {
    $pdo = database($config);
    $auth = $pdo->query('SELECT password_hash FROM admin_auth WHERE id = 1 LIMIT 1')->fetch();
} catch (Throwable $exception) {
    error_log('Admin database unavailable.');
    http_response_code(503);
    exit('Admin is temporarily unavailable.');
}

$mode = $auth === false ? 'setup' : 'login';
$error = '';
$notice = (string) ($_SESSION['admin_login_notice'] ?? '');
unset($_SESSION['admin_login_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_valid($_POST)) {
        http_response_code(400);
        $error = 'The form expired. Refresh this page and try again.';
    } elseif ($mode === 'setup') {
        try {
            if (admin_too_many_attempts($pdo, $config, 'setup')) {
                $error = 'Too many attempts. Try again in 15 minutes.';
            } else {
                $hostingPassword = is_string($_POST['hosting_password'] ?? null) ? $_POST['hosting_password'] : '';
                $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
                $confirmation = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
                $configuredPassword = (string) ($config['DB_PASSWORD'] ?? '');
                if ($configuredPassword === '' || !hash_equals($configuredPassword, $hostingPassword)) {
                    admin_record_failure($pdo, $config, 'setup');
                    $error = 'The hosting database password does not match.';
                } elseif ($newPassword !== $confirmation) {
                    $error = 'The new passwords do not match.';
                } elseif (!admin_valid_password($newPassword)) {
                    $error = 'Use an admin password of at least 12 characters and at most 72 bytes.';
                } elseif (hash_equals($configuredPassword, $newPassword)) {
                    $error = 'Choose an admin password different from the hosting password.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $insert = $pdo->prepare(
                        'INSERT INTO admin_auth (id, password_hash, created_at) VALUES (1, ?, UTC_TIMESTAMP())'
                    );
                    $insert->execute([$hash]);
                    admin_clear_failures($pdo, $config, 'setup');
                    admin_complete_login($hash);
                    redirect_to($config, 'admin/');
                }
            }
        } catch (Throwable $exception) {
            if ($exception instanceof PDOException && ($exception->errorInfo[1] ?? null) === 1062) {
                $error = 'Admin setup is already complete. Refresh and sign in.';
            } else {
                error_log('Admin setup failed.');
                http_response_code(503);
                $error = 'Admin is temporarily unavailable. Try again later.';
            }
        }
    } elseif (!admin_authenticated((string) $auth['password_hash'])) {
        try {
            if (admin_too_many_attempts($pdo, $config, 'login')) {
                $error = 'Three incorrect attempts. Sign-in is blocked for 15 minutes.';
            } else {
                $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
                $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
                if (!password_verify($password, (string) $auth['password_hash']) || !hash_equals('admin', $username)) {
                    admin_record_failure($pdo, $config, 'login');
                    $error = admin_too_many_attempts($pdo, $config, 'login')
                        ? 'Three incorrect attempts. Sign-in is blocked for 15 minutes.'
                        : 'Incorrect admin ID or password.';
                } else {
                    $currentHash = (string) $auth['password_hash'];
                    if (password_needs_rehash($currentHash, PASSWORD_DEFAULT)) {
                        $currentHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateHash = $pdo->prepare('UPDATE admin_auth SET password_hash = ? WHERE id = 1');
                        $updateHash->execute([$currentHash]);
                    }
                    admin_clear_failures($pdo, $config, 'login');
                    $pdo->exec('UPDATE admin_auth SET last_login_at = UTC_TIMESTAMP() WHERE id = 1');
                    admin_complete_login($currentHash);
                    redirect_to($config, 'admin/');
                }
            }
        } catch (Throwable $exception) {
            error_log('Admin login failed due to storage error.');
            http_response_code(503);
            $error = 'Admin is temporarily unavailable. Try again later.';
        }
    }
}

$isSignedIn = $mode === 'login' && admin_authenticated((string) $auth['password_hash']);
if ($isSignedIn) {
    try {
        $term = admin_search_term($_GET['q'] ?? '');
        [$where, $params] = admin_search_filter($term);
        $total = (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
        $today = (int) $pdo->query('SELECT COUNT(*) FROM registrations WHERE created_at >= UTC_DATE()')->fetchColumn();
        $count = $pdo->prepare('SELECT COUNT(*) FROM registrations' . $where);
        $count->execute($params);
        $matched = (int) $count->fetchColumn();
        $perPage = 25;
        $pageCount = max(1, (int) ceil($matched / $perPage));
        $requestedPage = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $page = min($requestedPage, $pageCount);
        $rowsQuery = $pdo->prepare(
            'SELECT reference, full_name, email_normalized, mobile_e164, college_name, college_city,
                    year_of_study, created_at FROM registrations' . $where .
            ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
        );
        $position = 1;
        foreach ($params as $param) {
            $rowsQuery->bindValue($position++, $param, PDO::PARAM_STR);
        }
        $rowsQuery->bindValue($position++, $perPage, PDO::PARAM_INT);
        $rowsQuery->bindValue($position, ($page - 1) * $perPage, PDO::PARAM_INT);
        $rowsQuery->execute();
        $rows = $rowsQuery->fetchAll();
    } catch (Throwable $exception) {
        error_log('Admin registrations query failed.');
        http_response_code(503);
        exit('Admin is temporarily unavailable.');
    }
}

$pageTitle = $mode === 'setup' ? 'Set up admin access' : ($isSignedIn ? 'Registrations' : 'Admin sign in');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow,noarchive">
  <meta name="theme-color" content="#A65A3A">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/admin.css')) ?>?v=20261002b">
  <title><?= e($pageTitle) ?> | DezignBank Admin</title>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="topbar">
    <div class="topbar-inner">
      <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>" aria-label="DezignBank registration home">
        <img src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" width="31" height="31" alt="">
        <span>DezignBank</span>
      </a>
      <div class="topbar-right">
        <span class="workspace-label">Competition / Admin</span>
        <?php if ($isSignedIn): ?>
          <a class="settings-link" href="<?= e(path_url($config, 'admin/settings.php')) ?>">Settings</a>
          <form method="post" action="<?= e(path_url($config, 'admin/logout.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <button class="logout" type="submit">Sign out</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <?php if (!$isSignedIn): ?>
    <main class="auth-shell" id="main">
      <div class="auth-intro">
        <p class="eyebrow">Private workspace</p>
        <h1><?= $mode === 'setup' ? 'Make this yours.' : 'Welcome back.' ?></h1>
        <p><?= $mode === 'setup'
            ? 'Set an admin password to view competition registrations. This one-time step verifies your existing hosting database password.'
            : 'Sign in to review student registrations and download the data when you need it.' ?></p>
        <div class="auth-aside"><span class="auth-aside-number">01 / 01</span><span>Architecture Student Competition<br>Registration management</span></div>
      </div>
      <section class="auth-card" aria-labelledby="form-heading">
        <p class="eyebrow">DezignBank admin</p>
        <h2 id="form-heading"><?= $mode === 'setup' ? 'Create admin access' : 'Sign in' ?></h2>
        <?php if ($error !== ''): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($notice !== ''): ?><p class="success" role="status"><?= e($notice) ?></p><?php endif; ?>
        <form method="post" action="<?= e(path_url($config, 'admin/')) ?>" autocomplete="on">
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
          <?php if ($mode === 'setup'): ?>
            <label>Admin ID</label>
            <div class="readonly-id">admin</div>
            <label for="hosting_password">Existing hosting database password</label>
            <input id="hosting_password" name="hosting_password" type="password" autocomplete="off" required>
            <p class="field-note">Find it in your InfinityFree account’s MySQL database details. It is checked once and is never saved here.</p>
            <label for="new_password">New admin password</label>
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            <label for="confirm_password">Confirm admin password</label>
            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            <button class="primary-button" type="submit">Create access <span aria-hidden="true">→</span></button>
            <p class="small-note">Use at least 12 characters. The new password is stored only as a salted hash.</p>
          <?php else: ?>
            <label for="username">Admin ID</label>
            <input id="username" name="username" type="text" autocomplete="username" value="admin" required>
            <label for="password">Admin password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button class="primary-button" type="submit">Sign in <span aria-hidden="true">→</span></button>
            <p class="small-note">Three incorrect attempts from one address block sign-in for 15 minutes.</p>
            <a class="forgot-link" href="<?= e(path_url($config, 'admin/forgot.php')) ?>">Forgot password?</a>
          <?php endif; ?>
        </form>
      </section>
    </main>
  <?php else: ?>
    <main class="dashboard" id="main">
      <div class="page-heading">
        <div><p class="eyebrow">Competition workspace</p><h1>Registrations<span class="heading-dot">.</span></h1><p>Student entries, in one place. Times are shown in UTC.</p></div>
        <span class="private-badge">Private access</span>
      </div>
      <div class="stats">
        <div class="stat"><span>Total registrations</span><strong><?= e(number_format($total)) ?></strong><small>All time</small></div>
        <div class="stat"><span>Received today</span><strong><?= e(number_format($today)) ?></strong><small>UTC day</small></div>
        <div class="stat"><span>Search results</span><strong><?= e(number_format($matched)) ?></strong><small><?= $term === '' ? 'Showing all entries' : 'Matching your search' ?></small></div>
      </div>
      <section class="entries" aria-labelledby="entries-title">
        <div class="entries-heading"><div><p class="eyebrow">Entries</p><h2 id="entries-title">All submissions</h2></div><span><?= e(number_format($matched)) ?> records</span></div>
        <div class="toolbar">
          <form class="search-form" method="get" action="<?= e(path_url($config, 'admin/')) ?>">
            <label class="sr-only" for="q">Search registrations</label>
            <input id="q" name="q" type="search" value="<?= e($term) ?>" placeholder="Search name, email, reference or college" maxlength="100">
            <button type="submit">Search</button>
          </form>
          <?php if ($term !== ''): ?><a class="clear-link" href="<?= e(path_url($config, 'admin/')) ?>">Clear</a><?php endif; ?>
          <form class="export-form" method="post" action="<?= e(path_url($config, 'admin/export.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="q" value="<?= e($term) ?>">
            <button type="submit">Download <?= $term === '' ? 'all' : 'matching' ?> CSV</button>
          </form>
        </div>
        <?php if ($rows === []): ?>
          <div class="empty-state"><span class="empty-number">00</span><h3><?= $term === '' ? 'No registrations yet' : 'No matching registrations' ?></h3><p><?= $term === '' ? 'New submissions will appear here automatically.' : 'Try a different name, email, reference, or college.' ?></p></div>
        <?php else: ?>
          <div class="table-wrap"><table>
            <thead><tr><th scope="col">Reference / received</th><th scope="col">Student</th><th scope="col">Contact</th><th scope="col">College</th><th scope="col">City</th><th scope="col">Year</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <tr>
                  <td><strong class="reference"><?= e($row['reference']) ?></strong><small><?= e($row['created_at']) ?> UTC</small></td>
                  <td><strong><?= e($row['full_name']) ?></strong></td>
                  <td><a href="mailto:<?= e($row['email_normalized']) ?>"><?= e($row['email_normalized']) ?></a><small><?= e($row['mobile_e164']) ?></small></td>
                  <td><?= e($row['college_name']) ?></td>
                  <td><?= e($row['college_city']) ?></td>
                  <td><span class="year-pill"><?= e($row['year_of_study']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table></div>
          <?php if ($pageCount > 1): ?>
            <nav class="pagination" aria-label="Registration pages">
              <span>Page <?= e($page) ?> of <?= e($pageCount) ?></span>
              <div>
                <?php if ($page > 1): ?><a href="<?= e(path_url($config, 'admin/') . '?q=' . rawurlencode($term) . '&page=' . ($page - 1)) ?>">← Previous</a><?php endif; ?>
                <?php if ($page < $pageCount): ?><a href="<?= e(path_url($config, 'admin/') . '?q=' . rawurlencode($term) . '&page=' . ($page + 1)) ?>">Next →</a><?php endif; ?>
              </div>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </section>
      <footer class="dashboard-footer">DezignBank Architecture Student Competition · Private registration data</footer>
    </main>
  <?php endif; ?>
</body>
</html>
