-- --------------------------------------------------------
-- Entorns de Natura - múltiples rols per membre d'equip
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS project_team_member_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_team_member_id BIGINT UNSIGNED NOT NULL,
    project_role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_team_member_roles_member_role (project_team_member_id, project_role_id),
    KEY idx_project_team_member_roles_role_id (project_role_id),
    CONSTRAINT fk_project_team_member_roles_member
        FOREIGN KEY (project_team_member_id) REFERENCES project_team_members (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_team_member_roles_role
        FOREIGN KEY (project_role_id) REFERENCES project_roles (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO project_team_member_roles (project_team_member_id, project_role_id)
SELECT ptm.id, ptm.project_role_id
  FROM project_team_members ptm
 WHERE ptm.project_role_id IS NOT NULL;
