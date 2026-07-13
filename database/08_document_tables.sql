-- --------------------------------------------------------
-- Entorns de Natura - Documents, fragments i visibilitat
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    slug VARCHAR(150) NOT NULL,
    title VARCHAR(255) NOT NULL,
    doc_type VARCHAR(50) NOT NULL DEFAULT 'markdown',
    default_visibility ENUM('public', 'student', 'teacher', 'admin', 'assigned_teacher') NOT NULL DEFAULT 'public',
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_documents_project_slug (project_id, slug),
    KEY idx_documents_project_id (project_id),
    KEY idx_documents_display_order (display_order),
    KEY idx_documents_is_active (is_active),
    CONSTRAINT fk_documents_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_sources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id BIGINT UNSIGNED NOT NULL,
    source_fingerprint VARCHAR(64) NOT NULL,
    source_type ENUM('markdown', 'google_doc', 'google_sheet', 'manual') NOT NULL DEFAULT 'markdown',
    source_url VARCHAR(500) NULL,
    external_id VARCHAR(255) NULL,
    sheet_name VARCHAR(255) NULL,
    range_name VARCHAR(255) NULL,
    sync_mode ENUM('manual', 'automatic', 'disabled') NOT NULL DEFAULT 'manual',
    notes TEXT NULL,
    last_synced_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_document_sources_fingerprint (document_id, source_fingerprint),
    KEY idx_document_sources_document_id (document_id),
    KEY idx_document_sources_source_type (source_type),
    CONSTRAINT fk_document_sources_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_fragments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id BIGINT UNSIGNED NOT NULL,
    fragment_key VARCHAR(150) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    content_format ENUM('markdown', 'html', 'text') NOT NULL DEFAULT 'markdown',
    notes TEXT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_document_fragments_document_key (document_id, fragment_key),
    KEY idx_document_fragments_document_id (document_id),
    KEY idx_document_fragments_display_order (display_order),
    CONSTRAINT fk_document_fragments_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_visibility_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id BIGINT UNSIGNED NOT NULL,
    fragment_id BIGINT UNSIGNED NULL,
    rule_fingerprint VARCHAR(64) NOT NULL,
    visibility_type ENUM('public', 'role', 'class', 'assigned_teacher') NOT NULL DEFAULT 'public',
    role_id INT UNSIGNED NULL,
    class_id INT UNSIGNED NULL,
    allow_view TINYINT(1) NOT NULL DEFAULT 1,
    allow_edit TINYINT(1) NOT NULL DEFAULT 0,
    priority INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_document_visibility_rules_fingerprint (document_id, rule_fingerprint),
    KEY idx_document_visibility_document_id (document_id),
    KEY idx_document_visibility_fragment_id (fragment_id),
    KEY idx_document_visibility_role_id (role_id),
    KEY idx_document_visibility_class_id (class_id),
    KEY idx_document_visibility_priority (priority),
    CONSTRAINT fk_document_visibility_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_document_visibility_fragment
        FOREIGN KEY (fragment_id) REFERENCES document_fragments (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_document_visibility_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_document_visibility_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
