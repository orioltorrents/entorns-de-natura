-- --------------------------------------------------------
-- Entorns de Natura - Eliminacio del camp legacy project_id a l'avaluacio
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @project_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND COLUMN_NAME = 'project_id'
);
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND CONSTRAINT_NAME = 'fk_assessment_sources_project'
);
SET @sql := IF(@project_id_exists > 0 AND @fk_exists > 0,
    'ALTER TABLE assessment_sources DROP FOREIGN KEY fk_assessment_sources_project',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND INDEX_NAME = 'uq_assessment_sources_project_name'
);
SET @sql := IF(@old_unique_exists > 0,
    'ALTER TABLE assessment_sources DROP INDEX uq_assessment_sources_project_name',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND INDEX_NAME = 'idx_assessment_sources_project_id'
);
SET @sql := IF(@old_index_exists > 0,
    'ALTER TABLE assessment_sources DROP INDEX idx_assessment_sources_project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND COLUMN_NAME = 'project_id'
);
SET @sql := IF(@project_id_exists > 0,
    'ALTER TABLE assessment_sources DROP COLUMN project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND COLUMN_NAME = 'project_id'
);
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND CONSTRAINT_NAME = 'fk_assessment_import_runs_project'
);
SET @sql := IF(@project_id_exists > 0 AND @fk_exists > 0,
    'ALTER TABLE assessment_import_runs DROP FOREIGN KEY fk_assessment_import_runs_project',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND INDEX_NAME = 'idx_assessment_import_runs_project_id'
);
SET @sql := IF(@old_index_exists > 0,
    'ALTER TABLE assessment_import_runs DROP INDEX idx_assessment_import_runs_project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND COLUMN_NAME = 'project_id'
);
SET @sql := IF(@project_id_exists > 0,
    'ALTER TABLE assessment_import_runs DROP COLUMN project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
