<?php

declare(strict_types=1);

class AdminActionService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function handle(string $action, array $post, array $files): array
    {
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
            $result = (new AdminProjectService($this->pdo))->{$projectHandlers[$action]}($post);
            $this->auditAdminAction($action);

            return $result;
        }

        $userHandlers = [
            'create_user' => 'createUser',
            'toggle_user' => 'toggleUser',
            'update_student' => 'updateUser',
        ];

        if (isset($userHandlers[$action])) {
            $result = (new AdminUserService($this->pdo))->{$userHandlers[$action]}($post);
            $this->auditAdminAction($action);

            return $result;
        }

        $classHandlers = [
            'sync_class_teachers' => 'syncClassTeachers',
            'sync_all_class_teachers' => 'syncAllClassTeachers',
        ];

        if (isset($classHandlers[$action])) {
            $result = (new AdminClassService($this->pdo))->{$classHandlers[$action]}($post);
            $this->auditAdminAction($action);

            return $result;
        }

        if ($action === 'import_students') {
            $result = (new AdminStudentImportService($this->pdo))->importUploadedFile($files['students_file'] ?? []);
            $this->auditAdminAction($action);

            return $result;
        }

        $assessmentHandlers = [
            'import_assessment_structure' => 'importAssessmentStructure',
            'toggle_assessment_phase' => 'toggleAssessmentPhase',
            'toggle_assessment_task' => 'toggleAssessmentTask',
        ];

        if (isset($assessmentHandlers[$action])) {
            $service = new AdminAssessmentStructureService($this->pdo);
            $method = $assessmentHandlers[$action];
            $result = $action === 'import_assessment_structure'
                ? $service->{$method}($files)
                : $service->{$method}($post);
            $this->auditAdminAction($action);

            return $result;
        }

        $this->auditAdminAction('unknown_action', ['action' => $action]);

        return [
            'message' => 'Acció d’administració no vàlida.',
            'type' => 'error',
        ];
    }

    public function auditAdminAction(string $action, array $context = []): void
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
}
