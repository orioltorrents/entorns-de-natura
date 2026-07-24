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
    <?php $editionQuery = !empty($projectAcademicYearId) ? '?edicio=' . (int) $projectAcademicYearId : ''; ?>
    <?php $academicYearName = (string) ($projectAcademicYear['academic_year_name'] ?? ''); ?>
    <?php $teamCodes = array_values(array_filter(array_map('strval', $context['team_codes'] ?? []))); ?>
    <?php $projectRoles = array_values(array_filter(array_map('strval', $context['project_roles'] ?? []))); ?>
    <article class="public-project-detail">
        <p class="breadcrumb public-project-detail__breadcrumb"><a href="<?= url(getLanguage() . '/projectes/' . $project['slug']) . $editionQuery ?>">Torna al projecte</a></p>
        <div class="public-project-detail__hero project-tasks-hero">
            <div>
                <h1 class="public-project-detail__title">Les teves tasques del <?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="project-task-context" aria-label="Context de les tasques">
                    <span class="project-task-context__pill">
                        <span>Projecte i curs</span>
                        <strong><?= htmlspecialchars((string) $project['title'], ENT_QUOTES, 'UTF-8') ?><?= $academicYearName !== '' ? ' · ' . htmlspecialchars($academicYearName, ENT_QUOTES, 'UTF-8') : '' ?></strong>
                    </span>
                    <span class="project-task-context__pill">
                        <span>El teu codi d'equip</span>
                        <strong><?= htmlspecialchars($teamCodes !== [] ? implode(', ', $teamCodes) : 'Sense equip assignat', ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                    <span class="project-task-context__pill">
                        <span>El teu rol d'equip</span>
                        <strong><?= htmlspecialchars($projectRoles !== [] ? implode(', ', $projectRoles) : 'Sense rol assignat', ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </div>
            </div>
        </div>
    </article>

    <?php if (!empty($tasks)): ?>
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
                                <div class="project-task-item__header">
                                    <strong><?= htmlspecialchars((string) $task['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($task['task_url'])): ?>
                                        <a class="project-task-item__classroom-link" href="<?= htmlspecialchars((string) $task['task_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Obrir tasca a Classroom" title="Obrir tasca a Classroom">↗</a>
                                    <?php endif; ?>
                                </div>
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
