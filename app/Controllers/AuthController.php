<?php

class AuthController
{
    public function login(): string
    {
        return view('auth.login', [
            'title' => trans('login_title'),
        ]);
    }
}
