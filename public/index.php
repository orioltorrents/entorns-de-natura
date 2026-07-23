<?php

require_once dirname(__DIR__) . '/app/Helpers/env.php';
require_once dirname(__DIR__) . '/app/Helpers/session.php';
require_once dirname(__DIR__) . '/app/Helpers/lang.php';
require_once dirname(__DIR__) . '/app/Helpers/route.php';
require_once dirname(__DIR__) . '/app/Helpers/view.php';

require_once dirname(__DIR__) . '/app/Support/Router.php';
require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectAssetService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectAccessService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectAssignmentService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectService.php';
require_once dirname(__DIR__) . '/app/Services/AssessmentService.php';
require_once dirname(__DIR__) . '/app/Services/AssessmentStructureImportService.php';
require_once dirname(__DIR__) . '/app/Services/AnalyticsService.php';
require_once dirname(__DIR__) . '/app/Services/AdminActionService.php';
require_once dirname(__DIR__) . '/app/Services/AdminAssessmentStructureService.php';
require_once dirname(__DIR__) . '/app/Services/AdminClassService.php';
require_once dirname(__DIR__) . '/app/Services/AdminDashboardService.php';
require_once dirname(__DIR__) . '/app/Services/AdminProjectService.php';
require_once dirname(__DIR__) . '/app/Services/AdminSchemaMaintenanceService.php';
require_once dirname(__DIR__) . '/app/Services/AdminStudentImportService.php';
require_once dirname(__DIR__) . '/app/Services/AdminUserService.php';
require_once dirname(__DIR__) . '/app/Services/DocumentImportService.php';
require_once dirname(__DIR__) . '/app/Services/DocumentService.php';
require_once dirname(__DIR__) . '/app/Services/LogService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectSectionService.php';
require_once dirname(__DIR__) . '/app/Controllers/PublicController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/StudentController.php';
require_once dirname(__DIR__) . '/app/Controllers/TeacherController.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/Controllers/DocumentSyncController.php';

$authService = new AuthService();
$projectAccessService = new ProjectAccessService();
$projectAssignmentService = new ProjectAssignmentService();
$projectService = new ProjectService();
$assessmentService = new AssessmentService();
$analyticsService = new AnalyticsService();
$documentImportService = new DocumentImportService();
$documentService = new DocumentService();
$projectSectionService = new ProjectSectionService();
$controller = new PublicController($projectService, $authService, $assessmentService, $documentService, $projectSectionService, $projectAccessService);
$authController = new AuthController($authService);
$studentController = new StudentController($authService, $projectAssignmentService);
$teacherController = new TeacherController($authService, $projectAssignmentService);
$adminController = new AdminController();
$documentSyncController = new DocumentSyncController($authService, $documentImportService);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$basePath = appBasePath();

if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$requestUri = '/' . trim($requestUri, '/');
$projectAcademicYearId = isset($_GET['edicio']) ? max(0, (int) $_GET['edicio']) : null;
$currentUser = $authService->user();
$analyticsService->recordVisit($requestUri, $_SERVER, $currentUser['id'] ?? null);

if ($authService->mustChangePassword() && !in_array($requestUri, ['/canviar-contrasenya', '/logout'], true)) {
    header('Location: ' . url('canviar-contrasenya'));
    exit;
}

$router = new Router();
$languageProjectConstraints = ['lang' => 'ca|es|en', 'slug' => '[a-z0-9-]+'];

$router->get('/', static fn (): string => $controller->home());
$router->get('/ca', static fn (): string => $controller->home());
$router->get('/ca/que-es-entorns', static fn (): string => $controller->about());

foreach (['/projectes', '/ca/projectes', '/es/projectes', '/en/projectes'] as $projectsRoute) {
    $router->get($projectsRoute, static fn (): string => $controller->projects());
}

$router->any('/login', static fn (): string => $authController->login());
$router->any('/logout', static function () use ($authController): void {
    $authController->logout();
});
$router->any('/canviar-contrasenya', static fn (): string => $authController->changePassword());

$router->get('/dashboard', static function () use ($authService, $authController): void {
    if (!$authService->check()) {
        header('Location: ' . url('login'));
        exit;
    }

    $authController->redirectToDashboard();
});

$router->any('/alumne', static function () use ($authService, $studentController): string {
    $authService->requireRole('student');
    $authService->requirePasswordChangeCompleted();

    return $studentController->dashboard();
});

$router->any('/professor', static function () use ($authService, $teacherController): string {
    $authService->requireRole('teacher');
    $authService->requirePasswordChangeCompleted();

    return $teacherController->dashboard();
});

$router->any('/admin', static function () use ($authService, $adminController): string {
    $authService->requireRole('admin');
    $authService->requirePasswordChangeCompleted();

    return $adminController->dashboard();
});

$router->get('/admin/impersonate-student', static function (): void {
    header('Location: ' . url('admin'));
    exit;
});
$router->post('/admin/impersonate-student', static function () use ($authService): void {
    $authService->requireActorRole('admin');

    $studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
    $csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $actor = $authService->actorUser();
    $actorId = $actor !== null ? (int) ($actor['id'] ?? 0) : 0;

    if ($studentId > 0 && $authService->verifyCsrfToken($csrfToken) && $authService->impersonateStudent($studentId)) {
        (new LogService())->write('admin_action=impersonate_student actor_id=' . $actorId . ' target_student_id=' . $studentId);
        header('Location: ' . url('alumne'));
        exit;
    }

    (new LogService())->write('admin_action=impersonate_student_failed actor_id=' . $actorId . ' target_student_id=' . $studentId);
    $_SESSION['admin_message'] = 'No s’ha pogut activar la vista com alumne.';
    $_SESSION['admin_message_type'] = 'error';
    header('Location: ' . url('admin'));
    exit;
});

$router->get('/admin/stop-impersonation', static function (): void {
    header('Location: ' . url('admin'));
    exit;
});
$router->post('/admin/stop-impersonation', static function () use ($authService): void {
    $authService->requireActorRole('admin');

    $csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    $actor = $authService->actorUser();
    $actorId = $actor !== null ? (int) ($actor['id'] ?? 0) : 0;

    if ($authService->verifyCsrfToken($csrfToken)) {
        $targetId = isset($_SESSION['impersonation']['target']['id']) ? (int) $_SESSION['impersonation']['target']['id'] : 0;
        $authService->stopImpersonating();
        (new LogService())->write('admin_action=stop_impersonation actor_id=' . $actorId . ' target_student_id=' . $targetId);
    } else {
        (new LogService())->write('admin_action=stop_impersonation_csrf_failed actor_id=' . $actorId);
    }

    header('Location: ' . url('admin'));
    exit;
});

$router->get('/admin/sync-documents', static fn (): string => $documentSyncController->index());
$router->post('/admin/sync-documents', static fn (): string => $documentSyncController->store());

$router->get('/{lang}/projectes/{slug}/tasques', static fn (array $params): string => $controller->projectTasks((string) $params['slug'], $projectAcademicYearId), $languageProjectConstraints);
$router->get('/{lang}/projectes/{slug}/notes', static fn (array $params): string => $controller->projectNotes((string) $params['slug'], $projectAcademicYearId), $languageProjectConstraints);
$router->get('/{lang}/projectes/{slug}/documents', static fn (array $params): string => $controller->projectDocuments((string) $params['slug'], $projectAcademicYearId), $languageProjectConstraints);
$router->get('/{lang}/projectes/{slug}', static fn (array $params): string => $controller->projectDetail((string) $params['slug'], $projectAcademicYearId), $languageProjectConstraints);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestUri);
