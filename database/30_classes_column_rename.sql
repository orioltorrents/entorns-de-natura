-- --------------------------------------------------------
-- Entorns de Natura - Renombra les columnes de classes
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @class_name_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'classes'
      AND column_name = 'class_name'
);

SET @legacy_name_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'classes'
      AND column_name = 'name'
);

SET @sql := IF(@class_name_exists = 0 AND @legacy_name_exists > 0,
    'ALTER TABLE classes CHANGE COLUMN name class_name VARCHAR(100) NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @class_code_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'classes'
      AND column_name = 'class_code'
);

SET @legacy_code_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'classes'
      AND column_name = 'code'
);

SET @sql := IF(@class_code_exists = 0 AND @legacy_code_exists > 0,
    'ALTER TABLE classes CHANGE COLUMN code class_code VARCHAR(20) NOT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'classes'
      AND index_name = 'uq_classes_year_name'
);

SET @sql := IF(@unique_exists = 0,
    'ALTER TABLE classes ADD UNIQUE KEY uq_classes_year_name (academic_year_id, class_name)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
