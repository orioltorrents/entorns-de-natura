<?php

declare(strict_types=1);

$pdo = require dirname(__DIR__) . '/config/database.php';

$stmt = $pdo->query('SELECT COUNT(*) AS total FROM users');
$result = $stmt->fetch();

echo '<h1>Connexió correcta</h1>';
echo '<p>La base de dades està connectada.</p>';
echo '<p>Usuaris registrats: ' . htmlspecialchars((string) $result['total']) . '</p>';