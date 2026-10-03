<?php
declare(strict_types=1);
define('DBAC_ADMIN_SESSION', true);
require dirname(__DIR__) . '/private/bootstrap.php';
require dirname(__DIR__) . '/private/database.php';
require dirname(__DIR__) . '/private/admin_functions.php';
header('X-Robots-Tag: noindex, nofollow, noarchive');
try {
    $pdo = database($config);
    admin_require_auth($config, $pdo);
} catch (Throwable $exception) {
    error_log('Sponsor admin unavailable.');
    http_response_code(503);
    exit('Admin is temporarily unavailable.');
}
function sponsor_file_path(string $logoPath): ?string {
    return preg_match('/^assets\/sponsors\/[a-f0-9]{32}\.(?:png|jpg|webp)$/D', $logoPath)
        ? dirname(__DIR__) . '/' . $logoPath : null;
}
$error = '';
$notice = (string) ($_SESSION['sponsor_notice'] ?? '');
unset($_SESSION['sponsor_notice']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_valid($_POST)) {
        http_response_code(400);
        $error = 'The form expired. Refresh and try again.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $oldLogo = $newLogo = '';
        try {
            if ($action === 'delete' && $id) {
                $lookup = $pdo->prepare('SELECT logo_path FROM sponsors WHERE id = ?');
                $lookup->execute([$id]);
                $oldLogo = (string) ($lookup->fetchColumn() ?: '');
                $delete = $pdo->prepare('DELETE FROM sponsors WHERE id = ?');
                $delete->execute([$id]);
                if ($delete->rowCount() && ($path = sponsor_file_path($oldLogo)) && is_file($path)) unlink($path);
                $_SESSION['sponsor_notice'] = 'Sponsor removed.';
                redirect_to($config, 'admin/sponsors.php');
            }
            if (!in_array($action, ['add', 'update'], true) || ($action === 'update' && !$id)) {
                throw new RuntimeException('Choose a valid sponsor action.');
            }
            $name = is_string($_POST['name'] ?? null) ? normalized_text($_POST['name']) : '';
            $url = is_string($_POST['website_url'] ?? null) ? trim($_POST['website_url']) : '';
            $order = filter_var($_POST['sort_order'] ?? '0', FILTER_VALIDATE_INT);
            if (character_count($name) < 2 || character_count($name) > 120 || preg_match('/[\p{C}]/u', $name)) {
                throw new RuntimeException('Sponsor name must be 2–120 characters.');
            }
            if ($url !== '' && (strlen($url) > 300 || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true))) {
                throw new RuntimeException('Enter a valid http or https website URL.');
            }
            if ($order === false || $order < -10000 || $order > 10000) {
                throw new RuntimeException('Display order must be between -10000 and 10000.');
            }
            if ($action === 'update') {
                $lookup = $pdo->prepare('SELECT logo_path FROM sponsors WHERE id = ?');
                $lookup->execute([$id]);
                $oldLogo = (string) ($lookup->fetchColumn() ?: '');
                if ($oldLogo === '') throw new RuntimeException('Sponsor not found.');
            }
            $file = $_FILES['logo'] ?? null;
            if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? '')) || (int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
                    throw new RuntimeException('Upload a logo smaller than 2 MB.');
                }
                $image = getimagesize((string) $file['tmp_name']);
                $types = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
                if ($image === false || !isset($types[$image[2]]) || $image[0] < 50 || $image[1] < 30 || $image[0] > 4000 || $image[1] > 4000) {
                    throw new RuntimeException('Use a PNG, JPEG or WebP logo with reasonable dimensions.');
                }
                $newLogo = 'assets/sponsors/' . bin2hex(random_bytes(16)) . '.' . $types[$image[2]];
                if (!move_uploaded_file((string) $file['tmp_name'], (string) sponsor_file_path($newLogo))) {
                    throw new RuntimeException('Logo upload failed. Try again.');
                }
            } elseif ($action === 'add') {
                throw new RuntimeException('Choose a sponsor logo.');
            }
            if ($action === 'add') {
                $insert = $pdo->prepare('INSERT INTO sponsors (name, logo_path, website_url, sort_order, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())');
                $insert->execute([$name, $newLogo, $url === '' ? null : $url, $order]);
            } else {
                $update = $pdo->prepare('UPDATE sponsors SET name = ?, logo_path = ?, website_url = ?, sort_order = ? WHERE id = ?');
                $update->execute([$name, $newLogo ?: $oldLogo, $url === '' ? null : $url, $order, $id]);
                if ($newLogo && ($oldFile = sponsor_file_path($oldLogo)) && is_file($oldFile)) unlink($oldFile);
            }
            $_SESSION['sponsor_notice'] = $action === 'add' ? 'Sponsor added.' : 'Sponsor updated.';
            redirect_to($config, 'admin/sponsors.php');
        } catch (Throwable $exception) {
            if ($newLogo && ($newFile = sponsor_file_path($newLogo)) && is_file($newFile)) unlink($newFile);
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Sponsor could not be saved. Please try again.';
            if (!($exception instanceof RuntimeException)) error_log('Sponsor save failed.');
        }
    }
}
try {
    $sponsors = $pdo->query('SELECT id, name, logo_path, website_url, sort_order FROM sponsors ORDER BY sort_order, id')->fetchAll();
} catch (Throwable $exception) {
    error_log('Sponsors query failed.');
    http_response_code(503);
    exit('Sponsor management is temporarily unavailable.');
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="<?= e(path_url($config, 'assets/admin.css')) ?>?v=20261003"><title>Sponsors | DezignBank Admin</title></head>
<body><a class="skip-link" href="#main">Skip to content</a><header class="topbar"><div class="topbar-inner"><a class="brand" href="<?= e(path_url($config, 'index.php')) ?>"><img src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" width="31" height="31" alt="">DezignBank</a><div class="topbar-right"><a class="settings-link" href="<?= e(path_url($config, 'admin/')) ?>">Registrations</a><a class="settings-link" href="<?= e(path_url($config, 'admin/settings.php')) ?>">Settings</a></div></div></header>
<main class="settings-shell sponsors-admin" id="main"><p class="eyebrow">Competition workspace</p><h1>Sponsors<span class="heading-dot">.</span></h1><p class="settings-intro">Add, update or remove logos shown on the competition page. Use a clear PNG, JPEG or WebP logo up to 2 MB.</p>
<?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?><?php if ($notice): ?><p class="success" role="status"><?= e($notice) ?></p><?php endif; ?>
<section class="settings-card"><p class="eyebrow">New partner</p><h2>Add a sponsor</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="add"><label for="new-name">Sponsor name</label><input id="new-name" name="name" maxlength="120" required><label for="new-logo">Logo image</label><input id="new-logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" required><label for="new-url">Website URL (optional)</label><input id="new-url" name="website_url" type="url" maxlength="300" placeholder="https://example.com"><label for="new-order">Display order</label><input id="new-order" name="sort_order" type="number" value="0" min="-10000" max="10000"><button class="primary-button" type="submit">Add sponsor <span aria-hidden="true">→</span></button></form></section>
<h2 class="sponsors-list-title">Current sponsors</h2><?php if ($sponsors === []): ?><p>No sponsors added yet.</p><?php endif; ?>
<div class="sponsors-admin-grid"><?php foreach ($sponsors as $sponsor): ?><section class="settings-card sponsor-edit"><img src="<?= e(path_url($config, $sponsor['logo_path'])) ?>" alt="<?= e($sponsor['name']) ?> logo"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= e($sponsor['id']) ?>"><label for="name-<?= e($sponsor['id']) ?>">Sponsor name</label><input id="name-<?= e($sponsor['id']) ?>" name="name" value="<?= e($sponsor['name']) ?>" maxlength="120" required><label for="logo-<?= e($sponsor['id']) ?>">Replace logo (optional)</label><input id="logo-<?= e($sponsor['id']) ?>" name="logo" type="file" accept="image/png,image/jpeg,image/webp"><label for="url-<?= e($sponsor['id']) ?>">Website URL</label><input id="url-<?= e($sponsor['id']) ?>" name="website_url" type="url" value="<?= e($sponsor['website_url'] ?? '') ?>" maxlength="300"><label for="order-<?= e($sponsor['id']) ?>">Display order</label><input id="order-<?= e($sponsor['id']) ?>" name="sort_order" type="number" value="<?= e($sponsor['sort_order']) ?>" min="-10000" max="10000"><button class="primary-button" type="submit">Save changes</button></form><form method="post" onsubmit="return confirm('Remove this sponsor and its logo?')"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($sponsor['id']) ?>"><button class="sponsor-delete" type="submit">Remove sponsor</button></form></section><?php endforeach; ?></div>
</main></body></html>
