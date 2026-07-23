<?php

declare(strict_types=1);

class PublicController
{
    public function __construct(
        private ProjectService $projectService,
        private AuthService $authService,
        private AssessmentService $assessmentService,
        private DocumentService $documentService,
        private ProjectSectionService $projectSectionService,
        private ProjectAccessService $projectAccessService
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

    public function projectDetail(string $slug, ?int $projectAcademicYearId = null): string
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
        $projectAcademicYear = $this->projectService->academicYearForProject((int) $project['id'], $projectAcademicYearId);
        if ($this->shouldEnforceEditionAccess($currentUser) && !$this->canAccessEdition($currentUser, $project, $projectAcademicYear)) {
            return $this->accessDenied();
        }

        $projectSectionsData = $this->projectSectionService->visibleSectionsForProject($slug, $currentUser, $projectAcademicYearId);

        return view('public.project-detail', [
            'title' => (string) $project['title'],
            'project' => $project,
            'projectSections' => $projectSectionsData['sections'] ?? [],
            'projectSectionsContext' => $projectSectionsData['context'] ?? [],
            'currentUser' => $currentUser,
            'projectAcademicYearId' => $projectAcademicYearId,
            'projectAcademicYear' => $projectAcademicYear,
        ]);
    }

    public function projectTasks(string $slug, ?int $projectAcademicYearId = null): string
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
        $projectAcademicYear = $this->projectService->academicYearForProject((int) $project['id'], $projectAcademicYearId);
        if ($this->shouldEnforceEditionAccess($currentUser) && !$this->canAccessEdition($currentUser, $project, $projectAcademicYear)) {
            return $this->accessDenied();
        }

        $tasksData = $this->assessmentService->visibleTaskSectionsForProject($slug, $currentUser, $projectAcademicYearId);

        return view('public.project-tasks', [
            'title' => 'Tasques de ' . (string) $project['title'],
            'project' => $project,
            'tasks' => $tasksData['sections'] ?? [],
            'context' => $tasksData['context'] ?? [],
            'currentUser' => $currentUser,
            'projectAcademicYearId' => $projectAcademicYearId,
        ]);
    }

    public function projectNotes(string $slug, ?int $projectAcademicYearId = null): string
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
        $projectAcademicYear = $this->projectService->academicYearForProject((int) $project['id'], $projectAcademicYearId);
        if ($this->shouldEnforceEditionAccess($currentUser) && !$this->canAccessEdition($currentUser, $project, $projectAcademicYear)) {
            return $this->accessDenied();
        }

        $notes = $this->resolveProjectNotes($slug, $currentUser, $projectAcademicYearId);

        if ($notes === null) {
            http_response_code(403);

            return view('public.project-notes', [
                'title' => 'Accés restringit',
                'project' => $project,
                'notes' => null,
                'currentUser' => $currentUser,
                'accessDenied' => true,
                'projectAcademicYearId' => $projectAcademicYearId,
            ]);
        }

        return view('public.project-notes', [
            'title' => 'Notes de ' . (string) $project['title'],
            'project' => $project,
            'notes' => $notes,
            'currentUser' => $currentUser,
            'accessDenied' => false,
            'projectAcademicYearId' => $projectAcademicYearId,
        ]);
    }

    public function projectDocuments(string $slug, ?int $projectAcademicYearId = null): string
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
        $projectAcademicYear = $this->projectService->academicYearForProject((int) $project['id'], $projectAcademicYearId);
        if ($this->shouldEnforceEditionAccess($currentUser) && !$this->canAccessEdition($currentUser, $project, $projectAcademicYear)) {
            return $this->accessDenied();
        }

        $documentsData = $this->documentService->projectDocuments($slug, $currentUser, $projectAcademicYearId);

        return view('public.project-documents', [
            'title' => 'Documents de ' . (string) $project['title'],
            'project' => $documentsData['project'] ?? $project,
            'projectAcademicYear' => $documentsData['projectAcademicYear'] ?? null,
            'documents' => $documentsData['documents'] ?? [],
            'context' => $documentsData['context'] ?? [],
            'projectAcademicYearId' => $projectAcademicYearId,
        ]);
    }

    private function resolveProjectNotes(string $slug, ?array $currentUser, ?int $projectAcademicYearId = null): ?array
    {
        if ($currentUser === null) {
            return null;
        }

        $hasStudentRole = $this->authService->hasRole('student');

        if (!$hasStudentRole) {
            return null;
        }

        return $this->assessmentService->gradesForStudentProject((int) $currentUser['id'], $slug, $projectAcademicYearId);
    }

    private function canAccessEdition(?array $currentUser, array $project, ?array $projectAcademicYear): bool
    {
        if ($projectAcademicYear === null) {
            return false;
        }

        $requestedProjectAcademicYearId = isset($_GET['edicio']) ? (int) $_GET['edicio'] : 0;
        if ($requestedProjectAcademicYearId > 0 && (int) $projectAcademicYear['id'] !== $requestedProjectAcademicYearId) {
            return false;
        }

        return $this->projectAccessService->canAccessProjectAcademicYear(
            $currentUser,
            (int) $project['id'],
            (int) $projectAcademicYear['id']
        );
    }

    private function shouldEnforceEditionAccess(?array $currentUser): bool
    {
        if ($currentUser === null) {
            return false;
        }

        $roles = array_values(array_map('strval', $currentUser['roles'] ?? []));
        if (in_array('admin', $roles, true) || in_array('coordinator', $roles, true)) {
            return false;
        }

        return in_array('student', $roles, true) || in_array('teacher', $roles, true) || in_array('guest_teacher', $roles, true);
    }

    private function accessDenied(): string
    {
        http_response_code(403);

        return view('public.access-denied', [
            'title' => 'Accés restringit',
        ]);
    }
}
