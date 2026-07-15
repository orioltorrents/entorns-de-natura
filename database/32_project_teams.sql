-- --------------------------------------------------------
-- Entorns de Natura - Project teams i membres per projecte/curs
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS project_teams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_academic_year_id BIGINT UNSIGNED NOT NULL,
    team_code VARCHAR(100) NOT NULL,
    team_name VARCHAR(255) NULL,
    class_group VARCHAR(100) NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_teams_year_code (project_academic_year_id, team_code),
    KEY idx_project_teams_year_active_order (project_academic_year_id, is_active, display_order),
    CONSTRAINT fk_project_teams_project_academic_year
        FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_team_members (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_team_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    project_role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_team_members_team_user (project_team_id, user_id),
    KEY idx_project_team_members_user_id (user_id),
    KEY idx_project_team_members_project_role_id (project_role_id),
    CONSTRAINT fk_project_team_members_project_team
        FOREIGN KEY (project_team_id) REFERENCES project_teams (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_team_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_team_members_project_role
        FOREIGN KEY (project_role_id) REFERENCES project_roles (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
