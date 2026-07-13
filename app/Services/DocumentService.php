<?php

declare(strict_types=1);

class DocumentService
{
    public function projectDocuments(string $projectSlug, ?array $currentUser = null): array
    {
        $pdo = $this->pdo();
        $project = $this->projectBySlug($projectSlug);

        if ($project === null) {
            return [
                'project' => null,
                'documents' => [],
                'context' => $this->buildContext($currentUser, null, [], []),
            ];
        }

        $projectId = (int) $project['id'];
        $documents = $this->fetchDocuments($projectId);
        $documentIds = array_map(static fn (array $document): int => (int) $document['id'], $documents);
        $sourcesByDocument = $this->groupRowsByDocumentId($this->fetchSources($documentIds));
        $fragmentsByDocument = $this->groupRowsByDocumentId($this->fetchFragments($documentIds));
        $rulesByDocument = $this->groupRulesByDocumentId($this->fetchRules($documentIds));

        $context = $this->buildContext($currentUser, $projectId, $documents, $rulesByDocument);

        foreach ($documents as &$document) {
            $documentId = (int) $document['id'];
            $documentRule = $this->pickDocumentRule($rulesByDocument[$documentId] ?? []);
            $document['visibility_rule'] = $documentRule;
            $document['is_visible'] = $this->canViewDocument($document, $documentRule, $context);
            $document['sources'] = $sourcesByDocument[$documentId] ?? [];

            $documentFragments = $fragmentsByDocument[$documentId] ?? [];
            foreach ($documentFragments as &$fragment) {
                $fragmentRule = $this->pickFragmentRule($rulesByDocument[$documentId] ?? [], (int) $fragment['id']);
                $effectiveRule = $fragmentRule ?? $documentRule;
                $fragment['visibility_rule'] = $effectiveRule;
                $fragment['is_visible'] = $this->canViewFragment($fragment, $document, $effectiveRule, $context);
            }
            unset($fragment);

            $document['fragments'] = $documentFragments;
        }
        unset($document);

        return [
            'project' => $project,
            'documents' => $documents,
            'context' => $context,
        ];
    }

    private function buildContext(?array $currentUser, ?int $projectId, array $documents, array $rulesByDocument): array
    {
        $context = [
            'user' => $currentUser,
            'roles' => [],
            'class_ids' => [],
            'teacher_class_ids' => [],
            'project_class_ids' => [],
            'is_admin' => false,
            'is_teacher' => false,
            'is_student' => false,
            'is_assigned_teacher' => false,
        ];

        if ($currentUser === null) {
            return $context;
        }

        $context['roles'] = array_values(array_map('strval', $currentUser['roles'] ?? []));
        $context['is_admin'] = in_array('admin', $context['roles'], true);
        $context['is_teacher'] = in_array('teacher', $context['roles'], true);
        $context['is_student'] = in_array('student', $context['roles'], true);
        $context['class_ids'] = $this->userClassIds((int) $currentUser['id']);
        $context['teacher_class_ids'] = $this->userTeacherClassIds((int) $currentUser['id']);
        $context['project_class_ids'] = $projectId !== null ? $this->projectClassIds($projectId) : [];
        $context['is_assigned_teacher'] = $context['is_teacher'] && array_intersect($context['teacher_class_ids'], $context['project_class_ids']) !== [];

        return $context;
    }

    private function canViewDocument(array $document, ?array $rule, array $context): bool
    {
        if ($context['is_admin']) {
            return true;
        }

        $visibility = (string) ($document['default_visibility'] ?? 'public');
        if ($rule !== null) {
            return $this->ruleAllows($rule, $context);
        }

        return $this->defaultVisibilityAllows($visibility, $context);
    }

    private function canViewFragment(array $fragment, array $document, ?array $rule, array $context): bool
    {
        if ($context['is_admin']) {
            return true;
        }

        if ($rule !== null) {
            return $this->ruleAllows($rule, $context);
        }

        return $this->defaultVisibilityAllows((string) ($document['default_visibility'] ?? 'public'), $context);
    }

    private function ruleAllows(array $rule, array $context): bool
    {
        if ((int) ($rule['allow_view'] ?? 0) !== 1) {
            return false;
        }

        $visibilityType = (string) ($rule['visibility_type'] ?? 'public');

        if ($visibilityType === 'public') {
            return true;
        }

        if ($visibilityType === 'role') {
            $roleName = (string) ($rule['role_name'] ?? '');

            return $roleName !== '' && in_array($roleName, $context['roles'], true);
        }

        if ($visibilityType === 'class') {
            $classId = (int) ($rule['class_id'] ?? 0);

            return $classId > 0 && in_array($classId, $context['class_ids'], true);
        }

        if ($visibilityType === 'assigned_teacher') {
            return (bool) $context['is_assigned_teacher'];
        }

        return false;
    }

