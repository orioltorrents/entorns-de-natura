<?php

declare(strict_types=1);

$envPath = dirname(__DIR__) . '/.env';

if (!file_exists($envPath)) {
    throw new RuntimeException('No s’ha trobat el fitxer .env');
}

$env = parse_ini_file($envPath);

if ($env === false) {
    throw new RuntimeException('No s’ha pogut llegir el fitxer .env');
}

$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? $env['DB_PASS'] ?? '';
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

$dbNames = array_values(array_unique(array_filter([
    $env['DB_NAME'] ?? '',
    $env['DB_NAME_FALLBACK'] ?? $env['DB_NAME_ALT'] ?? '',
    'entorns_natura_dev',
    'entorns_de_natura',
], static function ($value): bool {
    return $value !== '';
})));

if ($dbNames === []) {
    throw new RuntimeException('No s’ha configurat cap base de dades a .env');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$lastException = null;
foreach ($dbNames as $dbname) {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    try {
        return new PDO($dsn, $user, $password, $options);
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

throw new RuntimeException('Error de connexió amb la base de dades. S’ha provat amb: ' . implode(', ', $dbNames) . '. Detall: ' . ($lastException ? $lastException->getMessage() : 'sense detall'));
