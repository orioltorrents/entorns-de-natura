<?php
session_start();
require '../includes/connexio.php';
require '../includes/translations.php';

// Si no hi ha sessió iniciada, redirigeix a login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$missatge = '';

// Quan s’envia el formulari
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova = $_POST['nova_contrasenya'] ?? '';
    $confirma = $_POST['confirma_contrasenya'] ?? '';

    if (strlen($nova) < 6) {
        $error = "La contrasenya ha de tenir almenys 6 caràcters.";
    } elseif ($nova !== $confirma) {
        $error = "Les contrasenyes no coincideixen.";
    } else {
        try {
            $hash = password_hash($nova, PASSWORD_DEFAULT);
            $stmt = $conexio->prepare("UPDATE usuaris SET contrasenya = :contrasenya, contrasenya_temporal = 0 WHERE id = :id");
            $stmt->bindParam(':contrasenya', $hash);
            $stmt->bindParam(':id', $_SESSION['id']);
            $stmt->execute();

            // Redirigim a inici després de canviar-la
            header("Location: ../projectes/projectes.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error en guardar la nova contrasenya.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'ca'; ?>">
<head>
  <meta charset="UTF-8">
  <title><?php echo $t['titol_login']; ?></title>
</head>
<body>
  <h1><?php echo $t['titol_login']; ?></h1>

  <?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
  <?php endif; ?>

  <form method="post">
    <label><?php echo $t['contrasenya']; ?> nova:</label>
    <input type="password" name="nova_contrasenya" required><br><br>

    <label>Confirma <?php echo $t['contrasenya']; ?>:</label>
    <input type="password" name="confirma_contrasenya" required><br><br>

    <button type="submit"><?php echo $t['entrar']; ?></button>
  </form>
</body>
</html>
