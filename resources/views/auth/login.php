<?php
ob_start();
?>
<div class="auth-card">
    <h1><?= htmlspecialchars(trans('login_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <form action="<?= url('login') ?>" method="post">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
