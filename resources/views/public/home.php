<?php
ob_start();
?>
<div class="hero">
    <h1><?= htmlspecialchars(trans('home_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars(trans('home_intro'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="actions">
        <a class="button" href="<?= url('login') ?>"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="button button--secondary" href="<?= url('ca/projectes') ?>"><?= htmlspecialchars(trans('projects'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
