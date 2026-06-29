<?php
require_once dirname(__DIR__) . '/app/Helpers/env.php';
require_once dirname(__DIR__) . '/app/Helpers/lang.php';
require_once dirname(__DIR__) . '/app/Helpers/route.php';
require_once dirname(__DIR__) . '/app/Helpers/view.php';

require_once dirname(__DIR__) . '/app/Controllers/PublicController.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';

$controller = new PublicController();
$authController = new AuthController();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseUrl = rtrim(env('BASE_URL', '/'), '/');
if ($baseUrl !== '' && $baseUrl !== '/') {
    $requestUri = str_replace($baseUrl, '', $requestUri);
}
$requestUri = '/' . trim($requestUri, '/');

switch ($requestUri) {
    case '/':
        echo $controller->home();
        break;
    case '/projectes':
        echo $controller->projects();
        break;
    case '/projecte':
        echo $controller->projectDetail('default');
        break;
    case '/login':
        echo $authController->login();
        break;
    default:
        http_response_code(404);
        echo 'Pàgina no trobada';
        break;
}
