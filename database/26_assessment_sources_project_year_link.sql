-- --------------------------------------------------------
-- Entorns de Natura - Enllaç d'avaluacio amb project_academic_years
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @project_academic_year_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND COLUMN_NAME = 'project_academic_year_id'
);
SET @sql := IF(@project_academic_year_id_exists = 0,
    'ALTER TABLE assessment_sources ADD COLUMN project_academic_year_id BIGINT UNSIGNED NULL AFTER project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE assessment_sources s
SET s.project_academic_year_id = COALESCE(
    (
        SELECT pay.id
        FROM project_academic_years pay
        WHERE pay.project_id = s.project_id
        ORDER BY pay.academic_year_id DESC
        LIMIT 1
    ),
    s.project_academic_year_id
)
WHERE s.project_academic_year_id IS NULL;

SET @remaining_nulls := (
    SELECT COUNT(*)
    FROM assessment_sources
    WHERE project_academic_year_id IS NULL
);
SET @sql := IF(@remaining_nulls = 0,
    'ALTER TABLE assessment_sources MODIFY COLUMN project_academic_year_id BIGINT UNSIGNED NOT NULL',
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

SET @new_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND INDEX_NAME = 'uq_assessment_sources_project_academic_year_name'
);
SET @sql := IF(@new_unique_exists = 0,
    'ALTER TABLE assessment_sources ADD UNIQUE KEY uq_assessment_sources_project_academic_year_name (project_academic_year_id, name)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_academic_year_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND INDEX_NAME = 'idx_assessment_sources_project_academic_year_id'
);
SET @sql := IF(@project_academic_year_index_exists = 0,
    'ALTER TABLE assessment_sources ADD KEY idx_assessment_sources_project_academic_year_id (project_academic_year_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_sources'
      AND CONSTRAINT_NAME = 'fk_assessment_sources_project_academic_year'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE assessment_sources ADD CONSTRAINT fk_assessment_sources_project_academic_year FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_academic_year_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND COLUMN_NAME = 'project_academic_year_id'
);
SET @sql := IF(@project_academic_year_id_exists = 0,
    'ALTER TABLE assessment_import_runs ADD COLUMN project_academic_year_id BIGINT UNSIGNED NULL AFTER project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE assessment_import_runs r
SET r.project_academic_year_id = COALESCE(
    (
        SELECT pay.id
        FROM project_academic_years pay
        WHERE pay.project_id = r.project_id
        ORDER BY pay.academic_year_id DESC
        LIMIT 1
    ),
    r.project_academic_year_id
)
WHERE r.project_academic_year_id IS NULL;

SET @remaining_nulls := (
    SELECT COUNT(*)
    FROM assessment_import_runs
    WHERE project_academic_year_id IS NULL
);
SET @sql := IF(@remaining_nulls = 0,
    'ALTER TABLE assessment_import_runs MODIFY COLUMN project_academic_year_id BIGINT UNSIGNED NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_academic_year_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND INDEX_NAME = 'idx_assessment_import_runs_project_academic_year_id'
);
SET @sql := IF(@project_academic_year_index_exists = 0,
    'ALTER TABLE assessment_import_runs ADD KEY idx_assessment_import_runs_project_academic_year_id (project_academic_year_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'assessment_import_runs'
      AND CONSTRAINT_NAME = 'fk_assessment_import_runs_project_academic_year'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE assessment_import_runs ADD CONSTRAINT fk_assessment_import_runs_project_academic_year FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
