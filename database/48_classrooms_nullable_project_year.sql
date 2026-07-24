-- --------------------------------------------------------
-- Entorns de Natura - Classroom sense projecte directe obligatori
-- --------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE classrooms
    MODIFY COLUMN project_academic_year_id BIGINT UNSIGNED NULL;
