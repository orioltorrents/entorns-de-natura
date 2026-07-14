-- Replace several single-purpose document indexes with one composite index
-- that matches the edition document listing query.

SET NAMES utf8mb4;

SET @composite_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'idx_documents_project_year_active_order'
);

SET @sql := IF(@composite_exists = 0,
    'ALTER TABLE documents ADD KEY idx_documents_project_year_active_order (project_academic_year_id, is_active, display_order)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_year_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'idx_documents_project_academic_year_id'
);

SET @sql := IF(@idx_year_exists > 0,
    'ALTER TABLE documents DROP INDEX idx_documents_project_academic_year_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_display_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'idx_documents_display_order'
);

SET @sql := IF(@idx_display_exists > 0,
    'ALTER TABLE documents DROP INDEX idx_documents_display_order',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_active_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'idx_documents_is_active'
);

SET @sql := IF(@idx_active_exists > 0,
    'ALTER TABLE documents DROP INDEX idx_documents_is_active',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
