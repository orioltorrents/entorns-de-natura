<?php
ob_start();
?>
<div class="admin-layout">
    <aside class="admin-layout__sidebar">
        <div class="admin-layout__brand">
            <strong>Entorns de Natura</strong>
            <span>Administració</span>
        </div>
        <nav class="admin-layout__nav">
            <a class="active" href="#panell">Panell</a>
            <a href="#analytics">Visites</a>
            <div class="admin-layout__nav-group">
                <button class="admin-layout__nav-toggle" type="button" data-nav-group-toggle="usuaris-submenu" aria-expanded="false">
                    Usuaris
                </button>
                <div class="admin-layout__submenu" id="usuaris-submenu" hidden>
                    <a href="#crear-usuari">Crear usuari</a>
                    <a href="#importar-usuaris">Importar CSV</a>
                    <a href="#alumnes-seccio">Alumnes</a>
                    <a href="#professors-seccio">Professors</a>
                </div>
            </div>
            <a href="#classes">Classes</a>
            <a href="#projectes-lista">Projectes</a>
            <a href="#equips">Equips</a>
            <a href="#rols">Rols</a>
            <a href="#avaluacio">Fases i tasques</a>
            <a href="<?= url('admin/sync-documents') ?>">Google Sync</a>
            <a href="#logs">Logs</a>
        </nav>
    </aside>

    <div class="admin-layout__content" id="panell">
        <?php if (!empty($message)): ?>
            <div class="flash-message <?= htmlspecialchars((string) $messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="admin-summary">
            <div class="admin-summary__card">
                <div class="admin-summary__icon">👤</div>
                <div class="admin-summary__body">
                    <span class="admin-summary__label">Usuaris</span>
                    <span class="admin-summary__value"><?= count($users) ?></span>
                    <span class="admin-summary__desc">Registrats al sistema</span>
                </div>
            </div>
            <div class="admin-summary__card">
                <div class="admin-summary__icon">🛡️</div>
                <div class="admin-summary__body">
                    <span class="admin-summary__label">Rols web</span>
                    <span class="admin-summary__value"><?= count($roles) ?></span>
                    <div class="admin-summary__roles">
                        <?php foreach ($roles as $role): ?>
                            <span class="admin-summary__role-badge">
                                <?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?>
                                <small><?= (int) $role['user_count'] ?></small>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="admin-summary__card admin-summary__card--good">
                <div class="admin-summary__icon">✅</div>
                <div class="admin-summary__body">
                    <span class="admin-summary__label">Actius</span>
                    <span class="admin-summary__value"><?= count(array_filter($users, fn($user) => (int) $user['is_active'] === 1)) ?></span>
                    <span class="admin-summary__desc">Usuaris amb accés actiu</span>
                </div>
            </div>
            <div class="admin-summary__card admin-summary__card--muted">
                <div class="admin-summary__icon">⏸️</div>
                <div class="admin-summary__body">
                    <span class="admin-summary__label">Inactius</span>
                    <span class="admin-summary__value"><?= count(array_filter($users, fn($user) => (int) $user['is_active'] !== 1)) ?></span>
                    <span class="admin-summary__desc">Usuaris desactivats</span>
                </div>
            </div>
        </div>

        <div id="analytics" class="card admin-panel admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Visites</h2>
                <div class="admin-actions">
                    <span class="status">Visites, dispositius, geografia i classes</span>
                    <button class="collapse-toggle" type="button" data-collapse="analytics-content">Mostrar</button>
                </div>
            </div>

            <div id="analytics-content" class="admin-collapsible__content">
            <div class="admin-metrics">
                <div class="admin-metrics__card">
                    <div class="admin-metrics__icon">📈</div>
                    <div>
                        <h2>Visites totals</h2>
                        <p class="stat-value"><?= (int) ($analytics['total_visits'] ?? 0) ?></p>
                        <p>Registres de trànsit des de la instal·lació</p>
                    </div>
                </div>
                <div class="admin-metrics__card">
                    <div class="admin-metrics__icon">🧭</div>
                    <div>
                        <h2>Sessions úniques</h2>
                        <p class="stat-value"><?= (int) ($analytics['unique_sessions'] ?? 0) ?></p>
                        <p>Sessions diferents capturades</p>
                    </div>
                </div>
                <div class="admin-metrics__card">
                    <div class="admin-metrics__icon">👩‍🏫</div>
                    <div>
                        <h2>Usuaris reconeguts</h2>
                        <p class="stat-value"><?= (int) ($analytics['unique_users'] ?? 0) ?></p>
                        <p>Visites vinculades a usuaris</p>
                    </div>
                </div>
            </div>

            <div class="admin-panels">
                <section class="card admin-panel admin-panel--table">
                    <h3>Connexions per classe</h3>
                    <div class="admin-table__wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Classe</th>
                                    <th>Alumnes</th>
                                    <th>Connexions</th>
                                    <th>Sense connectar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($analytics['class_stats'] ?? []) as $class): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string) ($class['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= (int) ($class['total_students'] ?? 0) ?></td>
                                        <td><?= (int) ($class['connected_students'] ?? 0) ?></td>
                                        <td><?= (int) ($class['pending_students'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card admin-panel admin-panel--chart">
                    <div class="admin-panel__header">
                        <h3>Dispositius i Sistemes operatius</h3>
                        <span class="status">Dispositius i sistemes operatius</span>
                    </div>
                    <?php
                    $maxDevice = max(array_column($analytics['device_stats'] ?? [['total' => 0]], 'total'));
                    $maxOS = max(array_column($analytics['os_stats'] ?? [['total' => 0]], 'total'));
                    ?>
                    <div class="admin-chart-grid">
                        <article class="admin-chart-panel">
                            <h4>Dispositius</h4>
                            <div class="admin-chart-list">
                                <?php foreach (($analytics['device_stats'] ?? []) as $device): ?>
                                    <?php $deviceTotal = (int) ($device['total'] ?? 0); ?>
                                    <div class="admin-chart-row">
                                        <div class="admin-chart-row__meta">
                                            <span class="admin-chart-row__label"><?= htmlspecialchars(ucfirst((string) ($device['device_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="admin-chart-row__value"><?= $deviceTotal ?></span>
                                        </div>
                                        <div class="admin-chart-track">
                                            <div class="admin-chart-fill" style="width:<?= $maxDevice > 0 ? round(($deviceTotal / $maxDevice) * 100) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                        <article class="admin-chart-panel">
                            <h4>Sistemes operatius</h4>
                            <div class="admin-chart-list">
                                <?php foreach (($analytics['os_stats'] ?? []) as $os): ?>
                                    <?php $osTotal = (int) ($os['total'] ?? 0); ?>
                                    <div class="admin-chart-row">
                                        <div class="admin-chart-row__meta">
                                            <span class="admin-chart-row__label"><?= htmlspecialchars(ucfirst((string) ($os['os_family'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="admin-chart-row__value"><?= $osTotal ?></span>
                                        </div>
                                        <div class="admin-chart-track admin-chart-track--os">
                                            <div class="admin-chart-fill admin-chart-fill--os" style="width:<?= $maxOS > 0 ? round(($osTotal / $maxOS) * 100) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="card admin-panel admin-panel--map">
                    <div class="admin-panel__header">
                        <h3>Mapa de visites</h3>
                        <span class="status">Leaflet + punts geogràfics</span>
                    </div>
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
                    <div class="admin-geo-map-layout">
                        <div class="admin-geo-map" data-geo-map data-geo-points="<?= htmlspecialchars(json_encode($geoMapPoints ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"></div>
                        <div class="admin-geo-legend">
                            <h4>Resum geogràfic</h4>
                            <p class="muted">Els punts es mostren amb coordenades aproximades segons el país capturat.</p>
                            <div class="admin-geo-legend__stats">
                                <span class="admin-geo-legend__pill">Països: <?= count($analytics['geo_stats'] ?? []) ?></span>
                                <span class="admin-geo-legend__pill">Punts al mapa: <?= count($geoMapPoints ?? []) ?></span>
                            </div>
                            <ul class="admin-geo-legend__list">
                                <?php foreach (array_slice($analytics['geo_stats'] ?? [], 0, 8) as $geo): ?>
                                    <?php
                                    $code = strtoupper((string) ($geo['country_code'] ?? ''));
                                    $flag = strlen($code) === 2 ? mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65) : '🌍';
                                    ?>
                                    <li>
                                        <span><?= $flag ?> <?= htmlspecialchars($code ?: '??', ENT_QUOTES, 'UTF-8') ?></span>
                                        <strong><?= (int) ($geo['total'] ?? 0) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
                </section>
            </div>

            <div class="admin-panels">
                <section class="card admin-panel admin-panel--geo">
                    <h3>Geografia</h3>
                    <?php $maxGeo = max(array_column($analytics['geo_stats'] ?? [['total' => 0]], 'total')); ?>
                    <div class="bar-chart">
                        <?php foreach (($analytics['geo_stats'] ?? []) as $geo):
                            $code = strtoupper((string) ($geo['country_code'] ?? ''));
                            $flag = strlen($code) === 2 ? mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65) : '🌍';
                        ?>
                            <div class="bar-row">
                                <span class="bar-label"><?= $flag ?> <?= htmlspecialchars($code ?: '??', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string) ($geo['region'] ?? 'Desconegut'), ENT_QUOTES, 'UTF-8') ?></span>
                                <div class="bar-track"><div class="bar-fill bar-fill-geo" style="width:<?= $maxGeo > 0 ? round(((int) $geo['total'] / $maxGeo) * 100) : 0 ?>%"></div></div>
                                <span class="bar-value"><?= (int) ($geo['total'] ?? 0) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card admin-panel admin-panel--pages">
                    <h3>Pàgines més vistes</h3>
                    <ul>
                        <?php foreach (($analytics['page_stats'] ?? []) as $page): ?>
                            <li><strong><?= htmlspecialchars((string) ($page['path'] ?? '/'), ENT_QUOTES, 'UTF-8') ?></strong>: <?= (int) ($page['total'] ?? 0) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>

            <section class="card admin-panel admin-panel--recent">
                <h3>Visites recents</h3>
                <div class="admin-table__wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuari</th>
                                <th>Pàgina</th>
                                <th>Dispositiu</th>
                                <th>SO</th>
                                <th>Geografia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($analytics['recent_visits'] ?? []) as $visit): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($visit['visited_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(trim((string) ($visit['name'] ?? '') . ' ' . (string) ($visit['surname'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($visit['path'] ?? '/'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($visit['device_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($visit['os_family'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) (($visit['country_code'] ?? 'Desconegut') . '/' . ($visit['region'] ?? 'Desconegut')), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </div>

        <?php
        $classGroups = [];
        foreach ($users as $u) {
            $cg = !empty($u['class_group']) ? $u['class_group'] : 'Sense classe';
            $classGroups[$cg] = true;
        }
        ksort($classGroups);
        ?>

        <div id="usuaris-seccio" class="card admin-panel admin-panel--users admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Usuaris</h2>
                <div class="admin-actions">
                    <span class="status">Taula, creació i importació</span>
                    <button class="collapse-toggle" type="button" data-collapse="usuaris-content">Mostrar</button>
                </div>
            </div>

            <div id="usuaris-content" class="admin-collapsible__content">
                <section id="crear-usuari" class="card admin-panel admin-panel--users">
                    <h2>Crear usuari</h2>
                    <form class="admin-form admin-form--users" method="post" action="<?= url('admin') ?>">
                        <input type="hidden" name="action" value="create_user">
                        <div class="form__grid">
                            <label>
                                Nom
                                <input type="text" name="name" required>
                            </label>
                            <label>
                                Cognoms
                                <input type="text" name="surname">
                            </label>
                            <label>
                                Email
                                <input type="email" name="email" required>
                            </label>
                            <label>
                                Contrasenya
                                <input type="password" name="password" required>
                            </label>
                        </div>
                        <label class="form__check">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Usuari actiu
                        </label>
                        <div class="form__group">
                            <label>Assignar rols</label>
                            <div class="form__choices">
                                <?php foreach ($roles as $role): ?>
                                    <label class="form__choice">
                                        <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>">
                                        <?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button class="button" type="submit">Crear usuari</button>
                    </form>
                </section>

                <div id="importar-usuaris" class="card admin-panel admin-panel--import">
                    <h2>Importar alumnes CSV</h2>
                    <p>Importa fitxers amb columnes com <strong>email</strong>, <strong>name</strong>, <strong>surname</strong>, <strong>is_active</strong>, <strong>class_code</strong> o <strong>class</strong>, <strong>project_academic_year_id</strong> o <strong>project_slug</strong> + <strong>academic_year</strong>, <strong>team_code</strong> i <strong>project_role</strong>. El sistema crea o actualitza l’usuari, sincronitza la classe actual i assigna l’equip de projecte quan hi ha aquestes dades.</p>
                    <pre class="admin-import-example"><code>email,name,surname,is_active,class_code,project_slug,academic_year,team_code,team_name,project_role
aiman@example.com,Aiman,Aliaga,1,24-25_4ESOA,projecte-rius,2024-2025,24-25_projecte-rius_4ESOA-1,4ESOAB-1,científic/a
silvia@example.com,Sílvia,Serra,1,24-25_4ESOB,agroparc,2024-2025,24-25_agroparc_4ESOB-2,4ESOAB-1,cartògraf/a</code></pre>
                    <form class="admin-form admin-form--import" method="post" enctype="multipart/form-data" action="<?= url('admin') ?>">
                        <input type="hidden" name="action" value="import_students">
                        <label>
                            Fitxer CSV
                            <input type="file" name="students_file" accept=".csv,text/csv" required>
                        </label>
                        <button class="button" type="submit">Importar alumnes</button>
                    </form>
                    <?php if (!empty($importSummary) && is_array($importSummary)): ?>
                        <div class="admin-import-summary">
                            <div class="admin-import-summary__item">
                                <strong><?= (int) ($importSummary['created'] ?? 0) ?></strong>
                                <span>creats</span>
                            </div>
                            <div class="admin-import-summary__item">
                                <strong><?= (int) ($importSummary['updated'] ?? 0) ?></strong>
                                <span>actualitzats</span>
                            </div>
                            <div class="admin-import-summary__item">
                                <strong><?= (int) ($importSummary['team_assignments'] ?? 0) ?></strong>
                                <span>equips sincronitzats</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <section id="alumnes-seccio" class="card admin-panel admin-panel--students admin-collapsible is-collapsed">
                    <div class="admin-panel__header">
                        <h2>Alumnes</h2>
                        <div class="admin-actions">
                            <span class="status" id="alumnes-count"><?= count($users) ?> usuaris</span>
                            <button class="collapse-toggle" type="button" data-collapse="alumnes-content">Mostrar</button>
                        </div>
                    </div>
                    <div class="admin-filters" id="alumnes-filter" data-filter-table="alumnes-table">
                        <span class="admin-filters__label">Classe:</span>
                        <button class="admin-filters__chip is-active" type="button" data-value="all">Totes</button>
                        <?php foreach ($classGroups as $group => $_): ?>
                            <button class="admin-filters__chip" type="button" data-value="<?= htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8') ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div id="alumnes-content" class="admin-collapsible__content">
                        <div class="admin-table__wrapper">
                            <table class="admin-table" id="alumnes-table" data-sortable-table>
                                <thead>
                                    <tr>
                                        <th scope="col" data-sort-type="text">Usuari</th>
                                        <th scope="col" data-sort-type="text">Email</th>
                                        <th scope="col" data-sort-type="text" class="th-class">Classe</th>
                                        <th scope="col">Accions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php $userClass = !empty($user['class_group']) ? $user['class_group'] : 'Sense classe'; ?>
                                        <?php $isAdminUser = in_array('admin', $user['roles'], true); ?>
                                        <tr data-class="<?= htmlspecialchars((string) $userClass, ENT_QUOTES, 'UTF-8') ?>">
                                            <td><?= htmlspecialchars(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if (!empty($user['class_group'])): ?>
                                                    <?= htmlspecialchars((string) $user['class_group'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <span class="muted">Sense classe</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="admin-actions">
                                                    <?php if (!$isAdminUser): ?>
                                                        <form method="post" action="<?= url('admin') ?>" class="admin-inline-action">
                                                            <input type="hidden" name="action" value="toggle_user">
                                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                            <button class="button button--secondary" type="submit">
                                                                <?= ((int) $user['is_active'] === 1) ? 'Desactivar' : 'Activar' ?>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="status">Admin protegit</span>
                                                    <?php endif; ?>
                                                    <button class="button" type="button" data-target="student-<?= (int) $user['id'] ?>">Editar</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr id="student-<?= (int) $user['id'] ?>" class="student-editor-row">
                                            <td colspan="4">
                                                <form class="admin-form admin-form--compact" method="post" action="<?= url('admin') ?>">
                                                    <input type="hidden" name="action" value="update_student">
                                                    <input type="hidden" name="student_id" value="<?= (int) $user['id'] ?>">
                                                    <div class="form__grid form__grid--compact">
                                                        <label>Nom<input type="text" name="name" value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label>
                                                        <label>Cognoms<input type="text" name="surname" value="<?= htmlspecialchars((string) ($user['surname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                        <label>Email<input type="email" name="email" value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label>
                                                        <label>Classe
                                                            <select name="class_id">
                                                                <option value="">Sense classe</option>
                                                                <?php foreach ($classes as $class): ?>
                                                                    <option value="<?= (int) $class['id'] ?>" <?= ((int) ($user['class_id'] ?? 0) === (int) $class['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string) $class['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                         <label>Gènere<input type="text" name="gender" value="<?= htmlspecialchars((string) ($user['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                         <label>Article<input type="text" name="article" value="<?= htmlspecialchars((string) ($user['article'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                         <label>Inaturalist<input type="text" name="inaturalist_user_login" value="<?= htmlspecialchars((string) ($user['inaturalist_user_login'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                     </div>
                                                    <div class="form__group">
                                                        <label>Rols</label>
                                                        <div class="form__choices">
                                                            <?php foreach ($roles as $role): ?>
                                                                <label class="form__choice">
                                                                    <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>" <?= in_array((string) $role['name'], $user['roles'], true) ? 'checked' : '' ?>>
                                                                    <?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($isAdminUser): ?>
                                                        <input type="hidden" name="is_active" value="1">
                                                        <p class="muted">Aquest usuari té rol admin i es manté actiu per protegir l’accés al panell.</p>
                                                    <?php else: ?>
                                                        <label class="form__check">
                                                            <input type="checkbox" name="is_active" value="1" <?= ((int) $user['is_active'] === 1) ? 'checked' : '' ?>>
                                                            Usuari actiu
                                                        </label>
                                                    <?php endif; ?>
                                                    <button class="button" type="submit">Guardar canvis</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="professors-seccio" class="card admin-panel admin-panel--teachers admin-collapsible is-collapsed">
                    <div class="admin-panel__header">
                        <h2>Professors</h2>
                        <div class="admin-actions">
                            <span class="status"><?= count($teacherUsers) ?> professors</span>
                            <button class="collapse-toggle" type="button" data-collapse="professors-content">Mostrar</button>
                        </div>
                    </div>

                    <div id="professors-content" class="admin-collapsible__content">
                        <div class="admin-table__wrapper">
                            <table class="admin-table" data-sortable-table>
                                <thead>
                                    <tr>
                                        <th scope="col" data-sort-type="text">Professor</th>
                                        <th scope="col" data-sort-type="text">Email</th>
                                        <th scope="col" data-sort-type="text">Classes</th>
                                        <th scope="col">Accions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teacherUsers as $teacher): ?>
                                        <?php $teacherId = (int) $teacher['id']; ?>
                                        <?php $teacherClasses = $teacherClassMap[$teacherId] ?? []; ?>
                                        <tr data-class="teacher">
                                            <td><?= htmlspecialchars(trim((string) ($teacher['name'] ?? '') . ' ' . (string) ($teacher['surname'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($teacher['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if (!empty($teacherClasses)): ?>
                                                    <div class="admin-table__meta">
                                                        <?php foreach ($teacherClasses as $teacherClass): ?>
                                                            <span class="admin-table__meta-item"><?= htmlspecialchars((string) $teacherClass['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="muted">Sense classes assignades</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="admin-actions">
                                                    <form method="post" action="<?= url('admin') ?>" class="admin-inline-action">
                                                        <input type="hidden" name="action" value="toggle_user">
                                                        <input type="hidden" name="user_id" value="<?= $teacherId ?>">
                                                        <button class="button button--secondary" type="submit">
                                                            <?= ((int) $teacher['is_active'] === 1) ? 'Desactivar' : 'Activar' ?>
                                                        </button>
                                                    </form>
                                                    <button class="button" type="button" data-target="teacher-<?= $teacherId ?>">Editar</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr id="teacher-<?= $teacherId ?>" class="student-editor-row">
                                            <td colspan="4">
                                                <form class="admin-form admin-form--compact" method="post" action="<?= url('admin') ?>">
                                                    <input type="hidden" name="action" value="update_student">
                                                    <input type="hidden" name="student_id" value="<?= $teacherId ?>">
                                                    <div class="form__grid form__grid--compact">
                                                        <label>Nom<input type="text" name="name" value="<?= htmlspecialchars((string) ($teacher['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label>
                                                        <label>Cognoms<input type="text" name="surname" value="<?= htmlspecialchars((string) ($teacher['surname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                        <label>Email<input type="email" name="email" value="<?= htmlspecialchars((string) ($teacher['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></label>
                                                        <label>Classe
                                                            <select name="class_id">
                                                                <option value="">Sense classe</option>
                                                                <?php foreach ($classes as $class): ?>
                                                                    <option value="<?= (int) $class['id'] ?>" <?= ((int) ($teacher['class_id'] ?? 0) === (int) $class['id']) ? 'selected' : '' ?>><?= htmlspecialchars((string) $class['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </label>
                                                        <label>Gènere<input type="text" name="gender" value="<?= htmlspecialchars((string) ($teacher['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                        <label>Article<input type="text" name="article" value="<?= htmlspecialchars((string) ($teacher['article'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                        <label>Inaturalist<input type="text" name="inaturalist_user_login" value="<?= htmlspecialchars((string) ($teacher['inaturalist_user_login'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                                    </div>
                                                    <div class="form__group">
                                                        <label>Rols</label>
                                                        <div class="form__choices">
                                                            <?php foreach ($roles as $role): ?>
                                                                <label class="form__choice">
                                                                    <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>" <?= in_array((string) $role['name'], $teacher['roles'], true) ? 'checked' : '' ?>>
                                                                    <?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                    <?php if ((int) $teacher['is_active'] === 1): ?>
                                                        <input type="hidden" name="is_active" value="1">
                                                    <?php else: ?>
                                                        <label class="form__check">
                                                            <input type="checkbox" name="is_active" value="1">
                                                            Usuari actiu
                                                        </label>
                                                    <?php endif; ?>
                                                    <button class="button" type="submit">Guardar canvis</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div id="classes" class="card admin-panel admin-panel--teachers admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Classes</h2>
                <div class="admin-actions">
                    <span class="status">Controla `class_teachers`</span>
                    <button class="collapse-toggle" type="button" data-collapse="classes-content">Mostrar</button>
                </div>
            </div>

            <div id="classes-content" class="admin-collapsible__content">
                <p>Marca el professorat assignat a cada classe. Aquestes relacions alimenten el dashboard de professorat.</p>

                <div class="admin-table__wrapper">
                    <table class="admin-table admin-table--compact">
                        <thead>
                            <tr>
                                <th scope="col">Classe</th>
                                <th scope="col">Professorat assignat</th>
                                <th scope="col">Modificar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <?php
                                $classId = (int) $class['id'];
                                $assignedTeachers = $classTeachersMap[$classId] ?? [];
                                $assignedTeacherIds = array_map(static fn (array $teacher): int => (int) $teacher['id'], $assignedTeachers);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars((string) $class['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <span class="muted"><?= htmlspecialchars((string) $class['code'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($assignedTeachers)): ?>
                                            <div class="admin-table__meta">
                                                <?php foreach ($assignedTeachers as $teacher): ?>
                                                    <span class="admin-table__tag"><?= htmlspecialchars((string) $teacher['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="muted">Sense professorat assignat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form class="admin-form admin-form--compact" method="post" action="<?= url('admin') ?>#classes">
                                            <input type="hidden" name="action" value="sync_class_teachers">
                                            <input type="hidden" name="class_id" value="<?= $classId ?>">
                                            <div class="form__choices">
                                                <?php foreach ($teacherUsers as $teacher): ?>
                                                    <?php $teacherId = (int) $teacher['id']; ?>
                                                    <label class="form__choice">
                                                        <input type="checkbox" name="teacher_ids[]" value="<?= $teacherId ?>" <?= in_array($teacherId, $assignedTeacherIds, true) ? 'checked' : '' ?>>
                                                        <?= htmlspecialchars(trim((string) ($teacher['name'] ?? '') . ' ' . (string) ($teacher['surname'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="button" type="submit">Guardar professorat</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <?php
        $projectAssignmentsByProject = [];
        foreach ($projectAssignments as $assignment) {
            $projectAssignmentsByProject[(int) $assignment['project_id']][] = $assignment;
        }
        ?>
        <div id="projectes-lista" class="card admin-panel admin-panel--projects admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Projectes</h2>
                <div class="admin-actions">
                    <span class="status">Ordre, estat i assignacions</span>
                    <button class="collapse-toggle" type="button" data-collapse="projectes-content">Mostrar</button>
                </div>
            </div>

            <div id="projectes-content" class="admin-collapsible__content">
                <form id="project-order-form" method="post" action="<?= url('admin') ?>#projectes-lista">
                    <input type="hidden" name="action" value="update_project_order">
                </form>
                <div class="admin-projects">
                    <?php foreach ($projects as $project): ?>
                        <?php
                            $projectId = (int) $project['id'];
                            $projectSlug = (string) ($project['slug'] ?? '');
                            $projectAssignmentsForCard = $projectAssignmentsByProject[$projectId] ?? [];
                            $projectAsset = $project['logo_asset'] ?? ($project['assets'][0] ?? null);
                        ?>
                        <article class="project-admin-card">
                            <div class="project-admin-card__header">
                                <div class="project-admin-card__logo">
                                    <?php if (!empty($projectAsset['logo_path'])): ?>
                                        <img src="<?= url((string) $projectAsset['logo_path']) ?>" alt="<?= htmlspecialchars((string) $projectAsset['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                    <?php else: ?>
                                        <img src="<?= url('assets/logos/entorns/entorns_Sense-fons-quadrat-215px.png') ?>" alt="Entorns de Natura" loading="lazy">
                                    <?php endif; ?>
                                </div>
                                <div class="project-admin-card__title">
                                    <strong><?= htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars($projectSlug, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="status <?= ((int) ($project['is_active'] ?? 0) === 1) ? 'status--active' : 'status--inactive' ?>">
                                    <?= ((int) ($project['is_active'] ?? 0) === 1) ? 'Actiu' : 'Inactiu' ?>
                                </span>
                            </div>
                            <p class="project-admin-card__description"><?= htmlspecialchars((string) ($project['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="project-admin-card__controls">
                                <label class="project-admin-card__order">
                                    Ordre
                                    <input form="project-order-form" type="number" name="display_order[<?= $projectId ?>]" value="<?= (int) ($project['display_order'] ?? 0) ?>" min="0" step="1">
                                </label>
                                <button class="button button--secondary" form="project-order-form" type="submit">Guardar ordre</button>
                                <form method="post" action="<?= url('admin') ?>#projectes-lista" class="admin-inline-action">
                                    <input type="hidden" name="action" value="toggle_project">
                                    <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                    <button class="button button--secondary" type="submit">
                                        <?= ((int) ($project['is_active'] ?? 0) === 1) ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </div>

                            <div class="project-admin-card__assignments">
                                <div class="project-admin-card__assignments-header">
                                    <strong>Assignacions</strong>
                                    <span class="status"><?= count($projectAssignmentsForCard) ?> classes</span>
                                </div>

                                <form class="project-admin-card__assign-form" method="post" action="<?= url('admin') ?>#projectes-lista">
                                    <input type="hidden" name="action" value="assign_project_to_class">
                                    <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                    <select name="class_id" required aria-label="Classe">
                                        <option value="">Classe</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= (int) $class['id'] ?>"><?= htmlspecialchars((string) $class['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" required aria-label="Estat inicial">
                                        <option value="pendent">Pendent</option>
                                        <option value="actiu" selected>Actiu</option>
                                        <option value="realitzat">Realitzat</option>
                                    </select>
                                    <button class="button" type="submit">Assignar</button>
                                </form>

                                <?php if (!empty($projectAssignmentsForCard)): ?>
                                    <div class="project-admin-card__assignment-list">
                                        <?php foreach ($projectAssignmentsForCard as $assignment): ?>
                                            <?php
                                                $assignmentStatus = strtolower(trim((string) ($assignment['status'] ?? 'actiu')));
                                                $assignmentStatusMap = [
                                                    'planned' => 'pendent',
                                                    'previst' => 'pendent',
                                                    'active' => 'actiu',
                                                    'completed' => 'realitzat',
                                                    'completat' => 'realitzat',
                                                ];
                                                $assignmentStatus = $assignmentStatusMap[$assignmentStatus] ?? $assignmentStatus;
                                                $statusLabels = [
                                                    'pendent' => 'Pendent',
                                                    'actiu' => 'Actiu',
                                                    'realitzat' => 'Realitzat',
                                                ];
                                                $statusLabel = $statusLabels[$assignmentStatus] ?? $assignmentStatus;
                                            ?>
                                            <div class="project-admin-card__assignment">
                                                <div class="project-admin-card__assignment-main">
                                                    <strong><?= htmlspecialchars((string) ($assignment['class_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <?php if (!empty($assignment['academic_year_name'])): ?>
                                                        <span><?= htmlspecialchars((string) $assignment['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <div class="project-admin-card__assignment-actions">
                                                    <form method="post" action="<?= url('admin') ?>#projectes-lista" class="project-admin-card__assignment-status">
                                                        <input type="hidden" name="action" value="update_project_assignment_status">
                                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                                        <select name="status" aria-label="Estat del projecte">
                                                            <option value="pendent" <?= $assignmentStatus === 'pendent' ? 'selected' : '' ?>>Pendent</option>
                                                            <option value="actiu" <?= $assignmentStatus === 'actiu' ? 'selected' : '' ?>>Actiu</option>
                                                            <option value="realitzat" <?= $assignmentStatus === 'realitzat' ? 'selected' : '' ?>>Realitzat</option>
                                                        </select>
                                                        <button class="button button--secondary" type="submit">Guardar</button>
                                                    </form>
                                                    <form method="post" action="<?= url('admin') ?>#projectes-lista" class="project-admin-card__assignment-delete" data-confirm="Vols eliminar aquesta assignació?">
                                                        <input type="hidden" name="action" value="delete_project_assignment">
                                                        <input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>">
                                                        <button class="button button--danger" type="submit">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="muted">Encara no està assignat a cap classe.</p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <button class="button" form="project-order-form" type="submit">Guardar tots els ordres</button>
            </div>
        </div>

        <div id="equips" class="card admin-panel admin-panel--teams admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Equips de projecte</h2>
                <div class="admin-actions">
                    <span class="status"><?= count($projectTeams ?? []) ?> equips</span>
                    <button class="collapse-toggle" type="button" data-collapse="equips-content">Mostrar</button>
                </div>
            </div>

            <div id="equips-content" class="admin-collapsible__content">
                <?php if (empty($projectTeams ?? [])): ?>
                    <div class="empty-state">
                        <p>Encara no hi ha equips sincronitzats.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-table__wrapper">
                        <table class="admin-table admin-table--compact">
                            <thead>
                                <tr>
                                    <th>Projecte / curs</th>
                                    <th>Equip</th>
                                    <th>Classe</th>
                                    <th>Membres i rols</th>
                                    <th>Estat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectTeams as $team): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars((string) $team['project_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <span class="muted"><?= htmlspecialchars((string) $team['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></span><br>
                                            <span class="muted"><?= htmlspecialchars((string) $team['project_slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars((string) $team['team_code'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <?php if (!empty($team['team_name'])): ?>
                                                <span class="muted"><?= htmlspecialchars((string) $team['team_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= !empty($team['class_group']) ? htmlspecialchars((string) $team['class_group'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Sense classe</span>' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($team['members'])): ?>
                                                <div class="admin-team-members">
                                                    <?php foreach ($team['members'] as $member): ?>
                                                        <div class="admin-team-member">
                                                            <strong><?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                            <span><?= htmlspecialchars((string) $member['project_role_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                            <small><?= htmlspecialchars((string) $member['email'], ENT_QUOTES, 'UTF-8') ?></small>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="muted">Sense membres</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status <?= ((int) $team['is_active'] === 1) ? 'status--active' : 'status--inactive' ?>">
                                                <?= ((int) $team['is_active'] === 1) ? 'Actiu' : 'Inactiu' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="rols" class="card admin-panel admin-panel--roles admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Rols de projecte</h2>
                <div class="admin-actions">
                    <span class="status"><?= count($projectRoleGroups ?? []) ?> rols</span>
                    <button class="collapse-toggle" type="button" data-collapse="rols-content">Mostrar</button>
                </div>
            </div>

            <div id="rols-content" class="admin-collapsible__content">
                <div class="admin-role-filters">
                    <label for="role-filter-select">Mostra rol</label>
                    <select id="role-filter-select" data-role-filter>
                        <option value="all">Tots</option>
                        <?php foreach ($projectRoleGroups as $roleGroup): ?>
                            <option value="<?= htmlspecialchars((string) $roleGroup['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $roleGroup['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($projectRoleGroups ?? [])): ?>
                    <div class="empty-state">
                        <p>Encara no hi ha membres amb rols de projecte.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-role-groups">
                        <?php foreach ($projectRoleGroups as $roleIndex => $roleGroup): ?>
                            <section class="admin-role-group admin-collapsible" data-role-group data-role-name="<?= htmlspecialchars((string) $roleGroup['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="admin-panel__header admin-role-group__header">
                                    <h3><?= htmlspecialchars((string) $roleGroup['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="admin-actions">
                                        <span class="status"><?= count($roleGroup['members']) ?> membres</span>
                                        <button class="collapse-toggle" type="button" data-collapse="role-group-content-<?= (int) $roleIndex ?>">Amagar</button>
                                    </div>
                                </div>
                                <div id="role-group-content-<?= (int) $roleIndex ?>" class="admin-collapsible__content">
                                <div class="admin-table__wrapper">
                                    <table class="admin-table admin-table--compact">
                                        <thead>
                                            <tr>
                                                <th>Nom</th>
                                                <th>Email</th>
                                                <th>Equip</th>
                                                <th>Projecte / curs</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roleGroup['members'] as $member): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $member['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars((string) $member['team_code'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                        <?php if (!empty($member['team_name'])): ?>
                                                            <br><span class="muted"><?= htmlspecialchars((string) $member['team_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars((string) $member['project_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                                        <span class="muted"><?= htmlspecialchars((string) $member['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="avaluacio" class="card admin-panel admin-panel--assessment admin-collapsible is-collapsed">
            <div class="admin-panel__header">
                <h2>Fases i tasques</h2>
                <div class="admin-actions">
                    <span class="status">Estructura d’avaluació</span>
                    <button class="collapse-toggle" type="button" data-collapse="avaluacio-content">Mostrar</button>
                </div>
            </div>
            <div id="avaluacio-content" class="admin-collapsible__content">
            <p>Puja els CSV exportats de les pestanyes <strong>assessment_phases</strong> i <strong>assessment_tasks</strong>. La importació actualitza les plantilles i les assignacions per edició fent servir <code>project_slug</code>, <code>phase_key</code> i <code>source_column</code>.</p>
            <form class="admin-form" method="post" enctype="multipart/form-data" action="<?= url('admin') ?>">
                <input type="hidden" name="action" value="import_assessment_structure">
                <div class="form__grid">
                    <label>
                        assessment_phases.csv
                        <input type="file" name="phases_file" accept=".csv,text/csv" required>
                    </label>
                    <label>
                        assessment_tasks.csv
                        <input type="file" name="tasks_file" accept=".csv,text/csv" required>
                    </label>
                </div>
                <p class="muted">Headers fases: project_slug, phase_key, title, description, section_type, display_order, is_active.</p>
                <p class="muted">Headers tasques: project_slug, phase_key, source_column, title, description, weight_label, role_filter, display_order, is_visible.</p>
                <button class="button" type="submit">Importar estructura</button>
            </form>

            <div class="admin-assessment">
                <div class="admin-panel__header">
                    <h3>Gestionar visibilitat</h3>
                    <span class="status">Projectes configurats</span>
                </div>

                <?php if (empty($assessmentStructure)): ?>
                    <div class="empty-state">
                        <p>Encara no hi ha fases ni tasques configurades.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assessmentStructure as $assessmentProject): ?>
                        <section class="admin-assessment__project">
                            <div class="admin-assessment__project-header">
                                <div>
                                    <h4 class="admin-assessment__project-title"><?= htmlspecialchars((string) $assessmentProject['name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $assessmentProject['academic_year_name'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="admin-assessment__project-slug"><?= htmlspecialchars((string) $assessmentProject['slug'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="status"><?= count($assessmentProject['phases']) ?> fases</span>
                            </div>

                            <?php foreach ($assessmentProject['phases'] as $phase): ?>
                                <article class="admin-assessment__phase<?= ((int) $phase['is_active'] === 1) ? '' : ' admin-assessment__phase--disabled' ?>">
                                    <div class="admin-assessment__phase-header">
                                        <div>
                                            <h5 class="admin-assessment__phase-title"><?= htmlspecialchars((string) $phase['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                                            <p class="admin-assessment__phase-meta">
                                                <?= htmlspecialchars((string) $phase['phase_key'], ENT_QUOTES, 'UTF-8') ?> ·
                                                <?= htmlspecialchars((string) $phase['section_type'], ENT_QUOTES, 'UTF-8') ?> ·
                                                ordre <?= (int) $phase['display_order'] ?>
                                            </p>
                                        </div>
                                        <form method="post" action="<?= url('admin') ?>#avaluacio" class="admin-inline-action">
                                            <input type="hidden" name="action" value="toggle_assessment_phase">
                                            <input type="hidden" name="project_academic_year_phase_id" value="<?= (int) $phase['project_academic_year_phase_id'] ?>">
                                            <button class="button button--secondary" type="submit">
                                                <?= ((int) $phase['is_active'] === 1) ? 'Desactivar fase' : 'Activar fase' ?>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="admin-table__wrapper">
                                        <table class="admin-table admin-table--compact admin-assessment__table">
                                            <thead>
                                                <tr>
                                                    <th>Tasca</th>
                                                    <th>Columna CSV</th>
                                                    <th>Pes</th>
                                                    <th>Rol</th>
                                                    <th>Estat</th>
                                                    <th>Accions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($phase['tasks'])): ?>
                                                    <tr>
                                                        <td colspan="6"><span class="muted">Aquesta fase no té tasques.</span></td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($phase['tasks'] as $task): ?>
                                                         <tr class="<?= ((int) $task['is_visible'] === 1) ? '' : 'admin-assessment__row--muted' ?>">
                                                            <td>
                                                                <strong><?= htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                                                <span class="muted">ordre <?= (int) $task['display_order'] ?></span>
                                                            </td>
                                                            <td><?= htmlspecialchars((string) $task['source_column'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td><?= $task['weight_label'] !== '' ? htmlspecialchars((string) $task['weight_label'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Sense pes</span>' ?></td>
                                                            <td><?= $task['role_filter'] !== '' ? htmlspecialchars((string) $task['role_filter'], ENT_QUOTES, 'UTF-8') : '<span class="muted">Tots</span>' ?></td>
                                                            <td><span class="status"><?= ((int) $task['is_visible'] === 1) ? 'Visible' : 'Amagada' ?></span></td>
                                                            <td>
                                                                 <form method="post" action="<?= url('admin') ?>#avaluacio" class="admin-inline-action">
                                                                    <input type="hidden" name="action" value="toggle_assessment_task">
                                                                    <input type="hidden" name="project_academic_year_phase_task_id" value="<?= (int) $task['project_academic_year_phase_task_id'] ?>">
                                                                    <button class="button button--secondary" type="submit">
                                                                        <?= ((int) $task['is_visible'] === 1) ? 'Amagar' : 'Mostrar' ?>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            </div>
        </div>

        <div id="sincronitzacions" class="card admin-panel">
            <h2>Google Sync</h2>
            <p>El mòdul de sincronització està preparat per gestionar documents i fulls des d’aquest panell.</p>
        </div>

        <div id="logs" class="card admin-panel">
            <h2>Logs i auditoria</h2>
            <p>En properes versions es mostraran aquí les accions d’admin i els errors del sistema.</p>
        </div>

        <a class="admin-back-to-top" href="#panell" aria-label="Tornar a dalt">↑</a>

    </div>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
