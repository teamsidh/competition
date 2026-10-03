<?php
declare(strict_types=1);

function database(array $config): PDO
{
    $host = (string) ($config['DB_HOST'] ?? '');
    $port = (string) ($config['DB_PORT'] ?? '3306');
    $name = (string) ($config['DB_NAME'] ?? '');
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    return new PDO($dsn, (string) ($config['DB_USER'] ?? ''), (string) ($config['DB_PASSWORD'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function save_registration(PDO $pdo, array $values): array
{
    $insert = $pdo->prepare(
        'INSERT INTO registrations
         (reference, entry_type, team_members_json, full_name, email_normalized, mobile_e164, college_name, college_city, year_of_study, consented_at, created_at)
         VALUES (:reference, :entry_type, :team_members_json, :full_name, :email, :mobile, :college_name, :college_city, :year_of_study, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
    );
    $emailExists = $pdo->prepare('SELECT 1 FROM registrations WHERE email_normalized = ? LIMIT 1');

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $reference = 'DBAC-' . strtoupper(bin2hex(random_bytes(8)));
        try {
            $insert->execute([
                'reference' => $reference,
                'entry_type' => $values['entry_type'],
                'team_members_json' => $values['entry_type'] === 'team' ? json_encode($values['team_members'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null,
                'full_name' => $values['full_name'],
                'email' => $values['email'],
                'mobile' => $values['mobile_normalized'],
                'college_name' => $values['college_name'],
                'college_city' => $values['college_city'],
                'year_of_study' => (int) $values['year_of_study'],
            ]);
            return ['status' => 'saved', 'reference' => $reference];
        } catch (PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1062) {
                throw $exception;
            }
            $emailExists->execute([$values['email']]);
            if ($emailExists->fetchColumn()) {
                return ['status' => 'duplicate'];
            }
            // A rare reference collision: generate a fresh random reference.
        }
    }
    throw new RuntimeException('Unable to generate a unique registration reference.');
}
