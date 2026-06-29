<?php
ob_start();
?>
<?php if ($project === null): ?>
    <section class="page-header">
        <h1>Projecte no trobat</h1>
        <p>El projecte sol.licitat no existeix o no esta publicat.</p>
        <a class="button" href="<?= url('ca/projectes') ?>">Torna als projectes</a>
    </section>
<?php else: ?>
    <article class="project-detail">
        <p class="breadcrumb"><a href="<?= url('ca/projectes') ?>">Projectes</a></p>
        <h1><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (!empty($project['description'])): ?>
            <p class="lead"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <dl class="meta-list">
            <div>
                <dt>Identificador</dt>
                <dd><?= htmlspecialchars($project['slug'], ENT_QUOTES, 'UTF-8') ?></dd>
            </div>
            <div>
                <dt>Estat</dt>
                <dd>actiu</dd>
            </div>
        </dl>
    </article>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
