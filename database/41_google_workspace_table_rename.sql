-- --------------------------------------------------------
-- Entorns de Natura - Nomenclatura homogènia Google Workspace
-- Aplica a bases existents. En reconstruccions netes ja queda incorporat
-- a database/29_google_workspace_tables.sql.
-- --------------------------------------------------------

SET @rename_synced_documents = IF(
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'synced_documents') = 1
    AND (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'google_documents') = 0,
    'RENAME TABLE synced_documents TO google_documents',
    'DO 0'
);
PREPARE stmt FROM @rename_synced_documents;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @rename_synced_sheet_rows = IF(
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'synced_sheet_rows') = 1
    AND (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'google_sheet_rows') = 0,
    'RENAME TABLE synced_sheet_rows TO google_sheet_rows',
    'DO 0'
);
PREPARE stmt FROM @rename_synced_sheet_rows;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS google_document_blocks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_document_id BIGINT UNSIGNED NOT NULL,
    google_source_id BIGINT UNSIGNED NOT NULL,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    visibility_level ENUM('public', 'student', 'teacher', 'assigned_teacher', 'admin') NOT NULL DEFAULT 'public',
    section_title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content_html LONGTEXT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_google_document_blocks_document_slug (google_document_id, slug),
    KEY idx_google_document_blocks_year_visibility_order (project_academic_year_id, visibility_level, is_active, display_order),
    KEY idx_google_document_blocks_source_id (google_source_id),
    CONSTRAINT fk_google_document_blocks_document
        FOREIGN KEY (google_document_id) REFERENCES google_documents (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_google_document_blocks_google_source
        FOREIGN KEY (google_source_id) REFERENCES google_sources (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_google_document_blocks_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE google_sync_errors
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
