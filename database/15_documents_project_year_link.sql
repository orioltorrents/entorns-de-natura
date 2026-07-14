-- --------------------------------------------------------
-- Entorns de Natura - Vincle documents -> project_academic_years
-- Migracio no destructiva per a bases existents.
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @project_academic_year_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND column_name = 'project_academic_year_id'
);

SET @sql := IF(@project_academic_year_column_exists = 0,
    'ALTER TABLE documents ADD COLUMN project_academic_year_id BIGINT UNSIGNED NULL AFTER slug',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_id_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND column_name = 'project_id'
);

SET @sql := IF(@project_id_column_exists > 0,
    'UPDATE documents d\nSET d.project_academic_year_id = COALESCE(\n    (\n        SELECT pay.id\n        FROM project_academic_years pay\n        INNER JOIN academic_years ay ON ay.id = pay.academic_year_id\n        WHERE pay.project_id = d.project_id\n        ORDER BY ay.id DESC\n        LIMIT 1\n    ),\n    d.project_academic_year_id\n)\nWHERE d.project_academic_year_id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := 'ALTER TABLE documents MODIFY COLUMN project_academic_year_id BIGINT UNSIGNED NOT NULL';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'uq_documents_project_slug'
);

SET @sql := IF(@old_unique_exists > 0,
    'ALTER TABLE documents DROP INDEX uq_documents_project_slug',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @new_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'uq_documents_project_academic_year_slug'
);

SET @sql := IF(@new_unique_exists = 0,
    'ALTER TABLE documents ADD UNIQUE KEY uq_documents_project_academic_year_slug (project_academic_year_id, slug)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_academic_year_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'idx_documents_project_academic_year_id'
);

SET @sql := IF(@project_academic_year_index_exists = 0,
    'ALTER TABLE documents ADD KEY idx_documents_project_academic_year_id (project_academic_year_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND constraint_name = 'fk_documents_project_academic_year'
);

SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE documents ADD CONSTRAINT fk_documents_project_academic_year FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
