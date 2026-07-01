<?php
ob_start();
?>
<h1>Dashboard administració</h1>
<p>Espai preparat per a configuració, usuaris i sincronitzacions.</p>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
