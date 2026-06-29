<?php
function asset($path): string {
    return '/assets/' . ltrim($path, '/');
}

function url($path = ''): string {
    $baseUrl = rtrim(env('BASE_URL', '/'), '/');
    if ($baseUrl === '') {
        $baseUrl = '/';
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function isActivePage(string $current, string $expected): bool {
    return $current === $expected;
}
