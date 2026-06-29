<?php

declare(strict_types=1);

class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(): string
    {
        if ($this->authService->check()) {
            header('Location: ' . url($this->authService->redirectPathForCurrentUser()));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleLogin();
        }

        return view('auth.login', [
            'title' => trans('login_title'),
            'csrfToken' => $this->authService->csrfToken(),
            'error' => null,
            'email' => '',
        ]);
    }

    public function logout(): void
    {
        $this->authService->logout();

        header('Location: ' . url('login'));
        exit;
    }

    private function handleLogin(): string
    {
        $email = (string) ($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $csrfToken = (string) ($_POST['csrf_token'] ?? '');

        if (!$this->authService->verifyCsrfToken($csrfToken)) {
            return $this->loginView($email, 'La sessio ha caducat. Torna-ho a provar.');
        }

        if ($this->authService->attemptLogin($email, $password)) {
            header('Location: ' . url($this->authService->redirectPathForCurrentUser()));
            exit;
        }

        return $this->loginView($email, 'Email o contrasenya incorrectes.');
    }

    private function loginView(string $email, string $error): string
    {
        return view('auth.login', [
            'title' => trans('login_title'),
            'csrfToken' => $this->authService->csrfToken(),
            'error' => $error,
            'email' => $email,
        ]);
    }
}
