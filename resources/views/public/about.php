<?php
ob_start();
?>
<section class="public-about" aria-labelledby="about-title">
    <article class="public-about__card">
        <p class="public-home__eyebrow">Entorns de Natura</p>
        <h1 id="about-title" class="public-about__title">Què és Entorns de Natura?</h1>
        <p class="public-about__text">Text de prova.</p>
    </article>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
