-- --------------------------------------------------------
-- Entorns de Natura - Renom de project_groups a project_class_assignments
-- Migracio no destructiva per a bases existents.
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @project_groups_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'project_groups'
);

SET @project_class_assignments_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'project_class_assignments'
);

SET @sql := IF(@project_groups_exists > 0 AND @project_class_assignments_exists = 0,
    'RENAME TABLE project_groups TO project_class_assignments',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS project_class_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'actiu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_class_assignments (project_academic_year_id, class_id),
    KEY idx_project_class_assignments_project_academic_year_id (project_academic_year_id),
    KEY idx_project_class_assignments_class_id (class_id),
    CONSTRAINT fk_project_class_assignments_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_class_assignments_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
