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

        $sql = "
            SELECT
                classes.id AS class_id,
                classes.name AS class_name,
                classes.code AS class_code,
                projects.id AS project_id,
                projects.slug,
                COALESCE(project_translations.title, projects.name) AS title,
                project_translations.description,
                project_groups.status
            FROM {$membershipTable}
            INNER JOIN classes ON classes.id = {$membershipTable}.class_id
            INNER JOIN project_groups ON project_groups.class_id = classes.id
            INNER JOIN projects ON projects.id = project_groups.project_id
            LEFT JOIN languages ON languages.code = :language_code
            LEFT JOIN project_translations
                ON project_translations.project_id = projects.id
                AND project_translations.language_id = languages.id
            WHERE {$membershipTable}.user_id = :user_id
                AND projects.is_active = 1
            ORDER BY classes.name, title
        ";

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute([
            'language_code' => $languageCode,
            'user_id' => $userId,
        ]);

        return $this->groupRowsByClass($stmt->fetchAll());
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
                'title' => (string) $row['title'],
                'description' => $row['description'] !== null ? (string) $row['description'] : '',
                'status' => (string) $row['status'],
            ];
        }

        return array_values($classes);
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
