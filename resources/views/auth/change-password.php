<?php
ob_start();
?>
<div class="auth-card auth-card--password-change">
    <h1>Canviar contrasenya</h1>
    <p class="auth-card__intro">Abans de continuar, has de substituir la contrasenya inicial per una contrasenya pròpia.</p>

    <?php if (!empty($error)): ?>
        <p class="form-error"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form class="form" action="<?= url('canviar-contrasenya') ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label class="form__field" for="current_password">Contrasenya actual</label>
        <input class="form__control" type="password" name="current_password" id="current_password" required autocomplete="current-password">

        <label class="form__field" for="new_password">Nova contrasenya</label>
        <input class="form__control" type="password" name="new_password" id="new_password" required minlength="8" autocomplete="new-password">

        <label class="form__field" for="new_password_confirmation">Repeteix la nova contrasenya</label>
        <input class="form__control" type="password" name="new_password_confirmation" id="new_password_confirmation" required minlength="8" autocomplete="new-password">

        <button class="button" type="submit">Guardar contrasenya</button>
    </form>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
