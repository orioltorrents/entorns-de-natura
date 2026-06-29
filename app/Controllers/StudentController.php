<?php

class StudentController
{
    public function dashboard(): string
    {
        return view('students.dashboard', [
            'title' => 'Dashboard alumne',
        ]);
    }
}
