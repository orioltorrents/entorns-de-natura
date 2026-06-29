<?php

declare(strict_types=1);

class PublicController
{
    public function __construct(private ProjectService $projectService)
    {
    }

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
            'projects' => $this->projectService->allActive(getLanguage()),
        ]);
    }

    public function projectDetail(string $slug): string
    {
        $project = $this->projectService->findActiveBySlug($slug, getLanguage());

        if ($project === null) {
            http_response_code(404);

            return view('public.project-detail', [
                'title' => 'Projecte no trobat',
                'project' => null,
            ]);
        }

        return view('public.project-detail', [
            'title' => (string) $project['title'],
            'project' => $project,
        ]);
    }
}
