-- --------------------------------------------------------
-- Entorns de Natura - Neteja d'indices massa genèrics a l'avaluacio
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND INDEX_NAME = 'idx_assessment_sources_project_academic_year_id'
);
SET @sql := IF(@idx_exists > 0,
    'ALTER TABLE assessment_sources DROP INDEX idx_assessment_sources_project_academic_year_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @new_idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND INDEX_NAME = 'idx_assessment_import_runs_project_academic_year_started'
);
SET @sql := IF(@new_idx_exists = 0,
    'ALTER TABLE assessment_import_runs ADD KEY idx_assessment_import_runs_project_academic_year_started (project_academic_year_id, started_at)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND INDEX_NAME = 'idx_assessment_import_runs_project_academic_year_id'
);
SET @sql := IF(@old_idx_exists > 0,
    'ALTER TABLE assessment_import_runs DROP INDEX idx_assessment_import_runs_project_academic_year_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @started_idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND INDEX_NAME = 'idx_assessment_import_runs_started_at'
);
SET @sql := IF(@started_idx_exists > 0,
    'ALTER TABLE assessment_import_runs DROP INDEX idx_assessment_import_runs_started_at',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
