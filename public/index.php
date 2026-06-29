<?php

require_once dirname(__DIR__) . '/app/Helpers/env.php';
require_once dirname(__DIR__) . '/app/Helpers/lang.php';
require_once dirname(__DIR__) . '/app/Helpers/route.php';
require_once dirname(__DIR__) . '/app/Helpers/view.php';

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Services/ProjectAssignmentService.php';
require_once dirname(__DIR__) . '/app/Controllers/PublicController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/StudentController.php';
require_once dirname(__DIR__) . '/app/Controllers/TeacherController.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';

$authService = new AuthService();
$projectAssignmentService = new ProjectAssignmentService();
$controller = new PublicController();
$authController = new AuthController($authService);
$studentController = new StudentController($authService, $projectAssignmentService);
$teacherController = new TeacherController($authService, $projectAssignmentService);
$adminController = new AdminController();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$basePath = appBasePath();

if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$requestUri = '/' . trim($requestUri, '/');

switch ($requestUri) {
    case '/':
    case '/ca':
        echo $controller->home();
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

    default:
        if (preg_match('#^/(ca|es|en)/projectes/([a-z0-9-]+)$#', $requestUri, $matches) === 1) {
            echo $controller->projectDetail($matches[2]);
            break;
        }

        http_response_code(404);
        echo 'Pagina no trobada';
        break;
}
