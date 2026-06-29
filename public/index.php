<?php

require_once dirname(__DIR__) . '/app/Helpers/env.php';
require_once dirname(__DIR__) . '/app/Helpers/lang.php';
require_once dirname(__DIR__) . '/app/Helpers/route.php';
require_once dirname(__DIR__) . '/app/Helpers/view.php';

require_once dirname(__DIR__) . '/app/Controllers/PublicController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/StudentController.php';
require_once dirname(__DIR__) . '/app/Controllers/TeacherController.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';

$controller = new PublicController();
$authController = new AuthController();
$studentController = new StudentController();
$teacherController = new TeacherController();
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

    case '/alumne':
        echo $studentController->dashboard();
        break;

    case '/professor':
        echo $teacherController->dashboard();
        break;

    case '/admin':
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
