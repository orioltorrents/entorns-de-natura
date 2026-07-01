<?php

function appBasePath(): string
{
    $baseUrl = env('BASE_URL');

    if ($baseUrl === null || $baseUrl === '') {
        $appUrl = env('APP_URL', '/');
        $baseUrl = parse_url((string) $appUrl, PHP_URL_PATH) ?: '/';
    }

    $basePath = '/' . trim((string) $baseUrl, '/');

    return $basePath === '/' ? '' : $basePath;
}

function url(string $path = ''): string
{
    $basePath = appBasePath();
    $path = trim($path, '/');

    if ($path === '') {
        return $basePath === '' ? '/' : $basePath . '/';
    }

    return $basePath . '/' . $path;
}
