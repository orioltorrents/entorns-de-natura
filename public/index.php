<?php

require_once dirname(__DIR__) . '/app/Helpers/env.php';
require_once dirname(__DIR__) . '/app/Helpers/session.php';
require_once dirname(__DIR__) . '/app/Helpers/lang.php';
require_once dirname(__DIR__) . '/app/Helpers/route.php';
require_once dirname(__DIR__) . '/app/Helpers/view.php';

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectAssetService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectAssignmentService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectService.php';
require_once dirname(__DIR__) . '/app/Services/AssessmentService.php';
require_once dirname(__DIR__) . '/app/Services/AssessmentStructureImportService.php';
require_once dirname(__DIR__) . '/app/Services/AnalyticsService.php';
require_once dirname(__DIR__) . '/app/Services/DocumentImportService.php';
require_once dirname(__DIR__) . '/app/Services/DocumentService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectSectionService.php';
require_once dirname(__DIR__) . '/app/Controllers/PublicController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/StudentController.php';
require_once dirname(__DIR__) . '/app/Controllers/TeacherController.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';
require_once dirname(__DIR__) . '/app/Controllers/DocumentSyncController.php';

$authService = new AuthService();
$projectAssignmentService = new ProjectAssignmentService();
$projectService = new ProjectService();
$assessmentService = new AssessmentService();
$analyticsService = new AnalyticsService();
$documentImportService = new DocumentImportService();
$documentService = new DocumentService();
$projectSectionService = new ProjectSectionService();
$controller = new PublicController($projectService, $authService, $assessmentService, $documentService, $projectSectionService);
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
$currentUser = $authService->user();
$analyticsService->recordVisit($requestUri, $_SERVER, $currentUser['id'] ?? null);

switch ($requestUri) {
    case '/':
    case '/ca':
        echo $controller->home();
        break;

    case '/ca/que-es-entorns':
        echo $controller->about();
        break;

    case '/projectes':
    case '/ca/projectes':
    case '/es/projectes':
    case '/en/projectes':
        echo $controller->projects();
        break;

    case '/login':
        echo $authController->login();
        break;

    case '/logout':
        $authController->logout();
        break;

    case '/dashboard':
        if (!$authService->check()) {
            header('Location: ' . url('login'));
            exit;
        }

        $authController->redirectToDashboard();
        break;

    case '/alumne':
        $authService->requireRole('student');
        echo $studentController->dashboard();
        break;

    case '/professor':
        $authService->requireRole('teacher');
        echo $teacherController->dashboard();
        break;

    case '/admin':
        $authService->requireRole('admin');
        echo $adminController->dashboard();
        break;

    case '/admin/impersonate-student':
        $authService->requireActorRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('admin'));
            exit;
        }

        $studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
        $csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

        if ($studentId > 0 && $authService->verifyCsrfToken($csrfToken) && $authService->impersonateStudent($studentId)) {
            header('Location: ' . url('alumne'));
            exit;
        }

        $_SESSION['admin_message'] = 'No s’ha pogut activar la vista com alumne.';
        $_SESSION['admin_message_type'] = 'error';
        header('Location: ' . url('admin'));
        exit;

    case '/admin/stop-impersonation':
        $authService->requireActorRole('admin');

        $csrfToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authService->verifyCsrfToken($csrfToken)) {
            $authService->stopImpersonating();
        }

        header('Location: ' . url('admin'));
        exit;

    case '/admin/sync-documents':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo $documentSyncController->store();
            break;
        }

        echo $documentSyncController->index();
        break;

    default:
        if (preg_match('#^/(ca|es|en)/projectes/([a-z0-9-]+)/tasques$#', $requestUri, $matches) === 1) {
            echo $controller->projectTasks($matches[2]);
            break;
        }

        if (preg_match('#^/(ca|es|en)/projectes/([a-z0-9-]+)/notes$#', $requestUri, $matches) === 1) {
            echo $controller->projectNotes($matches[2]);
            break;
        }

        if (preg_match('#^/(ca|es|en)/projectes/([a-z0-9-]+)/documents$#', $requestUri, $matches) === 1) {
            echo $controller->projectDocuments($matches[2]);
            break;
        }

        if (preg_match('#^/(ca|es|en)/projectes/([a-z0-9-]+)$#', $requestUri, $matches) === 1) {
            echo $controller->projectDetail($matches[2]);
            break;
        }

        http_response_code(404);
        echo 'Pagina no trobada';
        break;
}
