<?php

declare(strict_types=1);

class AssessmentService
{
    public function visibleTaskSectionsForProject(string $projectSlug, ?array $currentUser = null): array
    {
        $project = $this->findProjectBySlug($projectSlug);

        if ($project === null) {
            return [
                'project' => null,
                'sections' => [],
                'context' => [],
            ];
        }

        $contextRoles = array_values(array_map('strval', $currentUser['roles'] ?? []));
        $showAll = in_array('admin', $contextRoles, true) || in_array('teacher', $contextRoles, true);
        $structure = $this->assessmentStructureForProject((int) $project['id']);

        if ($structure === []) {
            return [
                'project' => $project,
                'sections' => [],
                'context' => [
                    'roles' => $contextRoles,
                    'show_all' => $showAll,
                ],
            ];
        }

        $sections = [];

        foreach ($structure as $phase) {
            $items = [];

            foreach ($phase['tasks'] as $task) {
                if (!$showAll && !$this->taskMatchesAnyRole((string) ($task['role_filter'] ?? ''), $contextRoles)) {
                    continue;
                }

                $items[] = [
                    'label' => (string) $task['title'],
                    'description' => $task['description'] !== null ? (string) $task['description'] : '',
                    'weight' => $task['weight_label'] !== null ? (string) $task['weight_label'] : '',
                    'source_column' => (string) $task['source_column'],
                    'role_filter' => (string) ($task['role_filter'] ?? ''),
                ];
            }

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'title' => (string) $phase['title'],
                'description' => $phase['description'] !== null ? (string) $phase['description'] : '',
                'items' => $items,
            ];
        }

