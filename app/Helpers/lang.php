<?php

function getLanguage(): string {
    startAppSession();

    if (isset($_GET['lang']) && in_array($_GET['lang'], ['ca', 'es', 'en'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
        return $_GET['lang'];
    }

    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['ca', 'es', 'en'], true)) {
        return $_SESSION['lang'];
    }

    $_SESSION['lang'] = 'ca';
    return 'ca';
}

function trans(string $key, ?string $lang = null): string {
    $lang = $lang ?? getLanguage();
    $file = dirname(__DIR__, 2) . '/resources/lang/' . $lang . '.php';

    if (!is_file($file)) {
        $file = dirname(__DIR__, 2) . '/resources/lang/ca.php';
    }

    $translations = include $file;
    return $translations[$key] ?? $key;
}
