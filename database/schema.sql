-- --------------------------------------------------------
-- Entorns de Natura - Schema mestre
-- Executar des de l'arrel del repositori per recrear una BD completa.
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
-- --------------------------------------------------------

SOURCE database/02_education_tables.sql
SOURCE database/03_assessment_tables.sql
SOURCE database/04_assessment_structure_tables.sql
SOURCE database/06_project_assets.sql
SOURCE database/07_task_resources.sql
SOURCE database/08_document_tables.sql
SOURCE database/10_project_sections.sql
SOURCE database/11_roles_split.sql
SOURCE database/12_project_class_assignments.sql
SOURCE database/13_project_academic_years.sql
SOURCE database/14_project_class_assignments_project_year_link.sql
SOURCE database/15_documents_project_year_link.sql
SOURCE database/16_project_class_assignments_cleanup.sql
SOURCE database/17_documents_project_id_cleanup.sql
SOURCE database/18_assessment_records_project_id_cleanup.sql
SOURCE database/19_documents_composite_read_index.sql
SOURCE database/20_assessment_records_user_source_index.sql
SOURCE database/21_documents_composite_read_index_title.sql
SOURCE database/22_document_indexes_cleanup.sql
SOURCE database/23_project_sections_indexes_cleanup.sql
SOURCE database/24_assessment_project_year_phases.sql
SOURCE database/25_assessment_project_year_phase_tasks.sql
SOURCE database/26_assessment_sources_project_year_link.sql
SOURCE database/27_assessment_sources_project_id_cleanup.sql
SOURCE database/28_assessment_index_cleanup.sql
SOURCE database/29_google_workspace_tables.sql
