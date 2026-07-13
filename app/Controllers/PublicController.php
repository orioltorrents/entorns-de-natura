<?php

declare(strict_types=1);

class PublicController
{
    public function __construct(
        private ProjectService $projectService,
        private AuthService $authService,
        private AssessmentService $assessmentService,
        private DocumentService $documentService
    ) {
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

        $studentGrades = null;
        $currentUser = $this->authService->user();

        if ($currentUser !== null && $this->authService->hasRole('student')) {
            $studentGrades = $this->assessmentService->gradesForStudentProject((int) $currentUser['id'], $slug);
        }

        return view('public.project-detail', [
            'title' => (string) $project['title'],
            'project' => $project,
            'currentUser' => $currentUser,
            'studentGrades' => $studentGrades,
        ]);
    }

    public function projectDocuments(string $slug): string
    {
        $project = $this->projectService->findActiveBySlug($slug, getLanguage());

        if ($project === null) {
            http_response_code(404);

            return view('public.project-documents', [
                'title' => 'Documents no trobats',
                'project' => null,
                'documents' => [],
                'context' => [],
            ]);
        }

        $currentUser = $this->authService->user();
        $documentsData = $this->documentService->projectDocuments($slug, $currentUser);

        return view('public.project-documents', [
            'title' => 'Documents de ' . (string) $project['title'],
            'project' => $documentsData['project'] ?? $project,
            'documents' => $documentsData['documents'] ?? [],
            'context' => $documentsData['context'] ?? [],
        ]);
    }
}
