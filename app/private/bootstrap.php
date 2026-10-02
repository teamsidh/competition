<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/functions.php';

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Registration is temporarily unavailable.');
}

$config = require $configFile;
if (!is_array($config)) {
    http_response_code(503);
    exit('Registration is temporarily unavailable.');
}

$basePath = base_path($config);
$https = str_starts_with(strtolower((string) ($config['APP_URL'] ?? '')), 'https://')
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name(defined('DBAC_ADMIN_SESSION') && DBAC_ADMIN_SESSION === true ? 'dbac_admin' : 'dbac_registration');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $basePath === '' ? '/' : $basePath . '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (!session_start()) {
    http_response_code(503);
    exit('Registration is temporarily unavailable.');
}

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