    private function defaultVisibilityAllows(string $visibility, array $context): bool
    {
        $visibility = strtolower(trim($visibility));

        if ($visibility === '' || $visibility === 'public') {
            return true;
        }

        if ($visibility === 'assigned_teacher') {
            return (bool) $context['is_assigned_teacher'];
        }

        return in_array($visibility, $context['roles'], true);
    }

    private function pickDocumentRule(array $rules): ?array
    {
        $rules = array_values(array_filter($rules, static fn (array $rule): bool => empty($rule['fragment_id'])));

        if ($rules === []) {
            return null;
        }

        usort($rules, static fn (array $a, array $b): int => ((int) $a['priority'] <=> (int) $b['priority']) ?: ((int) $a['id'] <=> (int) $b['id']));

        return $rules[0];
    }

    private function pickFragmentRule(array $rules, int $fragmentId): ?array
    {
        $rules = array_values(array_filter($rules, static fn (array $rule): bool => (int) ($rule['fragment_id'] ?? 0) === $fragmentId));

        if ($rules === []) {
            return null;
        }

        usort($rules, static fn (array $a, array $b): int => ((int) $a['priority'] <=> (int) $b['priority']) ?: ((int) $a['id'] <=> (int) $b['id']));

        return $rules[0];
    }

    private function fetchDocuments(int $projectId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, project_id, slug, title, doc_type, default_visibility, notes, is_active, display_order
             FROM documents
             WHERE project_id = :project_id AND is_active = 1
             ORDER BY display_order, title'
        );
        $stmt->execute(['project_id' => $projectId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchSources(array $documentIds): array
    {
        if ($documentIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($documentIds), '?'));
        $stmt = $this->pdo()->prepare(
            "SELECT id, document_id, source_type, source_url, external_id, sheet_name, range_name, sync_mode, notes, last_synced_at, is_active
             FROM document_sources
             WHERE document_id IN ({$placeholders}) AND is_active = 1
             ORDER BY document_id, id"
        );
        $stmt->execute($documentIds);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchFragments(array $documentIds): array
    {
        if ($documentIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($documentIds), '?'));
        $stmt = $this->pdo()->prepare(
            "SELECT id, document_id, fragment_key, title, content, content_format, notes, display_order, is_active
             FROM document_fragments
             WHERE document_id IN ({$placeholders}) AND is_active = 1
             ORDER BY document_id, display_order, id"
        );
        $stmt->execute($documentIds);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function fetchRules(array $documentIds): array
    {
        if ($documentIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($documentIds), '?'));
        $stmt = $this->pdo()->prepare(
            "SELECT dvr.id, dvr.document_id, dvr.fragment_id, dvr.rule_fingerprint, dvr.visibility_type, dvr.role_id, r.name AS role_name, dvr.class_id, c.name AS class_name, dvr.allow_view, dvr.allow_edit, dvr.priority, dvr.is_active
             FROM document_visibility_rules dvr
             LEFT JOIN roles r ON r.id = dvr.role_id
             LEFT JOIN classes c ON c.id = dvr.class_id
             WHERE dvr.document_id IN ({$placeholders}) AND dvr.is_active = 1
             ORDER BY dvr.document_id, dvr.priority, dvr.id"
        );
        $stmt->execute($documentIds);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function groupRowsByDocumentId(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row['document_id']][] = $row;
        }

        return $grouped;
    }

    private function groupRulesByDocumentId(array $rows): array
    {
        return $this->groupRowsByDocumentId($rows);
    }

    private function projectBySlug(string $projectSlug): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT id, slug, name FROM projects WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $projectSlug]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project === false ? null : $project;
    }

    private function userClassIds(int $userId): array
    {
        $stmt = $this->pdo()->prepare('SELECT class_id FROM class_members WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function userTeacherClassIds(int $userId): array
    {
        $stmt = $this->pdo()->prepare('SELECT class_id FROM class_teachers WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function projectClassIds(int $projectId): array
    {
        $stmt = $this->pdo()->prepare('SELECT class_id FROM project_groups WHERE project_id = :project_id');
        $stmt->execute(['project_id' => $projectId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
