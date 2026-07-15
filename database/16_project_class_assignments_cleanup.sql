-- Remove legacy columns that are no longer used now that project assignments
-- are linked through project_academic_years.

SET @db_name = DATABASE();

SET @drop_project_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'project_class_assignments'
      AND column_name = 'project_id'
);

SET @drop_academic_year_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'project_class_assignments'
      AND column_name = 'academic_year_id'
);

SET @drop_starts_at := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'project_class_assignments'
      AND column_name = 'starts_at'
);

SET @drop_ends_at := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @db_name
      AND table_name = 'project_class_assignments'
      AND column_name = 'ends_at'
);

SET @sql := IF(@drop_project_id > 0, 'ALTER TABLE project_class_assignments DROP COLUMN project_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@drop_academic_year_id > 0, 'ALTER TABLE project_class_assignments DROP COLUMN academic_year_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@drop_starts_at > 0, 'ALTER TABLE project_class_assignments DROP COLUMN starts_at', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@drop_ends_at > 0, 'ALTER TABLE project_class_assignments DROP COLUMN ends_at', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @db_name
      AND table_name = 'project_class_assignments'
      AND index_name = 'uq_project_class_year'
);

SET @sql := IF(@old_unique_exists > 0, 'ALTER TABLE project_class_assignments DROP INDEX uq_project_class_year', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
