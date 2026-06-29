<header>
    <nav>
        <ul class="nav-list">

            <!-- Projecte Rius (individual) -->
            <li class="nav-element">
            <a href="/projectes/projecte-rius/projecte-rius.php">Projecte Rius</a>
            </li>

            <!-- Bloc 2: Informe d’impacte ambiental -->
            <li class="nav-element">
            <span class="titol-seccio">Informe d’impacte ambiental</span>
            <ul class="submenu">
                <li><a href="/projectes/mat-penedes/mat-penedes.php">MAT Penedès</a></li>
                <li><a href="/projectes/agroparc/agroparc.php">Agroparc</a></li>
            </ul>
            </li>

            <!-- Bloc 3: Ciència ciutadana -->
            <li class="nav-element">
            <span class="titol-seccio">Ciència ciutadana</span>
            <ul class="submenu">
                <li><a href="/projectes/liquencity/liquencity.php">Liquencity</a></li>
                <li><a href="/projectes/projecte-orenetes/projecte-orenetes.php">Projecte Orenetes</a></li>
                <li><a href="/projectes/vespa-velutina/vespa-velutina.php">Vespa velutina</a></li>
                <li><a href="/projectes/ratpenats/ratpenats.php">Ratpenats</a></li>
            </ul>
            </li>

            <!-- Moodle -->
            <li class="nav-element">
            <a href="../moodle/" target="_blank">Moodle</a>
            </li>

            <!-- Sessió: login o logout -->
            <li class="nav-element">
            <?php if (isset($_SESSION['usuari'])): ?>
                <a href="/logout.php"><?= $t['tancar_sessio'][$lang] ?? 'Tancar sessió' ?></a>
            <?php else: ?>
                <a href="/login/login.php"><?= $t['inici_sessio'][$lang] ?? 'Inicia sessió' ?></a>
            <?php endif; ?>
            </li>

            <!-- Idiomes amb banderes -->
            <li class="nav-element lang-switcher">
            <a href="?lang=ca"><img src="../assets/icons/flags/cat.svg" alt="Català" height="20"></a>
            <a href="?lang=es"><img src="../assets/icons/flags/es.svg" alt="Castellano" height="20"></a>
            <a href="?lang=en"><img src="../assets/icons/flags/en.svg" alt="English" height="20"></a>
            </li>

        </ul>
    </nav>
</header>
