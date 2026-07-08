-- --------------------------------------------------------
-- Entorns de Natura - Estructura visible d'avaluacio
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_phases (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    phase_key VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    section_type ENUM('phase', 'final', 'comments') NOT NULL DEFAULT 'phase',
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_phases_project_key (project_id, phase_key),
    KEY idx_assessment_phases_project_order (project_id, display_order),
    CONSTRAINT fk_assessment_phases_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    phase_id BIGINT UNSIGNED NOT NULL,
    source_column VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    weight_label VARCHAR(50) NULL,
    role_filter VARCHAR(255) NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_tasks_phase_source (phase_id, source_column),
    KEY idx_assessment_tasks_phase_order (phase_id, display_order),
    CONSTRAINT fk_assessment_tasks_phase
        FOREIGN KEY (phase_id) REFERENCES assessment_phases (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
