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
        $projectAcademicYear = $this->projectAcademicYearForProject((int) $project['id']);
        $structure = $this->assessmentStructureForProjectAcademicYear((int) $projectAcademicYear['id']);

        if ($structure === []) {
            return [
                'project' => $project,
                'projectAcademicYear' => $projectAcademicYear,
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
            'projectAcademicYear' => $projectAcademicYear,
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
        $projectAcademicYear = $this->projectAcademicYearForProjectSlug($projectSlug);

        if ($project === null) {
            return [
                'project' => null,
                'projectAcademicYear' => null,
                'summary' => [],
                'labels' => [],
            ];
        }

        if ($projectAcademicYear === null) {
            return [
                'project' => $project,
                'projectAcademicYear' => null,
                'summary' => [
                    'total_records' => 0,
                    'total_students' => 0,
                    'latest_imported_at' => null,
                ],
                'labels' => [],
            ];
        }

        $stmt = $this->pdo()->prepare(
            'SELECT
                 COUNT(*) AS total_records,
                 COUNT(DISTINCT user_id) AS total_students,
                 MAX(imported_at) AS latest_imported_at
              FROM assessment_records ar
              INNER JOIN assessment_sources s ON s.id = ar.source_id
              WHERE s.project_academic_year_id = :project_academic_year_id'
        );
        $stmt->execute(['project_academic_year_id' => (int) $projectAcademicYear['id']]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $labelsStmt = $this->pdo()->prepare(
            'SELECT label, COUNT(*) AS total
             FROM assessment_records ar
             INNER JOIN assessment_sources s ON s.id = ar.source_id
             WHERE s.project_academic_year_id = :project_academic_year_id
             GROUP BY label
             ORDER BY total DESC, label
             LIMIT 8'
        );
        $labelsStmt->execute(['project_academic_year_id' => (int) $projectAcademicYear['id']]);

        return [
            'project' => $project,
            'projectAcademicYear' => $projectAcademicYear,
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
        $projectAcademicYear = $this->projectAcademicYearForProjectSlug($projectSlug);

        if ($project === null || $projectAcademicYear === null || !$this->studentHasProjectAcademicYearAccess($userId, (int) $projectAcademicYear['id'])) {
            return [
                'project' => $project,
                'projectAcademicYear' => $projectAcademicYear,
                'summary' => [],
                'grades' => [],
                'comments' => [],
                'metadata' => [],
                'hasRecords' => false,
            ];
        }

        $stmt = $this->pdo()->prepare(
            'SELECT label, value, value_type, numeric_value, achievement_value, group_name, team_code, role_name, imported_at
             FROM assessment_records ar
             INNER JOIN assessment_sources s ON s.id = ar.source_id
             WHERE user_id = :user_id
                AND s.project_academic_year_id = :project_academic_year_id
              ORDER BY ar.display_order, ar.id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'project_academic_year_id' => (int) $projectAcademicYear['id'],
        ]);

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profileMetadata = $this->studentProfileMetadata($userId);

        $configuredGrades = $this->groupConfiguredRecords($project, $projectAcademicYear, $records, $profileMetadata);
        if ($configuredGrades !== null) {
            return $configuredGrades;
        }

        return $this->groupRecords($project, $records, $profileMetadata);
    }

    private function groupConfiguredRecords(array $project, array $projectAcademicYear, array $records, array $profileMetadata = []): ?array
    {
        $structure = $this->assessmentStructureForProjectAcademicYear((int) $projectAcademicYear['id']);
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
            'projectAcademicYear' => $projectAcademicYear,
            'summary' => [],
            'grades' => [],
            'comments' => $comments,
            'sections' => $sections,
            'final' => $final,
            'metadata' => $metadata,
            'hasRecords' => $records !== [],
        ];
    }

    private function assessmentStructureForProjectAcademicYear(int $projectAcademicYearId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT
                ap.id AS phase_id,
                ap.phase_key,
                ap.title AS phase_title,
                ap.description AS phase_description,
                ap.section_type,
                payp.display_order AS phase_order,
                at.id AS task_id,
                at.source_column,
                at.title AS task_title,
                at.description AS task_description,
                at.weight_label,
                at.role_filter,
                paypt.display_order AS task_order,
                paypt.is_visible
             FROM project_academic_year_phases payp
             INNER JOIN assessment_phases ap ON ap.id = payp.assessment_phase_id
             INNER JOIN project_academic_year_phase_tasks paypt ON paypt.project_academic_year_phase_id = payp.id
             INNER JOIN assessment_tasks at ON at.id = paypt.assessment_task_id
             WHERE payp.project_academic_year_id = :project_academic_year_id
                AND payp.is_active = 1
                AND paypt.is_visible = 1
             ORDER BY payp.display_order, ap.id, paypt.display_order, at.id'
        );
         $stmt->execute(['project_academic_year_id' => $projectAcademicYearId]);

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
                    'display_order' => (int) $row['phase_order'],
                    'tasks' => [],
                ];
            }

            $phases[$phaseId]['tasks'][] = [
                'id' => (int) $row['task_id'],
                'source_column' => (string) $row['source_column'],
                'title' => (string) $row['task_title'],
                'description' => $row['task_description'],
                'weight_label' => $row['weight_label'],
                'role_filter' => $row['role_filter'],
                'display_order' => (int) $row['task_order'],
            ];
        }

        return array_values($phases);
    }

    private function projectAcademicYearForProject(int $projectId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT pay.id, pay.project_id, pay.academic_year_id, ay.name AS academic_year_name
             FROM project_academic_years pay
             INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
             WHERE pay.project_id = :project_id
             ORDER BY ay.id DESC
             LIMIT 1'
        );
        $stmt->execute(['project_id' => $projectId]);
        $projectAcademicYear = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($projectAcademicYear === false) {
            throw new RuntimeException('No hi ha cap edició acadèmica per al projecte amb id ' . $projectId . '.');
        }

        return $projectAcademicYear;
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

    private function studentHasProjectAcademicYearAccess(int $userId, int $projectAcademicYearId): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT 1
              FROM class_members
              INNER JOIN project_class_assignments ON project_class_assignments.class_id = class_members.class_id
              WHERE class_members.user_id = :user_id
                 AND project_class_assignments.project_academic_year_id = :project_academic_year_id
              LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'project_academic_year_id' => $projectAcademicYearId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function projectAcademicYearForProjectSlug(string $projectSlug): ?array
    {
        $project = $this->findProjectBySlug($projectSlug);
        if ($project === null) {
            return null;
        }

        try {
            return $this->projectAcademicYearForProject((int) $project['id']);
        } catch (RuntimeException) {
            return null;
        }
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
