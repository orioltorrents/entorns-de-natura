<?php
ob_start();
?>
<?php if (($project ?? null) === null): ?>
    <section class="page-header">
        <h1>Tasques no trobades</h1>
        <p>El projecte sol.licitat no existeix o no esta publicat.</p>
        <a class="button" href="<?= url('ca/projectes') ?>">Torna als projectes</a>
    </section>
<?php else: ?>
    <article class="public-project-detail">
        <p class="breadcrumb public-project-detail__breadcrumb"><a href="<?= url(getLanguage() . '/projectes/' . $project['slug']) ?>">Torna al projecte</a></p>
        <div class="public-project-detail__hero">
            <div>
                <p class="public-project-detail__eyebrow">Projecte</p>
                <h1 class="public-project-detail__title">Tasques de <?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="lead public-project-detail__lead">Consulta les fases i tasques associades al projecte.</p>
            </div>
            <span class="public-project-detail__status status">tasques</span>
        </div>
    </article>

    <?php if (!empty($tasks)): ?>
        <?php if (!empty($context['roles'])): ?>
            <p class="status" style="margin-bottom: 1rem;">Context: <?= htmlspecialchars(implode(', ', $context['roles']), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="project-task-phases">
            <?php foreach ($tasks as $phase): ?>
                <article class="project-task-phase card">
                    <div class="project-task-phase__header">
                        <h2 class="project-task-phase__title"><?= htmlspecialchars((string) $phase['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!empty($phase['description'])): ?>
                            <p class="project-task-phase__description"><?= htmlspecialchars((string) $phase['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>

                    <ul class="project-task-list">
                        <?php foreach ($phase['items'] as $task): ?>
                            <li class="project-task-item">
                                <strong><?= htmlspecialchars((string) $task['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($task['description'])): ?>
                                    <p><?= htmlspecialchars((string) $task['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No hi ha tasques visibles per al teu context actual.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
