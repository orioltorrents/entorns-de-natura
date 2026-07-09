<?php
ob_start();
?>
<div class="hero hero--home">
    <h1 class="hero__title"><?= htmlspecialchars(trans('home_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="hero__text"><?= htmlspecialchars(trans('home_intro'), ENT_QUOTES, 'UTF-8') ?></p>
    <div class="actions hero__actions">
        <a class="button" href="<?= url('login') ?>"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></a>
        <a class="button button--secondary" href="<?= url('ca/projectes') ?>"><?= htmlspecialchars(trans('projects'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
