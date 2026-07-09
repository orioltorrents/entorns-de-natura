<!DOCTYPE html>
<html lang="<?= htmlspecialchars(getLanguage(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Entorns de Natura', ENT_QUOTES, 'UTF-8') ?></title>
    <?php
    $cssPath = dirname(__DIR__, 3) . '/public/assets/css/styles.css';
    $jsPath = dirname(__DIR__, 3) . '/public/assets/js/scripts.js';
    $cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '1';
    $jsVersion = is_file($jsPath) ? (string) filemtime($jsPath) : '1';
    ?>
    <link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <?php include dirname(__DIR__) . '/layouts/header.php'; ?>

    <main class="page-content">
        <?= $content ?? '' ?>
    </main>

    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
    <script src="<?= url('assets/js/scripts.js') ?>?v=<?= htmlspecialchars($jsVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
