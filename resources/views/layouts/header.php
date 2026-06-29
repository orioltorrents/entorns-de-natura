<header class="site-header">
    <div class="brand">
        <a href="<?= url('') ?>">Entorns de Natura</a>
    </div>
    <nav>
        <a href="<?= url('ca/projectes') ?>"><?= htmlspecialchars(trans('projects'), ENT_QUOTES, 'UTF-8') ?></a>

        <?php if (!empty($_SESSION['user'])): ?>
            <?php $roles = $_SESSION['user']['roles'] ?? []; ?>
            <?php if (in_array('student', $roles, true)): ?>
                <a href="<?= url('alumne') ?>">Alumne</a>
            <?php endif; ?>
            <?php if (in_array('teacher', $roles, true)): ?>
                <a href="<?= url('professor') ?>">Professorat</a>
            <?php endif; ?>
            <?php if (in_array('admin', $roles, true)): ?>
                <a href="<?= url('admin') ?>">Admin</a>
            <?php endif; ?>
            <a href="<?= url('logout') ?>">Surt</a>
        <?php else: ?>
            <a href="<?= url('login') ?>"><?= htmlspecialchars(trans('login'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
    </nav>
</header>
