-- --------------------------------------------------------
-- Entorns de Natura - Eliminacio del camp academic_role de users
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @academic_role_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'academic_role'
);

SET @sql := IF(@academic_role_exists > 0,
    'ALTER TABLE users DROP COLUMN academic_role',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
