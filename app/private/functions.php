<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(array $config): string
{
    $path = trim((string) ($config['BASE_PATH'] ?? ''));
    if ($path === '' || $path === '/') {
        return '';
    }
    if ($path[0] !== '/' || str_contains($path, '?') || str_contains($path, '#')) {
        throw new RuntimeException('Invalid BASE_PATH configuration.');
    }
    return rtrim($path, '/');
}

function path_url(array $config, string $file): string
{
    return base_path($config) . '/' . ltrim($file, '/');
}

function canonical_url(array $config, string $file): string
{
    return rtrim((string) ($config['APP_URL'] ?? ''), '/') . path_url($config, $file);
}

function redirect_to(array $config, string $file): never
{
    header('Location: ' . path_url($config, $file), true, 303);
    exit;
}

function normalized_text(string $value): string
{
    $value = trim($value);
    if (!preg_match('//u', $value)) {
        return '';
    }
    return preg_replace('/\s+/u', ' ', $value) ?? '';
}

function character_count(string $value): int
{
    return preg_match_all('/./us', $value);
}

function normalize_phone(string $value): ?string
{
    $phone = preg_replace('/[\s().-]+/', '', trim($value));
    if ($phone === null) {
        return null;
    }
    if (preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        return '+91' . $phone;
    }
    if (preg_match('/^91[6-9][0-9]{9}$/', $phone)) {
        return '+' . $phone;
    }
    if (str_starts_with($phone, '+91')) {
        return preg_match('/^\+91[6-9][0-9]{9}$/', $phone) ? $phone : null;
    }
    if (preg_match('/^\+[1-9][0-9]{7,14}$/', $phone)) {
        return $phone;
    }
    return null;
}

/** @return array{0: array<string, mixed>, 1: array<string, string>} */
function validate_registration(array $post): array
{
    $errors = [];
    $values = [];

    $values['entry_type'] = is_string($post['entry_type'] ?? null) ? $post['entry_type'] : '';
    if (!in_array($values['entry_type'], ['solo', 'team'], true)) {
        $errors['entry_type'] = 'Choose solo or team registration.';
    }
    $values['team_members'] = [];
    if ($values['entry_type'] === 'team') {
        $rawMembers = $post['team_members'] ?? null;
        if (!is_array($rawMembers) || $rawMembers === []) {
            $errors['team_members'] = 'Add at least one teammate name.';
        } else {
            foreach ($rawMembers as $rawMember) {
                $name = is_string($rawMember) ? normalized_text($rawMember) : '';
                $values['team_members'][] = $name;
                if (character_count($name) < 2 || character_count($name) > 120 || preg_match('/[\p{C}]/u', $name)) {
                    $errors['team_members'] = 'Each teammate name must be 2–120 characters.';
                }
            }
            if (count(array_unique($values['team_members'])) !== count($values['team_members'])) {
                $errors['team_members'] = 'Enter each teammate only once.';
            }
        }
    }

    foreach (['full_name', 'college_name', 'college_city'] as $key) {
        $raw = $post[$key] ?? '';
        $values[$key] = is_string($raw) ? normalized_text($raw) : '';
    }
    foreach ([
        'full_name' => ['Full name', 2, 120],
        'college_name' => ['College or institution name', 2, 160],
        'college_city' => ['College city', 2, 100],
    ] as $key => [$label, $min, $max]) {
        $length = character_count($values[$key]);
        if ($length < $min || $length > $max || preg_match('/[\p{C}]/u', $values[$key])) {
            $errors[$key] = "$label must be between $min and $max characters.";
        }
    }

    $rawEmail = $post['email'] ?? '';
    $values['email'] = is_string($rawEmail) ? strtolower(trim($rawEmail)) : '';
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    $rawPhone = $post['mobile'] ?? '';
    $values['mobile'] = is_string($rawPhone) ? trim($rawPhone) : '';
    $normalizedPhone = normalize_phone($values['mobile']);
    if ($normalizedPhone === null) {
        $errors['mobile'] = 'Enter a valid mobile number. Indian numbers can be entered as 10 digits or with +91.';
    }

    $rawYear = $post['year_of_study'] ?? '';
    $values['year_of_study'] = is_string($rawYear) ? $rawYear : '';
    if (!in_array($values['year_of_study'], ['1', '2', '3', '4', '5'], true)) {
        $errors['year_of_study'] = 'Select your year of study.';
    }

    $values['consent'] = ($post['consent'] ?? null) === '1' ? '1' : '';
    if ($values['consent'] !== '1') {
        $errors['consent'] = 'Please agree to the registration data use notice.';
    }

    if ($normalizedPhone !== null) {
        $values['mobile_normalized'] = $normalizedPhone;
    }
    return [$values, $errors];
}

function throttle_exceeded(): bool
{
    $now = time();
    $attempts = array_values(array_filter(
        $_SESSION['attempt_times'] ?? [],
        static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp > $now - 900
    ));
    if (count($attempts) >= 8) {
        $_SESSION['attempt_times'] = $attempts;
        return true;
    }
    $attempts[] = $now;
    $_SESSION['attempt_times'] = $attempts;
    return false;
}

function flash_form(array $values, array $errors, ?string $general = null): void
{
    unset($values['mobile_normalized']);
    $_SESSION['form_values'] = $values;
    $_SESSION['form_errors'] = $errors;
    if ($general !== null) {
        $_SESSION['form_general'] = $general;
    }
}
