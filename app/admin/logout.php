<?php
declare(strict_types=1);
define('DBAC_ADMIN_SESSION', true);
require dirname(__DIR__) . '/private/bootstrap.php';
require dirname(__DIR__) . '/private/admin_functions.php';

header('X-Robots-Tag: noindex, nofollow, noarchive');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_csrf_valid($_POST)) {
    http_response_code(405);
    exit('Logout request rejected.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookie['path'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'],
    ]);
}
session_destroy();
redirect_to($config, 'admin/');
