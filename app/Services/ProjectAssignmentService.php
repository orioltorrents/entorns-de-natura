<?php

declare(strict_types=1);

class ProjectAssignmentService
{
    public function projectsForStudent(int $userId, string $languageCode = 'ca'): array
    {
        return $this->projectsForUserClasses($userId, $languageCode, 'class_members');
    }

    public function projectsForTeacher(int $userId, string $languageCode = 'ca'): array
    {
        return $this->projectsForUserClasses($userId, $languageCode, 'class_teachers');
    }

    private function projectsForUserClasses(int $userId, string $languageCode, string $membershipTable): array
    {
        if (!in_array($membershipTable, ['class_members', 'class_teachers'], true)) {
            throw new InvalidArgumentException('Taula de relacio no valida.');
        }

        $pdo = $this->pdo();
        $this->ensureProjectsDisplayOrderColumn($pdo);

        $sql = "
            SELECT
                classes.id AS class_id,
                classes.class_name AS class_name,
                classes.class_code AS class_code,
                projects.id AS project_id,
                projects.slug,
                projects.display_order,
                academic_years.name AS academic_year_name,
                COALESCE(project_translations.title, projects.name) AS title,
                project_translations.description,
                project_class_assignments.status
             FROM {$membershipTable}
             INNER JOIN classes ON classes.id = {$membershipTable}.class_id
            INNER JOIN project_class_assignments ON project_class_assignments.class_id = classes.id
            INNER JOIN project_academic_years ON project_academic_years.id = project_class_assignments.project_academic_year_id
            INNER JOIN projects ON projects.id = project_academic_years.project_id
            INNER JOIN academic_years ON academic_years.id = project_academic_years.academic_year_id
             LEFT JOIN languages ON languages.code = :language_code
             LEFT JOIN project_translations
                 ON project_translations.project_id = projects.id
                 AND project_translations.language_id = languages.id
            WHERE {$membershipTable}.user_id = :user_id
                AND projects.is_active = 1
            ORDER BY classes.class_name, projects.display_order, title
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'language_code' => $languageCode,
            'user_id' => $userId,
        ]);

        return $this->groupRowsByClass($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function groupRowsByClass(array $rows): array
    {
        $classes = [];

        foreach ($rows as $row) {
            $classId = (int) $row['class_id'];

            if (!isset($classes[$classId])) {
                $classes[$classId] = [
                    'id' => $classId,
                    'name' => (string) $row['class_name'],
                    'code' => (string) $row['class_code'],
                    'projects' => [],
                ];
            }

            $classes[$classId]['projects'][] = [
                'id' => (int) $row['project_id'],
                'slug' => (string) $row['slug'],
                'display_order' => (int) $row['display_order'],
                'title' => (string) $row['title'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
                'academic_year_name' => (string) $row['academic_year_name'],
                'status' => (string) $row['status'],
            ];
        }

        $classes = array_values($classes);
        $projectIds = [];
        foreach ($classes as $class) {
            foreach ($class['projects'] as $project) {
                $projectIds[] = (int) $project['id'];
            }
        }

        $assetsByProject = (new ProjectAssetService())->assetsByProjectIds($projectIds);

        foreach ($classes as &$class) {
            foreach ($class['projects'] as &$project) {
                $project['assets'] = $assetsByProject[(int) $project['id']] ?? [];
                $project['logo_asset'] = $this->pickLogoAsset($project['assets']);
            }
            unset($project);
        }
        unset($class);

        return $classes;
    }

    private function pickLogoAsset(array $assets): ?array
    {
        $fallback = null;

        foreach ($assets as $asset) {
            if ($fallback === null) {
                $fallback = $asset;
            }

            if (($asset['asset_type'] ?? '') === 'project' && !empty($asset['logo_path'])) {
                return $asset;
            }
        }

        return $fallback;
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }

    private function ensureProjectsDisplayOrderColumn(PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            'table_name' => 'projects',
            'column_name' => 'display_order',
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $pdo->exec('ALTER TABLE projects ADD COLUMN display_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER name');
        $pdo->exec('ALTER TABLE projects ADD KEY idx_projects_display_order (display_order)');
        $pdo->exec(
            "UPDATE projects
             SET display_order = CASE slug
                 WHEN 'projecte-rius' THEN 10
                 WHEN 'mat-penedes' THEN 20
                 WHEN 'agroparc' THEN 30
                 WHEN 'projecte-orenetes' THEN 40
                 WHEN 'liquencity' THEN 50
                 WHEN 'vespa-velutina' THEN 60
                 ELSE display_order
             END
             WHERE display_order = 0"
        );
    }
}
