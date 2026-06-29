<?php

class AuthService
{
    public function login(string $email, string $password): bool
    {
        return !empty($email) && !empty($password);
    }
}
