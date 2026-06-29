<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/translations.php';

$baseUrl = rtrim(env('BASE_URL', '/'), '/');
if ($baseUrl === '') {
    $baseUrl = '/';
}

$language = $_SESSION['lang'] ?? 'ca';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ca', 'es', 'en'], true)) {
    $language = $_GET['lang'];
    $_SESSION['lang'] = $language;
}

if (!isset($translations[$language])) {
    $language = 'ca';
}

$t = $translations[$language];
$siteTitle = $t['site_title'] ?? 'Entorns de Natura';
