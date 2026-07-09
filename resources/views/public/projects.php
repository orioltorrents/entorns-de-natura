<?php
ob_start();
?>
<section class="public-projects__hero">
    <p class="public-projects__eyebrow">Projectes</p>
    <h1 class="public-projects__title"><?= htmlspecialchars(trans('projects_title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="public-projects__text">Consulta els projectes del centre i entra a cada proposta per veure'n el detall.</p>
</section>

<?php if (empty($projects)): ?>
    <div class="empty-state public-projects__empty">
        <p>No hi ha projectes publicats en aquest moment.</p>
    </div>
<?php else: ?>
    <div class="public-projects__grid">
        <?php foreach ($projects as $project): ?>
            <article class="public-project-card">
                <div class="public-project-card__header">
                    <h2 class="public-project-card__title"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="public-project-card__status status">actiu</span>
                </div>
                <?php if (!empty($project['description'])): ?>
                    <p class="public-project-card__text"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="public-project-card__actions">
                    <a class="button public-project-card__button" href="<?= url('ca/projectes/' . $project['slug']) ?>">Obre</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
