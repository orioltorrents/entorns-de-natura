<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/layout.php';

$email = $_POST['email'] ?? '';
$contrasenya = $_POST['contrasenya'] ?? '';
$error = '';

try {
    require_once dirname(__DIR__) . '/includes/connexio.php';

    $stmt = $conexio->prepare('SELECT * FROM usuaris WHERE email = :email AND actiu = 1');
    $stmt->execute([':email' => $email]);
    $usuari = $stmt->fetch();

    if ($usuari && password_verify($contrasenya, $usuari['contrasenya'])) {
        $_SESSION['id'] = $usuari['id'];
        $_SESSION['usuari'] = $usuari['email'];
        $_SESSION['rol'] = $usuari['rol'];

        require_once dirname(__DIR__) . '/includes/log.php';
        registraLog($conexio, $usuari, 'login');

        header('Location: ' . url('projectes/projectes.php'));
        exit;
    }

    $error = $t['incorrect_credentials'] ?? 'Credencials incorrectes';
} catch (Throwable $e) {
    $error = $t['database_error'] ?? 'Error de connexió a la base de dades';
}

ob_start();
?>
<div class="auth-card">
    <h1><?= htmlspecialchars($t['login_title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <a class="button secondary" href="<?= url('auth/login.php') ?>">Tornar</a>
</div>
<?php
$content = ob_get_clean();
renderLayout($t['login_title'], $content, 'login');
