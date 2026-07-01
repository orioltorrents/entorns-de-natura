<?php
ob_start();
?>
<section class="page-header">
    <h1><?= htmlspecialchars(trans('projects_title'), ENT_QUOTES, 'UTF-8') ?></h1>
</section>

<?php if (empty($projects)): ?>
    <div class="empty-state">
        <p>No hi ha projectes publicats en aquest moment.</p>
    </div>
<?php else: ?>
    <div class="project-grid">
        <?php foreach ($projects as $project): ?>
            <article class="card project-card">
                <h2><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (!empty($project['description'])): ?>
                    <p><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="card-actions">
                    <span class="status">actiu</span>
                    <a class="button" href="<?= url('ca/projectes/' . $project['slug']) ?>">Obre</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
