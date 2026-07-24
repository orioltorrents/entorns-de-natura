-- --------------------------------------------------------
-- Entorns de Natura - Enllacos de tasques per Google Classroom
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_task_classroom_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_phase_task_id BIGINT UNSIGNED NOT NULL,
    classroom_id BIGINT UNSIGNED NOT NULL,
    task_url VARCHAR(500) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_task_classroom_links (project_academic_year_phase_task_id, classroom_id),
    KEY idx_assessment_task_classroom_links_task_visible (project_academic_year_phase_task_id, is_visible),
    KEY idx_assessment_task_classroom_links_classroom (classroom_id),
    CONSTRAINT fk_assessment_task_classroom_links_task
        FOREIGN KEY (project_academic_year_phase_task_id) REFERENCES project_academic_year_phase_tasks (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assessment_task_classroom_links_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
