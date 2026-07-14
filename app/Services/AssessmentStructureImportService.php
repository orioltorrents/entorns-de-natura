<?php

declare(strict_types=1);

class AssessmentStructureImportService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function importFromCsv(string $phasesCsvPath, string $tasksCsvPath): array
    {
        $summary = [
            'phases_imported' => 0,
            'tasks_imported' => 0,
            'errors' => [],
        ];

        $this->pdo->beginTransaction();

        try {
            $phaseRows = $this->readCsvWithHeaders($phasesCsvPath);
            $taskRows = $this->readCsvWithHeaders($tasksCsvPath);

            $this->validateHeaders($phaseRows['headers'], ['project_slug', 'phase_key', 'title', 'display_order', 'is_active'], 'assessment_phases');
            $this->validateHeaders($taskRows['headers'], ['project_slug', 'phase_key', 'source_column', 'title', 'display_order', 'is_visible'], 'assessment_tasks');

            foreach ($phaseRows['rows'] as $rowNumber => $row) {
                try {
                    $this->importPhaseRow($row);
                    $summary['phases_imported']++;
                } catch (Throwable $e) {
                    $summary['errors'][] = 'assessment_phases fila ' . $rowNumber . ': ' . $e->getMessage();
                }
            }

            foreach ($taskRows['rows'] as $rowNumber => $row) {
                try {
                    $this->importTaskRow($row);
                    $summary['tasks_imported']++;
                } catch (Throwable $e) {
                    $summary['errors'][] = 'assessment_tasks fila ' . $rowNumber . ': ' . $e->getMessage();
                }
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $summary;
    }

    private function importPhaseRow(array $row): void
    {
        $projectSlug = trim((string) ($row['project_slug'] ?? ''));
        $phaseKey = trim((string) ($row['phase_key'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));

        if ($projectSlug === '' || $phaseKey === '' || $title === '') {
            throw new RuntimeException('project_slug, phase_key i title son obligatoris.');
        }

        $projectId = $this->projectIdBySlug($projectSlug);
        $sectionType = $this->normalizeSectionType((string) ($row['section_type'] ?? 'phase'));

        $stmt = $this->pdo->prepare(
            'INSERT INTO assessment_phases
                (project_id, phase_key, title, description, section_type, display_order, is_active)
             VALUES
                (:project_id, :phase_key, :title, :description, :section_type, :display_order, :is_active)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                section_type = VALUES(section_type),
                display_order = VALUES(display_order),
                is_active = VALUES(is_active),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'project_id' => $projectId,
            'phase_key' => $phaseKey,
            'title' => $title,
            'description' => $this->nullableString($row['description'] ?? ''),
            'section_type' => $sectionType,
            'display_order' => $this->toUnsignedInt($row['display_order'] ?? 0),
            'is_active' => $this->toBooleanInt($row['is_active'] ?? 1),
        ]);

        $phaseId = $this->phaseIdByProjectAndKey($projectId, $phaseKey);
        foreach ($this->projectAcademicYearIdsByProjectId($projectId) as $projectAcademicYearId) {
            $bridgeStmt = $this->pdo->prepare(
                'INSERT INTO project_academic_year_phases
                    (project_academic_year_id, assessment_phase_id, display_order, is_active)
                 VALUES
                    (:project_academic_year_id, :assessment_phase_id, :display_order, :is_active)
                 ON DUPLICATE KEY UPDATE
                    display_order = VALUES(display_order),
                    is_active = VALUES(is_active),
                    updated_at = CURRENT_TIMESTAMP'
            );
            $bridgeStmt->execute([
                'project_academic_year_id' => $projectAcademicYearId,
                'assessment_phase_id' => $phaseId,
                'display_order' => $this->toUnsignedInt($row['display_order'] ?? 0),
                'is_active' => $this->toBooleanInt($row['is_active'] ?? 1),
            ]);
        }
    }

    private function importTaskRow(array $row): void
    {
        $projectSlug = trim((string) ($row['project_slug'] ?? ''));
        $phaseKey = trim((string) ($row['phase_key'] ?? ''));
        $sourceColumn = trim((string) ($row['source_column'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));

        if ($projectSlug === '' || $phaseKey === '' || $sourceColumn === '' || $title === '') {
            throw new RuntimeException('project_slug, phase_key, source_column i title son obligatoris.');
        }

        $projectId = $this->projectIdBySlug($projectSlug);
        $phaseId = $this->phaseIdByProjectAndKey($projectId, $phaseKey);

        $stmt = $this->pdo->prepare(
            'INSERT INTO assessment_tasks
                (phase_id, source_column, title, description, weight_label, role_filter, display_order, is_visible)
             VALUES
                (:phase_id, :source_column, :title, :description, :weight_label, :role_filter, :display_order, :is_visible)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                weight_label = VALUES(weight_label),
                role_filter = VALUES(role_filter),
                display_order = VALUES(display_order),
                is_visible = VALUES(is_visible),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'phase_id' => $phaseId,
            'source_column' => $sourceColumn,
            'title' => $title,
            'description' => $this->nullableString($row['description'] ?? ''),
            'weight_label' => $this->nullableString($row['weight_label'] ?? ''),
            'role_filter' => $this->nullableString($row['role_filter'] ?? ''),
            'display_order' => $this->toUnsignedInt($row['display_order'] ?? 0),
            'is_visible' => $this->toBooleanInt($row['is_visible'] ?? 1),
        ]);

        foreach ($this->projectAcademicYearIdsByProjectId($projectId) as $projectAcademicYearId) {
            $projectAcademicYearPhaseId = $this->projectAcademicYearPhaseId($projectAcademicYearId, $phaseId);
            $bridgeStmt = $this->pdo->prepare(
                'INSERT INTO project_academic_year_phase_tasks
                    (project_academic_year_phase_id, assessment_task_id, display_order, is_visible)
                 VALUES
                    (:project_academic_year_phase_id, :assessment_task_id, :display_order, :is_visible)
                 ON DUPLICATE KEY UPDATE
                    display_order = VALUES(display_order),
                    is_visible = VALUES(is_visible),
                    updated_at = CURRENT_TIMESTAMP'
            );
            $bridgeStmt->execute([
                'project_academic_year_phase_id' => $projectAcademicYearPhaseId,
                'assessment_task_id' => $this->taskIdByPhaseAndSourceColumn($phaseId, $sourceColumn),
                'display_order' => $this->toUnsignedInt($row['display_order'] ?? 0),
                'is_visible' => $this->toBooleanInt($row['is_visible'] ?? 1),
            ]);
        }
    }

    private function readCsvWithHeaders(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('No es pot llegir el CSV: ' . $path);
        }

        $contents = file_get_contents($path, false, null, 0, 4096);
        $delimiter = $this->detectDelimiter((string) $contents);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('No es pot obrir el CSV: ' . $path);
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false || $headers === [null]) {
            fclose($handle);
            throw new RuntimeException('El CSV esta buit: ' . $path);
        }

        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        }

        $headers = array_map(fn($header): string => $this->normalizeHeader((string) $header), $headers);
        $rows = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $row = array_pad($row, count($headers), '');
            $data = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $data[$header] = trim((string) ($row[$index] ?? ''));
                }
            }
            $rows[$lineNumber] = $data;
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    private function validateHeaders(array $headers, array $required, string $sheetName): void
    {
        foreach ($required as $header) {
            if (!in_array($header, $headers, true)) {
                throw new RuntimeException("Falta la columna {$header} a {$sheetName}.");
            }
        }
    }

    private function projectIdBySlug(string $slug): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM projects WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $projectId = $stmt->fetchColumn();

        if ($projectId === false) {
            throw new RuntimeException("No existeix cap projecte amb slug {$slug}.");
        }

        return (int) $projectId;
    }

    private function phaseIdByProjectAndKey(int $projectId, string $phaseKey): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM assessment_phases WHERE project_id = :project_id AND phase_key = :phase_key LIMIT 1'
        );
        $stmt->execute(['project_id' => $projectId, 'phase_key' => $phaseKey]);
        $phaseId = $stmt->fetchColumn();

        if ($phaseId === false) {
            throw new RuntimeException("No existeix la fase {$phaseKey} per aquest projecte.");
        }

        return (int) $phaseId;
    }

    private function taskIdByPhaseAndSourceColumn(int $phaseId, string $sourceColumn): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM assessment_tasks WHERE phase_id = :phase_id AND source_column = :source_column LIMIT 1'
        );
        $stmt->execute(['phase_id' => $phaseId, 'source_column' => $sourceColumn]);
        $taskId = $stmt->fetchColumn();

        if ($taskId === false) {
            throw new RuntimeException('No existeix la tasca ' . $sourceColumn . '.');
        }

        return (int) $taskId;
    }

    private function projectAcademicYearIdsByProjectId(int $projectId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM project_academic_years
             WHERE project_id = :project_id
             ORDER BY academic_year_id'
        );
        $stmt->execute(['project_id' => $projectId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function projectAcademicYearPhaseId(int $projectAcademicYearId, int $phaseId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM project_academic_year_phases
             WHERE project_academic_year_id = :project_academic_year_id
               AND assessment_phase_id = :assessment_phase_id
             LIMIT 1'
        );
        $stmt->execute([
            'project_academic_year_id' => $projectAcademicYearId,
            'assessment_phase_id' => $phaseId,
        ]);

        $bridgeId = $stmt->fetchColumn();
        if ($bridgeId === false) {
            throw new RuntimeException('No existeix la fase assignada a la edició acadèmica.');
        }

        return (int) $bridgeId;
    }

    private function normalizeSectionType(string $sectionType): string
    {
        $sectionType = strtolower(trim($sectionType));

        return in_array($sectionType, ['phase', 'final', 'comments'], true) ? $sectionType : 'phase';
    }

    private function toUnsignedInt(mixed $value): int
    {
        return max(0, (int) $value);
    }

    private function toBooleanInt(mixed $value): int
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'si', 'sí', 'yes', 'actiu', 'visible'], true) ? 1 : 0;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function detectDelimiter(string $sample): string
    {
        return substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim($header));
    }
}
