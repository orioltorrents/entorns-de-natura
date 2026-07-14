-- Replace old project section indexes with a composite read index.

SET NAMES utf8mb4;

SET @sections_new_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_sections'
      AND index_name = 'idx_project_sections_project_active_order'
);

SET @sql := IF(@sections_new_exists = 0,
    'ALTER TABLE project_sections ADD KEY idx_project_sections_project_active_order (project_id, is_active, display_order, title)',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sections_old_project_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_sections'
      AND index_name = 'idx_project_sections_project_id'
);

SET @sql := IF(@sections_old_project_exists > 0,
    'ALTER TABLE project_sections DROP INDEX idx_project_sections_project_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sections_old_display_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_sections'
      AND index_name = 'idx_project_sections_display_order'
);

SET @sql := IF(@sections_old_display_exists > 0,
    'ALTER TABLE project_sections DROP INDEX idx_project_sections_display_order',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sections_old_visibility_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_sections'
      AND index_name = 'idx_project_sections_visibility_type'
);

SET @sql := IF(@sections_old_visibility_exists > 0,
    'ALTER TABLE project_sections DROP INDEX idx_project_sections_visibility_type',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sections_old_active_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_sections'
      AND index_name = 'idx_project_sections_is_active'
);

SET @sql := IF(@sections_old_active_exists > 0,
    'ALTER TABLE project_sections DROP INDEX idx_project_sections_is_active',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @section_roles_section_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'project_section_roles'
      AND index_name = 'idx_project_section_roles_section_id'
);

SET @sql := IF(@section_roles_section_exists > 0,
    'ALTER TABLE project_section_roles DROP INDEX idx_project_section_roles_section_id',
    'DO 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
