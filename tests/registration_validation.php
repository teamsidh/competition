<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/private/functions.php';

$base = [
    'entry_type' => 'solo',
    'full_name' => 'Asha Kumar',
    'email' => 'Asha@example.com',
    'mobile' => '9876543210',
    'college_name' => 'School of Architecture',
    'college_city' => 'Noida',
    'year_of_study' => '3',
    'consent' => '1',
];
[$solo, $errors] = validate_registration($base);
assert($errors === []);
assert($solo['entry_type'] === 'solo' && $solo['team_members'] === []);
assert($solo['mobile_normalized'] === '+919876543210');

[$team, $errors] = validate_registration($base + ['team_members' => ['First Teammate']]);
assert(isset($errors['team_members']) === false); // Solo input ignores hidden teammate fields.

[$team, $errors] = validate_registration(array_replace($base, ['entry_type' => 'team', 'team_members' => ['First Teammate', 'Second Teammate']]));
assert($errors === []);
assert($team['team_members'] === ['First Teammate', 'Second Teammate']);

[, $errors] = validate_registration(array_replace($base, ['entry_type' => 'team', 'team_members' => ['']]));
assert(isset($errors['team_members']));
[, $errors] = validate_registration(array_replace($base, ['entry_type' => 'team', 'team_members' => ['Same Name', 'Same Name']]));
assert(isset($errors['team_members']));
echo "Registration validation passed.\n";
