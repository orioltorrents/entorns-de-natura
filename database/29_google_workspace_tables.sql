-- --------------------------------------------------------
-- Entorns de Natura - Taules de Google Workspace
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS google_sources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    source_type ENUM('google_doc', 'google_sheet', 'google_drive_file') NOT NULL,
    google_file_id VARCHAR(255) NOT NULL,
    google_file_url VARCHAR(500) NULL,
    sheet_name VARCHAR(255) NULL,
    range_name VARCHAR(255) NULL,
    language_code VARCHAR(10) NOT NULL DEFAULT 'ca',
    content_type VARCHAR(100) NOT NULL DEFAULT 'programacio',
    visibility ENUM('public', 'students', 'teachers', 'assigned_teachers', 'admin') NOT NULL DEFAULT 'public',
    sync_mode ENUM('manual', 'automatic', 'disabled') NOT NULL DEFAULT 'manual',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_synced_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_google_sources_year_file (project_academic_year_id, source_type, google_file_id, sheet_name, range_name),
    KEY idx_google_sources_year_active_type (project_academic_year_id, is_active, source_type),
    KEY idx_google_sources_google_file_id (google_file_id),
    CONSTRAINT fk_google_sources_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS synced_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_source_id BIGINT UNSIGNED NOT NULL,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    language_code VARCHAR(10) NOT NULL DEFAULT 'ca',
    title VARCHAR(255) NOT NULL,
    content_html LONGTEXT NULL,
    plain_text LONGTEXT NULL,
    version_hash CHAR(64) NOT NULL,
    synced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_synced_documents_source_language (google_source_id, language_code),
    KEY idx_synced_documents_year_active_synced (project_academic_year_id, is_active, synced_at),
    KEY idx_synced_documents_source_id (google_source_id),
    CONSTRAINT fk_synced_documents_google_source
        FOREIGN KEY (google_source_id) REFERENCES google_sources (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_synced_documents_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS synced_sheet_rows (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_source_id BIGINT UNSIGNED NOT NULL,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    external_id VARCHAR(255) NULL,
    row_number INT UNSIGNED NOT NULL,
    row_data_json LONGTEXT NOT NULL,
    row_hash CHAR(64) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    synced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_synced_sheet_rows_source_external (google_source_id, external_id),
    KEY idx_synced_sheet_rows_year_active_row (project_academic_year_id, is_active, row_number),
    KEY idx_synced_sheet_rows_source_row (google_source_id, row_number),
    CONSTRAINT fk_synced_sheet_rows_google_source
        FOREIGN KEY (google_source_id) REFERENCES google_sources (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_synced_sheet_rows_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_sync_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_source_id BIGINT UNSIGNED NOT NULL,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    started_by_user_id INT UNSIGNED NULL,
    status ENUM('pending', 'running', 'completed', 'completed_with_warnings', 'failed') NOT NULL DEFAULT 'pending',
    rows_read INT UNSIGNED NOT NULL DEFAULT 0,
    rows_created INT UNSIGNED NOT NULL DEFAULT 0,
    rows_updated INT UNSIGNED NOT NULL DEFAULT 0,
    rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
    errors_count INT UNSIGNED NOT NULL DEFAULT 0,
    message TEXT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_google_sync_runs_source_id (google_source_id),
    KEY idx_google_sync_runs_year_started (project_academic_year_id, started_at),
    KEY idx_google_sync_runs_started_by_user_id (started_by_user_id),
    CONSTRAINT fk_google_sync_runs_google_source
        FOREIGN KEY (google_source_id) REFERENCES google_sources (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_google_sync_runs_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_google_sync_runs_started_by_user
        FOREIGN KEY (started_by_user_id) REFERENCES users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_sync_errors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    google_sync_run_id BIGINT UNSIGNED NOT NULL,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NULL,
    field_name VARCHAR(255) NULL,
    error_message TEXT NOT NULL,
    raw_value LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_google_sync_errors_run_id (google_sync_run_id),
    KEY idx_google_sync_errors_year_id (project_academic_year_id),
    KEY idx_google_sync_errors_row_number (row_number),
    CONSTRAINT fk_google_sync_errors_run
        FOREIGN KEY (google_sync_run_id) REFERENCES google_sync_runs (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_google_sync_errors_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
