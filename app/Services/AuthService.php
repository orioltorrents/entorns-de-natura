<?php

declare(strict_types=1);

class AuthService
{
    public function __construct()
    {
        startAppSession();
    }

    public function attemptLogin(string $email, string $password): bool
    {
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            return false;
        }

        $user = $this->findActiveUserByEmail($email);

        if ($user === null || empty($user['password_hash'])) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        $roles = $this->rolesForUser((int) $user['id']);

        if ($roles === []) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'surname' => (string) ($user['surname'] ?? ''),
            'email' => (string) $user['email'],
            'roles' => $roles,
            'must_change_password' => (int) ($user['must_change_password'] ?? 0) === 1,
        ];

        $this->markLastLogin((int) $user['id']);

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    public function check(): bool
    {
        return isset($_SESSION['user']['id'], $_SESSION['user']['roles'])
            && is_array($_SESSION['user']['roles']);
    }

    public function user(): ?array
    {
        if ($this->isImpersonating()) {
            return $_SESSION['impersonation']['target'];
        }

        return $this->check() ? $_SESSION['user'] : null;
    }

    public function actorUser(): ?array
    {
        return $this->check() ? $_SESSION['user'] : null;
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();

        return $user !== null && in_array($role, $user['roles'], true);
    }

    public function hasActorRole(string $role): bool
    {
        $user = $this->actorUser();

        return $user !== null && in_array($role, $user['roles'], true);
    }

    public function requireRole(string $role): void
    {
        if ($this->hasRole($role)) {
            return;
        }

        header('Location: ' . url('login'));
        exit;
    }

    public function requireActorRole(string $role): void
    {
        if ($this->hasActorRole($role)) {
            return;
        }

        header('Location: ' . url('login'));
        exit;
    }

    public function mustChangePassword(): bool
    {
        return $this->check()
            && !$this->isImpersonating()
            && (bool) ($_SESSION['user']['must_change_password'] ?? false);
    }

    public function requirePasswordChangeCompleted(): void
    {
        if (!$this->mustChangePassword()) {
            return;
        }

        header('Location: ' . url('canviar-contrasenya'));
        exit;
    }

    public function changeRequiredPassword(string $currentPassword, string $newPassword, string $confirmPassword): ?string
    {
        $user = $this->actorUser();

        if ($user === null) {
            return 'Cal iniciar sessió.';
        }

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return 'Tots els camps són obligatoris.';
        }

        if ($newPassword !== $confirmPassword) {
            return 'La nova contrasenya i la repetició no coincideixen.';
        }

        if (strlen($newPassword) < 8) {
            return 'La nova contrasenya ha de tenir com a mínim 8 caràcters.';
        }

        if ($currentPassword === $newPassword) {
            return 'La nova contrasenya ha de ser diferent de la contrasenya inicial.';
        }

        $storedUser = $this->findActiveUserWithPasswordById((int) $user['id']);

        if ($storedUser === null || empty($storedUser['password_hash'])) {
            return 'No s’ha pogut validar l’usuari.';
        }

        if (!password_verify($currentPassword, (string) $storedUser['password_hash'])) {
            return 'La contrasenya actual no és correcta.';
        }

        $stmt = $this->pdo()->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 must_change_password = 0,
                 password_changed_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => (int) $user['id'],
        ]);

        $_SESSION['user']['must_change_password'] = false;

        return null;
    }

    public function isImpersonating(): bool
    {
        return $this->check()
            && isset($_SESSION['impersonation']['target']['id'], $_SESSION['impersonation']['target']['roles'])
            && is_array($_SESSION['impersonation']['target']['roles']);
    }

    public function impersonateStudent(int $studentId): bool
    {
        if (!$this->hasActorRole('admin')) {
            return false;
        }

        $student = $this->findActiveUserById($studentId);

        if ($student === null) {
            return false;
        }

        $roles = $this->rolesForUser((int) $student['id']);

        if (!in_array('student', $roles, true)) {
            return false;
        }

        $_SESSION['impersonation'] = [
            'target' => [
                'id' => (int) $student['id'],
                'name' => (string) $student['name'],
                'surname' => (string) ($student['surname'] ?? ''),
                'email' => (string) $student['email'],
                'roles' => $roles,
            ],
            'started_at' => time(),
        ];

        return true;
    }

    public function stopImpersonating(): void
    {
        unset($_SESSION['impersonation']);
    }

    public function redirectPathForCurrentUser(): string
    {
        if ($this->mustChangePassword()) {
            return 'canviar-contrasenya';
        }

        if ($this->hasRole('admin')) {
            return 'admin';
        }

        if ($this->hasRole('teacher')) {
            return 'professor';
        }

        if ($this->hasRole('student')) {
            return 'alumne';
        }

        return '';
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    private function findActiveUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, name, surname, email, password_hash, is_active
                    , must_change_password
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user === false || (int) $user['is_active'] !== 1) {
            return null;
        }

        return $user;
    }

    private function findActiveUserWithPasswordById(int $userId): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, password_hash, is_active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user === false || (int) $user['is_active'] !== 1) {
            return null;
        }

        return $user;
    }

    private function findActiveUserById(int $userId): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, name, surname, email, is_active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user === false || (int) $user['is_active'] !== 1) {
            return null;
        }

        return $user;
    }

    private function rolesForUser(int $userId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT web_roles.name
              FROM user_web_roles
              INNER JOIN web_roles ON web_roles.id = user_web_roles.role_id
              WHERE user_web_roles.user_id = :user_id
              ORDER BY web_roles.name'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function markLastLogin(int $userId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE users
             SET last_login_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
    }

    private function pdo(): PDO
    {
        return require dirname(__DIR__, 2) . '/config/database.php';
    }
}
