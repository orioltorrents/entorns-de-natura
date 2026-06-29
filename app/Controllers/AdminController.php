<?php

class AdminController
{
    public function dashboard(): string
    {
        return view('admin.dashboard', [
            'title' => 'Dashboard administració',
        ]);
    }
}
