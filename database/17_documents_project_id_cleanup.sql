-- Remove the legacy project_id column from documents.

SET NAMES utf8mb4;

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

SET @project_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND column_name = 'project_id'
);

SET @project_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND constraint_name = 'fk_documents_project'
);

SET @sql := IF(@project_fk_exists > 0,
    'ALTER TABLE documents DROP FOREIGN KEY fk_documents_project',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@project_id_exists > 0,
    'ALTER TABLE documents DROP COLUMN project_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
