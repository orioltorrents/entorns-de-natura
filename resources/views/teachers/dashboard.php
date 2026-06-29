<?php
ob_start();
?>
<h1>Dashboard professorat</h1>
<p>Espai preparat per a gestió d’activitats i avaluació.</p>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
