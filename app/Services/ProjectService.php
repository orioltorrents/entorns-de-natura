<?php

declare(strict_types=1);

class ProjectService
{
    public function allActive(string $languageCode = 'ca'): array
    {
        $pdo = $this->pdo();
        $this->ensureProjectsDisplayOrderColumn($pdo);

        $stmt = $pdo->prepare(
            'SELECT
                projects.id,
                projects.slug,
                projects.name,
                projects.display_order,
                projects.is_active,
                COALESCE(project_translations.title, projects.name) AS title,
                project_translations.description
             FROM projects
             LEFT JOIN languages ON languages.code = :language_code
             LEFT JOIN project_translations
                ON project_translations.project_id = projects.id
                AND project_translations.language_id = languages.id
             WHERE projects.is_active = 1
             ORDER BY projects.display_order, title'
        );
        $stmt->execute(['language_code' => $languageCode]);

        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->attachAssetsToProjects($projects);
    }

    public function findActiveBySlug(string $slug, string $languageCode = 'ca'): ?array
    {
        $pdo = $this->pdo();
        $this->ensureProjectsDisplayOrderColumn($pdo);

        $stmt = $pdo->prepare(
            'SELECT
                projects.id,
                projects.slug,
                projects.name,
                projects.display_order,
                projects.is_active,
                COALESCE(project_translations.title, projects.name) AS title,
                project_translations.description
             FROM projects
             LEFT JOIN languages ON languages.code = :language_code
             LEFT JOIN project_translations
                ON project_translations.project_id = projects.id
                AND project_translations.language_id = languages.id
             WHERE projects.slug = :slug
                AND projects.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            'language_code' => $languageCode,
            'slug' => $slug,
        ]);

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($project === false) {
            return null;
        }

        $project['assets'] = (new ProjectAssetService())->assetsByProjectId((int) $project['id']);

        return $project;
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

    private function attachAssetsToProjects(array $projects): array
    {
        $projectIds = array_map(static fn (array $project): int => (int) $project['id'], $projects);
        $assetsByProject = (new ProjectAssetService())->assetsByProjectIds($projectIds);

        foreach ($projects as &$project) {
            $project['assets'] = $assetsByProject[(int) $project['id']] ?? [];
        }
        unset($project);

        return $projects;
    }
}
