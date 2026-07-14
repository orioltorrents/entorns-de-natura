<?php

class AdminController
{
    public function dashboard(): string
    {
        $pdo = $this->pdo();
        $this->ensureProjectClassAssignmentsTable($pdo);
        $this->ensureProjectsDisplayOrderColumn($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($pdo);
        }

        $usersStmt = $pdo->query(
            'SELECT u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                    u.academic_role, u.gender, u.article, u.inaturalist_user_login,
                    sp.class_group, sp.project, sp.team_number, sp.group_number, sp.group_code_1t,
                    sp.members_count, sp.external_id, sp.trimester,
                    COUNT(DISTINCT sv.id) AS visit_count
             FROM users u
             LEFT JOIN site_visits sv ON sv.user_id = u.id
             LEFT JOIN student_profiles sp ON sp.user_id = u.id
             GROUP BY u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                      u.academic_role, u.gender, u.article, u.inaturalist_user_login,
                      sp.class_group, sp.project, sp.team_number, sp.group_number, sp.group_code_1t,
                      sp.members_count, sp.external_id, sp.trimester
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

        $classesStmt = $pdo->query('SELECT id, name, code FROM classes ORDER BY name');
        $classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectAssignmentsStmt = $pdo->query(
            'SELECT pg.id, pg.class_id, p.id AS project_id, ay.name AS academic_year_name, pg.status, pg.created_at,
                    c.name AS class_name,
                    p.name AS project_name,
                    p.slug AS project_slug
              FROM project_class_assignments pg
              INNER JOIN classes c ON c.id = pg.class_id
              INNER JOIN project_academic_years pay ON pay.id = pg.project_academic_year_id
              INNER JOIN projects p ON p.id = pay.project_id
              INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
              ORDER BY c.name, ay.id, p.display_order, p.name'
        );
        $projectAssignments = $projectAssignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

        $classMembershipsStmt = $pdo->query(
            'SELECT cm.user_id, cm.class_id, c.name AS class_name
             FROM class_members cm
             INNER JOIN classes c ON c.id = cm.class_id
             ORDER BY cm.user_id, c.name'
        );
        $classMemberships = $classMembershipsStmt->fetchAll(PDO::FETCH_ASSOC);

        $classTeachersStmt = $pdo->query(
            'SELECT ct.class_id, ct.user_id, c.name AS class_name, u.name, u.surname
             FROM class_teachers ct
             INNER JOIN classes c ON c.id = ct.class_id
             INNER JOIN users u ON u.id = ct.user_id
             ORDER BY c.name, u.name, u.surname'
        );
        $classTeachers = $classTeachersStmt->fetchAll(PDO::FETCH_ASSOC);
        $assessmentStructure = $this->assessmentStructure($pdo);

        $roleMap = [];
        foreach ($userRoles as $row) {
            $roleMap[(int) $row['user_id']][] = (string) $row['role_name'];
        }

        $userClassMap = [];
        foreach ($classMemberships as $membership) {
            $userClassMap[(int) $membership['user_id']] = (int) $membership['class_id'];
        }

        foreach ($users as &$user) {
            $user['roles'] = $roleMap[(int) $user['id']] ?? [];
            $user['status'] = ((int) $user['is_active'] === 1) ? 'Actiu' : 'Inactiu';
            $user['class_id'] = $userClassMap[(int) $user['id']] ?? null;
        }
        unset($user);

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
            ];
        }

        $analyticsService = new AnalyticsService();
        $analytics = $analyticsService->getDashboardStats($pdo);
        $geoMapPoints = $this->buildGeoMapPoints($analytics['geo_stats'] ?? []);

        $message = $_SESSION['admin_message'] ?? null;
        $messageType = $_SESSION['admin_message_type'] ?? 'success';

        if ($message !== null) {
            unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
        }

