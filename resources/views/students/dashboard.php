<?php
ob_start();
?>
<h1>Dashboard alumne</h1>
<p>Espai preparat per a projectes, rúbriques i seguiment.</p>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
