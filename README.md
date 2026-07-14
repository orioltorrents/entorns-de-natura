# Entorns de Natura

**Entorns de Natura** és una aplicació web educativa per a projectes de 4t d'ESO.

Aquest document separa el que ja està implementat del que encara és previst.

## Estat actual

### Implementat

- aplicació PHP modular amb `app/`, `config/`, `public/`, `resources/`, `database/` i `storage/`;
- entrada única des de `public/index.php`;
- connexió PDO centralitzada a `config/database.php` i configuració amb `.env`;
- vistes públiques, d'autenticació, d'alumnat, de professorat i d'administració;
- layout comú a `resources/views/layouts/app.php` amb cache-busting per CSS i JS;
- rutes actuals per a portada, projectes, detall de projecte, login, logout i dashboards privats;
- projectes carregats des de base de dades amb traduccions i assets associats;
- fitxa pública de projecte amb selecció d'asset i bloc contextual de notes per alumnat autenticat;
- login bàsic amb sessió, CSRF i control de rols;
- analítica de visites a `site_visits` i panell d'administració amb estadístiques;
- CSS actiu a `public/assets/css/styles.css` i JavaScript actiu a `public/assets/js/scripts.js`;
- carpeta d'assets real amb logos de projectes, col·laboradors i eines.

### Encara previst

- sistema de rutes més formal i escalable que el `switch` actual;
- ampliació de rutes multidioma a `es` i `en`;
- sincronització real amb Google Docs i Google Sheets;
- taules específiques per a fonts de Google i sincronitzacions;
- rúbriques, notes completes i observacions d'aula;
- refinament del model de visibilitat per context d'accés;
- possible retirada definitiva de fitxers temporals o històrics, com `public/test-db.php`.

## Distinció

- `implementat` vol dir que hi ha codi o estructura real al repositori i es pot executar ara.
- `previst` vol dir que està definit com a criteri, documentació o roadmap, però encara no és una part completa del producte.

## Arquitectura

- `public/index.php` rep les peticions.
- `app/` conté controladors, models, serveis i helpers.
- `resources/views/` conté les plantilles.
- `database/` conté l'esquema i les migracions.
- `public/assets/` conté CSS, JS i imatges públiques.

## Base de dades

### Ja present

- `users`, `roles`, `user_roles`;
- `academic_years`, `classes`, `class_members`, `class_teachers`;
- `projects`, `project_translations`, `project_groups`;
- `project_assets`, `project_asset_links`;
- `documents`, `document_sources`, `document_fragments`, `document_visibility_rules`;
- `project_sections`, `project_section_roles`;
- `assessment_sources`, `assessment_import_runs`, `assessment_records`, `assessment_import_errors`;
- `assessment_phases`, `assessment_tasks`, `assessment_supports`, `assessment_task_resources`;
- `settings`;
- `site_visits`.

### Observació

- `site_visits` es garanteix des del servei d'analítica si encara no existeix.
- `database/schema.sql` és el mestre de reconstrucció i apunta a les parts actuals de l'esquema.
- si la base ja existia abans d'aquesta capa, també cal aplicar `database/09_document_tables_fix.sql`.

### Encara previst

- taules de Google Workspace més completes;
- estructures de rúbriques i notes definitives;
- ampliacions de visibilitat per context si calen més endavant.

## Rutes actuals

```text
/
/ca
/projectes
/ca/projectes
/es/projectes
/en/projectes
/ca/projectes/{slug}
/login
/logout
/dashboard
/alumne
/professor
/admin
```

## Vistes actuals

- `resources/views/public/home.php`
- `resources/views/public/projects.php`
- `resources/views/public/project-detail.php`
- `resources/views/auth/login.php`
- `resources/views/students/dashboard.php`
- `resources/views/teachers/dashboard.php`
- `resources/views/admin/dashboard.php`

## Projectes

Projectes ja definits:

```text
projecte-rius
mat-penedes
agroparc
projecte-orenetes
liquencity
vespa-velutina
```

Les fitxes públiques es generen amb una plantilla genèrica i es complementen amb assets del catàleg.

## Seguretat

- `.env` no s'ha de publicar;
- les contrasenyes es gestionen amb `password_hash()` i `password_verify()`;
- les zones privades depenen de sessió i rol;
- la sortida HTML s'ha d'escapar;
- `public/test-db.php` s'ha de tractar com a fitxer temporal o de prova.

## UI

- el CSS actiu és `public/assets/css/styles.css`;
- el layout fa cache-busting de CSS i JS;
- les vistes noves han de seguir classes clares i coherents.

## Tecnologies

- PHP
- HTML
- CSS
- JavaScript
- MySQL / MariaDB
- PDO
- Apache amb XAMPP

## Com començar

1. Configura `.env`.
2. Engega Apache i MySQL a XAMPP.
3. Obre `http://localhost/entorns-de-natura/public/`.
4. Si no veus canvis de CSS, fes un refresh fort.

## Full de ruta

1. Consolidar el sistema de rutes.
2. Consolidar Google Docs i Google Sheets.
3. Consolidar rúbriques i notes.
4. Afinar visibilitat per context i permisos.

## Autoria

Oriol Torrents Cabestany
Oriol Rovira Bertran
