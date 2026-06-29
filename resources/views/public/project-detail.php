<?php
ob_start();
?>
<h1><?= htmlspecialchars(trans('project_detail_title'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($slug ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars(trans('project_detail_intro'), ENT_QUOTES, 'UTF-8') ?></p>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
