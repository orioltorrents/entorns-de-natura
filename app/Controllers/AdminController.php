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
        $assessmentStructure = (new AdminAssessmentStructureService($pdo))->assessmentStructure();

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

    /**
     * @return array<string>
     */
    private function splitStoredProjectRoleNames(string $storedRoleNames): array
    {
        $roleNames = array_filter(array_map('trim', explode('||', $storedRoleNames)), static fn (string $roleName): bool => $roleName !== '');

        return array_values(array_unique($roleNames));
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
