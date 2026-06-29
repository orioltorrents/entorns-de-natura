<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/layout.php';

ob_start();
?>
<h1><?= htmlspecialchars($t['projects'], ENT_QUOTES, 'UTF-8') ?></h1>

<section class="project-grid">
    <article>
        <h2><?= htmlspecialchars($t['project_rivers'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Projecte de seguiment i recuperació dels rius amb enfocament educatiu i científic.</p>
    </article>
    <article>
        <h2><?= htmlspecialchars($t['mat'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Programa d’educació ambiental i observació de l’entorn natural al Penedès.</p>
    </article>
    <article>
        <h2><?= htmlspecialchars($t['agroparc'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Espai de treball sobre agricultura, sostenibilitat i relació amb el territori.</p>
    </article>
    <article>
        <h2><?= htmlspecialchars($t['orenetes'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Projecte per entendre i protegir les orenetes i la biodiversitat urbana.</p>
    </article>
    <article>
        <h2><?= htmlspecialchars($t['liquencity'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Iniciativa de ciència ciutadana centrada en líquens i qualitat ambiental.</p>
    </article>
    <article>
        <h2><?= htmlspecialchars($t['vespa'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Seguiment de la vespa velutina i conscienciació sobre espècies invasores.</p>
    </article>
</section>
<?php
$content = ob_get_clean();
renderLayout($t['projects'], $content, 'projectes');
