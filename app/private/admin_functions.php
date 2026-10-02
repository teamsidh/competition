<?php
declare(strict_types=1);

function admin_csrf_valid(array $post): bool
{
    return isset($post['csrf_token']) && is_string($post['csrf_token'])
        && hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $post['csrf_token']);
}

function admin_authenticated(): bool
{
    $lastActivity = $_SESSION['admin_last_activity'] ?? 0;
    if (($_SESSION['admin_authenticated'] ?? false) !== true || !is_int($lastActivity)) {
        return false;
    }
    if ($lastActivity < time() - 1800) {
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_last_activity']);
        return false;
    }
    $_SESSION['admin_last_activity'] = time();
    return true;
}

function admin_require_auth(array $config): void
{
    if (!admin_authenticated()) {
        redirect_to($config, 'admin/');
    }
}

function admin_attempt_key(array $config): string
{
    return hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''), (string) $config['DB_PASSWORD']);
}

function admin_too_many_attempts(PDO $pdo, array $config, string $purpose): bool
{
    $query = $pdo->prepare(
        'SELECT COUNT(*) FROM admin_login_attempts
         WHERE ip_hash = ? AND purpose = ? AND attempted_at > UTC_TIMESTAMP() - INTERVAL 15 MINUTE'
    );
    $query->execute([admin_attempt_key($config), $purpose]);
    return (int) $query->fetchColumn() >= 5;
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
