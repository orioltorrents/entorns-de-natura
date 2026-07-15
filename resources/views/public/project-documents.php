<?php
ob_start();
$languagePrefix = getLanguage();
?>
<section class="page-header">
    <?php if (!empty($project['slug'])): ?>
        <p class="breadcrumb"><a href="<?= url($languagePrefix . '/projectes/' . $project['slug']) ?>">Torna al projecte</a></p>
        <h1>Documents de <?= htmlspecialchars((string) $project['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if (!empty($projectAcademicYear['academic_year_name'])): ?>
            <p class="status">Curs: <?= htmlspecialchars((string) $projectAcademicYear['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    <?php else: ?>
        <h1>Documents no trobats</h1>
    <?php endif; ?>
    <p>Vista de prova per veure documents i fragments segons el context de sessió.</p>
</section>

<section class="card" style="padding: 1rem; margin-bottom: 1rem;">
    <h2>Context actual</h2>
    <p>Usuari: <?= !empty($context['user']['email']) ? htmlspecialchars((string) $context['user']['email'], ENT_QUOTES, 'UTF-8') : 'visitant' ?></p>
    <p>Rols: <?= !empty($context['roles']) ? htmlspecialchars(implode(', ', $context['roles']), ENT_QUOTES, 'UTF-8') : 'cap' ?></p>
    <p>Admin: <?= !empty($context['is_admin']) ? 'sí' : 'no' ?> | Professor: <?= !empty($context['is_teacher']) ? 'sí' : 'no' ?> | Alumne: <?= !empty($context['is_student']) ? 'sí' : 'no' ?> | Professor assignat: <?= !empty($context['is_assigned_teacher']) ? 'sí' : 'no' ?></p>
</section>

<?php if (empty($documents)): ?>
    <div class="empty-state">
        <p>No hi ha documents publicats per aquest projecte.</p>
    </div>
<?php else: ?>
    <div class="stack" style="display: grid; gap: 1rem;">
        <?php foreach ($documents as $document): ?>
            <article class="card" style="padding: 1rem; opacity: <?= !empty($document['is_visible']) ? '1' : '0.5' ?>;">
                <div class="admin-panel__header">
                    <div>
                        <p class="eyebrow">Document</p>
                        <h2><?= htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <span class="status"><?= !empty($document['is_visible']) ? 'visible' : 'ocult' ?></span>
                </div>
                <p><strong>Slug:</strong> <?= htmlspecialchars((string) $document['slug'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Visibilitat base:</strong> <?= htmlspecialchars((string) $document['default_visibility'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (!empty($context['is_admin']) || !empty($context['is_teacher']) || !empty($context['is_assigned_teacher'])): ?>
                    <p><strong>Notes:</strong> <?= htmlspecialchars((string) ($document['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <?php if (!empty($document['sources'])): ?>
                    <h3>Fonts</h3>
                    <ul>
                        <?php foreach ($document['sources'] as $source): ?>
                            <li>
                                <?= htmlspecialchars((string) $source['source_type'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($source['source_url'])): ?>
                                    - <a href="<?= htmlspecialchars((string) $source['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">obrir</a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h3>Fragments</h3>
                <?php if (empty($document['fragments'])): ?>
                    <p>Sense fragments.</p>
                <?php else: ?>
                    <div class="stack" style="display: grid; gap: 0.75rem;">
                        <?php foreach ($document['fragments'] as $fragment): ?>
                            <section class="card" style="padding: 0.75rem; background: <?= !empty($fragment['is_visible']) ? '#fff' : '#f7f7f7' ?>; border: 1px solid #ddd;">
                                <div class="admin-panel__header">
                                    <div>
                                        <h4><?= htmlspecialchars((string) $fragment['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                        <p class="muted">Key: <?= htmlspecialchars((string) $fragment['fragment_key'], ENT_QUOTES, 'UTF-8') ?> | Format: <?= htmlspecialchars((string) $fragment['content_format'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                    <span class="status"><?= !empty($fragment['is_visible']) ? 'visible' : 'ocult' ?></span>
                                </div>
                                <?php if (!empty($fragment['is_visible'])): ?>
                                    <div class="prose">
                                        <?= nl2br(htmlspecialchars((string) ($fragment['content'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php else: ?>
                                    <p class="muted">Fragment no visible per al context actual.</p>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
