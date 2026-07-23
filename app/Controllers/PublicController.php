<?php

declare(strict_types=1);

class PublicController
{
    public function __construct(
        private ProjectService $projectService,
        private AuthService $authService,
        private AssessmentService $assessmentService,
        private DocumentService $documentService,
        private ProjectSectionService $projectSectionService
    ) {
    }

    public function home(): string
    {
        return view('public.home', [
            'title' => trans('home_title'),
        ]);
    }

    public function about(): string
    {
        return view('public.about', [
            'title' => 'Què és Entorns de Natura',
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

        $currentUser = $this->authService->user();
        $projectSectionsData = $this->projectSectionService->visibleSectionsForProject($slug, $currentUser);

        return view('public.project-detail', [
            'title' => (string) $project['title'],
            'project' => $project,
            'projectSections' => $projectSectionsData['sections'] ?? [],
            'projectSectionsContext' => $projectSectionsData['context'] ?? [],
            'currentUser' => $currentUser,
        ]);
    }

    public function projectTasks(string $slug): string
    {
        $project = $this->projectService->findActiveBySlug($slug, getLanguage());

        if ($project === null) {
            http_response_code(404);

            return view('public.project-tasks', [
                'title' => 'Tasques no trobades',
                'project' => null,
                'tasks' => [],
                'context' => [],
                'currentUser' => $this->authService->user(),
            ]);
        }

        $currentUser = $this->authService->user();
        $tasksData = $this->assessmentService->visibleTaskSectionsForProject($slug, $currentUser);

        return view('public.project-tasks', [
            'title' => 'Tasques de ' . (string) $project['title'],
            'project' => $project,
            'tasks' => $tasksData['sections'] ?? [],
            'context' => $tasksData['context'] ?? [],
            'currentUser' => $currentUser,
        ]);
    }

    public function projectNotes(string $slug): string
    {
        $project = $this->projectService->findActiveBySlug($slug, getLanguage());

        if ($project === null) {
            http_response_code(404);

            return view('public.project-notes', [
                'title' => 'Notes no trobades',
                'project' => null,
                'notes' => null,
                'currentUser' => $this->authService->user(),
                'accessDenied' => false,
            ]);
        }

        $currentUser = $this->authService->user();
        $notes = $this->resolveProjectNotes($slug, $currentUser);

        if ($notes === null) {
            http_response_code(403);

            return view('public.project-notes', [
                'title' => 'Accés restringit',
                'project' => $project,
                'notes' => null,
                'currentUser' => $currentUser,
                'accessDenied' => true,
            ]);
        }

        return view('public.project-notes', [
            'title' => 'Notes de ' . (string) $project['title'],
            'project' => $project,
            'notes' => $notes,
            'currentUser' => $currentUser,
            'accessDenied' => false,
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
                'projectAcademicYear' => null,
                'documents' => [],
                'context' => [],
            ]);
        }

        $currentUser = $this->authService->user();
        $documentsData = $this->documentService->projectDocuments($slug, $currentUser);

        return view('public.project-documents', [
            'title' => 'Documents de ' . (string) $project['title'],
            'project' => $documentsData['project'] ?? $project,
            'projectAcademicYear' => $documentsData['projectAcademicYear'] ?? null,
            'documents' => $documentsData['documents'] ?? [],
            'context' => $documentsData['context'] ?? [],
        ]);
    }

    private function resolveProjectNotes(string $slug, ?array $currentUser): ?array
    {
        if ($currentUser === null) {
            return null;
        }

        $hasStudentRole = $this->authService->hasRole('student');

        if (!$hasStudentRole) {
            return null;
        }

        return $this->assessmentService->gradesForStudentProject((int) $currentUser['id'], $slug);
    }
}
