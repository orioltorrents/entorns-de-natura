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

        $usersStmt = $pdo->query(
            'SELECT u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                    u.gender, u.article, u.inaturalist_user_login,
                    COUNT(DISTINCT sv.id) AS visit_count
              FROM users u
              LEFT JOIN site_visits sv ON sv.user_id = u.id
              GROUP BY u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                       u.gender, u.article, u.inaturalist_user_login
              ORDER BY u.created_at DESC'
        );
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        $rolesStmt = $pdo->query(
            'SELECT r.id, r.name, COUNT(ur.user_id) AS user_count
             FROM web_roles r
             LEFT JOIN user_web_roles ur ON ur.role_id = r.id
             GROUP BY r.id, r.name
             ORDER BY r.name'
        );
        $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $typicalProjectRoleNames = ['coordinador/a', 'informàtic/a', 'cartògraf/a', 'científic/a'];
        $typicalRolePlaceholders = implode(',', array_fill(0, count($typicalProjectRoleNames), '?'));
        $projectRolesStmt = $pdo->prepare(
            'SELECT pr.id, pr.name, COUNT(DISTINCT ptmr.project_team_member_id) AS member_count
               FROM project_roles pr
               LEFT JOIN project_team_member_roles ptmr ON ptmr.project_role_id = pr.id
              WHERE pr.name IN (' . $typicalRolePlaceholders . ')
              GROUP BY pr.id, pr.name
              ORDER BY CASE pr.name
                  WHEN "coordinador/a" THEN 1
                  WHEN "informàtic/a" THEN 2
                  WHEN "cartògraf/a" THEN 3
                  WHEN "científic/a" THEN 4
                  ELSE 99
              END, pr.name'
        );
        $projectRolesStmt->execute($typicalProjectRoleNames);
        $projectRoles = $projectRolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectMembersWithoutRoleStmt = $pdo->prepare(
            'SELECT COUNT(*)
               FROM project_team_members ptm
               LEFT JOIN project_team_member_roles ptmr ON ptmr.project_team_member_id = ptm.id
               LEFT JOIN project_roles pr ON pr.id = ptmr.project_role_id
                AND pr.name IN (' . $typicalRolePlaceholders . ')
              GROUP BY ptm.id
             HAVING COUNT(pr.id) = 0'
        );
        $projectMembersWithoutRoleStmt->execute($typicalProjectRoleNames);
        $projectMembersWithoutRole = count($projectMembersWithoutRoleStmt->fetchAll(PDO::FETCH_COLUMN));
        $projectTeamMembershipCount = (int) $pdo->query('SELECT COUNT(*) FROM project_team_members')->fetchColumn();

        $projectsStmt = $pdo->query(
            'SELECT p.id, p.name, p.slug, p.display_order, p.is_active, p.created_at,
                    COALESCE(pt.description, "") AS description
              FROM projects p
             LEFT JOIN languages l ON l.code = "ca"
             LEFT JOIN project_translations pt ON pt.project_id = p.id AND pt.language_id = l.id
             ORDER BY p.display_order, p.name'
        );
        $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectAssetService = new ProjectAssetService();
        $assetsByProject = $projectAssetService->assetsByProjectIds(array_map(static fn (array $project): int => (int) $project['id'], $projects));
        $logoAssetsByProject = $projectAssetService->logoAssetByProjectIds(array_map(static fn (array $project): int => (int) $project['id'], $projects));

        foreach ($projects as &$project) {
            $project['assets'] = $assetsByProject[(int) $project['id']] ?? [];
            $project['logo_asset'] = $logoAssetsByProject[(int) $project['id']] ?? null;
        }
        unset($project);

        $userRolesStmt = $pdo->query(
            'SELECT ur.user_id, r.name AS role_name
             FROM user_web_roles ur
             INNER JOIN web_roles r ON r.id = ur.role_id
             ORDER BY ur.user_id, r.name'
        );
        $userRoles = $userRolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $classesStmt = $pdo->query(
            'SELECT c.id, c.class_name AS name, c.class_code AS code, c.academic_year_id, ay.name AS academic_year_name
               FROM classes c
               INNER JOIN academic_years ay ON ay.id = c.academic_year_id
              ORDER BY ay.start_year ASC, c.class_code ASC'
        );
        $classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

        $academicYearsStmt = $pdo->query('SELECT id, name, start_year, end_year, is_current FROM academic_years ORDER BY start_year ASC, end_year ASC');
        $academicYears = $academicYearsStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectAcademicYearsStmt = $pdo->query(
            'SELECT pay.id, pay.project_id, pay.academic_year_id, ay.name AS academic_year_name, pay.status
               FROM project_academic_years pay
               INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
              ORDER BY ay.start_year ASC, pay.project_id ASC'
        );
        $projectAcademicYears = $projectAcademicYearsStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectAssignmentsStmt = $pdo->query(
            'SELECT pg.id, pg.class_id, p.id AS project_id, ay.name AS academic_year_name, pg.status, pg.created_at,
                    c.class_name AS class_name,
                    c.class_code AS class_code,
                    p.name AS project_name,
                    p.slug AS project_slug
              FROM project_class_assignments pg
              INNER JOIN classes c ON c.id = pg.class_id
              INNER JOIN project_academic_years pay ON pay.id = pg.project_academic_year_id
              INNER JOIN projects p ON p.id = pay.project_id
              INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
              ORDER BY c.class_name, ay.id, p.display_order, p.name'
        );
        $projectAssignments = $projectAssignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectTeamsStmt = $pdo->query(
            'SELECT pt.id AS project_team_id,
                    pt.project_academic_year_id,
                    pt.team_code,
                    pt.team_name,
                    pt.class_group,
                    pt.display_order,
                    pt.is_active,
                    p.name AS project_name,
                    p.slug AS project_slug,
                    ay.name AS academic_year_name,
                    ptm.user_id,
                    ptm.class_id AS member_class_id,
                    u.name AS member_name,
                    u.surname AS member_surname,
                    u.email AS member_email,
                    mc.class_code AS member_class_code,
                    GROUP_CONCAT(DISTINCT pr.name ORDER BY pr.name SEPARATOR "||") AS project_role_names
               FROM project_teams pt
               INNER JOIN project_academic_years pay ON pay.id = pt.project_academic_year_id
               INNER JOIN projects p ON p.id = pay.project_id
               INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
               LEFT JOIN project_team_members ptm ON ptm.project_team_id = pt.id
               LEFT JOIN users u ON u.id = ptm.user_id
               LEFT JOIN classes mc ON mc.id = ptm.class_id
               LEFT JOIN project_team_member_roles ptmr ON ptmr.project_team_member_id = ptm.id
               LEFT JOIN project_roles pr ON pr.id = ptmr.project_role_id
              GROUP BY pt.id, pt.project_academic_year_id, pt.team_code, pt.team_name, pt.class_group,
                       pt.display_order, pt.is_active, p.name, p.slug, ay.name, ptm.id, ptm.user_id,
                       ptm.class_id, u.name, u.surname, u.email, mc.class_code
              ORDER BY ay.id, p.display_order, p.name, pt.display_order, pt.team_code, u.surname, u.name'
        );
        $projectTeamsRows = $projectTeamsStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectTeams = [];
        $projectRoleGroups = [];
        foreach ($projectTeamsRows as $row) {
            $projectTeamId = (int) $row['project_team_id'];
            $roleNames = array_values(array_intersect(
                $this->splitStoredProjectRoleNames((string) ($row['project_role_names'] ?? '')),
                $typicalProjectRoleNames
            ));
            if (!isset($projectTeams[$projectTeamId])) {
                $projectTeams[$projectTeamId] = [
                    'id' => $projectTeamId,
                    'project_academic_year_id' => (int) $row['project_academic_year_id'],
                    'team_code' => (string) $row['team_code'],
                    'team_name' => (string) ($row['team_name'] ?? ''),
                    'class_group' => (string) ($row['class_group'] ?? ''),
                    'display_order' => (int) $row['display_order'],
                    'is_active' => (int) $row['is_active'],
                    'project_name' => (string) $row['project_name'],
                    'project_slug' => (string) $row['project_slug'],
                    'academic_year_name' => (string) $row['academic_year_name'],
                    'members' => [],
                ];
            }

            if (!empty($row['user_id'])) {
                $member = [
                    'id' => (int) $row['user_id'],
                    'name' => trim((string) $row['member_name'] . ' ' . (string) $row['member_surname']),
                    'email' => (string) ($row['member_email'] ?? ''),
                    'class_id' => !empty($row['member_class_id']) ? (int) $row['member_class_id'] : null,
                    'class_code' => (string) ($row['member_class_code'] ?? ''),
                    'project_role_names' => $roleNames,
                    'team_code' => (string) $row['team_code'],
                    'team_name' => (string) ($row['team_name'] ?? ''),
                    'class_group' => (string) ($row['class_group'] ?? ''),
                    'project_name' => (string) $row['project_name'],
                    'academic_year_name' => (string) $row['academic_year_name'],
                    'project_slug' => (string) $row['project_slug'],
                ];

                $projectTeams[$projectTeamId]['members'][] = [
                    'id' => $member['id'],
                    'name' => $member['name'],
                    'email' => $member['email'],
                    'class_code' => $member['class_code'],
                    'project_role_names' => $member['project_role_names'],
                ];

                $roleGroupNames = $roleNames !== [] ? $roleNames : ['Sense rol'];
                foreach ($roleGroupNames as $roleGroupName) {
                    if (!isset($projectRoleGroups[$roleGroupName])) {
                        $projectRoleGroups[$roleGroupName] = [
                            'name' => $roleGroupName,
                            'members' => [],
                        ];
                    }

                    $projectRoleGroups[$roleGroupName]['members'][] = $member;
                }
            }
        }

        $classMembershipsStmt = $pdo->query(
            'SELECT cm.user_id, cm.class_id, c.class_name AS class_name, c.class_code,
                    c.academic_year_id, ay.name AS academic_year_name
              FROM class_members cm
              INNER JOIN classes c ON c.id = cm.class_id
              INNER JOIN academic_years ay ON ay.id = c.academic_year_id
              ORDER BY cm.user_id, ay.start_year, c.class_name'
        );
        $classMemberships = $classMembershipsStmt->fetchAll(PDO::FETCH_ASSOC);

        $classTeachersStmt = $pdo->query(
            'SELECT ct.class_id, ct.user_id, c.class_name AS class_name, c.class_code, u.name, u.surname
              FROM class_teachers ct
              INNER JOIN classes c ON c.id = ct.class_id
              INNER JOIN users u ON u.id = ct.user_id
              ORDER BY c.class_name, u.name, u.surname'
        );
        $classTeachers = $classTeachersStmt->fetchAll(PDO::FETCH_ASSOC);
        $assessmentStructure = $this->assessmentStructure($pdo);

        $roleMap = [];
        foreach ($userRoles as $row) {
            $roleMap[(int) $row['user_id']][] = (string) $row['role_name'];
        }

        $userClassMap = [];
        $userClassGroupMap = [];
        $userClassCodeMap = [];
        $userAcademicYearMap = [];
        foreach ($classMemberships as $membership) {
            $userClassMap[(int) $membership['user_id']] = (int) $membership['class_id'];
            $userClassGroupMap[(int) $membership['user_id']] = (string) $membership['class_name'];
            $userClassCodeMap[(int) $membership['user_id']] = (string) $membership['class_code'];
            $userAcademicYearMap[(int) $membership['user_id']] = [
                'id' => (int) $membership['academic_year_id'],
                'name' => (string) $membership['academic_year_name'],
            ];
        }

        foreach ($users as &$user) {
            $user['roles'] = $roleMap[(int) $user['id']] ?? [];
            $user['status'] = ((int) $user['is_active'] === 1) ? 'Actiu' : 'Inactiu';
            $user['class_id'] = $userClassMap[(int) $user['id']] ?? null;
            $user['class_group'] = $userClassGroupMap[(int) $user['id']] ?? null;
            $user['class_code'] = $userClassCodeMap[(int) $user['id']] ?? null;
            $user['academic_year'] = $userAcademicYearMap[(int) $user['id']] ?? null;
        }
        unset($user);

        $studentUsers = array_values(array_filter(
            $users,
            static fn (array $user): bool => in_array('student', $user['roles'], true)
        ));

        $teacherUsers = array_values(array_filter(
            $users,
            static fn (array $user): bool => in_array('teacher', $user['roles'], true)
        ));

        $classTeachersMap = [];
        $teacherClassMap = [];
        foreach ($classTeachers as $teacherAssignment) {
            $classId = (int) $teacherAssignment['class_id'];
            $teacherId = (int) $teacherAssignment['user_id'];
            $classTeachersMap[$classId][] = [
                'id' => $teacherId,
                'name' => trim((string) $teacherAssignment['name'] . ' ' . (string) $teacherAssignment['surname']),
            ];
            $teacherClassMap[$teacherId][] = [
                'id' => $classId,
                'name' => (string) $teacherAssignment['class_name'],
                'code' => (string) $teacherAssignment['class_code'],
            ];
        }

        $analyticsService = new AnalyticsService();
        $analytics = $analyticsService->getDashboardStats($pdo);
        $geoMapPoints = $this->buildGeoMapPoints($analytics['geo_stats'] ?? []);

        $message = $_SESSION['admin_message'] ?? null;
        $messageType = $_SESSION['admin_message_type'] ?? 'success';
        $importSummary = $_SESSION['admin_import_summary'] ?? null;

        if ($message !== null) {
            unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
        }

        if ($importSummary !== null) {
            unset($_SESSION['admin_import_summary']);
        }

        return view('admin.dashboard', [
            'title' => 'Dashboard administració',
            'users' => $users,
            'roles' => $roles,
            'projects' => $projects,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'projectAcademicYears' => $projectAcademicYears,
            'studentUsers' => $studentUsers,
            'teacherUsers' => $teacherUsers,
            'classTeachersMap' => $classTeachersMap,
            'teacherClassMap' => $teacherClassMap,
            'projectAssignments' => $projectAssignments,
            'projectTeams' => array_values($projectTeams),
            'projectRoleGroups' => array_values($projectRoleGroups),
            'projectRoles' => $projectRoles,
            'projectMembersWithoutRole' => $projectMembersWithoutRole,
            'projectTeamMembershipCount' => $projectTeamMembershipCount,
            'assessmentStructure' => $assessmentStructure,
            'roleMap' => $roleMap,
            'analytics' => $analytics,
            'geoMapPoints' => $geoMapPoints,
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

        $handlers = [
            'sync_class_teachers' => 'syncClassTeachers',
            'sync_all_class_teachers' => 'syncAllClassTeachers',
            'import_students' => 'importStudents',
            'import_assessment_structure' => 'importAssessmentStructure',
            'toggle_assessment_phase' => 'toggleAssessmentPhase',
            'toggle_assessment_task' => 'toggleAssessmentTask',
        ];

        if (!isset($handlers[$action])) {
            $this->setMessage('Acció d’administració no vàlida.', 'error');
            $this->auditAdminAction('unknown_action', ['action' => $action]);
            return;
        }

        $this->{$handlers[$action]}($pdo);
        $this->auditAdminAction($action);
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

    private function assessmentStructure(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT
                pay.id AS project_academic_year_id,
                p.id AS project_id,
                p.name AS project_name,
                p.slug AS project_slug,
                ay.name AS academic_year_name,
                ap.id AS phase_id,
                ap.phase_key,
                ap.title AS phase_title,
                ap.section_type,
                payp.display_order AS phase_order,
                payp.is_active,
                payp.id AS project_academic_year_phase_id,
                at.id AS task_id,
                at.source_column,
                at.title AS task_title,
                at.weight_label,
                at.role_filter,
                paypt.display_order AS task_order,
                paypt.is_visible,
                paypt.id AS project_academic_year_phase_task_id
             FROM project_academic_year_phases payp
             INNER JOIN project_academic_years pay ON pay.id = payp.project_academic_year_id
             INNER JOIN projects p ON p.id = pay.project_id
             INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
             INNER JOIN assessment_phases ap ON ap.id = payp.assessment_phase_id
             LEFT JOIN project_academic_year_phase_tasks paypt ON paypt.project_academic_year_phase_id = payp.id
             LEFT JOIN assessment_tasks at ON at.id = paypt.assessment_task_id
             ORDER BY p.display_order, p.name, ay.id, payp.display_order, ap.id, paypt.display_order, at.id'
        );

        $projects = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $projectAcademicYearId = (int) $row['project_academic_year_id'];
            $phaseId = (int) $row['phase_id'];

            if (!isset($projects[$projectAcademicYearId])) {
                $projects[$projectAcademicYearId] = [
                    'id' => $projectAcademicYearId,
                    'project_id' => (int) $row['project_id'],
                    'name' => (string) $row['project_name'],
                    'slug' => (string) $row['project_slug'],
                    'academic_year_name' => (string) $row['academic_year_name'],
                    'phases' => [],
                ];
            }

            if (!isset($projects[$projectAcademicYearId]['phases'][$phaseId])) {
                $projects[$projectAcademicYearId]['phases'][$phaseId] = [
                    'id' => $phaseId,
                    'project_academic_year_phase_id' => (int) $row['project_academic_year_phase_id'],
                    'phase_key' => (string) $row['phase_key'],
                    'title' => (string) $row['phase_title'],
                    'section_type' => (string) $row['section_type'],
                    'display_order' => (int) $row['phase_order'],
                    'is_active' => (int) $row['is_active'],
                    'tasks' => [],
                ];
            }

            if ($row['task_id'] !== null) {
                $projects[$projectAcademicYearId]['phases'][$phaseId]['tasks'][] = [
                    'id' => (int) $row['task_id'],
                    'project_academic_year_phase_task_id' => (int) $row['project_academic_year_phase_task_id'],
                    'source_column' => (string) $row['source_column'],
                    'title' => (string) $row['task_title'],
                    'weight_label' => $row['weight_label'] !== null ? (string) $row['weight_label'] : '',
                    'role_filter' => $row['role_filter'] !== null ? (string) $row['role_filter'] : '',
                    'display_order' => (int) $row['task_order'],
                    'is_visible' => (int) $row['is_visible'],
                ];
            }
        }

        foreach ($projects as &$project) {
            $project['phases'] = array_values($project['phases']);
        }
        unset($project);

        return array_values($projects);
    }

    private function toggleAssessmentPhase(PDO $pdo): void
    {
        $projectAcademicYearPhaseId = filter_input(INPUT_POST, 'project_academic_year_phase_id', FILTER_VALIDATE_INT);
        if ($projectAcademicYearPhaseId === null || $projectAcademicYearPhaseId === false) {
            $this->setMessage('Fase no vàlida.', 'error');
            return;
        }

        $stmt = $pdo->prepare('SELECT is_active FROM project_academic_year_phases WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $projectAcademicYearPhaseId]);
        $phase = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($phase === false) {
            $this->setMessage('No s’ha trobat la fase.', 'error');
            return;
        }

        $newState = ((int) $phase['is_active'] === 1) ? 0 : 1;
        $update = $pdo->prepare('UPDATE project_academic_year_phases SET is_active = :is_active WHERE id = :id');
        $update->execute(['is_active' => $newState, 'id' => (int) $projectAcademicYearPhaseId]);

        $this->setMessage('Estat de la fase actualitzat.', 'success');
    }

    private function toggleAssessmentTask(PDO $pdo): void
    {
        $projectAcademicYearPhaseTaskId = filter_input(INPUT_POST, 'project_academic_year_phase_task_id', FILTER_VALIDATE_INT);
        if ($projectAcademicYearPhaseTaskId === null || $projectAcademicYearPhaseTaskId === false) {
            $this->setMessage('Tasca no vàlida.', 'error');
            return;
        }

        $stmt = $pdo->prepare('SELECT is_visible FROM project_academic_year_phase_tasks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $projectAcademicYearPhaseTaskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task === false) {
            $this->setMessage('No s’ha trobat la tasca.', 'error');
            return;
        }

        $newState = ((int) $task['is_visible'] === 1) ? 0 : 1;
        $update = $pdo->prepare('UPDATE project_academic_year_phase_tasks SET is_visible = :is_visible WHERE id = :id');
        $update->execute(['is_visible' => $newState, 'id' => (int) $projectAcademicYearPhaseTaskId]);

        $this->setMessage('Visibilitat de la tasca actualitzada.', 'success');
    }

    private function importAssessmentStructure(PDO $pdo): void
    {
        if (!isset($_FILES['phases_file'], $_FILES['tasks_file'])
            || !is_uploaded_file($_FILES['phases_file']['tmp_name'])
            || !is_uploaded_file($_FILES['tasks_file']['tmp_name'])
        ) {
            $this->setMessage('Cal pujar els CSV assessment_phases i assessment_tasks.', 'error');
            return;
        }

        try {
            $importer = new AssessmentStructureImportService($pdo);
            $result = $importer->importFromCsv(
                (string) $_FILES['phases_file']['tmp_name'],
                (string) $_FILES['tasks_file']['tmp_name']
            );
        } catch (Throwable $e) {
            $this->setMessage('No s’ha pogut importar l’estructura d’avaluació: ' . $e->getMessage(), 'error');
            return;
        }

        $message = 'Estructura importada: '
            . (int) $result['phases_imported'] . ' fases i '
            . (int) $result['tasks_imported'] . ' tasques.';

        if (!empty($result['errors'])) {
            $message .= ' Errors: ' . implode(' | ', array_slice($result['errors'], 0, 5));
            $this->setMessage($message, 'error');
            return;
        }

        $this->setMessage($message, 'success');
    }

    private function importStudents(PDO $pdo): void
    {
        if (!isset($_FILES['students_file']) || !is_uploaded_file($_FILES['students_file']['tmp_name'])) {
            $this->setMessage('No s’ha rebut cap fitxer CSV.', 'error');
            return;
        }

        $filePath = (string) $_FILES['students_file']['tmp_name'];
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            $this->setMessage('No s’ha pogut llegir el fitxer CSV.', 'error');
            return;
        }

        $headers = fgetcsv($handle);
        if ($headers === false || $headers === [null]) {
            fclose($handle);
            $this->setMessage('El fitxer CSV està buit.', 'error');
            return;
        }

        $normalizedHeaders = [];
        foreach ($headers as $header) {
            $normalizedHeaders[] = $this->normalizeHeader((string) $header);
        }

        $created = 0;
        $updated = 0;
        $teamAssignments = 0;
        $errors = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if ($row === [null] || $row === false) {
                continue;
            }

            $data = [];
            foreach ($normalizedHeaders as $index => $header) {
                $data[$header] = isset($row[$index]) ? (string) $row[$index] : '';
            }

            $name = trim($data['name'] ?? $data['nom'] ?? '');
            $surname = trim($data['surname'] ?? $data['cognoms'] ?? '');
            $email = strtolower(trim($data['email'] ?? ''));
            $password = trim($data['password'] ?? '');
            $classIdValue = trim($data['class_id'] ?? '');
            $classCode = trim($data['class_code'] ?? $data['classid'] ?? '');
            $className = trim($data['class'] ?? $data['classe'] ?? $data['class_name'] ?? $data['grup_classe'] ?? $data['grup_classes'] ?? '');
            $rolesInput = trim($data['roles'] ?? $data['role'] ?? 'student');
            $isActive = $this->parseImportBoolean($data['is_active'] ?? $data['active'] ?? $data['status'] ?? '');
            $projectAcademicYearIdValue = trim($data['project_academic_year_id'] ?? $data['project_year_id'] ?? '');
            $projectIdValue = trim($data['project_id'] ?? '');
            $projectSlug = trim($data['project_slug'] ?? $data['project'] ?? '');
            $academicYearName = trim($data['academic_year'] ?? $data['academic_year_name'] ?? '');
            $teamCode = trim($data['team_code'] ?? $data['team'] ?? '');
            $teamName = trim($data['team_name'] ?? '');
            $teamClassGroup = trim($data['class_group'] ?? '');
            $projectRoleName = trim($data['project_role'] ?? $data['project_role_name'] ?? '');
            $gender = trim($data['gender'] ?? $data['genere'] ?? '');
            $article = trim($data['article'] ?? '');
            $inaturalistUserLogin = trim($data['inaturalist_user_login'] ?? '');
            if ($name === '' || $email === '') {
                $errors[] = 'Fila ' . $lineNumber . ': falta nom o email.';
                continue;
            }

            $existingUserStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existingUserStmt->execute(['email' => $email]);
            $existingUser = $existingUserStmt->fetch(PDO::FETCH_ASSOC);

            $pdo->beginTransaction();

            try {
                $userId = $this->findOrCreateUser($pdo, $name, $surname, $email, $password);
                if ($userId === null) {
                    throw new RuntimeException('No s’ha pogut crear l’usuari.');
                }

                if ($isActive !== null) {
                    $this->updateUserActiveState($pdo, $userId, $isActive);
                }

                $this->setRolesForUser($pdo, $userId, $rolesInput);
                $this->updateUserProfileFields($pdo, $userId, $gender, $article, $inaturalistUserLogin);

                $classId = $this->resolveImportClassId($pdo, $classIdValue, $classCode, $className, $academicYearName);
                if ($classId !== null) {
                    $this->syncClassAssignment($pdo, $userId, $classId, 'import');
                }

                $projectAcademicYearId = $this->resolveProjectAcademicYearId($pdo, $projectAcademicYearIdValue, $projectIdValue, $projectSlug, $academicYearName);
                if ($teamCode !== '') {
                    if ($projectAcademicYearId === null) {
                        throw new RuntimeException('Cal `project_academic_year_id` o bé `project_slug` + `academic_year` per importar `team_code`.');
                    }

                    $projectRoleIds = $this->resolveProjectRoleIds($pdo, $projectRoleName);
                    if ($projectRoleName !== '' && $projectRoleIds === []) {
                        throw new RuntimeException('No s’ha pogut resoldre el rol de projecte.');
                    }

                    $this->syncProjectTeamMembership(
                        $pdo,
                        $userId,
                        (int) $projectAcademicYearId,
                        $classId,
                        $teamCode,
                        $teamName,
                        $projectRoleIds,
                        $teamClassGroup !== '' ? $teamClassGroup : ($className !== '' ? $className : $classCode)
                    );
                    $teamAssignments++;
                }

                $pdo->commit();

                if ($existingUser === false) {
                    $created++;
                } else {
                    $updated++;
                }

            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $errors[] = 'Fila ' . $lineNumber . ': ' . $exception->getMessage();
                continue;
            }
        }

        fclose($handle);

        if ($errors !== []) {
            $this->setMessage('Importació finalitzada amb errors: ' . implode(' | ', $errors), 'error');
            return;
        }

        $message = 'Importació completada: ' . $created . ' usuaris creats, ' . $updated . ' actualitzats i ' . $teamAssignments . ' equips sincronitzats.';

        $_SESSION['admin_import_summary'] = [
            'created' => $created,
            'updated' => $updated,
            'team_assignments' => $teamAssignments,
        ];

        $this->setMessage($message, 'success');
    }

    private function syncClassTeachers(PDO $pdo): void
    {
        $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $teacherIds = $_POST['teacher_ids'] ?? [];

        if ($classId === null || $classId === false) {
            $this->setMessage('Classe no vàlida.', 'error');
            return;
        }

        if (!is_array($teacherIds)) {
            $teacherIds = [];
        }

        $teacherIds = array_values(array_unique(array_filter(array_map(
            static fn ($teacherId): int => (int) $teacherId,
            $teacherIds
        ), static fn (int $teacherId): bool => $teacherId > 0)));

         $classStmt = $pdo->prepare('SELECT id, class_name AS name FROM classes WHERE id = :id LIMIT 1');
        $classStmt->execute(['id' => (int) $classId]);
        $class = $classStmt->fetch(PDO::FETCH_ASSOC);

        if ($class === false) {
            $this->setMessage('No s’ha trobat la classe.', 'error');
            return;
        }

        $validTeacherIds = [];
        if ($teacherIds !== []) {
            $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
            $stmt = $pdo->prepare(
                "SELECT DISTINCT u.id
                 FROM users u
                  INNER JOIN user_web_roles ur ON ur.user_id = u.id
                  INNER JOIN web_roles r ON r.id = ur.role_id
                 WHERE r.name = 'teacher'
                   AND u.id IN ($placeholders)"
            );
            $stmt->execute($teacherIds);
            $validTeacherIds = array_map(static fn (array $row): int => (int) $row['id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        $pdo->beginTransaction();

        try {
            $deleteStmt = $pdo->prepare('DELETE FROM class_teachers WHERE class_id = :class_id');
            $deleteStmt->execute(['class_id' => (int) $classId]);

            if ($validTeacherIds !== []) {
                $insertStmt = $pdo->prepare('INSERT INTO class_teachers (class_id, user_id) VALUES (:class_id, :user_id)');
                foreach ($validTeacherIds as $teacherId) {
                    $insertStmt->execute([
                        'class_id' => (int) $classId,
                        'user_id' => $teacherId,
                    ]);
                }
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->setMessage('No s’ha pogut actualitzar el professorat de la classe.', 'error');
            return;
        }

        $this->setMessage('Professorat actualitzat per a ' . (string) $class['name'] . '.', 'success');
    }

    private function syncAllClassTeachers(PDO $pdo): void
    {
        $assignments = $_POST['teacher_ids_by_class'] ?? [];
        if (!is_array($assignments)) {
            $assignments = [];
        }

        $classRows = $pdo->query('SELECT id FROM classes')->fetchAll(PDO::FETCH_ASSOC);
        $classIds = array_map(static fn (array $row): int => (int) $row['id'], $classRows);

        $teacherRows = $pdo->query(
            "SELECT DISTINCT u.id
               FROM users u
               INNER JOIN user_web_roles ur ON ur.user_id = u.id
               INNER JOIN web_roles r ON r.id = ur.role_id
              WHERE r.name = 'teacher'"
        )->fetchAll(PDO::FETCH_ASSOC);
        $validTeacherIds = array_flip(array_map(static fn (array $row): int => (int) $row['id'], $teacherRows));

        $pdo->beginTransaction();

        try {
            $deleteStmt = $pdo->prepare('DELETE FROM class_teachers WHERE class_id = :class_id');
            $insertStmt = $pdo->prepare('INSERT INTO class_teachers (class_id, user_id) VALUES (:class_id, :user_id)');
            $savedClasses = 0;
            $savedAssignments = 0;

            foreach ($classIds as $classId) {
                $rawTeacherIds = $assignments[(string) $classId] ?? $assignments[$classId] ?? [];
                if (!is_array($rawTeacherIds)) {
                    $rawTeacherIds = [];
                }

                $teacherIds = array_values(array_unique(array_filter(array_map(
                    static fn ($teacherId): int => (int) $teacherId,
                    $rawTeacherIds
                ), static fn (int $teacherId): bool => $teacherId > 0 && isset($validTeacherIds[$teacherId]))));

                $deleteStmt->execute(['class_id' => $classId]);

                foreach ($teacherIds as $teacherId) {
                    $insertStmt->execute([
                        'class_id' => $classId,
                        'user_id' => $teacherId,
                    ]);
                    $savedAssignments++;
                }

                $savedClasses++;
            }

            $pdo->commit();
            $this->setMessage('Professorat actualitzat per a ' . $savedClasses . ' classes amb ' . $savedAssignments . ' assignacions.', 'success');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->setMessage('No s’ha pogut actualitzar el professorat de totes les classes.', 'error');
        }
    }

    private function setMessage(string $message, string $type): void
    {
        $_SESSION['admin_message'] = $message;
        $_SESSION['admin_message_type'] = $type;
    }

    private function buildGeoMapPoints(array $geoStats): array
    {
        $points = [];
        $countryCoordinates = [
            'ES' => [40.4168, -3.7038],
            'PT' => [38.7223, -9.1393],
            'FR' => [48.8566, 2.3522],
            'GB' => [51.5072, -0.1276],
            'DE' => [52.52, 13.405],
            'IT' => [41.9028, 12.4964],
            'BE' => [50.8503, 4.3517],
            'NL' => [52.3676, 4.9041],
            'CH' => [46.948, 7.4474],
            'US' => [38.9072, -77.0369],
            'CA' => [45.4215, -75.6972],
            'MX' => [19.4326, -99.1332],
            'BR' => [-15.7939, -47.8828],
            'AR' => [-34.6037, -58.3816],
            'CL' => [-33.4489, -70.6693],
            'PE' => [-12.0464, -77.0428],
            'CO' => [4.711, -74.0721],
        ];

        foreach ($geoStats as $row) {
            $countryCode = strtoupper(trim((string) ($row['country_code'] ?? '')));
            if ($countryCode === '') {
                continue;
            }

            $coordinates = $countryCoordinates[$countryCode] ?? null;
            if ($coordinates === null) {
                continue;
            }

            $region = trim((string) ($row['region'] ?? ''));
            if ($countryCode === 'ES') {
                $regionLower = function_exists('mb_strtolower') ? mb_strtolower($region) : strtolower($region);
                if ($regionLower !== '' && preg_match('/catal|barcel|girona|lleida|tarragon/i', $regionLower) === 1) {
                    $coordinates = [41.3874, 2.1686];
                }
            }

            $points[] = [
                'country_code' => $countryCode,
                'region' => $region !== '' ? $region : 'Desconegut',
                'total' => (int) ($row['total'] ?? 0),
                'lat' => $coordinates[0],
                'lng' => $coordinates[1],
            ];
        }

        return $points;
    }

    private function userHasRole(PDO $pdo, int $userId, string $roleName): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM user_web_roles ur
             INNER JOIN web_roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id
               AND r.name = :role_name
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'role_name' => $roleName,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function syncClassAssignment(PDO $pdo, int $userId, ?int $classId, string $changeSource = 'import'): void
    {
        $currentStmt = $pdo->prepare(
            'SELECT cm.class_id, c.academic_year_id
             FROM class_members cm
             INNER JOIN classes c ON c.id = cm.class_id
             WHERE cm.user_id = :user_id
             LIMIT 1'
        );
        $currentStmt->execute(['user_id' => $userId]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

        $currentClassId = $current !== false ? (int) $current['class_id'] : null;
        if ($currentClassId === $classId) {
            return;
        }

        $referenceClassId = $classId ?? $currentClassId;
        if ($referenceClassId !== null) {
            $academicYearStmt = $pdo->prepare('SELECT academic_year_id FROM classes WHERE id = :id LIMIT 1');
            $academicYearStmt->execute(['id' => $referenceClassId]);
            $academicYearId = $academicYearStmt->fetchColumn();

            if ($academicYearId !== false) {
                $historyStmt = $pdo->prepare(
                    'INSERT INTO class_member_history (user_id, previous_class_id, new_class_id, academic_year_id, change_source, changed_at)
                     VALUES (:user_id, :previous_class_id, :new_class_id, :academic_year_id, :change_source, NOW())'
                );
                $historyStmt->execute([
                    'user_id' => $userId,
                    'previous_class_id' => $currentClassId,
                    'new_class_id' => $classId,
                    'academic_year_id' => (int) $academicYearId,
                    'change_source' => $changeSource,
                ]);
            }
        }

        $deleteStmt = $pdo->prepare('DELETE FROM class_members WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $userId]);

        if ($classId !== null && $classId > 0) {
            $insertStmt = $pdo->prepare('INSERT INTO class_members (class_id, user_id) VALUES (:class_id, :user_id)');
            $insertStmt->execute(['class_id' => $classId, 'user_id' => $userId]);
        }
    }

    private function syncUserRoles(PDO $pdo, int $userId, array $roleIds): void
    {
        $deleteStmt = $pdo->prepare('DELETE FROM user_web_roles WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $userId]);

        if ($roleIds === []) {
            return;
        }

        $insertStmt = $pdo->prepare('INSERT INTO user_web_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        foreach ($roleIds as $roleId) {
            if ($roleId > 0) {
                $insertStmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }
        }
    }

    private function findOrCreateUser(PDO $pdo, string $name, string $surname, string $email, string $password): ?int
    {
        $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existingStmt->execute(['email' => $email]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing !== false) {
            $userId = (int) $existing['id'];
            $updateStmt = $pdo->prepare('UPDATE users SET name = :name, surname = :surname, is_active = 1 WHERE id = :id');
            $updateStmt->execute(['name' => $name, 'surname' => $surname, 'id' => $userId]);
            return $userId;
        }

        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : password_hash('Entorns2026!', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare(
            'INSERT INTO users (name, surname, email, password_hash, must_change_password, is_active, created_at)
             VALUES (:name, :surname, :email, :password_hash, 1, 1, NOW())'
        );
        $insertStmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function roleIdsContainRoleName(PDO $pdo, array $roleIds, string $roleName): bool
    {
        $roleIds = array_values(array_filter(array_map('intval', $roleIds), static fn (int $roleId): bool => $roleId > 0));

        if ($roleIds === []) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM web_roles WHERE id IN (' . $placeholders . ') AND name = ?');
        $stmt->execute([...$roleIds, $roleName]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function setRolesForUser(PDO $pdo, int $userId, string $rolesInput): void
    {
        $deleteStmt = $pdo->prepare('DELETE FROM user_web_roles WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $userId]);

        $roles = array_filter(array_map('trim', preg_split('/[;,|]/', $rolesInput) ?: []));
        if ($roles === []) {
            $roles = ['student'];
        }

        $insertStmt = $pdo->prepare('INSERT INTO user_web_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        foreach ($roles as $roleName) {
            $roleId = $this->findOrCreateRole($pdo, $roleName);
            if ($roleId !== null) {
                $insertStmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }
        }
    }

    private function updateUserProfileFields(PDO $pdo, int $userId, string $gender, string $article, string $inaturalistUserLogin): void
    {
        $stmt = $pdo->prepare(
            'UPDATE users
              SET gender = :gender,
                  article = :article,
                  inaturalist_user_login = :inaturalist_user_login
              WHERE id = :id'
        );
        $stmt->execute([
            'gender' => $gender !== '' ? $gender : null,
            'article' => $article !== '' ? $article : null,
            'inaturalist_user_login' => $inaturalistUserLogin !== '' ? $inaturalistUserLogin : null,
            'id' => $userId,
        ]);
    }

    private function findOrCreateRole(PDO $pdo, string $roleName): ?int
    {
        $normalized = trim(strtolower($roleName));
        if ($normalized === '') {
            return null;
        }

        $existingStmt = $pdo->prepare('SELECT id FROM web_roles WHERE LOWER(name) = :name LIMIT 1');
        $existingStmt->execute(['name' => $normalized]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $insertStmt = $pdo->prepare('INSERT INTO web_roles (name, created_at) VALUES (:name, NOW())');
        $insertStmt->execute(['name' => $normalized]);
        return (int) $pdo->lastInsertId();
    }

    private function findOrCreateProjectRole(PDO $pdo, string $roleName): ?int
    {
        $normalized = trim($roleName);
        if ($normalized === '') {
            return null;
        }

        $existingStmt = $pdo->prepare('SELECT id FROM project_roles WHERE LOWER(name) = :name LIMIT 1');
        $existingStmt->execute(['name' => mb_strtolower($normalized)]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $insertStmt = $pdo->prepare('INSERT INTO project_roles (name, created_at) VALUES (:name, NOW())');
        $insertStmt->execute(['name' => $normalized]);
        return (int) $pdo->lastInsertId();
    }

    private function findProjectRoleId(PDO $pdo, string $roleName): ?int
    {
        $normalized = trim($roleName);
        if ($normalized === '') {
            return null;
        }

        $stmt = $pdo->prepare('SELECT id FROM project_roles WHERE LOWER(name) = :name LIMIT 1');
        $stmt->execute(['name' => mb_strtolower($normalized)]);
        $projectRoleId = $stmt->fetchColumn();

        return $projectRoleId !== false ? (int) $projectRoleId : null;
    }

    /**
     * @return array<int>
     */
    private function resolveProjectRoleIds(PDO $pdo, string $roleInput): array
    {
        $roleInput = trim($roleInput);
        if ($roleInput === '') {
            return [];
        }

        $exactRoleId = $this->findProjectRoleId($pdo, $roleInput);
        if ($exactRoleId !== null) {
            return [$exactRoleId];
        }

        if (preg_match('/[;,|]/', $roleInput) === 1) {
            $roleNames = preg_split('/\s*[;,|]\s*/', $roleInput) ?: [];
        } else {
            $roleNames = $this->splitProjectRolesByKnownNames($pdo, $roleInput) ?? [$roleInput];
        }

        $projectRoleIds = [];
        foreach ($roleNames as $roleName) {
            $projectRoleId = $this->findOrCreateProjectRole($pdo, (string) $roleName);
            if ($projectRoleId !== null) {
                $projectRoleIds[$projectRoleId] = $projectRoleId;
            }
        }

        return array_values($projectRoleIds);
    }

    /**
     * @return array<string>|null
     */
    private function splitProjectRolesByKnownNames(PDO $pdo, string $roleInput): ?array
    {
        $stmt = $pdo->query('SELECT name FROM project_roles ORDER BY CHAR_LENGTH(name) DESC, name ASC');
        $knownRoles = array_map(static fn (array $row): string => (string) $row['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        $remaining = trim($roleInput);
        $matchedRoles = [];
        while ($remaining !== '') {
            $matchedRole = null;
            foreach ($knownRoles as $knownRole) {
                if ($knownRole === '') {
                    continue;
                }

                $pattern = '/^' . preg_quote($knownRole, '/') . '(?:\s+|$)/iu';
                if (preg_match($pattern, $remaining) === 1) {
                    $matchedRole = $knownRole;
                    break;
                }
            }

            if ($matchedRole === null) {
                return null;
            }

            $matchedRoles[] = $matchedRole;
            $remaining = trim((string) preg_replace('/^' . preg_quote($matchedRole, '/') . '\s*/iu', '', $remaining, 1));
        }

        return $matchedRoles !== [] ? $matchedRoles : null;
    }

    /**
     * @return array<string>
     */
    private function splitStoredProjectRoleNames(string $storedRoleNames): array
    {
        $roleNames = array_filter(array_map('trim', explode('||', $storedRoleNames)), static fn (string $roleName): bool => $roleName !== '');

        return array_values(array_unique($roleNames));
    }

    private function resolveImportClassId(PDO $pdo, string $classIdValue, string $classCode, string $className, string $academicYearName): ?int
    {
        if ($classIdValue !== '' && filter_var($classIdValue, FILTER_VALIDATE_INT) !== false) {
            $stmt = $pdo->prepare('SELECT id FROM classes WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $classIdValue]);
            $classId = $stmt->fetchColumn();
            if ($classId !== false) {
                return (int) $classId;
            }
        }

        $normalizedCode = trim($classCode);
        $normalizedName = trim($className);
        $normalizedYearNames = array_values(array_filter(array_unique([
            trim($academicYearName),
            $this->normalizeAcademicYearLabel($academicYearName),
        ])));

        if ($normalizedCode !== '' && $normalizedYearNames !== []) {
            $yearPlaceholders = [];
            $yearParams = [];
            foreach ($normalizedYearNames as $index => $yearName) {
                $placeholder = ':year_' . $index;
                $yearPlaceholders[] = $placeholder;
                $yearParams['year_' . $index] = $yearName;
            }

            $stmt = $pdo->prepare(
                'SELECT c.id
                   FROM classes c
                   INNER JOIN academic_years ay ON ay.id = c.academic_year_id
                  WHERE ay.name IN (' . implode(',', $yearPlaceholders) . ')
                    AND (c.class_name = :class_name OR c.class_code = :class_code OR c.class_code = :built_code)
                  ORDER BY c.id ASC
                  LIMIT 1'
            );
            $stmt->execute($yearParams + [
                'class_name' => $normalizedCode,
                'class_code' => $normalizedCode,
                'built_code' => $this->buildClassCode($normalizedYearNames[0], $normalizedCode),
            ]);
            $classId = $stmt->fetchColumn();
            if ($classId !== false) {
                return (int) $classId;
            }
        }

        if ($normalizedCode !== '') {
            $stmt = $pdo->prepare('SELECT id FROM classes WHERE class_code = :class_code LIMIT 1');
            $stmt->execute(['class_code' => $normalizedCode]);
            $classId = $stmt->fetchColumn();
            if ($classId !== false) {
                return (int) $classId;
            }
        }

        if ($normalizedName !== '') {
            $stmt = $pdo->prepare('SELECT id FROM classes WHERE class_name = :class_name LIMIT 1');
            $stmt->execute(['class_name' => $normalizedName]);
            $classId = $stmt->fetchColumn();
            if ($classId !== false) {
                return (int) $classId;
            }
        }

        if ($normalizedCode === '' && $normalizedName === '') {
            return null;
        }

        $yearStmt = $pdo->query('SELECT id, name FROM academic_years WHERE is_current = 1 ORDER BY id DESC LIMIT 1');
        $year = $yearStmt->fetch(PDO::FETCH_ASSOC);
        $academicYearId = $year !== false ? (int) $year['id'] : 1;
        $academicYearName = $year !== false ? (string) $year['name'] : '';

        $classNameToInsert = $normalizedName !== '' ? $normalizedName : $normalizedCode;
        $classCodeToInsert = $normalizedCode !== '' ? $normalizedCode : $this->buildClassCode($academicYearName, $classNameToInsert);

        $insertStmt = $pdo->prepare('INSERT INTO classes (academic_year_id, class_name, class_code) VALUES (:academic_year_id, :class_name, :class_code)');
        $insertStmt->execute([
            'academic_year_id' => $academicYearId,
            'class_name' => $classNameToInsert,
            'class_code' => $classCodeToInsert,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function normalizeAcademicYearLabel(string $academicYearName): string
    {
        $academicYearName = trim($academicYearName);
        if ($academicYearName === '') {
            return '';
        }

        if (preg_match('/^(\d{2})-(\d{2})$/', $academicYearName, $matches) === 1) {
            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];
            $prefixStart = $startYear >= 70 ? 1900 : 2000;
            $prefixEnd = $endYear < $startYear ? $prefixStart + 100 : $prefixStart;

            return sprintf('%04d-%04d', $prefixStart + $startYear, $prefixEnd + $endYear);
        }

        return $academicYearName;
    }

    private function resolveProjectAcademicYearId(PDO $pdo, string $projectAcademicYearIdValue, string $projectIdValue, string $projectSlug, string $academicYearName): ?int
    {
        if ($projectAcademicYearIdValue !== '' && filter_var($projectAcademicYearIdValue, FILTER_VALIDATE_INT) !== false) {
            $stmt = $pdo->prepare('SELECT id FROM project_academic_years WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $projectAcademicYearIdValue]);
            $projectAcademicYearId = $stmt->fetchColumn();

            if ($projectAcademicYearId !== false) {
                return (int) $projectAcademicYearId;
            }
        }

        $normalizedYearNames = array_values(array_filter(array_unique([
            trim($academicYearName),
            $this->normalizeAcademicYearLabel($academicYearName),
        ])));

        if ($projectSlug === '' || $normalizedYearNames === []) {
            return null;
        }

        $yearPlaceholders = [];
        $yearParams = [];
        foreach ($normalizedYearNames as $index => $yearName) {
            $placeholder = ':year_' . $index;
            $yearPlaceholders[] = $placeholder;
            $yearParams['year_' . $index] = $yearName;
        }

        $projectStmt = $pdo->prepare(
            'SELECT pay.id
               FROM project_academic_years pay
               INNER JOIN projects p ON p.id = pay.project_id
               INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
              WHERE p.slug = :project_slug
                AND ay.name IN (' . implode(',', $yearPlaceholders) . ')
              LIMIT 1'
        );
        $projectStmt->execute($yearParams + ['project_slug' => $projectSlug]);
        $projectAcademicYearId = $projectStmt->fetchColumn();

        if ($projectAcademicYearId !== false) {
            return (int) $projectAcademicYearId;
        }

        if ($projectIdValue !== '' && filter_var($projectIdValue, FILTER_VALIDATE_INT) !== false) {
            $projectStmt = $pdo->prepare(
                'SELECT pay.id
                   FROM project_academic_years pay
                   INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
                  WHERE pay.project_id = :project_id
                    AND ay.name IN (' . implode(',', $yearPlaceholders) . ')
                  LIMIT 1'
            );
            $projectStmt->execute($yearParams + ['project_id' => (int) $projectIdValue]);
            $projectAcademicYearId = $projectStmt->fetchColumn();

            if ($projectAcademicYearId !== false) {
                return (int) $projectAcademicYearId;
            }
        }

        return null;
    }

    private function updateUserActiveState(PDO $pdo, int $userId, int $isActive): void
    {
        $stmt = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $stmt->execute([
            'is_active' => $isActive,
            'id' => $userId,
        ]);
    }

    /**
     * @param array<int> $projectRoleIds
     */
    private function syncProjectTeamMembership(PDO $pdo, int $userId, int $projectAcademicYearId, ?int $classId, string $teamCode, ?string $teamName, array $projectRoleIds, ?string $classGroup): int
    {
        if ($classId !== null) {
            $classYearStmt = $pdo->prepare(
                'SELECT 1
                   FROM classes c
                   INNER JOIN project_academic_years pay ON pay.academic_year_id = c.academic_year_id
                  WHERE c.id = :class_id
                    AND pay.id = :project_academic_year_id
                  LIMIT 1'
            );
            $classYearStmt->execute([
                'class_id' => $classId,
                'project_academic_year_id' => $projectAcademicYearId,
            ]);

            if ($classYearStmt->fetchColumn() === false) {
                throw new RuntimeException('La classe de l’alumne no correspon a l’any acadèmic del projecte.');
            }

            $projectClassAssignmentStmt = $pdo->prepare(
                'SELECT p.name AS project_name, ay.name AS academic_year_name, c.class_code
                   FROM project_academic_years pay
                   INNER JOIN projects p ON p.id = pay.project_id
                   INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
                   INNER JOIN classes c ON c.id = :class_id
                   INNER JOIN project_class_assignments pca ON pca.project_academic_year_id = pay.id
                    AND pca.class_id = c.id
                  WHERE pay.id = :project_academic_year_id
                  LIMIT 1'
            );
            $projectClassAssignmentStmt->execute([
                'class_id' => $classId,
                'project_academic_year_id' => $projectAcademicYearId,
            ]);

            if ($projectClassAssignmentStmt->fetch(PDO::FETCH_ASSOC) === false) {
                $contextStmt = $pdo->prepare(
                    'SELECT p.name AS project_name, ay.name AS academic_year_name, c.class_code
                       FROM project_academic_years pay
                       INNER JOIN projects p ON p.id = pay.project_id
                       INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
                       INNER JOIN classes c ON c.id = :class_id
                      WHERE pay.id = :project_academic_year_id
                      LIMIT 1'
                );
                $contextStmt->execute([
                    'class_id' => $classId,
                    'project_academic_year_id' => $projectAcademicYearId,
                ]);
                $context = $contextStmt->fetch(PDO::FETCH_ASSOC) ?: [];

                throw new RuntimeException(
                    'El projecte ' . (string) ($context['project_name'] ?? '')
                    . ' (' . (string) ($context['academic_year_name'] ?? '') . ') no està assignat a la classe '
                    . (string) ($context['class_code'] ?? '') . '.'
                );
            }
        }

        $teamId = $this->findOrCreateProjectTeam($pdo, $projectAcademicYearId, $teamCode, $teamName, $classGroup);

        $deleteStmt = $pdo->prepare(
            'DELETE ptm
               FROM project_team_members ptm
               INNER JOIN project_teams pt ON pt.id = ptm.project_team_id
              WHERE pt.project_academic_year_id = :project_academic_year_id
                AND ptm.user_id = :user_id'
        );
        $deleteStmt->execute([
            'project_academic_year_id' => $projectAcademicYearId,
            'user_id' => $userId,
        ]);

        $primaryProjectRoleId = $projectRoleIds[0] ?? null;
        $insertStmt = $pdo->prepare('INSERT INTO project_team_members (project_team_id, user_id, class_id, project_role_id) VALUES (:project_team_id, :user_id, :class_id, :project_role_id)');
        $insertStmt->execute([
            'project_team_id' => $teamId,
            'user_id' => $userId,
            'class_id' => $classId,
            'project_role_id' => $primaryProjectRoleId,
        ]);

        $projectTeamMemberId = (int) $pdo->lastInsertId();
        $this->syncProjectTeamMemberRoles($pdo, $projectTeamMemberId, $projectRoleIds);

        return $teamId;
    }

    /**
     * @param array<int> $projectRoleIds
     */
    private function syncProjectTeamMemberRoles(PDO $pdo, int $projectTeamMemberId, array $projectRoleIds): void
    {
        $deleteStmt = $pdo->prepare('DELETE FROM project_team_member_roles WHERE project_team_member_id = :project_team_member_id');
        $deleteStmt->execute(['project_team_member_id' => $projectTeamMemberId]);

        if ($projectRoleIds === []) {
            return;
        }

        $insertStmt = $pdo->prepare(
            'INSERT IGNORE INTO project_team_member_roles (project_team_member_id, project_role_id)
             VALUES (:project_team_member_id, :project_role_id)'
        );
        foreach (array_values(array_unique($projectRoleIds)) as $projectRoleId) {
            $insertStmt->execute([
                'project_team_member_id' => $projectTeamMemberId,
                'project_role_id' => (int) $projectRoleId,
            ]);
        }
    }

    private function findOrCreateProjectTeam(PDO $pdo, int $projectAcademicYearId, string $teamCode, ?string $teamName, ?string $classGroup): int
    {
        $normalizedTeamCode = trim($teamCode);
        if ($normalizedTeamCode === '') {
            throw new RuntimeException('El codi d’equip és obligatori.');
        }

        $existingStmt = $pdo->prepare('SELECT id FROM project_teams WHERE project_academic_year_id = :project_academic_year_id AND team_code = :team_code LIMIT 1');
        $existingStmt->execute([
            'project_academic_year_id' => $projectAcademicYearId,
            'team_code' => $normalizedTeamCode,
        ]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        $normalizedTeamName = trim((string) $teamName);
        $normalizedClassGroup = trim((string) $classGroup);

        if ($existing !== false) {
            $updateStmt = $pdo->prepare(
                'UPDATE project_teams
                    SET team_name = COALESCE(NULLIF(:team_name, ""), team_name),
                        class_group = COALESCE(NULLIF(:class_group, ""), class_group)
                  WHERE id = :id'
            );
            $updateStmt->execute([
                'team_name' => $normalizedTeamName,
                'class_group' => $normalizedClassGroup,
                'id' => (int) $existing['id'],
            ]);

            return (int) $existing['id'];
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO project_teams (project_academic_year_id, team_code, team_name, class_group, display_order, is_active)
             VALUES (:project_academic_year_id, :team_code, :team_name, :class_group, 0, 1)'
        );
        $insertStmt->execute([
            'project_academic_year_id' => $projectAcademicYearId,
            'team_code' => $normalizedTeamCode,
            'team_name' => $normalizedTeamName !== '' ? $normalizedTeamName : $normalizedTeamCode,
            'class_group' => $normalizedClassGroup !== '' ? $normalizedClassGroup : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function findOrCreateClass(PDO $pdo, string $className): ?int
    {
        $normalizedName = trim($className);
        if ($normalizedName === '') {
            return null;
        }

        $existingStmt = $pdo->prepare('SELECT id FROM classes WHERE class_name = :name LIMIT 1');
        $existingStmt->execute(['name' => $normalizedName]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $yearStmt = $pdo->query('SELECT id, name FROM academic_years WHERE is_current = 1 ORDER BY id DESC LIMIT 1');
        $year = $yearStmt->fetch(PDO::FETCH_ASSOC);
        $academicYearId = $year !== false ? (int) $year['id'] : 1;
        $academicYearName = $year !== false ? (string) $year['name'] : '';
        $code = $this->buildClassCode($academicYearName, $normalizedName);

        $insertStmt = $pdo->prepare('INSERT INTO classes (academic_year_id, class_name, class_code) VALUES (:academic_year_id, :class_name, :class_code)');
        $insertStmt->execute([
            'academic_year_id' => $academicYearId,
            'class_name' => $normalizedName,
            'class_code' => $code,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function buildClassCode(string $academicYearName, string $className): string
    {
        $yearCode = $academicYearName;

        if (preg_match('/^(\d{4})-(\d{4})$/', $academicYearName, $matches) === 1) {
            $yearCode = substr($matches[1], -2) . '-' . substr($matches[2], -2);
        }

        $classCode = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $className) ?? '');

        return $yearCode . '_' . $classCode;
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = strtr($normalized, [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ï' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        return trim($normalized, '_');
    }

    private function parseImportBoolean(string $value): ?int
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            '1', 'true', 'yes', 'si', 'sí', 's', 'active', 'actiu', 'activa' => 1,
            '0', 'false', 'no', 'n', 'inactive', 'inactiu', 'inactiva' => 0,
            default => null,
        };
    }

    private function extractInteger(string $value): string
    {
        if (preg_match('/\d+/', $value, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    private function userExistsByEmail(PDO $pdo, string $email): bool
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
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
