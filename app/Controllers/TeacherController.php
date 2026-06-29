<?php

class TeacherController
{
    public function dashboard(): string
    {
        return view('teachers.dashboard', [
            'title' => 'Dashboard professorat',
        ]);
    }
}
