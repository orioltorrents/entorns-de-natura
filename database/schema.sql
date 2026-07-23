-- --------------------------------------------------------
-- Entorns de Natura - Schema mestre
-- Executar des de l'arrel del repositori per recrear una BD completa.
-- Aquest fitxer és l'autoritat executable per a una reconstrucció neta.
-- El nom de la base de dades no es fixa aquí: s'ha de seleccionar o proporcionar
-- externament segons DB_NAME, amb `entorns_de_natura` com a valor local canònic.
--
-- Nota:
-- - `02_education_tables.sql` conté l'estructura base i les taules mare.
-- - `03_assessment_tables.sql` afegeix la capa d'importació d'avaluació.
-- - `04_assessment_structure_tables.sql` afegeix fases i tasques visibles.
-- - `24_assessment_project_year_phases.sql` vincula fases a edicions de projecte.
-- - `25_assessment_project_year_phase_tasks.sql` vincula tasques a fases per edició.
-- - `28_assessment_index_cleanup.sql` neteja índexs massa genèrics de la capa d'avaluació.
-- - `29_google_workspace_tables.sql` afegeix la capa de Google Workspace lligada a `project_academic_years`.
-- - `06_project_assets.sql` afegeix assets, logos, apps i softwares reutilitzables.
-- - `07_task_resources.sql` afegeix bastides, ajudes i recursos per a tasques.
-- - `08_document_tables.sql` afegeix documents, fragments i visibilitat.
-- - `10_project_sections.sql` afegeix seccions de projecte i permisos per rol.
-- - `11_roles_split.sql` separa rols web i rols acadèmics i migra dades existents.
-- - `12_project_class_assignments.sql` renombra project_groups a project_class_assignments.
-- - `13_project_academic_years.sql` vincula projectes amb cursos acadèmics.
-- - `14_project_class_assignments_project_year_link.sql` enllaça assignacions amb project_academic_years.
-- - `15_documents_project_year_link.sql` vincula documents amb project_academic_years.
-- - `16_project_class_assignments_cleanup.sql` elimina columnes antigues sobreres.
-- - `17_documents_project_id_cleanup.sql` elimina la columna redundant de documents.
-- - `18_assessment_records_project_id_cleanup.sql` elimina la columna redundant de assessment_records.
-- - `19_documents_composite_read_index.sql` afina l'indexació de lectura de documents.
-- - `20_assessment_records_user_source_index.sql` afina l'indexació de lectura d'avaluacions.
-- - `21_documents_composite_read_index_title.sql` completa l'index de lectura de documents.
-- - `22_document_indexes_cleanup.sql` neteja índexs redundants de documents i visibilitat.
-- - `23_project_sections_indexes_cleanup.sql` neteja índexs redundants de seccions.
-- - `05_project_display_order.sql` és una migració no destructiva per a BD existents;
--   no cal executar-la en una reconstrucció neta perquè l'ordre ja queda definit a la base.
-- - `26_assessment_sources_project_year_link.sql` enllaça les fonts d'avaluació amb project_academic_years.
-- - `27_assessment_sources_project_id_cleanup.sql` elimina el camp legacy project_id de l'avaluació.
-- - `30_classes_column_rename.sql` renombra els camps de `classes` a `class_name` i `class_code`.
-- - `31_student_profiles_external_id_cleanup.sql` elimina el camp redundant `external_id` de `student_profiles`.
-- - `32_project_teams.sql` afegeix equips i membres d'equip per projecte/curs.
-- - `39_project_team_member_roles.sql` afegeix múltiples rols per membre d'equip.
-- - `33_users_academic_role_cleanup.sql` elimina el camp legacy `academic_role` de `users`.
-- - `34_student_profiles_cleanup.sql` redueix `student_profiles` al nucli mínim.
-- - `35_class_member_history.sql` afegeix l'historial de canvis de classe i reforça la unicitat actual.
-- - `38_ratpenats_project.sql` afegeix el projecte Ratpenats del curs 2023-2024.
-- - `40_users_password_change_required.sql` és una migració no destructiva per a BD existents;
--   no cal executar-la en una reconstrucció neta perquè users ja inclou aquests camps.
-- - `41_google_workspace_table_rename.sql` és una migració no destructiva per a BD existents;
--   no cal executar-la en una reconstrucció neta perquè Google Workspace ja usa noms homogenis.
-- - `42_project_academic_year_statuses.sql` és una migració no destructiva per a BD existents;
--   en reconstrucció neta els valors nous ja són els esperats.
-- - `43_site_pages.sql` afegeix pàgines públiques globals sincronitzables des de Google Docs.
-- --------------------------------------------------------

SOURCE database/02_education_tables.sql
SOURCE database/13_project_academic_years.sql
SOURCE database/12_project_class_assignments.sql
SOURCE database/03_assessment_tables.sql
SOURCE database/04_assessment_structure_tables.sql
SOURCE database/06_project_assets.sql
SOURCE database/07_task_resources.sql
SOURCE database/08_document_tables.sql
SOURCE database/11_roles_split.sql
SOURCE database/10_project_sections.sql
SOURCE database/24_assessment_project_year_phases.sql
SOURCE database/25_assessment_project_year_phase_tasks.sql
SOURCE database/29_google_workspace_tables.sql
SOURCE database/43_site_pages.sql
SOURCE database/32_project_teams.sql
SOURCE database/39_project_team_member_roles.sql
SOURCE database/38_ratpenats_project.sql
