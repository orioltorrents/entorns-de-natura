-- --------------------------------------------------------
-- Entorns de Natura - Taules d'avaluacio i notes
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_sources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    source_type ENUM('csv', 'google_sheet') NOT NULL DEFAULT 'csv',
    source_reference VARCHAR(500) NULL,
    sheet_name VARCHAR(255) NULL,
    email_column VARCHAR(255) NOT NULL DEFAULT 'email',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_imported_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_sources_project_academic_year_name (project_academic_year_id, name),
    CONSTRAINT fk_assessment_sources_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_import_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    source_id BIGINT UNSIGNED NULL,
    filename VARCHAR(500) NULL,
    status ENUM('running', 'success', 'partial', 'failed') NOT NULL DEFAULT 'running',
    rows_total INT UNSIGNED NOT NULL DEFAULT 0,
    rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
    rows_failed INT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    message TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_assessment_import_runs_project_academic_year_started (project_academic_year_id, started_at),
    KEY idx_assessment_import_runs_source_id (source_id),
    CONSTRAINT fk_assessment_import_runs_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assessment_import_runs_source
        FOREIGN KEY (source_id) REFERENCES assessment_sources (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    source_id BIGINT UNSIGNED NULL,
    import_run_id BIGINT UNSIGNED NULL,
    student_email VARCHAR(190) NOT NULL,
    label VARCHAR(255) NOT NULL,
    source_column VARCHAR(255) NOT NULL,
    value TEXT NULL,
    value_type ENUM('numeric', 'achievement', 'text', 'empty') NOT NULL DEFAULT 'text',
    numeric_value DECIMAL(5,2) NULL,
    achievement_value ENUM('AE', 'AN', 'AS', 'NA') NULL,
    group_name VARCHAR(100) NULL,
    team_code VARCHAR(100) NULL,
    role_name VARCHAR(100) NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assessment_records_user_source (user_id, source_id),
    KEY idx_assessment_records_source_id (source_id),
    KEY idx_assessment_records_import_run_id (import_run_id),
    KEY idx_assessment_records_student_email (student_email),
    CONSTRAINT fk_assessment_records_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assessment_records_source
        FOREIGN KEY (source_id) REFERENCES assessment_sources (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_assessment_records_import_run
        FOREIGN KEY (import_run_id) REFERENCES assessment_import_runs (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_import_errors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    import_run_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NULL,
    student_email VARCHAR(190) NULL,
    message TEXT NOT NULL,
    raw_data LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assessment_import_errors_import_run_id (import_run_id),
    KEY idx_assessment_import_errors_student_email (student_email),
    CONSTRAINT fk_assessment_import_errors_import_run
        FOREIGN KEY (import_run_id) REFERENCES assessment_import_runs (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
