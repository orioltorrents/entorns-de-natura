<?php

function url(string $path = ''): string {
    $baseUrl = rtrim(env('BASE_URL', '/'), '/');
    if ($baseUrl === '') {
        $baseUrl = '/';
    }

    return $baseUrl . '/' . ltrim($path, '/');
}
