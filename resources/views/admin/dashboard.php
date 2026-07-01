<?php
ob_start();
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <strong>Entorns de Natura</strong>
            <span>Administració</span>
        </div>
        <nav class="admin-nav">
            <a class="active" href="#panell">Panell</a>
            <a href="#usuaris">Usuaris</a>
            <a href="#projectes-lista">Projectes</a>
            <a href="#analytics">Analítica</a>
            <a href="#sincronitzacions">Google Sync</a>
            <a href="#logs">Logs</a>
        </nav>
    </aside>

    <div class="admin-content">
        <div id="panell" class="dashboard-header">
            <h1>Dashboard administració</h1>
            <p class="lead">Gestió real d’usuaris, rols i permisos del portal.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="flash-message <?= htmlspecialchars((string) $messageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="card metric-card">
                <div class="metric-icon">👤</div>
                <div>
                    <h2>Usuaris</h2>
                    <p class="stat-value"><?= count($users) ?></p>
                    <p>Usuaris carregats des de la base de dades</p>
                </div>
            </div>
            <div class="card metric-card">
                <div class="metric-icon">🛡️</div>
                <div>
                    <h2>Rols</h2>
                    <p class="stat-value"><?= count($roles) ?></p>
                    <p>Rols disponibles al sistema</p>
                </div>
            </div>
            <div class="card metric-card">
                <div class="metric-icon">✅</div>
                <div>
                    <h2>Actius</h2>
                    <p class="stat-value"><?= count(array_filter($users, fn($user) => (int) $user['is_active'] === 1)) ?></p>
                    <p>Usuaris amb accés actiu</p>
                </div>
            </div>
        </div>

        <div id="analytics" class="card">
            <div class="card-actions">
                <h2>Analítica web completa</h2>
                <span class="status">Visites, dispositius, geografia i classes</span>
            </div>

            <div class="stats-grid">
                <div class="card metric-card">
                    <div class="metric-icon">📈</div>
                    <div>
                        <h2>Visites totals</h2>
                        <p class="stat-value"><?= (int) ($analytics['total_visits'] ?? 0) ?></p>
                        <p>Registres de trànsit des de la instal·lació</p>
                    </div>
                </div>
                <div class="card metric-card">
                    <div class="metric-icon">🧭</div>
                    <div>
                        <h2>Sessions úniques</h2>
                        <p class="stat-value"><?= (int) ($analytics['unique_sessions'] ?? 0) ?></p>
                        <p>Sessions diferents capturades</p>
                    </div>
                </div>
                <div class="card metric-card">
                    <div class="metric-icon">👩‍🏫</div>
                    <div>
                        <h2>Usuaris reconeguts</h2>
                        <p class="stat-value"><?= (int) ($analytics['unique_users'] ?? 0) ?></p>
                        <p>Visites vinculades a usuaris</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-sections">
                <section class="card">
                    <h3>Connexions per classe</h3>
                    <div class="table-wrapper">
                        <table class="data-table">
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

                <section class="card">
                    <h3>Dispositius i SO</h3>
                    <ul>
                        <?php foreach (($analytics['device_stats'] ?? []) as $device): ?>
                            <li><strong><?= htmlspecialchars((string) ($device['device_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>: <?= (int) ($device['total'] ?? 0) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <ul>
                        <?php foreach (($analytics['os_stats'] ?? []) as $os): ?>
                            <li><strong><?= htmlspecialchars((string) ($os['os_family'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>: <?= (int) ($os['total'] ?? 0) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>

            <div class="dashboard-sections">
                <section class="card">
                    <h3>Geografia</h3>
                    <ul>
                        <?php foreach (($analytics['geo_stats'] ?? []) as $geo): ?>
                            <li><strong><?= htmlspecialchars((string) ($geo['country_code'] ?? 'Desconegut'), ENT_QUOTES, 'UTF-8') ?></strong> / <?= htmlspecialchars((string) ($geo['region'] ?? 'Desconegut'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) ($geo['total'] ?? 0) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="card">
                    <h3>Pàgines més vistes</h3>
                    <ul>
                        <?php foreach (($analytics['page_stats'] ?? []) as $page): ?>
                            <li><strong><?= htmlspecialchars((string) ($page['path'] ?? '/'), ENT_QUOTES, 'UTF-8') ?></strong>: <?= (int) ($page['total'] ?? 0) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>

            <section class="card">
                <h3>Visites recents</h3>
                <div class="table-wrapper">
                    <table class="data-table">
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

        <div id="usuaris" class="dashboard-sections">
            <section class="card">
                <h2>Crear usuari</h2>
                <form class="admin-form" method="post" action="<?= url('admin') ?>">
                    <input type="hidden" name="action" value="create_user">
                    <div class="form-grid">
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
                    <label class="checkbox-row">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Usuari actiu
                    </label>
                    <div class="form-group">
                        <label>Assignar rols</label>
                        <div class="checkbox-group">
                            <?php foreach ($roles as $role): ?>
                                <label class="checkbox-pill">
                                    <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>">
                                    <?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="button" type="submit">Crear usuari</button>
                </form>
            </section>

            <section class="card">
                <h2>Rols disponibles</h2>
                <ul>
                    <?php foreach ($roles as $role): ?>
                        <li><strong><?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <div class="card">
            <h2>Importar alumnes CSV</h2>
            <p>Importa fitxers amb columnes com <strong>name</strong>, <strong>surname</strong>, <strong>email</strong>, <strong>password</strong>, <strong>class</strong>, <strong>roles</strong> i <strong>trimester</strong>. La proposta més robusta és tenir una sola base d’usuaris i assignar classe/rols per relació, perquè un alumne pot canviar de grup sense duplicar el registre.</p>
            <form class="admin-form" method="post" enctype="multipart/form-data" action="<?= url('admin') ?>">
                <input type="hidden" name="action" value="import_students">
                <label>
                    Fitxer CSV
                    <input type="file" name="students_file" accept=".csv,text/csv" required>
                </label>
                <button class="button" type="submit">Importar alumnes</button>
            </form>
        </div>

        <div id="projectes-section" class="card">
            <div class="card-actions">
                <h2>Alumnes</h2>
                <span class="status">Informació completa</span>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuari</th>
                            <th>Email</th>
                            <th>Visites</th>
                            <th>Rols</th>
                            <th>Estat</th>
                            <th>Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) ($user['visit_count'] ?? 0) ?></td>
                                <td>
                                    <?php if (!empty($user['roles'])): ?>
                                        <?php foreach ($user['roles'] as $role): ?>
                                            <span class="role-pill"><?= htmlspecialchars((string) $role, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="muted">Sense rols</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status"><?= htmlspecialchars((string) $user['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <div class="action-stack">
                                        <form method="post" action="<?= url('admin') ?>" class="inline-form">
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <button class="button secondary" type="submit">
                                                <?= ((int) $user['is_active'] === 1) ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                        <button class="button" type="button" data-target="student-<?= (int) $user['id'] ?>">Editar</button>
                                    </div>
                                </td>
                            </tr>
                            <tr id="student-<?= (int) $user['id'] ?>" class="student-editor-row">
                                <td colspan="6">
                                    <form class="admin-form compact-form" method="post" action="<?= url('admin') ?>">
                                        <input type="hidden" name="action" value="update_student">
                                        <input type="hidden" name="student_id" value="<?= (int) $user['id'] ?>">
                                        <div class="form-grid">
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
                                        <div class="form-group">
                                            <label>Rols</label>
                                            <div class="checkbox-group">
                                                <?php foreach ($roles as $role): ?>
                                                    <label class="checkbox-pill">
                                                        <input type="checkbox" name="roles[]" value="<?= (int) $role['id'] ?>" <?= in_array((string) $role['name'], $user['roles'], true) ? 'checked' : '' ?>>
                                                        <?= htmlspecialchars((string) $role['name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <label class="checkbox-row">
                                            <input type="checkbox" name="is_active" value="1" <?= ((int) $user['is_active'] === 1) ? 'checked' : '' ?>>
                                            Usuari actiu
                                        </label>
                                        <button class="button" type="submit">Guardar canvis</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="sincronitzacions" class="card">
            <h2>Google Sync</h2>
            <p>El mòdul de sincronització està preparat per gestionar documents i fulls des d’aquest panell.</p>
        </div>

        <div id="logs" class="card">
            <h2>Logs i auditoria</h2>
            <p>En properes versions es mostraran aquí les accions d’admin i els errors del sistema.</p>
        </div>

        <div id="projectes-lista" class="card">
            <div class="card-actions">
                <h2>Projectes</h2>
                <span class="status">Activar o desactivar</span>
            </div>
            <div class="project-list">
                <?php foreach ($projects as $project): ?>
                    <div class="project-item">
                        <div class="project-item-header">
                            <strong><?= htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="status"><?= ((int) ($project['is_active'] ?? 0) === 1) ? 'Actiu' : 'Inactiu' ?></span>
                        </div>
                        <p><?= htmlspecialchars((string) ($project['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <form method="post" action="<?= url('admin') ?>" class="inline-form">
                            <input type="hidden" name="action" value="toggle_project">
                            <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                            <button class="button secondary" type="submit">
                                <?= ((int) ($project['is_active'] ?? 0) === 1) ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/app.php';
