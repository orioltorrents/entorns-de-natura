<?php

class PublicController
{
    public function home(): string
    {
        return view('public.home', [
            'title' => trans('home_title'),
        ]);
    }

    public function projects(): string
    {
        return view('public.projects', [
            'title' => trans('projects_title'),
        ]);
    }

    public function projectDetail(string $slug): string
    {
        return view('public.project-detail', [
            'title' => ucfirst($slug),
            'slug' => $slug,
        ]);
    }
}
