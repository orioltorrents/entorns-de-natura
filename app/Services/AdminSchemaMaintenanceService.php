<?php

declare(strict_types=1);

class AdminSchemaMaintenanceService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureAdminSchema(): void
    {
        $this->ensureProjectClassAssignmentsTable();
        $this->ensureProjectsDisplayOrderColumn();
        $this->ensureProjectTeamMemberRolesTable();
    }

    private function ensureProjectClassAssignmentsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS project_class_assignments (
                 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                 project_academic_year_id BIGINT UNSIGNED NOT NULL,
                 class_id INT UNSIGNED NOT NULL,
                 status VARCHAR(50) NOT NULL DEFAULT "actiu",
                 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                 PRIMARY KEY (id),
                 UNIQUE KEY uq_project_class_assignments (project_academic_year_id, class_id),
                 KEY idx_project_class_assignments_project_academic_year_id (project_academic_year_id),
                 KEY idx_project_class_assignments_class_id (class_id),
                 CONSTRAINT fk_project_class_assignments_project_academic_year
                     FOREIGN KEY (project_academic_year_id) REFERENCES project_academic_years (id)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE,
                 CONSTRAINT fk_project_class_assignments_class
                     FOREIGN KEY (class_id) REFERENCES classes (id)
                     ON DELETE CASCADE
                     ON UPDATE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdo->exec(
            "UPDATE project_class_assignments
             SET status = CASE status
                 WHEN 'planned' THEN 'pendent'
                 WHEN 'previst' THEN 'pendent'
                 WHEN 'active' THEN 'actiu'
                 WHEN 'completed' THEN 'realitzat'
                 WHEN 'completat' THEN 'realitzat'
                 ELSE status
             END
             WHERE status IN ('planned', 'previst', 'active', 'completed', 'completat')"
        );
    }

    private function ensureProjectsDisplayOrderColumn(): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name'
        );
        $stmt->execute([
            'table_name' => 'projects',
            'column_name' => 'display_order',
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $this->pdo->exec('ALTER TABLE projects ADD COLUMN display_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER name');
        $this->pdo->exec('ALTER TABLE projects ADD KEY idx_projects_display_order (display_order)');
        $this->pdo->exec(
            "UPDATE projects
             SET display_order = CASE slug
                 WHEN 'projecte-rius' THEN 10
                 WHEN 'mat-penedes' THEN 20
                 WHEN 'agroparc' THEN 30
                 WHEN 'projecte-orenetes' THEN 40
                 WHEN 'liquencity' THEN 50
                 WHEN 'vespa-velutina' THEN 60
                 ELSE display_order
             END
             WHERE display_order = 0"
        );
    }

    private function ensureProjectTeamMemberRolesTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS project_team_member_roles (
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
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->pdo->exec(
            'INSERT IGNORE INTO project_team_member_roles (project_team_member_id, project_role_id)
             SELECT ptm.id, ptm.project_role_id
               FROM project_team_members ptm
              WHERE ptm.project_role_id IS NOT NULL'
        );
    }
}
