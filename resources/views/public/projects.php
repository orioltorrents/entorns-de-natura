<?php
ob_start();
?>
<h1><?= htmlspecialchars(trans('projects_title'), ENT_QUOTES, 'UTF-8') ?></h1>
<ul>
    <li>Projecte Rius</li>
    <li>MAT Penedès</li>
    <li>Agroparc</li>
    <li>Projecte Orenetes</li>
    <li>Liquencity</li>
    <li>Vespa velutina</li>
</ul>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
