<?php

class AdminController
{
    public function dashboard(): string
    {
        $pdo = $this->pdo();
        $this->ensureProjectGroupsTable($pdo);

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

        $rolesStmt = $pdo->query('SELECT id, name FROM roles ORDER BY name');
        $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectsStmt = $pdo->query(
            'SELECT id, name, slug, is_active, created_at FROM projects ORDER BY name'
        );
        $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($projects as &$project) {
            $project['description'] = 'Projecte disponible al portal';
        }
        unset($project);

        $userRolesStmt = $pdo->query(
            'SELECT ur.user_id, r.name AS role_name
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             ORDER BY ur.user_id, r.name'
        );
        $userRoles = $userRolesStmt->fetchAll(PDO::FETCH_ASSOC);

        $classesStmt = $pdo->query('SELECT id, name, code FROM classes ORDER BY name');
        $classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

        $projectAssignmentsStmt = $pdo->query(
            'SELECT pg.id, pg.class_id, pg.project_id, pg.status, pg.created_at,
                    c.name AS class_name,
                    p.name AS project_name,
                    p.slug AS project_slug
             FROM project_groups pg
             INNER JOIN classes c ON c.id = pg.class_id
             INNER JOIN projects p ON p.id = pg.project_id
             ORDER BY c.name, p.name'
        );
        $projectAssignments = $projectAssignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

        $classMembershipsStmt = $pdo->query(
            'SELECT cm.user_id, cm.class_id, c.name AS class_name
             FROM class_members cm
             INNER JOIN classes c ON c.id = cm.class_id
             ORDER BY cm.user_id, c.name'
        );
        $classMemberships = $classMembershipsStmt->fetchAll(PDO::FETCH_ASSOC);

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

        $analyticsService = new AnalyticsService();
        $analytics = $analyticsService->getDashboardStats($pdo);

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
            'projectAssignments' => $projectAssignments,
            'roleMap' => $roleMap,
            'analytics' => $analytics,
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

        if ($action === 'assign_project_to_class') {
            $this->assignProjectToClass($pdo);
            return;
        }

        if ($action === 'update_project_assignment_status') {
            $this->updateProjectAssignmentStatus($pdo);
            return;
        }

        if ($action === 'import_students') {
            $this->importStudents($pdo);
        }
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
            $insertRoleStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
            foreach ($roleIds as $roleId) {
                $insertRoleStmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }
        }

        $this->setMessage('Usuari creat correctament.', 'success');
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

        $this->setMessage('Informació de l’alumne actualitzada.', 'success');
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

            $userId = $this->findOrCreateUser($pdo, $name, $surname, $email, $password);
            if ($userId === null) {
                $errors[] = 'Fila ' . $lineNumber . ': no s’ha pogut crear l’usuari.';
                continue;
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

            if ($existingUser === false) {
                $created++;
            } else {
                $updated++;
            }

            if ($trimester !== '') {
                $trimesterLabel = $trimester;
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

        $stmt = $pdo->prepare(
            'INSERT INTO project_groups (class_id, project_id, status, created_at)
             VALUES (:class_id, :project_id, :status, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status)'
        );
        $stmt->execute([
            'class_id' => (int) $classId,
            'project_id' => (int) $projectId,
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

        $stmt = $pdo->prepare('SELECT status FROM project_groups WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assignment === false) {
            $this->setMessage('No sâ€™ha trobat lâ€™assignaciÃ³.', 'error');
            return;
        }

        $updateStmt = $pdo->prepare('UPDATE project_groups SET status = :status WHERE id = :id');
        $updateStmt->execute([
            'status' => $status,
            'id' => (int) $assignmentId,
        ]);

        $this->setMessage('Estat de lâ€™assignaciÃ³ actualitzat.', 'success');
    }

    private function setMessage(string $message, string $type): void
    {
        $_SESSION['admin_message'] = $message;
        $_SESSION['admin_message_type'] = $type;
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
        $deleteStmt = $pdo->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $userId]);

        if ($roleIds === []) {
            return;
        }

        $insertStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
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
        $deleteStmt = $pdo->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
        $deleteStmt->execute(['user_id' => $userId]);

        $roles = array_filter(array_map('trim', preg_split('/[;,|]/', $rolesInput) ?: []));
        if ($roles === []) {
            $roles = ['student'];
        }

        $insertStmt = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
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

        $existingStmt = $pdo->prepare('SELECT id FROM roles WHERE LOWER(name) = :name LIMIT 1');
        $existingStmt->execute(['name' => $normalized]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $insertStmt = $pdo->prepare('INSERT INTO roles (name, created_at) VALUES (:name, NOW())');
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

    private function ensureProjectGroupsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS project_groups (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                class_id INT UNSIGNED NOT NULL,
                project_id INT UNSIGNED NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "actiu",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_project_groups (class_id, project_id),
                KEY idx_project_groups_class_id (class_id),
                KEY idx_project_groups_project_id (project_id),
                CONSTRAINT fk_project_groups_class
                    FOREIGN KEY (class_id) REFERENCES classes (id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                CONSTRAINT fk_project_groups_project
                    FOREIGN KEY (project_id) REFERENCES projects (id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            "UPDATE project_groups
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

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