        return view('admin.dashboard', [
            'title' => 'Dashboard administració',
            'users' => $users,
            'roles' => $roles,
            'projects' => $projects,
            'classes' => $classes,
            'teacherUsers' => $teacherUsers,
            'classTeachersMap' => $classTeachersMap,
            'teacherClassMap' => $teacherClassMap,
            'projectAssignments' => $projectAssignments,
            'assessmentStructure' => $assessmentStructure,
            'roleMap' => $roleMap,
            'analytics' => $analytics,
            'geoMapPoints' => $geoMapPoints,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    private function handlePost(PDO $pdo): void
    {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_user') {
            $this->createUser($pdo);
            return;
        }

        if ($action === 'toggle_user') {
            $this->toggleUser($pdo);
            return;
        }

        if ($action === 'update_student') {
            $this->updateStudent($pdo);
            return;
        }

        if ($action === 'toggle_project') {
            $this->toggleProject($pdo);
            return;
        }

        if ($action === 'update_project_order') {
            $this->updateProjectOrder($pdo);
            return;
        }

        if ($action === 'assign_project_to_class') {
            $this->assignProjectToClass($pdo);
            return;
        }

        if ($action === 'sync_class_teachers') {
            $this->syncClassTeachers($pdo);
            return;
        }

        if ($action === 'update_project_assignment_status') {
            $this->updateProjectAssignmentStatus($pdo);
            return;
        }

        if ($action === 'delete_project_assignment') {
            $this->deleteProjectAssignment($pdo);
            return;
        }

        if ($action === 'import_students') {
            $this->importStudents($pdo);
            return;
        }

        if ($action === 'import_assessment_structure') {
            $this->importAssessmentStructure($pdo);
            return;
        }

        if ($action === 'toggle_assessment_phase') {
            $this->toggleAssessmentPhase($pdo);
            return;
        }

        if ($action === 'toggle_assessment_task') {
            $this->toggleAssessmentTask($pdo);
        }
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

    private function createUser(PDO $pdo): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $surname = trim((string) ($_POST['surname'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $roles = $_POST['roles'] ?? [];

        if ($name === '' || $email === '' || $password === '') {
            $this->setMessage('Nom, email i contrasenya són obligatoris.', 'error');
            return;
        }

        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetch()) {
            $this->setMessage('Ja existeix un usuari amb aquest email.', 'error');
            return;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, surname, email, password_hash, is_active, created_at)
                 VALUES (:name, :surname, :email, :password_hash, :is_active, NOW())'
            );

            $stmt->execute([
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_active' => $isActive,
            ]);

            $userId = (int) $pdo->lastInsertId();
            $roleIds = [];

            if (!empty($roles) && is_array($roles)) {
                $roleIds = array_map('intval', $roles);
            }

            if ($roleIds !== []) {
                $insertRoleStmt = $pdo->prepare('INSERT INTO user_web_roles (user_id, role_id) VALUES (:user_id, :role_id)');
                foreach ($roleIds as $roleId) {
                    $insertRoleStmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
                }
            }

            $pdo->commit();
            $this->setMessage('Usuari creat correctament.', 'success');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->setMessage('No s’ha pogut crear l’usuari.', 'error');
        }
    }

    private function toggleUser(PDO $pdo): void
    {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($userId === null || $userId === false) {
            $this->setMessage('Usuari no vàlid.', 'error');
            return;
        }

        $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            $this->setMessage('No s’ha trobat l’usuari.', 'error');
            return;
        }

        if ((int) $user['is_active'] === 1 && $this->userHasRole($pdo, (int) $userId, 'admin')) {
            $this->setMessage('No es pot desactivar un usuari administrador.', 'error');
            return;
        }

        $newState = ((int) $user['is_active'] === 1) ? 0 : 1;
        $updateStmt = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $updateStmt->execute(['is_active' => $newState, 'id' => $userId]);

        $this->setMessage('Estat d’usuari actualitzat.', 'success');
    }

