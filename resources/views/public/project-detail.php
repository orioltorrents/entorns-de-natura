<?php
ob_start();
$gradeAchievementClass = static function (array $grade): string {
    if (($grade['type'] ?? '') !== 'achievement' || empty($grade['achievement_value'])) {
        return '';
    }

    $value = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $grade['achievement_value']));

    if (!is_string($value) || $value === '') {
        return '';
    }

    return ' grade-achievement--' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
};
?>
<?php if ($project === null): ?>
    <section class="page-header">
        <h1>Projecte no trobat</h1>
        <p>El projecte sol.licitat no existeix o no esta publicat.</p>
        <a class="button" href="<?= url('ca/projectes') ?>">Torna als projectes</a>
    </section>
<?php else: ?>
    <?php
    $editionQuery = !empty($projectAcademicYearId) ? '?edicio=' . (int) $projectAcademicYearId : '';
    $currentRoles = array_values(array_map('strval', $currentUser['roles'] ?? []));
    $projectsBackHref = url('ca/projectes');
    if (in_array('teacher', $currentRoles, true)) {
        $projectsBackHref = url('professor');
    } elseif (in_array('student', $currentRoles, true)) {
        $projectsBackHref = url('alumne');
    }
    ?>
    <article class="public-project-detail">
        <p class="breadcrumb public-project-detail__breadcrumb"><a href="<?= htmlspecialchars($projectsBackHref, ENT_QUOTES, 'UTF-8') ?>">Projectes</a></p>
        <?php $projectAsset = $project['logo_asset'] ?? ($project['assets'][0] ?? null); ?>
        <div class="public-project-detail__hero">
            <?php if (!empty($projectAsset['logo_path'])): ?>
                <div class="public-project-detail__hero-logo-wrap">
                    <img class="public-project-detail__logo" src="<?= url((string) $projectAsset['logo_path']) ?>" alt="<?= htmlspecialchars((string) $projectAsset['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                </div>
            <?php endif; ?>
            <div class="public-project-detail__hero-content">
                <p class="public-project-detail__eyebrow">Fitxa de projecte</p>
                <h1 class="public-project-detail__title"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (!empty($project['description'])): ?>
                    <p class="lead public-project-detail__lead public-project-detail__lead--hero"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <div class="public-project-detail__meta">
                <?php if (!empty($projectAcademicYear['academic_year_name'])): ?>
                    <span class="public-project-detail__status status">Curs <?= htmlspecialchars((string) $projectAcademicYear['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="public-project-detail__status status"><?= htmlspecialchars((string) ($projectAcademicYear['status'] ?? 'actiu'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </article>

    <?php if (!empty($projectSections)): ?>
        <section class="project-sections public-project-detail__sections">
            <div class="project-sections__header">
                <div>
                    <p class="eyebrow">Organització del projecte</p>
                    <h2 class="project-sections__title">Blocs visibles</h2>
                </div>
                <?php if (!empty($projectSectionsContext['roles'])): ?>
                    <span class="status">Context: <?= htmlspecialchars(implode(', ', $projectSectionsContext['roles']), ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <span class="status">Context: visitant</span>
                <?php endif; ?>
            </div>

            <div class="project-sections__grid">
                <?php foreach ($projectSections as $section): ?>
                    <?php
                    $sectionHref = null;
                    $sectionClass = 'project-section-card card';
                    $sectionAttrs = '';
                    $sectionKey = (string) ($section['section_key'] ?? '');
                    if (($section['section_key'] ?? '') === 'programacio' || ($section['section_key'] ?? '') === 'planificacio') {
                        $sectionHref = url(getLanguage() . '/projectes/' . $project['slug'] . '/documents') . $editionQuery;
                    } elseif (($section['section_key'] ?? '') === 'tasques') {
                        $sectionHref = url(getLanguage() . '/projectes/' . $project['slug'] . '/tasques') . $editionQuery;
                        $sectionAttrs = ' target="_blank" rel="noopener noreferrer"';
                    } elseif (($section['section_key'] ?? '') === 'notes') {
                        $sectionHref = url(getLanguage() . '/projectes/' . $project['slug'] . '/notes') . $editionQuery;
                        $sectionAttrs = ' target="_blank" rel="noopener noreferrer"';
                    }
                    ?>
                    <?php if ($sectionHref !== null): ?>
                        <a class="<?= htmlspecialchars($sectionClass . ' project-section-card--' . $sectionKey, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($sectionHref, ENT_QUOTES, 'UTF-8') ?>"<?= $sectionAttrs ?>>
                            <h3 class="project-section-card__title"><?= htmlspecialchars((string) $section['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        </a>
                    <?php else: ?>
                        <article class="<?= htmlspecialchars($sectionClass . ' project-section-card--' . $sectionKey, ENT_QUOTES, 'UTF-8') ?>">
                            <h3 class="project-section-card__title"><?= htmlspecialchars((string) $section['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
