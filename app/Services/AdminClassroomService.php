<?php

declare(strict_types=1);

class AdminClassroomService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function toggleClassroom(array $input): array
    {
        $classroomId = filter_var($input['classroom_id'] ?? null, FILTER_VALIDATE_INT);
        if ($classroomId === false || $classroomId === null) {
            return $this->result('Classroom no vàlid.', 'error');
        }

        $stmt = $this->pdo->prepare('SELECT is_active FROM classrooms WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $classroomId]);
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($classroom === false) {
            return $this->result('No s’ha trobat el Classroom.', 'error');
        }

        $newState = ((int) $classroom['is_active'] === 1) ? 0 : 1;
        $update = $this->pdo->prepare('UPDATE classrooms SET is_active = :is_active WHERE id = :id');
        $update->execute([
            'is_active' => $newState,
            'id' => (int) $classroomId,
        ]);

        return $this->result($newState === 1 ? 'Classroom reactivat.' : 'Classroom arxivat.', 'success');
    }

    public function importMembersUploadedFile(array $file): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return $this->result('No s’ha rebut cap fitxer CSV de membres de Classroom.', 'error');
        }

        try {
            $csv = $this->readCsvWithHeaders((string) $file['tmp_name']);
            $this->validateHeaders($csv['headers'], ['academic_year', 'classroom_key', 'email'], 'classroom_members');
        } catch (Throwable $throwable) {
            return $this->result('No s’ha pogut llegir el CSV: ' . $throwable->getMessage(), 'error');
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $warnings = [];

        foreach ($csv['rows'] as $rowNumber => $row) {
            try {
                $result = $this->importMemberRow($row);
                if ($result['created'] === true) {
                    $created++;
                } else {
                    $updated++;
                }

                foreach ($result['warnings'] as $warning) {
                    $warnings[] = 'Fila ' . $rowNumber . ': ' . $warning;
                }
            } catch (Throwable $throwable) {
                $errors[] = 'Fila ' . $rowNumber . ': ' . $throwable->getMessage();
            }
        }

        $message = 'Importació de membres Classroom: ' . $created . ' creats i ' . $updated . ' actualitzats.';
        if ($warnings !== []) {
            $message .= ' Avisos: ' . implode(' | ', array_slice($warnings, 0, 5));
        }

        if ($errors !== []) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
            return $this->result($message, 'error');
        }

        return $this->result($message, 'success');
    }

    public function importProjectLinksUploadedFile(array $file): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return $this->result('No s’ha rebut cap fitxer CSV de vincles Classroom-projecte.', 'error');
        }

        try {
            $csv = $this->readCsvWithHeaders((string) $file['tmp_name']);
            $this->validateHeaders($csv['headers'], ['academic_year', 'classroom_key', 'project_slug'], 'classroom_project_links');
        } catch (Throwable $throwable) {
            return $this->result('No s’ha pogut llegir el CSV: ' . $throwable->getMessage(), 'error');
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($csv['rows'] as $rowNumber => $row) {
            try {
                $wasCreated = $this->importProjectLinkRow($row);
                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (Throwable $throwable) {
                $errors[] = 'Fila ' . $rowNumber . ': ' . $throwable->getMessage();
            }
        }

        $message = 'Importació de vincles Classroom-projecte: ' . $created . ' creats i ' . $updated . ' actualitzats.';
        if ($errors !== []) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
            return $this->result($message, 'error');
        }

        return $this->result($message, 'success');
    }

    public function importTaskLinksUploadedFile(array $file): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            return $this->result('No s’ha rebut cap fitxer CSV de tasques de Classroom.', 'error');
        }

        try {
            $csv = $this->readCsvWithHeaders((string) $file['tmp_name']);
            $this->validateHeaders(
                $csv['headers'],
                ['academic_year', 'classroom_key', 'project_slug', 'phase_key', 'phase_title', 'task_key', 'task_title', 'task_url'],
                'classroom_task_links'
            );
        } catch (Throwable $throwable) {
            return $this->result('No s’ha pogut llegir el CSV: ' . $throwable->getMessage(), 'error');
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($csv['rows'] as $rowNumber => $row) {
            try {
                $this->pdo->beginTransaction();
                $wasCreated = $this->importTaskLinkRow($row);
                $this->pdo->commit();

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (Throwable $throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $errors[] = 'Fila ' . $rowNumber . ': ' . $throwable->getMessage();
            }
        }

        $message = 'Importació de tasques Classroom: ' . $created . ' enllaços creats i ' . $updated . ' actualitzats.';
        if ($errors !== []) {
            $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
            return $this->result($message, 'error');
        }

        return $this->result($message, 'success');
    }

    private function importMemberRow(array $row): array
    {
        $academicYear = trim((string) ($row['academic_year'] ?? ''));
        $projectSlug = trim((string) ($row['project_slug'] ?? ''));
        $classroomKey = trim((string) ($row['classroom_key'] ?? ''));
        $classroomName = trim((string) ($row['classroom_name'] ?? ''));
        $classroomUrl = trim((string) ($row['classroom_url'] ?? ''));
        $googleClassroomId = trim((string) ($row['google_classroom_id'] ?? ''));
        $email = strtolower(trim((string) ($row['email'] ?? '')));
        $name = trim((string) ($row['name'] ?? ''));
        $surname = trim((string) ($row['surname'] ?? ''));
        $googleUserId = trim((string) ($row['google_user_id'] ?? ''));
        $googlePhotoUrl = trim((string) ($row['google_photo_url'] ?? ''));

        if ($academicYear === '' || $classroomKey === '' || $email === '') {
            throw new RuntimeException('academic_year, classroom_key i email son obligatoris.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('email no vàlid: ' . $email);
        }

        $academicYearId = $this->academicYearId($academicYear);
        $projectAcademicYearId = $projectSlug !== '' ? $this->projectAcademicYearId($academicYear, $projectSlug) : null;
        $classroom = $this->findOrCreateClassroom($academicYearId, $projectAcademicYearId, $classroomKey, $classroomName, $classroomUrl, $googleClassroomId);
        $user = $this->userByEmail($email);
        $warnings = [];

        if ($projectAcademicYearId !== null) {
            $this->upsertProjectLink((int) $classroom['id'], $projectAcademicYearId, true);
        }

        if ($googleClassroomId !== '' && trim((string) ($classroom['google_classroom_id'] ?? '')) !== '' && $googleClassroomId !== trim((string) $classroom['google_classroom_id'])) {
            $warnings[] = 'google_classroom_id no coincideix amb el Classroom configurat.';
        }

        if (!$this->userHasRole((int) $user['id'], 'student')) {
            $warnings[] = 'l’usuari existeix però no té rol student.';
        }

        if ($name !== '' && $this->normalizeText($name) !== $this->normalizeText((string) ($user['name'] ?? ''))) {
            $warnings[] = 'el name del CSV no coincideix amb users.name.';
        }

        if ($surname !== '' && $this->normalizeText($surname) !== $this->normalizeText((string) ($user['surname'] ?? ''))) {
            $warnings[] = 'el surname del CSV no coincideix amb users.surname.';
        }

        $membershipExists = $this->classroomMembershipExists((int) $classroom['id'], (int) $user['id']);
        $stmt = $this->pdo->prepare(
            'INSERT INTO classroom_members
                (classroom_id, user_id, student_email, google_user_id, google_photo_url, classroom_group, external_group_id, is_active)
             VALUES
                (:classroom_id, :user_id, :student_email, :google_user_id, :google_photo_url, NULL, NULL, 1)
             ON DUPLICATE KEY UPDATE
                student_email = VALUES(student_email),
                google_user_id = VALUES(google_user_id),
                google_photo_url = VALUES(google_photo_url),
                classroom_group = NULL,
                external_group_id = NULL,
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'classroom_id' => (int) $classroom['id'],
            'user_id' => (int) $user['id'],
            'student_email' => $email,
            'google_user_id' => $googleUserId !== '' ? $googleUserId : null,
            'google_photo_url' => $googlePhotoUrl !== '' ? $googlePhotoUrl : null,
        ]);

        return [
            'created' => !$membershipExists,
            'warnings' => $warnings,
        ];
    }

    private function importProjectLinkRow(array $row): bool
    {
        $academicYear = trim((string) ($row['academic_year'] ?? ''));
        $classroomKey = trim((string) ($row['classroom_key'] ?? ''));
        $projectSlug = trim((string) ($row['project_slug'] ?? ''));
        $isActive = $this->parseActiveFlag($row['is_active'] ?? '1');

        if ($academicYear === '' || $classroomKey === '' || $projectSlug === '') {
            throw new RuntimeException('academic_year, classroom_key i project_slug son obligatoris.');
        }

        $academicYearId = $this->academicYearId($academicYear);
        $projectAcademicYearId = $this->projectAcademicYearId($academicYear, $projectSlug);
        $classroom = $this->findOrCreateClassroom($academicYearId, $projectAcademicYearId, $classroomKey, '', '', '');

        return $this->upsertProjectLink((int) $classroom['id'], $projectAcademicYearId, $isActive);
    }

    private function importTaskLinkRow(array $row): bool
    {
        $academicYear = trim((string) ($row['academic_year'] ?? ''));
        $classroomKey = trim((string) ($row['classroom_key'] ?? ''));
        $classroomName = trim((string) ($row['classroom_name'] ?? ''));
        $classroomUrl = trim((string) ($row['classroom_url'] ?? ''));
        $googleClassroomId = trim((string) ($row['google_classroom_id'] ?? ''));
        $projectSlug = trim((string) ($row['project_slug'] ?? ''));
        $phaseKey = trim((string) ($row['phase_key'] ?? ''));
        $phaseTitle = trim((string) ($row['phase_title'] ?? ''));
        $taskKey = trim((string) ($row['task_key'] ?? ''));
        $taskTitle = trim((string) ($row['task_title'] ?? ''));
        $taskUrl = trim((string) ($row['task_url'] ?? ''));
        $roleFilter = trim((string) ($row['role_filter'] ?? ''));

        if ($academicYear === '' || $classroomKey === '' || $projectSlug === '' || $phaseKey === '' || $phaseTitle === '' || $taskKey === '' || $taskTitle === '' || $taskUrl === '') {
            throw new RuntimeException('academic_year, classroom_key, project_slug, phase_key, phase_title, task_key, task_title i task_url son obligatoris.');
        }

        $taskUrlScheme = strtolower((string) parse_url($taskUrl, PHP_URL_SCHEME));
        if (filter_var($taskUrl, FILTER_VALIDATE_URL) === false || !in_array($taskUrlScheme, ['http', 'https'], true)) {
            throw new RuntimeException('task_url no és una URL http/https vàlida.');
        }

        $academicYearId = $this->academicYearId($academicYear);
        $projectAcademicYear = $this->projectAcademicYear($academicYear, $projectSlug);
        $projectAcademicYearId = (int) $projectAcademicYear['id'];
        $projectId = (int) $projectAcademicYear['project_id'];
        $classroom = $this->findOrCreateClassroom($academicYearId, $projectAcademicYearId, $classroomKey, $classroomName, $classroomUrl, $googleClassroomId);
        $this->upsertProjectLink((int) $classroom['id'], $projectAcademicYearId, true);

        $phaseId = $this->upsertAssessmentPhase($projectId, $phaseKey, $phaseTitle);
        $projectAcademicYearPhaseId = $this->upsertProjectAcademicYearPhase($projectAcademicYearId, $phaseId);
        $taskId = $this->upsertAssessmentTask($phaseId, $taskKey, $taskTitle, $roleFilter);
        $projectAcademicYearPhaseTaskId = $this->upsertProjectAcademicYearPhaseTask($projectAcademicYearPhaseId, $taskId);

        return $this->upsertTaskClassroomLink($projectAcademicYearPhaseTaskId, (int) $classroom['id'], $taskUrl);
    }

    private function academicYearId(string $academicYear): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM academic_years WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $academicYear]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No existeix el curs ' . $academicYear . '.');
        }

        return (int) $id;
    }

    private function projectAcademicYearId(string $academicYear, string $projectSlug): int
    {
        return (int) $this->projectAcademicYear($academicYear, $projectSlug)['id'];
    }

    private function projectAcademicYear(string $academicYear, string $projectSlug): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pay.id, pay.project_id
             FROM project_academic_years pay
             INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
             INNER JOIN projects p ON p.id = pay.project_id
             WHERE ay.name = :academic_year
               AND p.slug = :project_slug
             LIMIT 1'
        );
        $stmt->execute([
            'academic_year' => $academicYear,
            'project_slug' => $projectSlug,
        ]);
        $projectAcademicYear = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($projectAcademicYear === false) {
            throw new RuntimeException('No existeix l’edició ' . $academicYear . ' / ' . $projectSlug . '.');
        }

        return $projectAcademicYear;
    }

    private function upsertAssessmentPhase(int $projectId, string $phaseKey, string $phaseTitle): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM assessment_phases WHERE project_id = :project_id AND phase_key = :phase_key LIMIT 1');
        $stmt->execute([
            'project_id' => $projectId,
            'phase_key' => $phaseKey,
        ]);
        $phaseId = $stmt->fetchColumn();

        if ($phaseId !== false) {
            $update = $this->pdo->prepare(
                'UPDATE assessment_phases
                 SET title = :title,
                     is_active = 1,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'title' => $phaseTitle,
                'id' => (int) $phaseId,
            ]);

            return (int) $phaseId;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO assessment_phases (project_id, phase_key, title, display_order, is_active)
             VALUES (:project_id, :phase_key, :title, :display_order, 1)'
        );
        $insert->execute([
            'project_id' => $projectId,
            'phase_key' => $phaseKey,
            'title' => $phaseTitle,
            'display_order' => $this->nextPhaseDisplayOrder($projectId),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function upsertProjectAcademicYearPhase(int $projectAcademicYearId, int $phaseId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM project_academic_year_phases
             WHERE project_academic_year_id = :project_academic_year_id
               AND assessment_phase_id = :assessment_phase_id
             LIMIT 1'
        );
        $stmt->execute([
            'project_academic_year_id' => $projectAcademicYearId,
            'assessment_phase_id' => $phaseId,
        ]);
        $projectAcademicYearPhaseId = $stmt->fetchColumn();

        if ($projectAcademicYearPhaseId !== false) {
            $update = $this->pdo->prepare('UPDATE project_academic_year_phases SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute(['id' => (int) $projectAcademicYearPhaseId]);

            return (int) $projectAcademicYearPhaseId;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO project_academic_year_phases (project_academic_year_id, assessment_phase_id, display_order, is_active)
             SELECT :project_academic_year_id, ap.id, ap.display_order, 1
             FROM assessment_phases ap
             WHERE ap.id = :assessment_phase_id'
        );
        $insert->execute([
            'project_academic_year_id' => $projectAcademicYearId,
            'assessment_phase_id' => $phaseId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function upsertAssessmentTask(int $phaseId, string $taskKey, string $taskTitle, string $roleFilter): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM assessment_tasks WHERE phase_id = :phase_id AND source_column = :source_column LIMIT 1');
        $stmt->execute([
            'phase_id' => $phaseId,
            'source_column' => $taskKey,
        ]);
        $taskId = $stmt->fetchColumn();
        $roleFilter = $roleFilter !== '' ? $roleFilter : null;

        if ($taskId !== false) {
            $update = $this->pdo->prepare(
                'UPDATE assessment_tasks
                 SET title = :title,
                     role_filter = :role_filter,
                     is_visible = 1,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'title' => $taskTitle,
                'role_filter' => $roleFilter,
                'id' => (int) $taskId,
            ]);

            return (int) $taskId;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO assessment_tasks (phase_id, source_column, title, role_filter, display_order, is_visible)
             VALUES (:phase_id, :source_column, :title, :role_filter, :display_order, 1)'
        );
        $insert->execute([
            'phase_id' => $phaseId,
            'source_column' => $taskKey,
            'title' => $taskTitle,
            'role_filter' => $roleFilter,
            'display_order' => $this->nextTaskDisplayOrder($phaseId),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function upsertProjectAcademicYearPhaseTask(int $projectAcademicYearPhaseId, int $taskId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM project_academic_year_phase_tasks
             WHERE project_academic_year_phase_id = :project_academic_year_phase_id
               AND assessment_task_id = :assessment_task_id
             LIMIT 1'
        );
        $stmt->execute([
            'project_academic_year_phase_id' => $projectAcademicYearPhaseId,
            'assessment_task_id' => $taskId,
        ]);
        $projectAcademicYearPhaseTaskId = $stmt->fetchColumn();

        if ($projectAcademicYearPhaseTaskId !== false) {
            $update = $this->pdo->prepare('UPDATE project_academic_year_phase_tasks SET is_visible = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute(['id' => (int) $projectAcademicYearPhaseTaskId]);

            return (int) $projectAcademicYearPhaseTaskId;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO project_academic_year_phase_tasks (project_academic_year_phase_id, assessment_task_id, display_order, is_visible)
             SELECT :project_academic_year_phase_id, at.id, at.display_order, 1
             FROM assessment_tasks at
             WHERE at.id = :assessment_task_id'
        );
        $insert->execute([
            'project_academic_year_phase_id' => $projectAcademicYearPhaseId,
            'assessment_task_id' => $taskId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function upsertTaskClassroomLink(int $projectAcademicYearPhaseTaskId, int $classroomId, string $taskUrl): bool
    {
        $existsStmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM assessment_task_classroom_links
             WHERE project_academic_year_phase_task_id = :project_academic_year_phase_task_id
               AND classroom_id = :classroom_id'
        );
        $existsStmt->execute([
            'project_academic_year_phase_task_id' => $projectAcademicYearPhaseTaskId,
            'classroom_id' => $classroomId,
        ]);
        $exists = (int) $existsStmt->fetchColumn() > 0;

        $stmt = $this->pdo->prepare(
            'INSERT INTO assessment_task_classroom_links
                (project_academic_year_phase_task_id, classroom_id, task_url, is_visible)
             VALUES
                (:project_academic_year_phase_task_id, :classroom_id, :task_url, 1)
             ON DUPLICATE KEY UPDATE
                task_url = VALUES(task_url),
                is_visible = 1,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'project_academic_year_phase_task_id' => $projectAcademicYearPhaseTaskId,
            'classroom_id' => $classroomId,
            'task_url' => $taskUrl,
        ]);

        return !$exists;
    }

    private function nextPhaseDisplayOrder(int $projectId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(display_order), 0) + 10 FROM assessment_phases WHERE project_id = :project_id');
        $stmt->execute(['project_id' => $projectId]);

        return (int) $stmt->fetchColumn();
    }

    private function nextTaskDisplayOrder(int $phaseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(display_order), 0) + 10 FROM assessment_tasks WHERE phase_id = :phase_id');
        $stmt->execute(['phase_id' => $phaseId]);

        return (int) $stmt->fetchColumn();
    }

    private function findOrCreateClassroom(int $academicYearId, ?int $projectAcademicYearId, string $classroomKey, string $classroomName, string $classroomUrl, string $googleClassroomId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, google_classroom_id
              FROM classrooms
              WHERE academic_year_id = :academic_year_id
                AND classroom_key = :classroom_key
              LIMIT 1'
        );
        $stmt->execute([
            'academic_year_id' => $academicYearId,
            'classroom_key' => $classroomKey,
        ]);
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);

        $classroomNameInput = $classroomName;
        $classroomName = $classroomName !== '' ? $classroomName : $classroomKey;
        $classroomUrl = $classroomUrl !== '' ? $classroomUrl : null;
        $googleClassroomId = $googleClassroomId !== '' ? $googleClassroomId : null;

        if ($classroom !== false) {
            $update = $this->pdo->prepare(
                "UPDATE classrooms
                 SET classroom_name = CASE WHEN :classroom_name_input = '' THEN classroom_name ELSE :classroom_name END,
                     project_academic_year_id = COALESCE(project_academic_year_id, :project_academic_year_id),
                     classroom_url = COALESCE(:classroom_url, classroom_url),
                     google_classroom_id = COALESCE(:google_classroom_id, google_classroom_id),
                     is_active = 1,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $update->execute([
                'classroom_name' => $classroomName,
                'classroom_name_input' => $classroomNameInput,
                'project_academic_year_id' => $projectAcademicYearId,
                'classroom_url' => $classroomUrl,
                'google_classroom_id' => $googleClassroomId,
                'id' => (int) $classroom['id'],
            ]);

            return [
                'id' => (int) $classroom['id'],
                'google_classroom_id' => trim((string) ($classroom['google_classroom_id'] ?? '')) !== '' ? (string) $classroom['google_classroom_id'] : ($googleClassroomId ?? ''),
            ];
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO classrooms
                (academic_year_id, project_academic_year_id, classroom_key, classroom_name, classroom_url, google_classroom_id, is_active)
             VALUES
                (:academic_year_id, :project_academic_year_id, :classroom_key, :classroom_name, :classroom_url, :google_classroom_id, 1)'
        );
        $insert->execute([
            'academic_year_id' => $academicYearId,
            'project_academic_year_id' => $projectAcademicYearId,
            'classroom_key' => $classroomKey,
            'classroom_name' => $classroomName,
            'classroom_url' => $classroomUrl,
            'google_classroom_id' => $googleClassroomId,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'google_classroom_id' => $googleClassroomId ?? '',
        ];
    }

    private function upsertProjectLink(int $classroomId, int $projectAcademicYearId, bool $isActive): bool
    {
        $exists = $this->classroomProjectLinkExists($classroomId, $projectAcademicYearId);
        $stmt = $this->pdo->prepare(
            'INSERT INTO classroom_project_academic_years
                (classroom_id, project_academic_year_id, is_active)
             VALUES
                (:classroom_id, :project_academic_year_id, :is_active)
             ON DUPLICATE KEY UPDATE
                is_active = VALUES(is_active),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'classroom_id' => $classroomId,
            'project_academic_year_id' => $projectAcademicYearId,
            'is_active' => $isActive ? 1 : 0,
        ]);

        return !$exists;
    }

    private function classroomProjectLinkExists(int $classroomId, int $projectAcademicYearId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM classroom_project_academic_years
             WHERE classroom_id = :classroom_id
               AND project_academic_year_id = :project_academic_year_id'
        );
        $stmt->execute([
            'classroom_id' => $classroomId,
            'project_academic_year_id' => $projectAcademicYearId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function parseActiveFlag(mixed $value): bool
    {
        $value = strtolower(trim((string) $value));
        if ($value === '' || in_array($value, ['1', 'true', 'yes', 'si', 'sí', 'actiu', 'active'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'inactiu', 'inactive', 'arxivat'], true)) {
            return false;
        }

        throw new RuntimeException('is_active no vàlid: ' . $value);
    }

    private function userByEmail(string $email): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, surname, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            throw new RuntimeException('No existeix cap usuari amb email ' . $email . '.');
        }

        return $user;
    }

    private function userHasRole(int $userId, string $roleName): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM user_web_roles ur
             INNER JOIN web_roles wr ON wr.id = ur.role_id
             WHERE ur.user_id = :user_id
               AND wr.name = :role_name'
        );
        $stmt->execute([
            'user_id' => $userId,
            'role_name' => $roleName,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function classroomMembershipExists(int $classroomId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM classroom_members WHERE classroom_id = :classroom_id AND user_id = :user_id');
        $stmt->execute([
            'classroom_id' => $classroomId,
            'user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function readCsvWithHeaders(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('No es pot llegir el CSV.');
        }

        $contents = file_get_contents($path, false, null, 0, 4096);
        $delimiter = $this->detectDelimiter((string) $contents);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('No es pot obrir el CSV.');
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false || $headers === [null]) {
            fclose($handle);
            throw new RuntimeException('El CSV està buit.');
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
                throw new RuntimeException('Falta la columna ' . $header . ' a ' . $sheetName . '.');
            }
        }
    }

    private function detectDelimiter(string $sample): string
    {
        $counts = [
            "\t" => substr_count($sample, "\t"),
            ';' => substr_count($sample, ';'),
            ',' => substr_count($sample, ','),
        ];
        arsort($counts);

        return (string) array_key_first($counts);
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

    private function normalizeText(string $text): string
    {
        $text = trim($text);
        return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    }

    private function result(string $message, string $type): array
    {
        return [
            'message' => $message,
            'type' => $type,
        ];
    }
}
