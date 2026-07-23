# Entorns de Natura

**Entorns de Natura** és una aplicació web educativa per a projectes de 4t d'ESO.

Aquest document separa el que ja està implementat del que encara és previst.

## Documentació canònica

- `AGENTS.md`: criteris generals, arquitectura, seguretat i normes de treball.
- `database/README.md`: esquema i procediments de base de dades.
- `docs/skills/`: procediments detallats per àrea.
- `database/schema.sql`: autoritat executable per reconstruir una base de dades neta.

Aquest `README.md` és la introducció breu del projecte. Els detalls s'han de mantenir a la font especialitzada corresponent per evitar duplicacions.

## Estat actual

### Implementat

- aplicació PHP modular amb `app/`, `config/`, `public/`, `resources/`, `database/` i `storage/`;
- entrada única des de `public/index.php`;
- router declaratiu propi a `app/Support/Router.php`;
- connexió PDO centralitzada a `config/database.php` i configuració amb `.env`;
- vistes públiques, d'autenticació, d'alumnat, de professorat i d'administració;
- layout comú a `resources/views/layouts/app.php` amb cache-busting per CSS i JS;
- rutes actuals per a portada, projectes, detall de projecte, login, logout i dashboards privats;
- projectes carregats des de base de dades amb traduccions i assets associats;
- fitxa pública de projecte amb selecció d'asset i bloc contextual de notes per alumnat autenticat;
- login bàsic amb sessió, CSRF al login i a accions sensibles d'admin, auditoria bàsica i control de rols;
- analítica de visites a `site_visits` i panell d'administració amb estadístiques;
- capa de Google Workspace amb taules pròpies lligades a `project_academic_years`;
- CSS actiu a `public/assets/css/styles.css` i JavaScript actiu a `public/assets/js/scripts.js`;
- carpeta d'assets real amb logos de projectes, col·laboradors i eines.

### Encara previst

- ampliació de rutes multidioma a `es` i `en`;
- sincronització real amb Google Docs i Google Sheets;
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

El nom canònic de la base de dades local és `entorns_de_natura`. La connexió llegeix el valor efectiu de `DB_NAME` a `.env`.

### Ja present

- `users`, `web_roles`, `user_web_roles`, `project_roles`;
- `academic_years`, `classes`, `class_members`, `class_member_history`, `class_teachers`;
- `projects`, `project_translations`, `project_academic_years`, `project_class_assignments`;
- `project_assets`, `project_asset_links`;
- `documents`, `document_sources`, `document_fragments`, `document_visibility_rules`;
- `project_sections`, `project_section_roles`;
- `assessment_sources`, `assessment_import_runs`, `assessment_records`, `assessment_import_errors`;
- `assessment_phases`, `assessment_tasks`, `project_academic_year_phases`, `project_academic_year_phase_tasks`, `assessment_supports`, `assessment_task_resources`;
- `project_teams`, `project_team_members`, `project_team_member_roles`;
- `google_sources`, `google_documents`, `google_document_blocks`, `google_sheet_rows`, `google_sync_runs`, `google_sync_errors`;
- `settings`;
- `site_visits`.

### Observació

- `site_visits` es garanteix des del servei d'analítica si encara no existeix.
- `database/schema.sql` és l'autoritat executable per reconstruir una base neta; `database/README.md` documenta el procediment.
- `scripts/check-schema-coherence.php` valida camps legacy i relacions mal situades després de canvis d'esquema.
- `scripts/check-code-quality.php` executa les verificacions bàsiques: lint PHP, coherència d'esquema i controladors sense SQL/DDL directe.
- si la base ja existia abans d'aquesta capa, també cal aplicar `database/09_document_tables_fix.sql`.
- per deixar els documents completament lligats a l'edició, també cal aplicar `database/17_documents_project_id_cleanup.sql` si la base ve d'una versió anterior.
- si la base ve d'una versió anterior, `assessment_records.project_id` també s'ha d'eliminar amb `database/18_assessment_records_project_id_cleanup.sql`.
- `project_id` continua sent correcte en relacions de catàleg del projecte base, com `project_translations`, `project_asset_links` i `project_sections`; el que s'elimina és l'ús de `project_id` com a context de document, import o edició.
- `project_team_member_roles` és la font per mostrar, filtrar i comptar múltiples rols de projecte per membre; `project_team_members.project_role_id` queda com a rol principal de compatibilitat.

### Documents

- els documents van lligats a `project_academic_years`, no directament a `projects`;
- la clau funcional recomanada és `project_academic_year_id + slug`;
- `project_id` és correcte per al catàleg base del projecte, però en documents i avaluació es considera herència històrica i s'està eliminant del model.

### Avaluació

- `assessment_phases` i `assessment_tasks` defineixen la plantilla reutilitzable;
- `project_academic_year_phases` activa o desactiva fases per edició de projecte;
- `project_academic_year_phase_tasks` activa o desactiva tasques per edició de projecte;
- així una fase o tasca pot reutilitzar-se en diferents anys sense copiar la definició.
- `assessment_sources` i `assessment_import_runs` treballen per `project_academic_year_id`;
- les notes i imports s'aïllen per edició, no només per projecte;
- `assessment_records` es llegeix a través de `assessment_sources`.
- la vista pública de notes és només per alumnat autenticat;
- les notes de document són internes per defecte i no s'han de mostrar a visitants ni alumnat.

### Regla del model

- `projects` és el catàleg base del projecte;
- `project_academic_years` és la unitat funcional quan una dada depèn del curs concret;
- si una entitat canvia per edició, no s'ha de resoldre només amb `projects`.

### Quan usar cada una

- `projects`: nom, slug, ordre, activació i relacions que són comunes a totes les edicions;
- `project_academic_years`: documents, imports, notes, assignacions, visibilitat i qualsevol dada que pugui variar per curs;
- si tens dubte, pregunta't si la dada canviaria l'any que ve sense canviar el projecte base; si la resposta és sí, usa `project_academic_years`.

### Encara previst

- integració real amb Google Docs i Google Sheets;
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

## Verificació

Abans de tancar canvis de codi, executa:

```text
php scripts/check-code-quality.php
```

També es pot executar amb Composer:

```text
composer check
```

## Full de ruta

1. Consolidar el sistema de rutes.
2. Consolidar Google Docs i Google Sheets.
3. Consolidar rúbriques i notes.
4. Afinar visibilitat per context i permisos.

## Autoria

Oriol Torrents Cabestany
Oriol Rovira Bertran
