<?php
ob_start();
?>
<section class="dashboard-header">
    <h1>Dashboard professorat</h1>
    <p>Projectes assignats a les classes on fas docencia.</p>
</section>

<?php if (empty($classes)): ?>
    <div class="empty-state">
        <p>Encara no tens projectes assignats a cap classe.</p>
    </div>
<?php else: ?>
    <?php foreach ($classes as $class): ?>
        <section class="class-projects">
            <h2><?= htmlspecialchars($class['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="project-grid">
                <?php foreach ($class['projects'] as $project): ?>
                    <article class="card project-card">
                        <h3><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <?php if ($project['description'] !== ''): ?>
                            <p><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <div class="card-actions">
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
