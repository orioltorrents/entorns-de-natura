<header class="site-header">
    <div class="brand site-header__brand">
        <a href="<?= url('') ?>">Entorns de Natura</a>
    </div>
    <nav class="site-header__nav">
        <a class="site-header__nav-link" href="<?= url('ca/projectes') ?>"><?= htmlspecialchars(trans('projects'), ENT_QUOTES, 'UTF-8') ?></a>

        <?php if (!empty($_SESSION['user'])): ?>
            <?php $roles = $_SESSION['user']['roles'] ?? []; ?>
            <?php if (in_array('student', $roles, true)): ?>
                <a class="site-header__nav-link" href="<?= url('alumne') ?>">Alumne</a>
            <?php endif; ?>
            <?php if (in_array('teacher', $roles, true)): ?>
                <a class="site-header__nav-link" href="<?= url('professor') ?>">Professorat</a>
            <?php endif; ?>
            <?php if (in_array('admin', $roles, true)): ?>
                <a class="site-header__nav-link" href="<?= url('admin') ?>">Admin</a>
            <?php endif; ?>
            <a class="site-header__nav-link" href="<?= url('logout') ?>">Surt</a>
        <?php else: ?>
            <a class="site-header__nav-link" href="<?= url('login') ?>"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </nav>
</header>
