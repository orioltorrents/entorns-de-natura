-- --------------------------------------------------------
-- Entorns de Natura - Schema mestre
-- Executar des de l'arrel del repositori per recrear una BD completa.
--
-- Nota:
-- - `02_education_tables.sql` conté l'estructura base i les taules mare.
-- - `03_assessment_tables.sql` afegeix la capa d'importació d'avaluació.
-- - `04_assessment_structure_tables.sql` afegeix fases i tasques visibles.
-- - `06_project_assets.sql` afegeix assets, logos, apps i softwares reutilitzables.
-- - `07_task_resources.sql` afegeix bastides, ajudes i recursos per a tasques.
-- - `08_document_tables.sql` afegeix documents, fragments i visibilitat.
-- - `10_project_sections.sql` afegeix seccions de projecte i permisos per rol.
-- - `05_project_display_order.sql` és una migració no destructiva per a BD existents;
--   no cal executar-la en una reconstrucció neta perquè l'ordre ja queda definit a la base.
-- --------------------------------------------------------

SOURCE database/02_education_tables.sql
SOURCE database/03_assessment_tables.sql
SOURCE database/04_assessment_structure_tables.sql
SOURCE database/06_project_assets.sql
SOURCE database/07_task_resources.sql
SOURCE database/08_document_tables.sql
SOURCE database/10_project_sections.sql
