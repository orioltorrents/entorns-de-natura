-- Replace the single user_id index with a composite index for student/project lookups.

SET NAMES utf8mb4;

SET @composite_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND index_name = 'idx_assessment_records_user_source'
);

SET @sql := IF(@composite_exists = 0,
    'ALTER TABLE assessment_records ADD KEY idx_assessment_records_user_source (user_id, source_id)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @user_idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'assessment_records'
      AND index_name = 'idx_assessment_records_user_id'
);

SET @sql := IF(@user_idx_exists > 0,
    'ALTER TABLE assessment_records DROP INDEX idx_assessment_records_user_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
