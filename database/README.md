# Database

Aquesta carpeta conté l'esquema, les migracions i les peces de reconstrucció de la base de dades del projecte **Entorns de Natura**.

## Responsabilitat d'aquest document

Aquest fitxer és la font canònica per descriure l'esquema i els procediments de base de dades. `database/schema.sql` és l'autoritat executable per reconstruir una base neta; si una llista documental no coincideix amb els seus `SOURCE`, preval `database/schema.sql`.

## Base de dades local

Nom canònic:

```text
entorns_de_natura
```

La connexió es gestiona des de `config/database.php` i llegeix `DB_NAME` des de `.env`. El codi no ha de fixar aquest nom directament.

## Requisits

- Motor: MariaDB 10.4.28 (entorn local actual)
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- PHP: 8.2.4

## Objectiu

La base de dades gestiona principalment:

- usuaris, rols i permisos;
- classes i alumnat;
- projectes educatius;
- assignacions de projectes a classes i cursos;
- documents i materials;
- avaluació, notes i imports;
- recursos i ajudes per a tasques;
- integració amb Google Docs i Google Sheets;
- configuració i visites del web.

## Regla de model

- `projects` és el catàleg base del projecte.
- `project_academic_years` és la unitat funcional quan les dades depenen del curs o de l'edició.
- `project_academic_years.status` controla l'estat global de l'edició: `pendent`, `actiu`, `realitzat` o `arxivat`.
- `project_class_assignments.status` controla l'estat d'aquella edició per classe: `pendent`, `actiu` o `realitzat`.
- Si una dada pot canviar l'any següent sense canviar el projecte base, ha d'anar a `project_academic_years`.
- `project_id` és correcte per a relacions de catàleg base.
- `project_id` no s'ha d'usar com a context de document, import o avaluació quan el context real és l'edició.

## Reconstrucció neta

Per crear una base de dades buida, cal seleccionar `entorns_de_natura` i executar `database/schema.sql` des de l'arrel del repositori. No s'han d'executar després, una per una, totes les migracions històriques: els fitxers base ja incorporen aquests canvis.

La seqüència canònica és exactament la definida pels `SOURCE` de `database/schema.sql`. Aquest README no la duplica perquè el fitxer executable és l'autoritat.

## Bases de dades existents

Les migracions numerades que no formen part dels `SOURCE` de `database/schema.sql` són ajustos incrementals per portar bases antigues a l'estat actual. S'han d'aplicar només quan la base parteix de l'estat anterior corresponent, amb còpia de seguretat i revisant abans cada script.

Canvis històrics ja absorbits pels fitxers de reconstrucció neta:

- `05_project_display_order.sql`: `projects.display_order` ja forma part de la base;
- `09_document_tables_fix.sql`: les definicions actuals de documents ja incorporen l'ajust;
- `14` a `23`: els enllaços per edició, neteges i índexs ja estan consolidats;
- `26` a `28`: les fonts d'avaluació i els seus índexs ja treballen amb l'edició;
- `30` i `31`: `classes` i `student_profiles` ja tenen les columnes actuals;
- `33` a `35`: la neteja de rols acadèmics, perfils i historial de classe ja és a l'esquema base.

Canvis incrementals recents per a bases existents:

- `40_users_password_change_required.sql`: afegeix `must_change_password` i `password_changed_at` a `users` per forçar el canvi de contrasenya inicial.
- `41_google_workspace_table_rename.sql`: renombra `synced_documents` i `synced_sheet_rows` a `google_documents` i `google_sheet_rows`, i afegeix `google_document_blocks`.
- `42_project_academic_year_statuses.sql`: normalitza els estats d'edició de projecte a `pendent`, `actiu`, `realitzat` i `arxivat`.
- `43_site_pages.sql`: afegeix pàgines públiques globals sincronitzables des de Google Docs, sense dependre d'una edició de projecte.
- `44_classrooms.sql`: afegeix Classrooms vinculats a una edició concreta de projecte.
- `45_classroom_members.sql`: afegeix l'assignació d'alumnat a Classrooms.
- `46_assessment_task_classroom_links.sql`: afegeix les URLs de lliurament de tasques per Classroom.

No s'ha d'inferir que totes les migracions incrementals s'han d'executar en qualsevol base existent. Cal identificar-ne la versió o inspeccionar-ne l'estructura abans d'aplicar-les.

## Taules principals

### Educació i usuaris

