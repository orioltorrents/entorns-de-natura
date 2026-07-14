-- Replace old document-related indexes with the composite read indexes.

SET NAMES utf8mb4;

SET @doc_src_new_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_sources'
      AND index_name = 'idx_document_sources_document_active'
);

SET @sql := IF(@doc_src_new_exists = 0,
    'ALTER TABLE document_sources ADD KEY idx_document_sources_document_active (document_id, is_active, id)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @doc_src_old_doc_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_sources'
      AND index_name = 'idx_document_sources_document_id'
);

SET @sql := IF(@doc_src_old_doc_exists > 0,
    'ALTER TABLE document_sources DROP INDEX idx_document_sources_document_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @doc_src_old_type_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_sources'
      AND index_name = 'idx_document_sources_source_type'
);

SET @sql := IF(@doc_src_old_type_exists > 0,
    'ALTER TABLE document_sources DROP INDEX idx_document_sources_source_type',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @frag_new_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_fragments'
      AND index_name = 'idx_document_fragments_document_active_order'
);

SET @sql := IF(@frag_new_exists = 0,
    'ALTER TABLE document_fragments ADD KEY idx_document_fragments_document_active_order (document_id, is_active, display_order, id)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @frag_old_doc_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_fragments'
      AND index_name = 'idx_document_fragments_document_id'
);

SET @sql := IF(@frag_old_doc_exists > 0,
    'ALTER TABLE document_fragments DROP INDEX idx_document_fragments_document_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @frag_old_order_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_fragments'
      AND index_name = 'idx_document_fragments_display_order'
);

SET @sql := IF(@frag_old_order_exists > 0,
    'ALTER TABLE document_fragments DROP INDEX idx_document_fragments_display_order',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @vis_new_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND index_name = 'idx_document_visibility_document_active_priority'
);

SET @sql := IF(@vis_new_exists = 0,
    'ALTER TABLE document_visibility_rules ADD KEY idx_document_visibility_document_active_priority (document_id, is_active, priority, id)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @vis_old_doc_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND index_name = 'idx_document_visibility_document_id'
);

SET @sql := IF(@vis_old_doc_exists > 0,
    'ALTER TABLE document_visibility_rules DROP INDEX idx_document_visibility_document_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @vis_old_priority_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND index_name = 'idx_document_visibility_priority'
);

SET @sql := IF(@vis_old_priority_exists > 0,
    'ALTER TABLE document_visibility_rules DROP INDEX idx_document_visibility_priority',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
