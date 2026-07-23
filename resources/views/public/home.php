<?php
ob_start();
?>
<section class="hero hero--home public-home">
    <div class="public-home__content">
        <div class="public-home__brand-row" aria-label="Logotips del projecte">
            <img class="public-home__brand public-home__brand--entorns" src="<?= url('assets/logos/entorns/entorns_Sense-fons-quadrat-215px.png') ?>" alt="Entorns de Natura">
            <img class="public-home__brand public-home__brand--intermunicipal" src="<?= url('assets/logos/intermunicipal/logo-inter-2017-transparent.png') ?>" alt="Intermunicipal">
        </div>
        <p class="public-home__eyebrow">Entorn educatiu</p>
        <h1 class="hero__title public-home__title"><?= htmlspecialchars(trans('home_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="hero__text public-home__text"><?= htmlspecialchars(trans('home_intro'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="actions hero__actions public-home__actions">
            <a class="button" href="<?= url('ca/que-es-entorns') ?>">Entra</a>
        </div>
    </div>
</section>

<?php
$collaborators = [
    ['name' => 'Associació d\'Hàbitats', 'logo' => 'collaboradors/Logotip_AssociacioHabitats.png', 'url' => 'https://www.associaciohabitats.cat/', 'logo_class' => 'public-home-collaborator-card__logo--large'],
    ['name' => 'ADF', 'logo' => 'collaboradors/adf-agrupacio-defensa-forestal.png', 'url' => 'https://santsadurni.cat/fitxa.php?id=5277', 'logo_class' => 'public-home-collaborator-card__logo--medium'],
    ['name' => 'CREAF', 'logo' => 'collaboradors/CREAF_logo_A4.png', 'url' => 'https://www.creaf.cat'],
    ['name' => 'ICO', 'logo' => 'collaboradors/Logotip_ICO.png', 'url' => 'https://www.ornitologia.org', 'logo_class' => 'public-home-collaborator-card__logo--large'],
    ['name' => 'Ajuntament de Sant Sadurní', 'logo' => 'collaboradors/Ajuntament-SantSadurni.png', 'url' => 'https://www.santsadurni.cat', 'logo_class' => 'public-home-collaborator-card__logo--medium'],
];
?>

<section class="public-home-collaborators" aria-labelledby="collaborators-title">
    <div class="public-home-collaborators__header">
        <p class="public-home-collaborators__eyebrow">Col·laboradors</p>
    </div>
    <div class="public-home-collaborators__grid">
        <?php foreach ($collaborators as $collaborator): ?>
            <article class="public-home-collaborator-card">
                <?php if (!empty($collaborator['url'])): ?>
                    <a class="public-home-collaborator-card__link" href="<?= htmlspecialchars((string) $collaborator['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                        <img class="public-home-collaborator-card__logo<?= !empty($collaborator['logo_class']) ? ' ' . htmlspecialchars($collaborator['logo_class'], ENT_QUOTES, 'UTF-8') : '' ?>" src="<?= url('assets/logos/' . $collaborator['logo']) ?>" alt="<?= htmlspecialchars($collaborator['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                    </a>
                <?php else: ?>
                    <img class="public-home-collaborator-card__logo<?= !empty($collaborator['logo_class']) ? ' ' . htmlspecialchars($collaborator['logo_class'], ENT_QUOTES, 'UTF-8') : '' ?>" src="<?= url('assets/logos/' . $collaborator['logo']) ?>" alt="<?= htmlspecialchars($collaborator['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
