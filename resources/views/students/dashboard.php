<?php
ob_start();
?>
<section class="student-dashboard__hero">
    <div>
        <p class="student-dashboard__eyebrow">Espai d'alumne</p>
        <h1 class="student-dashboard__title">Els teus projectes</h1>
        <p class="student-dashboard__text">Projectes assignats a les teves classes, ordenats per grup i amb accés ràpid a cada fitxa.</p>
    </div>
</section>

<?php if (empty($classes)): ?>
    <div class="empty-state student-dashboard__empty">
        <p>Encara no tens projectes assignats.</p>
    </div>
<?php else: ?>
    <div class="student-dashboard__classes">
        <?php foreach ($classes as $class): ?>
            <section class="student-dashboard__class">
                <div class="student-dashboard__class-header">
                    <h2 class="student-dashboard__class-title"><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="status student-dashboard__class-count"><?= count($class['projects']) ?> projectes</span>
                </div>
                <div class="student-dashboard__projects">
                <?php foreach ($class['projects'] as $project): ?>
                    <?php $projectAsset = $project['logo_asset'] ?? ($project['assets'][0] ?? null); ?>
                    <article class="student-project-card">
                        <?php if (!empty($projectAsset['logo_path'])): ?>
                            <div class="student-project-card__media">
                                <img class="student-project-card__logo" src="<?= url((string) $projectAsset['logo_path']) ?>" alt="<?= htmlspecialchars((string) $projectAsset['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="student-project-card__body">
                            <div class="student-project-card__header">
                                <h3 class="student-project-card__title"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="student-project-card__status status"><?= htmlspecialchars($project['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if ($project['description'] !== ''): ?>
                                <p class="student-project-card__text"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <div class="student-project-card__actions">
                                <a class="button student-project-card__button" href="<?= url('ca/projectes/' . $project['slug']) ?>">Obre</a>
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