        return [
            'project' => $project,
            'sections' => $sections,
            'context' => [
                'roles' => $contextRoles,
                'show_all' => $showAll,
            ],
        ];
    }

    public function projectNotesOverview(string $projectSlug): array
    {
        $project = $this->findProjectBySlug($projectSlug);

        if ($project === null) {
            return [
                'project' => null,
                'summary' => [],
                'labels' => [],
            ];
        }

        $stmt = $this->pdo()->prepare(
            'SELECT
                COUNT(*) AS total_records,
                COUNT(DISTINCT user_id) AS total_students,
                MAX(imported_at) AS latest_imported_at
             FROM assessment_records
             WHERE project_id = :project_id'
        );
        $stmt->execute(['project_id' => (int) $project['id']]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $labelsStmt = $this->pdo()->prepare(
            'SELECT label, COUNT(*) AS total
             FROM assessment_records
             WHERE project_id = :project_id
             GROUP BY label
             ORDER BY total DESC, label
             LIMIT 8'
        );
        $labelsStmt->execute(['project_id' => (int) $project['id']]);

        return [
            'project' => $project,
            'summary' => [
                'total_records' => (int) ($summary['total_records'] ?? 0),
                'total_students' => (int) ($summary['total_students'] ?? 0),
                'latest_imported_at' => $summary['latest_imported_at'] ?? null,
            ],
            'labels' => $labelsStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function gradesForStudentProject(int $userId, string $projectSlug): array
    {
        $project = $this->findProjectBySlug($projectSlug);

        if ($project === null || !$this->studentHasProjectAccess($userId, (int) $project['id'])) {
            return [
                'project' => $project,
                'summary' => [],
                'grades' => [],
                'comments' => [],
                'metadata' => [],
                'hasRecords' => false,
            ];
        }

        $stmt = $this->pdo()->prepare(
            'SELECT label, value, value_type, numeric_value, achievement_value, group_name, team_code, role_name, imported_at
             FROM assessment_records
             WHERE user_id = :user_id
                AND project_id = :project_id
             ORDER BY display_order, id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'project_id' => (int) $project['id'],
        ]);

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profileMetadata = $this->studentProfileMetadata($userId);

        $configuredGrades = $this->groupConfiguredRecords($project, $records, $profileMetadata);
        if ($configuredGrades !== null) {
            return $configuredGrades;
        }

        return $this->groupRecords($project, $records, $profileMetadata);
    }

    private function groupConfiguredRecords(array $project, array $records, array $profileMetadata = []): ?array
    {
        $structure = $this->assessmentStructureForProject((int) $project['id']);
        if ($structure === []) {
            return null;
        }

        $metadata = [
            'group_name' => null,
            'team_code' => null,
            'role_name' => null,
            'imported_at' => null,
        ];
        $recordsByLabel = [];

        foreach ($records as $record) {
            foreach (['group_name', 'team_code', 'role_name', 'imported_at'] as $key) {
                if ($metadata[$key] === null && !empty($record[$key])) {
                    $metadata[$key] = (string) $record[$key];
                }
            }

            $value = trim((string) ($record['value'] ?? ''));
            if ((string) $record['value_type'] === 'empty' || $value === '') {
                continue;
            }

            $recordsByLabel[(string) $record['label']] = [
                'label' => (string) $record['label'],
                'value' => $value,
                'type' => (string) $record['value_type'],
                'numeric_value' => $record['numeric_value'] !== null ? (float) $record['numeric_value'] : null,
                'achievement_value' => $record['achievement_value'] !== null ? (string) $record['achievement_value'] : null,
            ];
        }

        $metadata = $this->mergeProfileMetadata($metadata, $profileMetadata);
        $roleName = (string) ($metadata['role_name'] ?? '');
        $sections = [];
        $final = [];
        $comments = [];

        foreach ($structure as $phase) {
            $items = [];
            foreach ($phase['tasks'] as $task) {
                if (!$this->taskMatchesRole((string) ($task['role_filter'] ?? ''), $roleName)) {
                    continue;
                }

                $sourceColumn = (string) $task['source_column'];
                if (!isset($recordsByLabel[$sourceColumn])) {
                    continue;
                }

                $item = $recordsByLabel[$sourceColumn];
                $item['label'] = (string) $task['title'];
                $item['description'] = $task['description'] !== null ? (string) $task['description'] : null;
                $item['weight'] = $task['weight_label'] !== null ? (string) $task['weight_label'] : null;
                $items[] = $item;
            }

            if ($items === []) {
                continue;
            }

            if ($phase['section_type'] === 'final') {
                $final = array_merge($final, $items);
                continue;
            }

            if ($phase['section_type'] === 'comments') {
                $comments = array_merge($comments, $items);
                continue;
            }

            $sections[] = [
                'title' => (string) $phase['title'],
                'description' => $phase['description'] !== null ? (string) $phase['description'] : '',
                'items' => $items,
            ];
        }

        return [
            'project' => $project,
            'summary' => [],
            'grades' => [],
            'comments' => $comments,
            'sections' => $sections,
            'final' => $final,
            'metadata' => $metadata,
            'hasRecords' => $records !== [],
        ];
    }

    private function assessmentStructureForProject(int $projectId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT
                assessment_phases.id AS phase_id,
                assessment_phases.phase_key,
                assessment_phases.title AS phase_title,
                assessment_phases.description AS phase_description,
                assessment_phases.section_type,
                assessment_tasks.source_column,
                assessment_tasks.title AS task_title,
                assessment_tasks.description AS task_description,
                assessment_tasks.weight_label,
                assessment_tasks.role_filter
             FROM assessment_phases
             INNER JOIN assessment_tasks ON assessment_tasks.phase_id = assessment_phases.id
             WHERE assessment_phases.project_id = :project_id
                AND assessment_phases.is_active = 1
                AND assessment_tasks.is_visible = 1
             ORDER BY assessment_phases.display_order, assessment_phases.id, assessment_tasks.display_order, assessment_tasks.id'
        );
        $stmt->execute(['project_id' => $projectId]);

        $phases = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $phaseId = (int) $row['phase_id'];
            if (!isset($phases[$phaseId])) {
                $phases[$phaseId] = [
                    'id' => $phaseId,
                    'phase_key' => (string) $row['phase_key'],
                    'title' => (string) $row['phase_title'],
                    'description' => $row['phase_description'],
                    'section_type' => (string) $row['section_type'],
                    'tasks' => [],
                ];
            }

            $phases[$phaseId]['tasks'][] = [
                'source_column' => (string) $row['source_column'],
                'title' => (string) $row['task_title'],
                'description' => $row['task_description'],
                'weight_label' => $row['weight_label'],
                'role_filter' => $row['role_filter'],
            ];
        }

        return array_values($phases);
    }

    private function taskMatchesRole(string $roleFilter, string $studentRole): bool
    {
        $roleFilter = trim($roleFilter);
        if ($roleFilter === '') {
            return true;
        }

        $normalizedStudentRole = $this->normalizeText($studentRole);
        $roles = array_map('trim', explode(',', $roleFilter));

        foreach ($roles as $role) {
            if ($this->normalizeText($role) === $normalizedStudentRole) {
                return true;
            }
        }

        return false;
    }

    private function taskMatchesAnyRole(string $roleFilter, array $roles): bool
    {
        $roleFilter = trim($roleFilter);
        if ($roleFilter === '') {
            return true;
        }

        $normalizedRoles = array_map([$this, 'normalizeText'], $roles);
        $filters = array_filter(array_map('trim', explode(',', $roleFilter)));

        foreach ($filters as $filter) {
            if (in_array($this->normalizeText($filter), $normalizedRoles, true)) {
                return true;
            }
        }

        return false;
    }

    private function groupRecords(array $project, array $records, array $profileMetadata = []): array
    {
        $summary = [];
        $grades = [];
        $comments = [];
        $metadata = [
            'group_name' => null,
            'team_code' => null,
            'role_name' => null,
            'imported_at' => null,
        ];

        foreach ($records as $record) {
            $value = trim((string) ($record['value'] ?? ''));
            $label = (string) $record['label'];
            $type = (string) $record['value_type'];

            foreach (['group_name', 'team_code', 'role_name', 'imported_at'] as $key) {
                if ($metadata[$key] === null && !empty($record[$key])) {
                    $metadata[$key] = (string) $record[$key];
                }
            }

            if ($type === 'empty' || $value === '') {
                continue;
            }

            $item = [
                'label' => $label,
                'value' => $value,
                'type' => $type,
                'numeric_value' => $record['numeric_value'] !== null ? (float) $record['numeric_value'] : null,
                'achievement_value' => $record['achievement_value'] !== null ? (string) $record['achievement_value'] : null,
            ];

            if ($this->isSummaryLabel($label)) {
                $summary[] = $item;
                continue;
            }

            if ($type === 'text') {
                $comments[] = $item;
                continue;
            }

            $grades[] = $item;
        }

        $metadata = $this->mergeProfileMetadata($metadata, $profileMetadata);

        return [
            'project' => $project,
            'summary' => $summary,
            'grades' => $grades,
            'comments' => $comments,
            'sections' => [],
            'final' => [],
            'metadata' => $metadata,
            'hasRecords' => $records !== [],
        ];
    }

    private function mergeProfileMetadata(array $metadata, array $profileMetadata): array
    {
        if (empty($metadata['group_name']) && !empty($profileMetadata['class_group'])) {
            $metadata['group_name'] = (string) $profileMetadata['class_group'];
        }

        if (empty($metadata['team_code']) && !empty($profileMetadata['group_code_1t'])) {
            $metadata['team_code'] = (string) $profileMetadata['group_code_1t'];
        }

        return $metadata;
    }

    private function studentProfileMetadata(int $userId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT class_group, group_code_1t
             FROM student_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile === false ? [] : $profile;
    }

    private function isSummaryLabel(string $label): bool
    {
        $normalized = $this->normalizeText($label);

        return in_array($normalized, ['nota1', 'nota2', 'mitjana global', 'mitjana de coavaluacions'], true)
            || str_contains($normalized, 'mitjana');
    }

    private function studentHasProjectAccess(int $userId, int $projectId): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT 1
             FROM class_members
             INNER JOIN project_groups ON project_groups.class_id = class_members.class_id
             INNER JOIN projects ON projects.id = project_groups.project_id
             WHERE class_members.user_id = :user_id
                AND project_groups.project_id = :project_id
                AND projects.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'project_id' => $projectId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function findProjectBySlug(string $slug): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, slug, name, is_active
             FROM projects
             WHERE slug = :slug
                AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project === false ? null : $project;
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted !== false ? $converted : $text;
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
