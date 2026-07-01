<?php

require_once dirname(__DIR__) . '/app/Helpers/env.php';

return [
    'name' => env('APP_NAME', 'Entorns de Natura'),
    'env' => env('APP_ENV', 'local'),
    'base_url' => env('BASE_URL', '/'),
];
