<?php
ob_start();
?>
<section class="hero hero--home public-home">
    <div class="public-home__content">
        <img class="public-home__brand" src="<?= url('assets/logos/entorns_Sense-fons-quadrat-215px.png') ?>" alt="Entorns de Natura">
        <p class="public-home__eyebrow">Entorn educatiu</p>
        <h1 class="hero__title public-home__title"><?= htmlspecialchars(trans('home_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="hero__text public-home__text"><?= htmlspecialchars(trans('home_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="actions hero__actions public-home__actions">
            <a class="button" href="<?= url('login') ?>"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></a>
            <a class="button button--secondary" href="<?= url('ca/projectes') ?>"><?= htmlspecialchars(trans('projects'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>

    <aside class="public-home__panel" aria-label="Punts destacats">
        <div class="public-home__panel-item">
            <span class="public-home__panel-label">Projectes</span>
            <strong class="public-home__panel-title">Rius, agroecologia i biodiversitat</strong>
            <p>Continguts reals i seguiment per classes.</p>
        </div>
        <div class="public-home__panel-item">
            <span class="public-home__panel-label">Espais privats</span>
            <strong class="public-home__panel-title">Alumnes i professorat</strong>
            <p>Accés separat per rols i visualització clara.</p>
        </div>
        <div class="public-home__panel-item">
            <span class="public-home__panel-label">Base modular</span>
            <strong class="public-home__panel-title">Pensada per créixer</strong>
            <p>Preparada per rúbriques, notes i Google Workspace.</p>
        </div>
    </aside>
</section>

<?php
$collaborators = [
    ['name' => 'Associació d\'Hàbitats', 'logo' => 'Logotip_AssociacioHabitats.png'],
    ['name' => 'ADF', 'logo' => 'adf-agrupacio-defensa-forestal.png'],
    ['name' => 'CREAF', 'logo' => 'CREAF_logo_A4.png'],
    ['name' => 'Moodle', 'logo' => 'Moodle-1-740x380.png'],
    ['name' => 'ICO', 'logo' => 'Logotip_ICO.png'],
    ['name' => 'Exocat', 'logo' => 'Logotip_EXOCAT.png'],
    ['name' => 'Ajuntament de Sant Sadurní', 'logo' => 'Ajuntament-SantSadurni.png'],
];
?>

<section class="public-home-collaborators" aria-labelledby="collaborators-title">
    <div class="public-home-collaborators__header">
        <p class="public-home-collaborators__eyebrow">Col·laboradors</p>
        <h2 id="collaborators-title" class="public-home-collaborators__title">Entitats i suport del projecte</h2>
    </div>
    <div class="public-home-collaborators__grid">
        <?php foreach ($collaborators as $collaborator): ?>
            <article class="public-home-collaborator-card">
                <img class="public-home-collaborator-card__logo" src="<?= url('assets/logos/' . $collaborator['logo']) ?>" alt="<?= htmlspecialchars($collaborator['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
