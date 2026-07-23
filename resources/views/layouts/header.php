<?php if (!empty($_SESSION['impersonation']['target'])): ?>
    <?php
    $impersonatedUser = $_SESSION['impersonation']['target'];
    $impersonatedName = trim((string) ($impersonatedUser['name'] ?? '') . ' ' . (string) ($impersonatedUser['surname'] ?? ''));
    $impersonatedName = $impersonatedName !== '' ? $impersonatedName : (string) ($impersonatedUser['email'] ?? '');
    ?>
    <div class="impersonation-banner" role="status">
        <span class="impersonation-banner__text">Estàs veient com <?= htmlspecialchars($impersonatedName, ENT_QUOTES, 'UTF-8') ?></span>
        <form class="impersonation-banner__form" method="post" action="<?= url('admin/stop-impersonation') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <button class="impersonation-banner__button" type="submit">Torna al teu usuari</button>
        </form>
    </div>
<?php endif; ?>
<header class="site-header">
    <div class="brand site-header__brand">
        <a href="<?= url('') ?>">Entorns de Natura</a>
    </div>
    <nav class="site-header__nav">
        <?php if (!empty($_SESSION['user'])): ?>
            <?php
            $headerUser = $_SESSION['impersonation']['target'] ?? $_SESSION['user'];
            $roles = $headerUser['roles'] ?? [];
            $displayName = trim((string) ($headerUser['name'] ?? '') . ' ' . (string) ($headerUser['surname'] ?? ''));
            $displayName = $displayName !== '' ? $displayName : (string) ($headerUser['email'] ?? '');
            ?>
            <?php if (in_array('student', $roles, true)): ?>
                <a class="site-header__nav-link" href="<?= url('alumne') ?>">Alumne</a>
            <?php endif; ?>
            <?php if (in_array('teacher', $roles, true)): ?>
                <a class="site-header__nav-link" href="<?= url('professor') ?>">Professorat</a>
            <?php endif; ?>
            <?php if (in_array('admin', $roles, true)): ?>
                <a class="site-header__nav-link" href="<?= url('admin') ?>">Admin</a>
            <?php endif; ?>
            <?php if ($displayName !== ''): ?>
                <span class="site-header__user">Connectat: <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <a class="site-header__nav-link" href="<?= url('logout') ?>">Surt</a>
        <?php else: ?>
            <a class="site-header__nav-link" href="<?= url('login') ?>"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </nav>
</header>
