-- --------------------------------------------------------
-- Entorns de Natura - Project class assignments vinculats a project_academic_years
-- Migracio no destructiva per a bases existents.
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @project_academic_year_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'project_class_assignments'
      AND column_name = 'project_academic_year_id'
);

SET @sql := IF(@project_academic_year_id_exists = 0,
    'ALTER TABLE project_class_assignments ADD COLUMN project_academic_year_id BIGINT UNSIGNED NULL AFTER id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE project_class_assignments pca
SET pca.project_academic_year_id = COALESCE(
    (
        SELECT pay.id
        FROM project_academic_years pay
        WHERE pay.project_id = pca.project_id
          AND pay.academic_year_id = pca.academic_year_id
        LIMIT 1
    ),
    (
        SELECT pay.id
        FROM project_academic_years pay
        INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
        WHERE pay.project_id = pca.project_id
        ORDER BY ay.id DESC
        LIMIT 1
    )
)
WHERE pca.project_academic_year_id IS NULL;

SET @sql := 'ALTER TABLE project_class_assignments MODIFY COLUMN class_id INT UNSIGNED NOT NULL';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_academic_year_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'project_class_assignments'
      AND constraint_name = 'fk_project_class_assignments_project_academic_year'
);

SET @sql := IF(@project_academic_year_fk_exists = 0,
    'ALTER TABLE project_class_assignments ADD CONSTRAINT fk_project_class_assignments_project_academic_year FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @class_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'project_class_assignments'
      AND constraint_name = 'fk_project_class_assignments_class'
);

SET @sql := IF(@class_fk_exists = 0,
    'ALTER TABLE project_class_assignments ADD CONSTRAINT fk_project_class_assignments_class FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := 'ALTER TABLE project_class_assignments MODIFY COLUMN project_academic_year_id BIGINT UNSIGNED NOT NULL';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_academic_year_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_class_assignments'
      AND index_name = 'idx_project_class_assignments_project_academic_year_id'
);

SET @sql := IF(@project_academic_year_index_exists = 0,
    'ALTER TABLE project_class_assignments ADD KEY idx_project_class_assignments_project_academic_year_id (project_academic_year_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_class_assignments'
      AND index_name = 'uq_project_class_assignments'
);

SET @sql := IF(@unique_exists = 0,
    'ALTER TABLE project_class_assignments ADD UNIQUE KEY uq_project_class_assignments (project_academic_year_id, class_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
