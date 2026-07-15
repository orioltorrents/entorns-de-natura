-- --------------------------------------------------------
-- Entorns de Natura - Eliminacio del camp external_id de student_profiles
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @external_id_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'student_profiles'
      AND column_name = 'external_id'
);

SET @sql := IF(@external_id_exists > 0,
    'ALTER TABLE student_profiles DROP COLUMN external_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