- `users`
- `student_profiles`
- `web_roles`
- `user_web_roles`
- `project_roles`
- `languages`
- `academic_years`
- `classes`
- `class_members`
- `class_member_history`
- `class_teachers`

### Projectes

- `projects`
- `project_translations`
- `project_academic_years`
- `project_class_assignments`

`project_groups` és el nom legacy que `12_project_class_assignments.sql` pot migrar. No forma part del model final d'una reconstrucció neta.

`ratpenats` és un projecte històric del curs `2023-2024`, assignat a `23-24_4ESOA` amb estat `realitzat`.

### Equips de projecte

- `project_teams`
- `project_team_members`
- `project_team_member_roles`

`project_team_members.class_id` guarda la classe contextual de l'alumne dins d'aquella edició de projecte. No s'ha d'inferir només de `class_members`, perquè aquesta taula representa la classe actual, ni de `project_teams.class_group`, perquè un equip pot barrejar alumnat de més d'una classe.

`project_team_member_roles` és la font principal dels rols de projecte d'una pertinença. Permet que un membre tingui més d'un rol real, per exemple `científic/a` i `cartògraf/a`. `project_team_members.project_role_id` es manté com a rol principal de compatibilitat i no s'ha d'utilitzar com a única font quan cal mostrar, filtrar o importar rols múltiples.

### Assets i recursos

- `project_assets`
- `project_asset_links`
- `assessment_supports`
- `assessment_task_resources`

### Documents

- `documents`
- `document_sources`
- `document_fragments`
- `document_visibility_rules`

### Seccions i permisos

- `project_sections`
- `project_section_roles`

### Avaluació

- `assessment_sources`
- `assessment_import_runs`
- `assessment_records`
- `assessment_import_errors`
- `assessment_phases`
- `assessment_tasks`
- `project_academic_year_phases`
- `project_academic_year_phase_tasks`
- `classrooms`
- `classroom_members`
- `assessment_task_classroom_links`

### Google Workspace

- `google_sources`
- `google_documents`
- `google_document_blocks`
- `google_sheet_rows`
- `google_sync_runs`
- `google_sync_errors`

### Altres

- `settings`
- `site_pages`
- `site_visits`

## Relacions importants

- `documents` van lligats a `project_academic_year_id`.
- `assessment_sources` i `assessment_import_runs` van lligats a `project_academic_year_id`.
- `assessment_records` es llegeix a través de `assessment_sources`.
- `assessment_phases` i `assessment_tasks` són definició base.
- `project_academic_year_phases` i `project_academic_year_phase_tasks` controlen visibilitat i ordre per curs.
- `classrooms` descriu els Google Classrooms d'una edició concreta de projecte; les URLs de tasques concretes aniran en una relació posterior entre tasca d'edició i Classroom.
- `classroom_members` relaciona usuaris existents amb un Classroom concret i conserva dades d'auditoria provinents de Google Classroom.
- `assessment_task_classroom_links` relaciona una tasca d'una edició amb un Classroom i guarda la URL concreta de lliurament.
- Google Workspace també treballa amb `project_academic_year_id`.
- `site_pages` guarda contingut global del web que no depèn d'una edició acadèmica, com `/ca/que-es-entorns`.
- Les taules `google_*` són capa d'origen i sincronització; no substitueixen les taules internes `documents_*`.
- `documents`, `document_sources`, `document_fragments` i `document_visibility_rules` continuen sent la capa publicable que l'aplicació pot mostrar i filtrar segons permisos.
- El contingut Google s'ha de validar, sanititzar i transformar cap a les taules finals quan calgui abans de publicar-lo.

## Validació

Després de qualsevol canvi d'esquema, és obligatori executar:

```text
scripts/check-schema-coherence.php
```

Aquest script ajuda a detectar:

- camps legacy;
- relacions mal situades;
- índexs o uniques que haurien de seguir existint.

## Notes per a DBeaver

Quan exploris l'esquema:

- comença per `projects` i `project_academic_years`;
- després mira `documents`, `assessment_sources` i `assessment_import_runs`;
- separa sempre el catàleg base del context per curs;
- no confonguis `project_id` amb `project_academic_year_id`;
- si una relació descriu el projecte en general, `project_id` és correcte;
- si descriu una edició concreta, usa `project_academic_year_id`.

## Resum curt

Aquesta base de dades està pensada per gestionar una plataforma educativa modular amb dades per projecte base i dades per curs/edició, amb documents, avaluació i sincronització amb Google Workspace.
