-- --------------------------------------------------------
-- Entorns de Natura - Separacio de web_roles i project_roles
-- Migracio no destructiva per a bases ja creades.
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @roles_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'roles'
);

SET @web_roles_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'web_roles'
);

SET @user_roles_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'user_roles'
);

SET @user_web_roles_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'user_web_roles'
);

SET @sql := IF(@roles_table_exists > 0 AND @web_roles_table_exists = 0, 'RENAME TABLE roles TO web_roles', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@user_roles_table_exists > 0 AND @user_web_roles_table_exists = 0, 'RENAME TABLE user_roles TO user_web_roles', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO web_roles (name, created_at)
SELECT 'student', NOW()
WHERE NOT EXISTS (SELECT 1 FROM web_roles WHERE name = 'student');

INSERT INTO web_roles (name, created_at)
SELECT 'teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM web_roles WHERE name = 'teacher');

INSERT INTO web_roles (name, created_at)
SELECT 'guest_teacher', NOW()
WHERE NOT EXISTS (SELECT 1 FROM web_roles WHERE name = 'guest_teacher');

INSERT INTO web_roles (name, created_at)
SELECT 'coordinator', NOW()
WHERE NOT EXISTS (SELECT 1 FROM web_roles WHERE name = 'coordinator');

INSERT INTO web_roles (name, created_at)
SELECT 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM web_roles WHERE name = 'admin');

SET @project_role_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'project_roles'
);

SET @academic_roles_table_exists := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'academic_roles'
);

SET @sql := IF(@academic_roles_table_exists > 0 AND @project_role_table_exists = 0, 'RENAME TABLE academic_roles TO project_roles', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@academic_roles_table_exists > 0 AND @project_role_table_exists > 0,
    'INSERT INTO project_roles (name, created_at) SELECT ar.name, ar.created_at FROM academic_roles ar LEFT JOIN project_roles pr ON pr.name = ar.name WHERE pr.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@academic_roles_table_exists > 0 AND @project_role_table_exists > 0,
    'DROP TABLE academic_roles',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS project_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @academic_role_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND column_name = 'academic_role_id'
);

SET @project_role_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND column_name = 'project_role_id'
);

SET @sql := IF(@academic_role_column_exists > 0 AND @project_role_column_exists = 0,
    'ALTER TABLE document_visibility_rules CHANGE COLUMN academic_role_id project_role_id INT UNSIGNED NULL AFTER role_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@academic_role_column_exists = 0 AND @project_role_column_exists = 0,
    'ALTER TABLE document_visibility_rules ADD COLUMN project_role_id INT UNSIGNED NULL AFTER role_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := 'ALTER TABLE document_visibility_rules MODIFY COLUMN visibility_type ENUM("public", "role", "project_role", "class", "assigned_teacher") NOT NULL DEFAULT "public"';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_role_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND index_name = 'idx_document_visibility_project_role_id'
);

SET @sql := IF(@project_role_index_exists = 0,
    'ALTER TABLE document_visibility_rules ADD KEY idx_document_visibility_project_role_id (project_role_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @project_role_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND constraint_name = 'fk_document_visibility_project_role'
);

SET @sql := IF(@project_role_fk_exists = 0,
    'ALTER TABLE document_visibility_rules ADD CONSTRAINT fk_document_visibility_project_role FOREIGN KEY (project_role_id) REFERENCES project_roles (id) ON DELETE CASCADE ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_project_role_fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND constraint_name = 'fk_document_visibility_academic_role'
);

SET @sql := IF(@old_project_role_fk_exists > 0,
    'ALTER TABLE document_visibility_rules DROP FOREIGN KEY fk_document_visibility_academic_role',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @old_project_role_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'document_visibility_rules'
      AND index_name = 'idx_document_visibility_academic_role_id'
);

SET @sql := IF(@old_project_role_index_exists > 0,
    'ALTER TABLE document_visibility_rules DROP INDEX idx_document_visibility_academic_role_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE project_roles
SET name = 'cartògraf/a'
WHERE name IN ('cartograf', 'cartògraf/a');

INSERT INTO project_roles (name, created_at)
SELECT 'coordinador/a', NOW()
WHERE NOT EXISTS (SELECT 1 FROM project_roles WHERE name = 'coordinador/a');

INSERT INTO project_roles (name, created_at)
SELECT 'informàtic/a', NOW()
WHERE NOT EXISTS (SELECT 1 FROM project_roles WHERE name = 'informàtic/a');

INSERT INTO project_roles (name, created_at)
SELECT 'cartògraf/a', NOW()
WHERE NOT EXISTS (SELECT 1 FROM project_roles WHERE name = 'cartògraf/a');

INSERT INTO project_roles (name, created_at)
SELECT 'científic/a', NOW()
WHERE NOT EXISTS (SELECT 1 FROM project_roles WHERE name = 'científic/a');
