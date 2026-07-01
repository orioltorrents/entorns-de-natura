<?php

declare(strict_types=1);

class ProjectService
{
    public function allActive(string $languageCode = 'ca'): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT
                projects.id,
                projects.slug,
                projects.name,
                projects.is_active,
                COALESCE(project_translations.title, projects.name) AS title,
                project_translations.description
             FROM projects
             LEFT JOIN languages ON languages.code = :language_code
             LEFT JOIN project_translations
                ON project_translations.project_id = projects.id
                AND project_translations.language_id = languages.id
             WHERE projects.is_active = 1
             ORDER BY title'
        );
        $stmt->execute(['language_code' => $languageCode]);

        return $stmt->fetchAll();
    }

    public function findActiveBySlug(string $slug, string $languageCode = 'ca'): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT
                projects.id,
                projects.slug,
                projects.name,
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

        $project = $stmt->fetch();

        return $project === false ? null : $project;
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
