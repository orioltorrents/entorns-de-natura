-- --------------------------------------------------------
-- Entorns de Natura - Historial de canvis de classe
-- --------------------------------------------------------

SET NAMES utf8mb4;

SET @class_members_user_unique_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'class_members'
      AND index_name = 'uq_class_members_user'
);

SET @class_members_class_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'class_members'
      AND index_name = 'idx_class_members_class'
);

SET @sql := IF(@class_members_user_unique_exists = 0,
    'ALTER TABLE class_members ADD UNIQUE KEY uq_class_members_user (user_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(@class_members_class_index_exists = 0,
    'ALTER TABLE class_members ADD KEY idx_class_members_class (class_id)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS class_member_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    previous_class_id INT UNSIGNED NULL,
    new_class_id INT UNSIGNED NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    change_source VARCHAR(50) NOT NULL DEFAULT 'import',
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    note VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_class_member_history_user_id (user_id),
    KEY idx_class_member_history_academic_year_id (academic_year_id),
    KEY idx_class_member_history_previous_class_id (previous_class_id),
    KEY idx_class_member_history_new_class_id (new_class_id),
    CONSTRAINT fk_class_member_history_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_class_member_history_previous_class
        FOREIGN KEY (previous_class_id) REFERENCES classes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_class_member_history_new_class
        FOREIGN KEY (new_class_id) REFERENCES classes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_class_member_history_academic_year
        FOREIGN KEY (academic_year_id) REFERENCES academic_years (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
