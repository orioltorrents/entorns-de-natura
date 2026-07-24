-- --------------------------------------------------------
-- Entorns de Natura - Relacio entre Classrooms i edicions de projecte
-- --------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE classrooms
    ADD COLUMN IF NOT EXISTS academic_year_id INT UNSIGNED NULL AFTER id;

UPDATE classrooms c
INNER JOIN project_academic_years pay ON pay.id = c.project_academic_year_id
SET c.academic_year_id = pay.academic_year_id
WHERE c.academic_year_id IS NULL;

ALTER TABLE classrooms
    ADD KEY IF NOT EXISTS idx_classrooms_academic_year_active (academic_year_id, is_active),
    ADD UNIQUE KEY IF NOT EXISTS uq_classrooms_academic_year_key (academic_year_id, classroom_key);

SET @add_classrooms_academic_year_fk = IF(
    (SELECT COUNT(*)
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'classrooms'
       AND COLUMN_NAME = 'academic_year_id'
       AND REFERENCED_TABLE_NAME = 'academic_years'
       AND REFERENCED_COLUMN_NAME = 'id') = 0,
    'ALTER TABLE classrooms ADD CONSTRAINT fk_classrooms_academic_year FOREIGN KEY (academic_year_id) REFERENCES academic_years (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'DO 0'
);
PREPARE stmt FROM @add_classrooms_academic_year_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS classroom_project_academic_years (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    classroom_id BIGINT UNSIGNED NOT NULL,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classroom_project_academic_years (classroom_id, project_academic_year_id),
    KEY idx_classroom_project_academic_years_classroom_active (classroom_id, is_active),
    KEY idx_classroom_project_academic_years_project_year (project_academic_year_id),
    CONSTRAINT fk_classroom_project_academic_years_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_classroom_project_academic_years_project_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO classroom_project_academic_years (classroom_id, project_academic_year_id, is_active)
SELECT c.id, c.project_academic_year_id, c.is_active
FROM classrooms c
WHERE NOT EXISTS (
    SELECT 1
    FROM classroom_project_academic_years cpay
    WHERE cpay.classroom_id = c.id
      AND cpay.project_academic_year_id = c.project_academic_year_id
);
