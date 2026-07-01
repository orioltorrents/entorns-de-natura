<?php

class AdminController
{
    public function dashboard(): string
    {
        $pdo = $this->pdo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($pdo);
        }

        $usersStmt = $pdo->query(
            'SELECT u.id, u.name, u.surname, u.email, u.is_active, u.created_at,
                    COUNT(DISTINCT sv.id) AS visit_count
             FROM users u
             LEFT JOIN site_visits sv ON sv.user_id = u.id
             GROUP BY u.id, u.name, u.surname, u.email, u.is_active, u.created_at
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
        $stmt = $pdo->prepare('UPDATE users SET name = :name, surname = :surname, email = :email, is_active = :is_active WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'is_active' => $isActive,
            'id' => $userId,
        ]);

        $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $this->syncClassAssignment($pdo, $userId, $classId === null || $classId === false ? null : $classId);

        $roles = $_POST['roles'] ?? [];
        if (is_array($roles)) {
            $this->syncUserRoles($pdo, $userId, array_map('intval', $roles));
        }

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

            $name = trim($data['name'] ?? '');
            $surname = trim($data['surname'] ?? '');
            $email = strtolower(trim($data['email'] ?? ''));
            $password = trim($data['password'] ?? '');
            $className = trim($data['class'] ?? $data['classe'] ?? '');
            $rolesInput = trim($data['roles'] ?? $data['role'] ?? 'student');
            $trimester = trim($data['trimester'] ?? $data['trimestre'] ?? '');

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
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        return trim($normalized, '_');
    }

    private function userExistsByEmail(PDO $pdo, string $email): bool
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
