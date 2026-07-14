-- Refine the document read index so it also covers ORDER BY title.

SET NAMES utf8mb4;

SET @existing_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'documents'
      AND index_name = 'idx_documents_project_year_active_order'
);

SET @sql := IF(@existing_exists > 0,
    'ALTER TABLE documents DROP INDEX idx_documents_project_year_active_order, ADD KEY idx_documents_project_year_active_order (project_academic_year_id, is_active, display_order, title)',
    'ALTER TABLE documents ADD KEY idx_documents_project_year_active_order (project_academic_year_id, is_active, display_order, title)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
