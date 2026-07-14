-- --------------------------------------------------------
-- Entorns de Natura - Tasques d'avaluacio per edicio de projecte
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS project_academic_year_phase_tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_phase_id BIGINT UNSIGNED NOT NULL,
    assessment_task_id BIGINT UNSIGNED NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_academic_year_phase_tasks (project_academic_year_phase_id, assessment_task_id),
    KEY idx_project_academic_year_phase_tasks_phase_active_order (project_academic_year_phase_id, is_visible, display_order),
    KEY idx_project_academic_year_phase_tasks_task_id (assessment_task_id),
    CONSTRAINT fk_project_academic_year_phase_tasks_phase
        FOREIGN KEY (project_academic_year_phase_id) REFERENCES project_academic_year_phases (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_academic_year_phase_tasks_task
        FOREIGN KEY (assessment_task_id) REFERENCES assessment_tasks (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project_academic_year_phase_tasks (project_academic_year_phase_id, assessment_task_id, display_order, is_visible)
SELECT payp.id, at.id, at.display_order, at.is_visible
FROM project_academic_year_phases payp
INNER JOIN assessment_tasks at ON at.phase_id = payp.assessment_phase_id
WHERE NOT EXISTS (
    SELECT 1
    FROM project_academic_year_phase_tasks paypt
    WHERE paypt.project_academic_year_phase_id = payp.id
      AND paypt.assessment_task_id = at.id
);
