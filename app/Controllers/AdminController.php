<?php

class AdminController
{
    public function dashboard(): string
    {
        $pdo = $this->pdo();
        (new AdminSchemaMaintenanceService($pdo))->ensureAdminSchema();

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

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
