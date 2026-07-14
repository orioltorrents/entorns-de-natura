-- --------------------------------------------------------
-- Entorns de Natura - Fases d'avaluacio per edicio de projecte
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS project_academic_year_phases (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    assessment_phase_id BIGINT UNSIGNED NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_academic_year_phases (project_academic_year_id, assessment_phase_id),
    KEY idx_project_academic_year_phases_year_active_order (project_academic_year_id, is_active, display_order),
    KEY idx_project_academic_year_phases_phase_id (assessment_phase_id),
    CONSTRAINT fk_project_academic_year_phases_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_academic_year_phases_phase
        FOREIGN KEY (assessment_phase_id) REFERENCES assessment_phases (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project_academic_year_phases (project_academic_year_id, assessment_phase_id, display_order, is_active)
SELECT pay.id, ap.id, ap.display_order, ap.is_active
FROM assessment_phases ap
INNER JOIN project_academic_years pay ON pay.project_id = ap.project_id
WHERE NOT EXISTS (
    SELECT 1
    FROM project_academic_year_phases payp
    WHERE payp.project_academic_year_id = pay.id
      AND payp.assessment_phase_id = ap.id
);
