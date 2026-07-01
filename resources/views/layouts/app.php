<!DOCTYPE html>
<html lang="<?= htmlspecialchars(getLanguage(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Entorns de Natura', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>">
</head>
<body>
    <?php include dirname(__DIR__) . '/layouts/header.php'; ?>

    <main class="page-content">
        <?= $content ?? '' ?>
    </main>

    <?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
    <script src="<?= url('assets/js/scripts.js') ?>"></script>
</body>
</html>
