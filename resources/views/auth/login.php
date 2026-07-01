<?php
ob_start();
?>
<div class="auth-card">
    <h1><?= htmlspecialchars(trans('login_title'), ENT_QUOTES, 'UTF-8') ?></h1>

    <?php if (!empty($error)): ?>
        <p class="form-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form action="<?= url('login') ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label for="password">Contrasenya</label>
        <input type="password" name="password" id="password" required>

        <button type="submit"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