    private function updateStudent(PDO $pdo): void
    {
        $userId = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $name = trim((string) ($_POST['name'] ?? ''));
        $surname = trim((string) ($_POST['surname'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($userId === null || $userId === false || $name === '' || $email === '') {
            $this->setMessage('Nom i email són obligatoris.', 'error');
            return;
        }

        $isActive = isset($_POST['is_active']) ? 1 : 0;
        if ($this->userHasRole($pdo, (int) $userId, 'admin')) {
            $isActive = 1;
        }
        $academicRole = trim((string) ($_POST['academic_role'] ?? ''));
        $gender = trim((string) ($_POST['gender'] ?? ''));
        $article = trim((string) ($_POST['article'] ?? ''));
        $inaturalistUserLogin = trim((string) ($_POST['inaturalist_user_login'] ?? ''));
        $project = trim((string) ($_POST['project'] ?? ''));
        $teamNumber = trim((string) ($_POST['team_number'] ?? ''));
        $groupNumber = trim((string) ($_POST['group_number'] ?? ''));
        $groupCode1T = trim((string) ($_POST['group_code_1t'] ?? ''));
        $membersCount = trim((string) ($_POST['members_count'] ?? ''));
        $externalId = trim((string) ($_POST['external_id'] ?? ''));
        $trimester = trim((string) ($_POST['trimester'] ?? ''));

        $stmt = $pdo->prepare(
            'UPDATE users
             SET name = :name,
                 surname = :surname,
                 email = :email,
                 is_active = :is_active,
                 academic_role = :academic_role,
                 gender = :gender,
                 article = :article,
                 inaturalist_user_login = :inaturalist_user_login
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'is_active' => $isActive,
            'academic_role' => $academicRole !== '' ? $academicRole : null,
            'gender' => $gender !== '' ? $gender : null,
            'article' => $article !== '' ? $article : null,
            'inaturalist_user_login' => $inaturalistUserLogin !== '' ? $inaturalistUserLogin : null,
            'id' => $userId,
        ]);

        $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $resolvedClassId = $classId === null || $classId === false ? null : (int) $classId;
        $pdo->beginTransaction();

        try {
            $this->syncClassAssignment($pdo, $userId, $resolvedClassId);

            $roles = $_POST['roles'] ?? [];
            if (is_array($roles)) {
                $this->syncUserRoles($pdo, $userId, array_map('intval', $roles));
            }

            $this->upsertStudentProfile(
                $pdo,
                $userId,
                '',
                $project,
                $teamNumber,
                $groupNumber,
                $groupCode1T,
                $membersCount,
                $externalId,
                $trimester,
                $resolvedClassId
            );

            $pdo->commit();
            $this->setMessage('Informació de l’alumne actualitzada.', 'success');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->setMessage('No s’ha pogut actualitzar l’alumne.', 'error');
        }
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
            $className = trim($data['class'] ?? $data['classe'] ?? $data['grup_classe'] ?? $data['grup_classes'] ?? '');
            $rolesInput = trim($data['roles'] ?? $data['role'] ?? 'student');
            $trimester = trim($data['trimester'] ?? $data['trimestre'] ?? $data['codi_grup_1t'] ?? '');
            $academicRole = trim($data['academic_role'] ?? $data['rol'] ?? '');
            $gender = trim($data['gender'] ?? $data['genere'] ?? '');
            $article = trim($data['article'] ?? '');
            $inaturalistUserLogin = trim($data['inaturalist_user_login'] ?? '');
            $project = trim($data['projecte'] ?? $data['project'] ?? '');
            $teamNumber = $this->extractInteger($data['numero_equip'] ?? '');
            $groupNumber = $this->extractInteger($data['numero_grup'] ?? '');
            $groupCode1T = trim($data['codi_grup_1t'] ?? '');
            $membersCount = $this->extractInteger($data['quants_membres'] ?? '');
            $externalId = trim($data['userid'] ?? $data['id'] ?? '');

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

                $this->setRolesForUser($pdo, $userId, $rolesInput);
                $this->updateUserAcademicFields($pdo, $userId, $academicRole, $gender, $article, $inaturalistUserLogin);
                $this->upsertStudentProfile($pdo, $userId, $className, $project, $teamNumber, $groupNumber, $groupCode1T, $membersCount, $externalId, $trimester);

                if ($className !== '') {
                    $classId = $this->findOrCreateClass($pdo, $className);
                    if ($classId !== null) {
                        $this->syncClassAssignment($pdo, $userId, $classId);
                    }
                } else {
                    $this->syncClassAssignment($pdo, $userId, null);
                }

                $pdo->commit();

                if ($existingUser === false) {
                    $created++;
                } else {
                    $updated++;
                }

                if ($trimester !== '') {
                    $trimesterLabel = $trimester;
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

        $message = 'Importació completada: ' . $created . ' usuaris creats i ' . $updated . ' actualitzats.';
        if (!empty($trimesterLabel ?? '')) {
            $message .= ' Trimestre: ' . $trimesterLabel;
        }

        $this->setMessage($message, 'success');
    }

    private function toggleProject(PDO $pdo): void
    {
        $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
        if ($projectId === null || $projectId === false) {
            $this->setMessage('Projecte no vàlid.', 'error');
            return;
        }

        $stmt = $pdo->prepare('SELECT is_active FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($project === false) {
            $this->setMessage('No s’ha trobat el projecte.', 'error');
            return;
        }

        $newState = ((int) $project['is_active'] === 1) ? 0 : 1;
        $updateStmt = $pdo->prepare('UPDATE projects SET is_active = :is_active WHERE id = :id');
        $updateStmt->execute(['is_active' => $newState, 'id' => $projectId]);

        $this->setMessage('Estat del projecte actualitzat.', 'success');
    }

    private function updateProjectOrder(PDO $pdo): void
    {
        $orders = $_POST['display_order'] ?? [];
        if (!is_array($orders)) {
            $this->setMessage('Ordre de projectes no vàlid.', 'error');
            return;
        }

        $stmt = $pdo->prepare('UPDATE projects SET display_order = :display_order WHERE id = :id');
        $updated = 0;

        $pdo->beginTransaction();

        try {
            foreach ($orders as $projectId => $displayOrder) {
                $id = filter_var($projectId, FILTER_VALIDATE_INT);
                if ($id === false || $id === null) {
                    continue;
                }

                $order = filter_var($displayOrder, FILTER_VALIDATE_INT);
                if ($order === false || $order === null || $order < 0) {
                    $order = 0;
                }

                $stmt->execute([
                    'display_order' => (int) $order,
                    'id' => (int) $id,
                ]);
                $updated++;
            }

            $pdo->commit();
            $this->setMessage('Ordre dels projectes actualitzat (' . $updated . ').', 'success');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->setMessage('No s’ha pogut actualitzar l’ordre dels projectes.', 'error');
        }
    }

    private function assignProjectToClass(PDO $pdo): void
    {
        $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
        $status = $this->normalizeProjectAssignmentStatus((string) ($_POST['status'] ?? 'actiu'));
        $allowedStatuses = ['pendent', 'actiu', 'realitzat'];

        if ($classId === null || $classId === false || $projectId === null || $projectId === false) {
            $this->setMessage('Classe o projecte no vÃ lid.', 'error');
            return;
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $this->setMessage('Estat de lâ€™assignaciÃ³ no vÃ lid.', 'error');
            return;
        }

        $projectAcademicYearStmt = $pdo->prepare(
            'SELECT pay.id
             FROM project_academic_years pay
             INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
             WHERE pay.project_id = :project_id
             ORDER BY ay.id DESC
             LIMIT 1'
        );
        $projectAcademicYearStmt->execute([
            'project_id' => (int) $projectId,
        ]);
        $projectAcademicYearId = $projectAcademicYearStmt->fetchColumn();

        if ($projectAcademicYearId === false) {
            $this->setMessage('Aquest projecte no té cap edició acadèmica vinculada.', 'error');
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO project_class_assignments (project_academic_year_id, class_id, status, created_at)
             VALUES (:project_academic_year_id, :class_id, :status, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), project_academic_year_id = VALUES(project_academic_year_id)'
        );
        $stmt->execute([
            'project_academic_year_id' => (int) $projectAcademicYearId,
            'class_id' => (int) $classId,
            'status' => $status,
        ]);

        $this->setMessage('Projecte assignat a la classe correctament.', 'success');
    }

    private function updateProjectAssignmentStatus(PDO $pdo): void
    {
        $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $status = $this->normalizeProjectAssignmentStatus((string) ($_POST['status'] ?? 'actiu'));
        $allowedStatuses = ['pendent', 'actiu', 'realitzat'];

        if ($assignmentId === null || $assignmentId === false) {
            $this->setMessage('AssignaciÃ³ no vÃ lida.', 'error');
            return;
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $this->setMessage('Estat de lâ€™assignaciÃ³ no vÃ lid.', 'error');
            return;
        }

        $stmt = $pdo->prepare('SELECT status FROM project_class_assignments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assignment === false) {
            $this->setMessage('No sâ€™ha trobat lâ€™assignaciÃ³.', 'error');
            return;
        }

        $updateStmt = $pdo->prepare('UPDATE project_class_assignments SET status = :status WHERE id = :id');
        $updateStmt->execute([
            'status' => $status,
            'id' => (int) $assignmentId,
        ]);

        $this->setMessage('Estat de lâ€™assignaciÃ³ actualitzat.', 'success');
    }

    private function deleteProjectAssignment(PDO $pdo): void
    {
        $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);

        if ($assignmentId === null || $assignmentId === false) {
            $this->setMessage('Assignació no vàlida.', 'error');
            return;
        }

        $stmt = $pdo->prepare('SELECT id FROM project_class_assignments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $assignmentId]);

        if ($stmt->fetch(PDO::FETCH_ASSOC) === false) {
            $this->setMessage('No s’ha trobat l’assignació.', 'error');
            return;
        }

        $deleteStmt = $pdo->prepare('DELETE FROM project_class_assignments WHERE id = :id');
        $deleteStmt->execute(['id' => (int) $assignmentId]);

        $this->setMessage('Assignació eliminada correctament.', 'success');
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

        $classStmt = $pdo->prepare('SELECT id, name FROM classes WHERE id = :id LIMIT 1');
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

    private function syncClassAssignment(PDO $pdo, int $userId, ?int $classId): void
    {
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
            'INSERT INTO users (name, surname, email, password_hash, is_active, created_at)
             VALUES (:name, :surname, :email, :password_hash, 1, NOW())'
        );
        $insertStmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return (int) $pdo->lastInsertId();
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

    private function updateUserAcademicFields(PDO $pdo, int $userId, string $academicRole, string $gender, string $article, string $inaturalistUserLogin): void
    {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET academic_role = :academic_role,
                 gender = :gender,
                 article = :article,
                 inaturalist_user_login = :inaturalist_user_login
             WHERE id = :id'
        );
        $stmt->execute([
            'academic_role' => $academicRole !== '' ? $academicRole : null,
            'gender' => $gender !== '' ? $gender : null,
            'article' => $article !== '' ? $article : null,
            'inaturalist_user_login' => $inaturalistUserLogin !== '' ? $inaturalistUserLogin : null,
            'id' => $userId,
        ]);
    }

    private function upsertStudentProfile(PDO $pdo, int $userId, string $className, string $project, string $teamNumber, string $groupNumber, string $groupCode1T, string $membersCount, string $externalId, string $trimester, ?int $classId = null): void
    {
        $resolvedClassId = $classId;
        if ($resolvedClassId === null && $className !== '') {
            $resolvedClassId = $this->findOrCreateClass($pdo, $className);
        }

        $existingStmt = $pdo->prepare('SELECT id FROM student_profiles WHERE user_id = :user_id LIMIT 1');
        $existingStmt->execute(['user_id' => $userId]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing !== false) {
            $stmt = $pdo->prepare(
                'UPDATE student_profiles
                 SET class_id = :class_id,
                     class_group = :class_group,
                     project = :project,
                     team_number = :team_number,
                     group_number = :group_number,
                     group_code_1t = :group_code_1t,
                     members_count = :members_count,
                     external_id = :external_id,
                     trimester = :trimester,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id'
            );
            $stmt->execute([
                'class_id' => $resolvedClassId,
                'class_group' => $className !== '' ? $className : null,
                'project' => $project !== '' ? $project : null,
                'team_number' => $teamNumber !== '' ? (int) $teamNumber : null,
                'group_number' => $groupNumber !== '' ? (int) $groupNumber : null,
                'group_code_1t' => $groupCode1T !== '' ? $groupCode1T : null,
                'members_count' => $membersCount !== '' ? (int) $membersCount : null,
                'external_id' => $externalId !== '' ? $externalId : null,
                'trimester' => $trimester !== '' ? $trimester : null,
                'user_id' => $userId,
            ]);
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO student_profiles (user_id, class_id, class_group, project, team_number, group_number, group_code_1t, members_count, external_id, trimester, created_at, updated_at)
             VALUES (:user_id, :class_id, :class_group, :project, :team_number, :group_number, :group_code_1t, :members_count, :external_id, :trimester, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'class_id' => $resolvedClassId,
            'class_group' => $className !== '' ? $className : null,
            'project' => $project !== '' ? $project : null,
            'team_number' => $teamNumber !== '' ? (int) $teamNumber : null,
            'group_number' => $groupNumber !== '' ? (int) $groupNumber : null,
            'group_code_1t' => $groupCode1T !== '' ? $groupCode1T : null,
            'members_count' => $membersCount !== '' ? (int) $membersCount : null,
            'external_id' => $externalId !== '' ? $externalId : null,
            'trimester' => $trimester !== '' ? $trimester : null,
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

    private function findOrCreateClass(PDO $pdo, string $className): ?int
    {
        $normalizedName = trim($className);
        if ($normalizedName === '') {
            return null;
        }

        $existingStmt = $pdo->prepare('SELECT id FROM classes WHERE name = :name LIMIT 1');
        $existingStmt->execute(['name' => $normalizedName]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $yearStmt = $pdo->query('SELECT id FROM academic_years WHERE is_current = 1 ORDER BY id DESC LIMIT 1');
        $year = $yearStmt->fetch(PDO::FETCH_ASSOC);
        $academicYearId = $year !== false ? (int) $year['id'] : 1;

        $insertStmt = $pdo->prepare('INSERT INTO classes (academic_year_id, name, code) VALUES (:academic_year_id, :name, :code)');
        $insertStmt->execute([
            'academic_year_id' => $academicYearId,
            'name' => $normalizedName,
            'code' => strtoupper(str_replace(' ', '-', $normalizedName)),
        ]);

        return (int) $pdo->lastInsertId();
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

    private function extractInteger(string $value): string
    {
        if (preg_match('/\d+/', $value, $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    private function normalizeProjectAssignmentStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'planned', 'previst', 'pendent' => 'pendent',
            'active', 'actiu' => 'actiu',
            'completed', 'completat', 'realitzat' => 'realitzat',
            default => $normalized,
        };
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

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
