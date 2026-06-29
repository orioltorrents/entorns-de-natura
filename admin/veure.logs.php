<?php
session_start();
require '../includes/connexio.php';

// ⚠️ Només admin pot accedir
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Obtenim els logs
$stmt = $conexio->prepare("SELECT * FROM logs ORDER BY data_hora DESC LIMIT 100");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <title>Registres d'accés</title>
  <style>
    table { border-collapse: collapse; width: 100%; font-size: 0.9em; }
    th, td { padding: 6px 8px; border: 1px solid #ccc; }
    th { background-color: #eee; }
  </style>
</head>
<body>
  <h1>📋 Registres d'accés (últims 100)</h1>
  <table>
    <thead>
      <tr>
        <th>Data i hora</th>
        <th>Usuari</th>
        <th>Email</th>
        <th>Rol</th>
        <th>Acció</th>
        <th>IP</th>
        <th>Navegador</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
      <tr>
        <td><?php echo $log['data_hora']; ?></td>
        <td><?php echo htmlspecialchars($log['nom_usuari']); ?></td>
        <td><?php echo htmlspecialchars($log['email']); ?></td>
        <td><?php echo $log['rol']; ?></td>
        <td><?php echo $log['accio']; ?></td>
        <td><?php echo $log['ip']; ?></td>
        <td><?php echo substr($log['navegador'], 0, 60); ?>...</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
