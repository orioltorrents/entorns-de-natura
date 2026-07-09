<?php
ob_start();
?>
<section class="dashboard-header dashboard-header--student">
    <h1 class="dashboard-header__title">Dashboard alumne</h1>
    <p class="dashboard-header__text">Projectes assignats a les teves classes.</p>
</section>

<?php if (empty($classes)): ?>
    <div class="empty-state">
        <p>Encara no tens projectes assignats.</p>
    </div>
<?php else: ?>
    <?php foreach ($classes as $class): ?>
        <section class="class-projects class-projects--student">
            <h2 class="class-projects__title"><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="project-grid project-grid--cards">
                <?php foreach ($class['projects'] as $project): ?>
                    <article class="card project-card project-card--item">
                        <h3 class="project-card__title"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if ($project['description'] !== ''): ?>
                            <p><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <div class="card-actions project-card__actions">
                            <span class="status"><?= htmlspecialchars($project['status'], ENT_QUOTES, 'UTF-8') ?></span>
                            <a class="button" href="<?= url('ca/projectes/' . $project['slug']) ?>">Obre</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
