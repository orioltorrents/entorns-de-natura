-- --------------------------------------------------------
-- Entorns de Natura - Membres dels Google Classrooms
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS classroom_members (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    classroom_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    student_email VARCHAR(255) NOT NULL,
    google_user_id VARCHAR(100) NULL,
    google_photo_url VARCHAR(500) NULL,
    classroom_group VARCHAR(100) NULL,
    external_group_id VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classroom_members_classroom_user (classroom_id, user_id),
    KEY idx_classroom_members_classroom_active (classroom_id, is_active),
    KEY idx_classroom_members_user_id (user_id),
    KEY idx_classroom_members_student_email (student_email),
    KEY idx_classroom_members_google_user_id (google_user_id),
    CONSTRAINT fk_classroom_members_classroom
        FOREIGN KEY (classroom_id) REFERENCES classrooms (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_classroom_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
