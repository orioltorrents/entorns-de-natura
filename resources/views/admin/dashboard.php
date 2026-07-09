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
            <a href="#analytics">Analítica</a>
            <a href="#usuaris">Usuaris</a>
            <a href="#projectes-lista">Projectes</a>
            <a href="#avaluacio">Fases i tasques</a>
            <a href="#sincronitzacions">Google Sync</a>
            <a href="#logs">Logs</a>
        </nav>
    </aside>

    <div class="admin-layout__content">
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
                    <span class="admin-summary__label">Rols</span>
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

        <div id="analytics" class="card admin-panel">
            <div class="admin-panel__header">
                <h2>Analítica web completa</h2>
                <span class="status">Visites, dispositius, geografia i classes</span>
            </div>

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
                    <h3>Dispositius i SO</h3>
                    <?php
                    $maxDevice = max(array_column($analytics['device_stats'] ?? [['total' => 0]], 'total'));
                    $maxOS = max(array_column($analytics['os_stats'] ?? [['total' => 0]], 'total'));
                    ?>
                    <div class="bar-chart-group">
                        <div>
                            <h4>Dispositius</h4>
                            <div class="bar-chart">
                                <?php foreach (($analytics['device_stats'] ?? []) as $device): ?>
                                    <div class="bar-row">
                                        <span class="bar-label"><?= htmlspecialchars(ucfirst((string) ($device['device_type'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="bar-track"><div class="bar-fill" style="width:<?= $maxDevice > 0 ? round(((int) $device['total'] / $maxDevice) * 100) : 0 ?>%"></div></div>
                                        <span class="bar-value"><?= (int) ($device['total'] ?? 0) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <h4>Sistemes operatius</h4>
                            <div class="bar-chart">
                                <?php foreach (($analytics['os_stats'] ?? []) as $os): ?>
                                    <div class="bar-row">
                                        <span class="bar-label"><?= htmlspecialchars(ucfirst((string) ($os['os_family'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="bar-track"><div class="bar-fill bar-fill-os" style="width:<?= $maxOS > 0 ? round(((int) $os['total'] / $maxOS) * 100) : 0 ?>%"></div></div>
                                        <span class="bar-value"><?= (int) ($os['total'] ?? 0) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
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

        <div id="usuaris" class="admin-panels">
            <section class="card admin-panel admin-panel--users">
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

        </div>

        <div class="card admin-panel admin-panel--import">
            <h2>Importar alumnes CSV</h2>
            <p>Importa fitxers amb columnes com <strong>name</strong>, <strong>surname</strong>, <strong>email</strong>, <strong>password</strong>, <strong>class</strong>, <strong>roles</strong> i <strong>trimester</strong>. La proposta més robusta és tenir una sola base d’usuaris i assignar classe/rols per relació, perquè un alumne pot canviar de grup sense duplicar el registre.</p>
            <form class="admin-form admin-form--import" method="post" enctype="multipart/form-data" action="<?= url('admin') ?>">
                <input type="hidden" name="action" value="import_students">
                <label>
                    Fitxer CSV
                    <input type="file" name="students_file" accept=".csv,text/csv" required>
                </label>
                <button class="button" type="submit">Importar alumnes</button>
            </form>
        </div>

        <?php
        $classGroups = [];
        foreach ($users as $u) {
            $cg = !empty($u['class_group']) ? $u['class_group'] : 'Sense classe';
            $classGroups[$cg] = true;
        }
        ksort($classGroups);
        ?>
        <div id="projectes-section" class="card admin-panel admin-panel--students admin-collapsible">
            <div class="admin-panel__header">
                <h2>Alumnes</h2>
                <span class="status" id="alumnes-count"><?= count($users) ?> usuaris</span>
                <button class="collapse-toggle" type="button" data-collapse="projectes-table-wrap">Amagar</button>
            </div>
            <div class="admin-filters" id="alumnes-filter" data-filter-table="alumnes-table">
                <span class="admin-filters__label">Classe:</span>
                <button class="admin-filters__chip is-active" type="button" data-value="all">Totes</button>
                <?php foreach ($classGroups as $group => $_): ?>
                    <button class="admin-filters__chip" type="button" data-value="<?= htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $group, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
            </div>
            <div id="projectes-table-wrap" class="admin-collapsible__content">
                <div class="admin-table__wrapper">
                    <table class="admin-table" id="alumnes-table" data-sortable-table>
                        <thead>
                            <tr>
                                <th scope="col" data-sort-type="text">Usuari</th>
                                <th scope="col" data-sort-type="text">Email</th>
                                <th scope="col" data-sort-type="text" class="th-class">Classe</th>
                                <th scope="col" data-sort-type="text">Projecte</th>
                                <th scope="col" data-sort-type="text">Equip / grup</th>
                                <th scope="col" data-sort-type="text">iNaturalist</th>
                                <th scope="col" data-sort-type="number">Visites</th>
                                <th scope="col" data-sort-type="text">Rols</th>
                                <th scope="col" data-sort-type="text">Estat</th>
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
                                    <?php if (!empty($user['project'])): ?>
                                        <?= htmlspecialchars((string) $user['project'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="muted">Sense projecte</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                        <div class="admin-table__meta">
                                        <?php if (!empty($user['team_number'])): ?>
                                            <span class="admin-table__meta-item">Equip <?= htmlspecialchars((string) $user['team_number'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($user['group_number'])): ?>
                                            <span class="admin-table__meta-item">Grup <?= htmlspecialchars((string) $user['group_number'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($user['group_code_1t'])): ?>
                                            <span class="admin-table__meta-item"><?= htmlspecialchars((string) $user['group_code_1t'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <?php if (empty($user['team_number']) && empty($user['group_number']) && empty($user['group_code_1t'])): ?>
                                            <span class="admin-table__meta-item muted">Sense grup</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($user['inaturalist_user_login'])): ?>
                                        <?= htmlspecialchars((string) $user['inaturalist_user_login'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        <span class="muted">No informat</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) ($user['visit_count'] ?? 0) ?></td>
                                <td>
                                    <?php if (!empty($user['roles'])): ?>
                                        <?php foreach ($user['roles'] as $role): ?>
                                            <span class="admin-table__tag"><?= htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="muted">Sense rols</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status"><?= htmlspecialchars((string) $user['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
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
                                <td colspan="10">
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
                                            <label>Rol acadèmic<input type="text" name="academic_role" value="<?= htmlspecialchars((string) ($user['academic_role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Gènere<input type="text" name="gender" value="<?= htmlspecialchars((string) ($user['gender'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Article<input type="text" name="article" value="<?= htmlspecialchars((string) ($user['article'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Inaturalist<input type="text" name="inaturalist_user_login" value="<?= htmlspecialchars((string) ($user['inaturalist_user_login'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Projecte<input type="text" name="project" value="<?= htmlspecialchars((string) ($user['project'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Equip<input type="number" name="team_number" value="<?= htmlspecialchars((string) ($user['team_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Grup<input type="number" name="group_number" value="<?= htmlspecialchars((string) ($user['group_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Codi grup 1T<input type="text" name="group_code_1t" value="<?= htmlspecialchars((string) ($user['group_code_1t'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Membres<input type="number" name="members_count" value="<?= htmlspecialchars((string) ($user['members_count'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>External ID<input type="text" name="external_id" value="<?= htmlspecialchars((string) ($user['external_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
                                            <label>Trimestre<input type="text" name="trimester" value="<?= htmlspecialchars((string) ($user['trimester'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
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
        </div>

        <?php
        $projectAssignmentsByProject = [];
        foreach ($projectAssignments as $assignment) {
            $projectAssignmentsByProject[(int) $assignment['project_id']][] = $assignment;
        }

        $projectLogoMap = [
            'projecte-rius' => 'Logotip_AssociacioHabitats.png',
            'mat-penedes' => 'adf-agrupacio-defensa-forestal.png',
            'agroparc' => 'Ajuntament-SantSadurni.png',
            'projecte-orenetes' => 'Logotip_ICO.png',
            'liquencity' => 'CREAF_logo_A4.png',
            'vespa-velutina' => 'Logotip_EXOCAT.png',
        ];
        ?>
        <div id="projectes-lista" class="card admin-panel admin-panel--projects admin-collapsible">
            <div class="admin-panel__header">
                <h2>Projectes</h2>
                <div class="admin-actions">
                    <span class="status">Ordre, estat i assignacions</span>
                    <button class="collapse-toggle" type="button" data-collapse="projectes-content">Amagar</button>
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
                            $projectLogo = $projectLogoMap[$projectSlug] ?? 'entorns_Sense-fons-quadrat-215px.png';
                        ?>
                        <article class="project-admin-card">
                            <div class="project-admin-card__header">
                                <div class="project-admin-card__logo">
                                    <img src="<?= url('assets/logos/' . $projectLogo) ?>" alt="" loading="lazy">
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

        <div id="avaluacio" class="card admin-panel admin-panel--assessment admin-collapsible">
            <div class="admin-panel__header">
                <h2>Fases i tasques</h2>
                <div class="admin-actions">
                    <span class="status">Estructura d’avaluació</span>
                    <button class="collapse-toggle" type="button" data-collapse="avaluacio-content">Amagar</button>
                </div>
            </div>
            <div id="avaluacio-content" class="admin-collapsible__content">
            <p>Puja els CSV exportats de les pestanyes <strong>assessment_phases</strong> i <strong>assessment_tasks</strong>. La importació actualitza fases i tasques existents fent servir <code>project_slug</code>, <code>phase_key</code> i <code>source_column</code>.</p>
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
                                    <h4 class="admin-assessment__project-title"><?= htmlspecialchars((string) $assessmentProject['name'], ENT_QUOTES, 'UTF-8') ?></h4>
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
                                            <input type="hidden" name="phase_id" value="<?= (int) $phase['id'] ?>">
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
                                                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
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

    </div>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
