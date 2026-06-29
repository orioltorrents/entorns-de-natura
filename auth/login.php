<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/layout.php';

if (isset($_SESSION['usuari'])) {
    header('Location: ' . url('projectes/projectes.php'));
    exit;
}

ob_start();
?>
<div class="auth-card">
    <h1><?= htmlspecialchars($t['login_title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <form action="<?= url('auth/validar.php') ?>?lang=<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>" method="post">
        <label for="email"><?= htmlspecialchars($t['user'], ENT_QUOTES, 'UTF-8') ?></label>
        <input type="email" name="email" id="email" required>

        <label for="password"><?= htmlspecialchars($t['password'], ENT_QUOTES, 'UTF-8') ?></label>
        <input type="password" name="contrasenya" id="password" required>

        <button type="submit"><?= htmlspecialchars($t['enter'], ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
<?php
$content = ob_get_clean();
renderLayout($t['login_title'], $content, 'login');
