# Database

Aquesta carpeta conté l'esquema, les migracions i les peces de reconstrucció de la base de dades del projecte **Entorns de Natura**.

## Base de dades local

Nom local habitual:

```text
entorns_de_natura
```

En alguns entorns de desenvolupament també es fa servir:

```text
entorns_natura_dev
```

La connexió es gestiona des de `config/database.php` llegint `.env`.

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
- Si una dada pot canviar l'any següent sense canviar el projecte base, ha d'anar a `project_academic_years`.
- `project_id` és correcte per a relacions de catàleg base.
- `project_id` no s'ha d'usar com a context de document, import o avaluació quan el context real és l'edició.

## Ordre de càrrega

Per reconstruir una base de dades neta, l'ordre recomanat és:

```text
database/schema.sql
  -> 02_education_tables.sql
  -> 03_assessment_tables.sql
  -> 04_assessment_structure_tables.sql
  -> 06_project_assets.sql
  -> 07_task_resources.sql
  -> 08_document_tables.sql
  -> 10_project_sections.sql
  -> 11_roles_split.sql
  -> 12_project_class_assignments.sql
  -> 13_project_academic_years.sql
  -> 14_project_class_assignments_project_year_link.sql
  -> 15_documents_project_year_link.sql
  -> 16_project_class_assignments_cleanup.sql
  -> 17_documents_project_id_cleanup.sql
  -> 18_assessment_records_project_id_cleanup.sql
  -> 19_documents_composite_read_index.sql
  -> 20_assessment_records_user_source_index.sql
  -> 21_documents_composite_read_index_title.sql
  -> 22_document_indexes_cleanup.sql
  -> 23_project_sections_indexes_cleanup.sql
  -> 24_assessment_project_year_phases.sql
  -> 25_assessment_project_year_phase_tasks.sql
  -> 26_assessment_sources_project_year_link.sql
  -> 27_assessment_sources_project_id_cleanup.sql
  -> 28_assessment_index_cleanup.sql
  -> 29_google_workspace_tables.sql
  -> 30_classes_column_rename.sql
  -> 31_student_profiles_external_id_cleanup.sql
   -> 32_project_teams.sql
   -> 33_users_academic_role_cleanup.sql
   -> 34_student_profiles_cleanup.sql
   -> 35_class_member_history.sql
```

`05_project_display_order.sql` es manté com a canvi no destructiu per a bases ja creades. En una reconstrucció neta no cal, perquè `display_order` ja ve definit a la base.

## Taules principals

### Educació i usuaris

- `users`
- `roles`
- `user_roles`
- `academic_years`
- `classes`
- `class_members`
- `class_member_history`
- `class_teachers`

### Projectes

- `projects`
- `project_translations`
- `project_groups`
- `project_academic_years`
- `project_class_assignments`

### Equips de projecte

- `project_teams`
- `project_team_members`

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

### Avaluació

- `assessment_sources`
- `assessment_import_runs`
- `assessment_records`
- `assessment_import_errors`
- `assessment_phases`
- `assessment_tasks`
- `project_academic_year_phases`
- `project_academic_year_phase_tasks`

### Google Workspace

- `google_sources`
- `synced_documents`
- `synced_sheet_rows`
- `google_sync_runs`
- `google_sync_errors`

### Altres

- `settings`
- `site_visits`

## Relacions importants

- `documents` van lligats a `project_academic_year_id`.
- `assessment_sources` i `assessment_import_runs` van lligats a `project_academic_year_id`.
- `assessment_records` es llegeix a través de `assessment_sources`.
- `assessment_phases` i `assessment_tasks` són definició base.
- `project_academic_year_phases` i `project_academic_year_phase_tasks` controlen visibilitat i ordre per curs.
- Google Workspace també treballa amb `project_academic_year_id`.

## Validació

Després de canvis d'esquema, cal revisar:

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
