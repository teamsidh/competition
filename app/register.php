<?php
declare(strict_types=1);
require __DIR__ . '/private/bootstrap.php';
require_once __DIR__ . '/private/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if (($config['REGISTRATION_OPEN'] ?? false) !== true) {
    flash_form([], [], 'Registration is currently closed.');
    redirect_to($config, 'apply.php');
}

if (throttle_exceeded()) {
    flash_form([], [], 'Too many attempts. Please try again in 15 minutes.');
    redirect_to($config, 'apply.php');
}

$postedToken = $_POST['csrf_token'] ?? null;
if (!is_string($postedToken) || !hash_equals($_SESSION['csrf_token'], $postedToken)) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    flash_form([], [], 'The form expired. Please try again.');
    redirect_to($config, 'apply.php');
}

if (!empty($_POST['website'])) {
    flash_form([], [], 'Unable to submit the form. Please try again.');
    redirect_to($config, 'apply.php');
}

[$values, $errors] = validate_registration($_POST);
if ($errors !== []) {
    flash_form($values, $errors);
    redirect_to($config, 'apply.php');
}

try {
    $result = save_registration(database($config), $values);
} catch (Throwable $exception) {
    error_log('Competition registration storage error: ' . get_class($exception));
    flash_form($values, [], 'Registration is temporarily unavailable. Please try again later.');
    redirect_to($config, 'apply.php');
}

if ($result['status'] === 'duplicate') {
    flash_form($values, ['email' => 'This email address is already registered for the competition.']);
    redirect_to($config, 'apply.php');
}

session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['confirmation_reference'] = $result['reference'];
$_SESSION['confirmation_entry_type'] = $values['entry_type'];
$_SESSION['confirmation_team_members'] = $values['team_members'];
unset($_SESSION['form_values'], $_SESSION['form_errors'], $_SESSION['form_general']);
redirect_to($config, 'confirmation.php');
