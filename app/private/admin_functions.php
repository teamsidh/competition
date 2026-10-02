<?php
declare(strict_types=1);

function admin_csrf_valid(array $post): bool
{
    return isset($post['csrf_token']) && is_string($post['csrf_token'])
        && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $post['csrf_token']);
}

function admin_authenticated(?string $passwordHash = null): bool
{
    $lastActivity = $_SESSION['admin_last_activity'] ?? 0;
    if (($_SESSION['admin_authenticated'] ?? false) !== true || !is_int($lastActivity)) {
        return false;
    }
    if ($lastActivity < time() - 1800) {
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_last_activity'], $_SESSION['admin_auth_tag']);
        return false;
    }
    if ($passwordHash !== null && !hash_equals(
        hash('sha256', $passwordHash),
        (string) ($_SESSION['admin_auth_tag'] ?? '')
    )) {
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_last_activity'], $_SESSION['admin_auth_tag']);
        return false;
    }
    $_SESSION['admin_last_activity'] = time();
    return true;
}

function admin_require_auth(array $config, PDO $pdo): void
{
    if (!admin_authenticated()) {
        redirect_to($config, 'admin/');
    }
    $hash = $pdo->query('SELECT password_hash FROM admin_auth WHERE id = 1 LIMIT 1')->fetchColumn();
    if ($hash === false || !admin_authenticated((string) $hash)) {
        redirect_to($config, 'admin/');
    }
}

function admin_complete_login(string $passwordHash): void
{
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_auth_tag'] = hash('sha256', $passwordHash);
}

function admin_attempt_key(array $config): string
{
    return hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), (string) $config['DB_PASSWORD']);
}

function admin_too_many_attempts(PDO $pdo, array $config, string $purpose, int $limit = 3, int $windowSeconds = 900): bool
{
    $query = $pdo->prepare(
        'SELECT COUNT(*) FROM admin_login_attempts
         WHERE ip_hash = ? AND purpose = ? AND attempted_at > ?'
    );
    $query->execute([admin_attempt_key($config), $purpose, gmdate('Y-m-d H:i:s', time() - $windowSeconds)]);
    return (int) $query->fetchColumn() >= $limit;
}

function admin_record_failure(PDO $pdo, array $config, string $purpose): void
{
    $query = $pdo->prepare(
        'INSERT INTO admin_login_attempts (ip_hash, purpose, attempted_at) VALUES (?, ?, UTC_TIMESTAMP())'
    );
    $query->execute([admin_attempt_key($config), $purpose]);
    // Keep the small rate-limit table from growing indefinitely.
    if (random_int(1, 20) === 1) {
        $pdo->exec('DELETE FROM admin_login_attempts WHERE attempted_at < UTC_TIMESTAMP() - INTERVAL 1 DAY');
    }
}

function admin_clear_failures(PDO $pdo, array $config, string $purpose): void
{
    $query = $pdo->prepare('DELETE FROM admin_login_attempts WHERE ip_hash = ? AND purpose = ?');
    $query->execute([admin_attempt_key($config), $purpose]);
}

function admin_search_term(mixed $raw): string
{
    if (!is_string($raw) || strlen($raw) > 320 || !preg_match('//u', $raw)) {
        return '';
    }
    $term = trim($raw);
    if (character_count($term) > 100 || preg_match('/[\p{C}]/u', $term)) {
        return '';
    }
    return $term;
}

/** @return array{0: string, 1: array<int, string>} */
function admin_search_filter(string $term): array
{
    if ($term === '') {
        return ['', []];
    }
    $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    $pattern = '%' . $escaped . '%';
    $columns = ['reference', 'full_name', 'email_normalized', 'mobile_e164', 'college_name', 'college_city'];
    $parts = array_map(static fn (string $column): string => "$column LIKE ? ESCAPE '!'", $columns);
    return [' WHERE ' . implode(' OR ', $parts), array_fill(0, count($columns), $pattern)];
}

function admin_csv_cell(mixed $value): string
{
    $text = (string) $value;
    return preg_match('/^\s*[=+\-@]|^[\t\r\n]/u', $text) ? "'" . $text : $text;
}

function admin_valid_password(string $password): bool
{
    return preg_match('//u', $password) === 1
        && character_count($password) >= 12
        && strlen($password) <= 72;
}

function admin_new_otp(): string
{
    return str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
}

function admin_otp_hash(array $config, string $otp): string
{
    return hash_hmac('sha256', 'admin-reset:' . $otp, (string) $config['DB_PASSWORD']);
}
