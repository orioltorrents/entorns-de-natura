-- Remove the redundant project_id column from assessment_records.

SET NAMES utf8mb4;

SET @project_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND constraint_name = 'fk_assessment_records_project'
);

SET @sql := IF(@project_fk_exists > 0,
    'ALTER TABLE assessment_records DROP FOREIGN KEY fk_assessment_records_project',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND column_name = 'project_id'
);

SET @sql := IF(@project_id_exists > 0,
    'ALTER TABLE assessment_records DROP COLUMN project_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_student_project_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND index_name = 'idx_assessment_records_student_project'
);

SET @sql := IF(@old_student_project_index_exists > 0,
    'ALTER TABLE assessment_records DROP INDEX idx_assessment_records_student_project',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_project_user_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND index_name = 'idx_assessment_records_project_user'
);

SET @sql := IF(@old_project_user_index_exists > 0,
    'ALTER TABLE assessment_records DROP INDEX idx_assessment_records_project_user',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @user_id_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND index_name = 'idx_assessment_records_user_id'
);

SET @sql := IF(@user_id_index_exists = 0,
    'ALTER TABLE assessment_records ADD KEY idx_assessment_records_user_id (user_id)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
