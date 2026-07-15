-- --------------------------------------------------------
-- Entorns de Natura - Reduccio de student_profiles a una extensio minima
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @class_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'class_id'
);
SET @sql := IF(@class_id_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN class_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @class_group_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'class_group'
);
SET @sql := IF(@class_group_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN class_group', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'project'
);
SET @sql := IF(@project_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN project', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @team_number_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'team_number'
);
SET @sql := IF(@team_number_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN team_number', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @group_number_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'group_number'
);
SET @sql := IF(@group_number_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN group_number', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @group_code_1t_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'group_code_1t'
);
SET @sql := IF(@group_code_1t_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN group_code_1t', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @members_count_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'members_count'
);
SET @sql := IF(@members_count_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN members_count', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @trimester_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'trimester'
);
SET @sql := IF(@trimester_exists > 0, 'ALTER TABLE student_profiles DROP COLUMN trimester', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
