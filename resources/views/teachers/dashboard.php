<?php
ob_start();
?>
<section class="teacher-dashboard__hero">
    <div>
        <p class="teacher-dashboard__eyebrow">Espai de professorat</p>
        <h1 class="teacher-dashboard__title">Projectes de les teves classes</h1>
        <p class="teacher-dashboard__text">Consulta ràpidament els projectes assignats a les classes on fas docència.</p>
    </div>
</section>

<?php if (empty($classes)): ?>
    <div class="empty-state teacher-dashboard__empty">
        <p>Encara no tens projectes assignats a cap classe.</p>
    </div>
<?php else: ?>
    <div class="teacher-dashboard__classes">
        <?php foreach ($classes as $class): ?>
            <section class="teacher-dashboard__class">
                <div class="teacher-dashboard__class-header">
                    <h2 class="teacher-dashboard__class-title"><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="status teacher-dashboard__class-count"><?= count($class['projects']) ?> projectes</span>
                </div>
                <div class="teacher-dashboard__projects">
                <?php foreach ($class['projects'] as $project): ?>
                    <?php $projectAsset = $project['assets'][0] ?? null; ?>
                    <article class="teacher-project-card">
                        <?php if (!empty($projectAsset['logo_path'])): ?>
                            <div class="teacher-project-card__media">
                                <img class="teacher-project-card__logo" src="<?= url((string) $projectAsset['logo_path']) ?>" alt="<?= htmlspecialchars((string) $projectAsset['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="teacher-project-card__body">
                            <div class="teacher-project-card__header">
                                <h3 class="teacher-project-card__title"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="teacher-project-card__status status"><?= htmlspecialchars($project['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if ($project['description'] !== ''): ?>
                                <p class="teacher-project-card__text"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <div class="teacher-project-card__actions">
                                <a class="button teacher-project-card__button" href="<?= url('ca/projectes/' . $project['slug']) ?>">Obre</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
