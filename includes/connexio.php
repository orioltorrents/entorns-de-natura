<?php
require_once dirname(__DIR__) . '/config/app.php';

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST', 'localhost'),
        env('DB_NAME', 'entorns_de_natura')
    );

    $conexio = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''));
    $conexio->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexio->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    throw new RuntimeException('No s’ha pogut establir la connexió a la base de dades.');
}
