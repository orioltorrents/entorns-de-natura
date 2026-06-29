<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';

function renderLayout(string $pageTitle, string $content, string $currentPage = ''): void {
    global $t, $language, $siteTitle;
    ?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle . ' · ' . $siteTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
</head>
<body>
    <header class="site-header">
        <div class="brand">
            <a href="<?= url('') ?>">Entorns de Natura</a>
        </div>
        <nav>
            <a href="<?= url('projectes/projectes.php') ?>" class="<?= isActivePage($currentPage, 'projectes') ? 'active' : '' ?>"><?= htmlspecialchars($t['projects'] ?? 'Projectes', ENT_QUOTES, 'UTF-8') ?></a>
            <a href="<?= url('auth/login.php') ?>" class="<?= isActivePage($currentPage, 'login') ? 'active' : '' ?>"><?= htmlspecialchars($t['login'] ?? 'Inicia sessió', ENT_QUOTES, 'UTF-8') ?></a>
            <a href="<?= url('?lang=ca') ?>">CA</a>
            <a href="<?= url('?lang=es') ?>">ES</a>
            <a href="<?= url('?lang=en') ?>">EN</a>
        </nav>
    </header>

    <main class="page-content">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Entorns de Natura</p>
    </footer>
</body>
</html>
    <?php
}
