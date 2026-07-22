-- --------------------------------------------------------
-- Entorns de Natura - Classe contextual dels membres d'equip
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @class_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'project_team_members'
      AND column_name = 'class_id'
);

SET @sql := IF(@class_column_exists = 0,
    'ALTER TABLE project_team_members ADD COLUMN class_id INT UNSIGNED NULL AFTER user_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @class_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_team_members'
      AND index_name = 'idx_project_team_members_class_id'
);

SET @sql := IF(@class_index_exists = 0,
    'ALTER TABLE project_team_members ADD KEY idx_project_team_members_class_id (class_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @class_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'project_team_members'
      AND constraint_name = 'fk_project_team_members_class'
);

SET @sql := IF(@class_fk_exists = 0,
    'ALTER TABLE project_team_members ADD CONSTRAINT fk_project_team_members_class FOREIGN KEY (class_id) REFERENCES classes (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE project_team_members ptm
INNER JOIN project_teams pt ON pt.id = ptm.project_team_id
INNER JOIN project_academic_years pay ON pay.id = pt.project_academic_year_id
INNER JOIN (
    SELECT h.user_id, h.academic_year_id, MAX(h.id) AS history_id
    FROM class_member_history h
    WHERE h.new_class_id IS NOT NULL
    GROUP BY h.user_id, h.academic_year_id
) latest_history ON latest_history.user_id = ptm.user_id
    AND latest_history.academic_year_id = pay.academic_year_id
INNER JOIN class_member_history h ON h.id = latest_history.history_id
INNER JOIN classes c ON c.id = h.new_class_id
    AND c.academic_year_id = pay.academic_year_id
SET ptm.class_id = c.id
WHERE ptm.class_id IS NULL;

UPDATE project_team_members ptm
INNER JOIN project_teams pt ON pt.id = ptm.project_team_id
INNER JOIN project_academic_years pay ON pay.id = pt.project_academic_year_id
INNER JOIN class_members cm ON cm.user_id = ptm.user_id
INNER JOIN classes c ON c.id = cm.class_id
    AND c.academic_year_id = pay.academic_year_id
SET ptm.class_id = c.id
WHERE ptm.class_id IS NULL;

UPDATE project_team_members ptm
INNER JOIN project_teams pt ON pt.id = ptm.project_team_id
INNER JOIN project_academic_years pay ON pay.id = pt.project_academic_year_id
INNER JOIN classes c ON c.academic_year_id = pay.academic_year_id
    AND c.class_code = pt.class_group
SET ptm.class_id = c.id
WHERE ptm.class_id IS NULL
  AND pt.class_group IS NOT NULL
  AND pt.class_group <> '';
