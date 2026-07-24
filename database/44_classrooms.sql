-- --------------------------------------------------------
-- Entorns de Natura - Google Classrooms per edicio de projecte
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS classrooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    classroom_key VARCHAR(150) NOT NULL,
    classroom_name VARCHAR(255) NOT NULL,
    classroom_url VARCHAR(500) NULL,
    google_classroom_id VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classrooms_project_year_key (project_academic_year_id, classroom_key),
    KEY idx_classrooms_project_year_active (project_academic_year_id, is_active),
    KEY idx_classrooms_google_id (google_classroom_id),
    CONSTRAINT fk_classrooms_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
