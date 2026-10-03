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
    error_log('Admin export unavailable.');
    http_response_code(503);
    exit('Export is temporarily unavailable.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_csrf_valid($_POST)) {
    http_response_code(405);
    exit('Export request rejected.');
}

$term = admin_search_term($_POST['q'] ?? '');
[$where, $params] = admin_search_filter($term);
try {
    $query = $pdo->prepare(
        'SELECT reference, entry_type, team_members_json, full_name, email_normalized, mobile_e164, college_name, college_city,
                year_of_study, consented_at, created_at FROM registrations' . $where .
        ' ORDER BY created_at DESC, id DESC'
    );
    $query->execute($params);
} catch (Throwable $exception) {
    error_log('Admin export query failed.');
    http_response_code(503);
    exit('Export is temporarily unavailable.');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="dezignbank-registrations-' . gmdate('Ymd') . '.csv"');
header('Cache-Control: no-store, private');
$output = fopen('php://output', 'wb');
if ($output === false) {
    http_response_code(503);
    exit('Export is temporarily unavailable.');
}
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Reference', 'Entry type', 'Team members', 'Lead full name', 'Email', 'Mobile', 'College', 'College city', 'Year of study', 'Consented at UTC', 'Registered at UTC']);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $members = json_decode((string) ($row['team_members_json'] ?? ''), true);
    $row['team_members_json'] = is_array($members) ? implode('; ', $members) : '';
    fputcsv($output, array_map('admin_csv_cell', array_values($row)));
}
fclose($output);
