<?php

class AdminController
{
    public function dashboard(): string
    {
        $pdo = $this->pdo();
        $this->ensureProjectClassAssignmentsTable($pdo);
        $this->ensureProjectsDisplayOrderColumn($pdo);
        $this->ensureProjectTeamMemberRolesTable($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($pdo);
        }

        $dashboardData = (new AdminDashboardService($pdo))->dashboardData();

        $message = $_SESSION['admin_message'] ?? null;
        $messageType = $_SESSION['admin_message_type'] ?? 'success';
        $importSummary = $_SESSION['admin_import_summary'] ?? null;

        if ($message !== null) {
            unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
        }

        if ($importSummary !== null) {
            unset($_SESSION['admin_import_summary']);
        }

        return view('admin.dashboard', $dashboardData + [
            'title' => 'Dashboard administració',
            'message' => $message,
            'messageType' => $messageType,
            'importSummary' => $importSummary,
            'csrfToken' => (new AuthService())->csrfToken(),
        ]);
    }

    private function handlePost(PDO $pdo): void
    {
        $action = (string) ($_POST['action'] ?? '');
        $csrfToken = (string) ($_POST['csrf_token'] ?? '');
        $authService = new AuthService();

        if (!$authService->verifyCsrfToken($csrfToken)) {
            $this->setMessage('La sessió del formulari ha caducat. Torna-ho a provar.', 'error');
            $this->auditAdminAction('csrf_failed', ['action' => $action]);
            return;
        }

        $projectHandlers = [
            'toggle_project' => 'toggleProject',
            'update_project_order' => 'updateProjectOrder',
            'assign_project_to_class' => 'assignProjectToClass',
            'sync_project_class_assignments' => 'syncProjectClassAssignments',
            'update_project_academic_year_statuses' => 'updateProjectAcademicYearStatuses',
            'update_project_assignment_status' => 'updateProjectAssignmentStatus',
            'delete_project_assignment' => 'deleteProjectAssignment',
        ];

        if (isset($projectHandlers[$action])) {
            $result = (new AdminProjectService($pdo))->{$projectHandlers[$action]}($_POST);
            $this->setMessage((string) $result['message'], (string) $result['type']);
            $this->auditAdminAction($action);
            return;
        }

        $userHandlers = [
            'create_user' => 'createUser',
            'toggle_user' => 'toggleUser',
            'update_student' => 'updateUser',
        ];

        if (isset($userHandlers[$action])) {
            $result = (new AdminUserService($pdo))->{$userHandlers[$action]}($_POST);
            $this->setMessage((string) $result['message'], (string) $result['type']);
            $this->auditAdminAction($action);
            return;
        }

        $classHandlers = [
            'sync_class_teachers' => 'syncClassTeachers',
            'sync_all_class_teachers' => 'syncAllClassTeachers',
        ];

        if (isset($classHandlers[$action])) {
            $result = (new AdminClassService($pdo))->{$classHandlers[$action]}($_POST);
            $this->setMessage((string) $result['message'], (string) $result['type']);
            $this->auditAdminAction($action);
            return;
        }

        if ($action === 'import_students') {
            $result = (new AdminStudentImportService($pdo))->importUploadedFile($_FILES['students_file'] ?? []);
            if (!empty($result['summary'])) {
                $_SESSION['admin_import_summary'] = $result['summary'];
            }

            $this->setMessage((string) $result['message'], (string) $result['type']);
            $this->auditAdminAction($action);
            return;
        }

        $assessmentHandlers = [
            'import_assessment_structure' => 'importAssessmentStructure',
            'toggle_assessment_phase' => 'toggleAssessmentPhase',
            'toggle_assessment_task' => 'toggleAssessmentTask',
        ];

        if (isset($assessmentHandlers[$action])) {
            $service = new AdminAssessmentStructureService($pdo);
            $method = $assessmentHandlers[$action];
            $result = $action === 'import_assessment_structure'
                ? $service->{$method}($_FILES)
                : $service->{$method}($_POST);

            $this->setMessage((string) $result['message'], (string) $result['type']);
            $this->auditAdminAction($action);
            return;
        }

        $this->setMessage('Acció d’administració no vàlida.', 'error');
        $this->auditAdminAction('unknown_action', ['action' => $action]);
    }

    private function auditAdminAction(string $action, array $context = []): void
    {
        $actor = (new AuthService())->actorUser();
        $actorId = $actor !== null ? (int) ($actor['id'] ?? 0) : 0;
        $actorEmail = $actor !== null ? (string) ($actor['email'] ?? '') : '';
        $parts = [
            'admin_action=' . $action,
            'actor_id=' . $actorId,
        ];

        if ($actorEmail !== '') {
            $parts[] = 'actor_email=' . $actorEmail;
        }

        foreach ($context as $key => $value) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $key);
            $parts[] = $safeKey . '=' . str_replace(["\r", "\n"], ' ', (string) $value);
        }

        (new LogService())->write(implode(' ', $parts));
    }

    private function setMessage(string $message, string $type): void
    {
        $_SESSION['admin_message'] = $message;
        $_SESSION['admin_message_type'] = $type;
    }

    private function ensureProjectClassAssignmentsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS project_class_assignments (
                 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                 project_academic_year_id BIGINT UNSIGNED NOT NULL,
                 class_id INT UNSIGNED NOT NULL,
                 status VARCHAR(50) NOT NULL DEFAULT "actiu",
                 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                 PRIMARY KEY (id),
                 UNIQUE KEY uq_project_class_assignments (project_academic_year_id, class_id),
                 KEY idx_project_class_assignments_project_academic_year_id (project_academic_year_id),
                 KEY idx_project_class_assignments_class_id (class_id),
                 CONSTRAINT fk_project_class_assignments_project_academic_year
                     FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE,
                 CONSTRAINT fk_project_class_assignments_class
                     FOREIGN KEY (class_id) REFERENCES classes (id)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            "UPDATE project_class_assignments
             SET status = CASE status
                 WHEN 'planned' THEN 'pendent'
                 WHEN 'previst' THEN 'pendent'
                 WHEN 'active' THEN 'actiu'
                 WHEN 'completed' THEN 'realitzat'
                 WHEN 'completat' THEN 'realitzat'
                 ELSE status
             END
             WHERE status IN ('planned', 'previst', 'active', 'completed', 'completat')"
        );
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

    private function ensureProjectTeamMemberRolesTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS project_team_member_roles (
                 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                 project_team_member_id BIGINT UNSIGNED NOT NULL,
                 project_role_id INT UNSIGNED NOT NULL,
                 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                 PRIMARY KEY (id),
                 UNIQUE KEY uq_project_team_member_roles_member_role (project_team_member_id, project_role_id),
                 KEY idx_project_team_member_roles_role_id (project_role_id),
                 CONSTRAINT fk_project_team_member_roles_member
                     FOREIGN KEY (project_team_member_id) REFERENCES project_team_members (id)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE,
                 CONSTRAINT fk_project_team_member_roles_role
                     FOREIGN KEY (project_role_id) REFERENCES project_roles (id)
                     ON DELETE RESTRICT
                     ON UPDATE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'INSERT IGNORE INTO project_team_member_roles (project_team_member_id, project_role_id)
             SELECT ptm.id, ptm.project_role_id
               FROM project_team_members ptm
              WHERE ptm.project_role_id IS NOT NULL'
        );
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
