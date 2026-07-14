-- --------------------------------------------------------
-- Entorns de Natura - Seccions de projecte i permisos per rol
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS project_sections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    section_type ENUM('summary', 'tasks', 'documents', 'notes', 'scaffold', 'custom') NOT NULL DEFAULT 'custom',
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    visibility_type ENUM('public', 'role', 'class', 'assigned_teacher') NOT NULL DEFAULT 'public',
    role_id INT UNSIGNED NULL,
    class_id INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    config_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_sections_project_key (project_id, section_key),
    KEY idx_project_sections_project_active_order (project_id, is_active, display_order, title),
    KEY idx_project_sections_role_id (role_id),
    KEY idx_project_sections_class_id (class_id),
    CONSTRAINT fk_project_sections_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ,CONSTRAINT fk_project_sections_role
        FOREIGN KEY (role_id) REFERENCES web_roles (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
    ,CONSTRAINT fk_project_sections_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_section_roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_section_id BIGINT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    allow_view TINYINT(1) NOT NULL DEFAULT 1,
    allow_edit TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_section_roles (project_section_id, role_id),
    KEY idx_project_section_roles_role_id (role_id),
    CONSTRAINT fk_project_section_roles_section
        FOREIGN KEY (project_section_id) REFERENCES project_sections (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_section_roles_role
        FOREIGN KEY (role_id) REFERENCES web_roles (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seccions base per a tots els projectes actius
-- --------------------------------------------------------

INSERT INTO project_sections (project_id, section_key, title, section_type, display_order, visibility_type, is_active, config_json)
SELECT p.id, 'programacio', 'Programació', 'documents', 10, 'public', 1, JSON_OBJECT('icon', 'calendar')
FROM projects p
WHERE p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM project_sections ps
      WHERE ps.project_id = p.id
        AND ps.section_key = 'programacio'
  );

INSERT INTO project_sections (project_id, section_key, title, section_type, display_order, visibility_type, is_active, config_json)
SELECT p.id, 'planificacio', 'Planificació', 'documents', 20, 'role', 1, JSON_OBJECT('icon', 'clipboard')
FROM projects p
WHERE p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM project_sections ps
      WHERE ps.project_id = p.id
        AND ps.section_key = 'planificacio'
  );

INSERT INTO project_sections (project_id, section_key, title, section_type, display_order, visibility_type, is_active, config_json)
SELECT p.id, 'tasques', 'Tasques', 'tasks', 30, 'role', 1, JSON_OBJECT('icon', 'checklist')
FROM projects p
WHERE p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM project_sections ps
      WHERE ps.project_id = p.id
        AND ps.section_key = 'tasques'
  );

INSERT INTO project_sections (project_id, section_key, title, section_type, display_order, visibility_type, is_active, config_json)
SELECT p.id, 'bastides', 'Bastides', 'scaffold', 40, 'role', 1, JSON_OBJECT('icon', 'support')
FROM projects p
WHERE p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM project_sections ps
      WHERE ps.project_id = p.id
        AND ps.section_key = 'bastides'
  );

INSERT INTO project_sections (project_id, section_key, title, section_type, display_order, visibility_type, is_active, config_json)
SELECT p.id, 'notes', 'Notes', 'notes', 50, 'role', 1, JSON_OBJECT('icon', 'note')
FROM projects p
WHERE p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM project_sections ps
      WHERE ps.project_id = p.id
        AND ps.section_key = 'notes'
  );

INSERT INTO project_sections (project_id, section_key, title, section_type, display_order, visibility_type, is_active, config_json)
SELECT p.id, 'observacions_aula', 'Observacions d''aula', 'custom', 60, 'role', 1, JSON_OBJECT('icon', 'chat')
FROM projects p
WHERE p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM project_sections ps
      WHERE ps.project_id = p.id
        AND ps.section_key = 'observacions_aula'
  );

-- --------------------------------------------------------
-- Permisos per rol
-- --------------------------------------------------------

INSERT INTO project_section_roles (project_section_id, role_id, allow_view, allow_edit)
SELECT ps.id, r.id, 1, 0
FROM project_sections ps
JOIN projects p ON p.id = ps.project_id
JOIN web_roles r ON r.name IN ('student', 'teacher', 'admin')
WHERE p.is_active = 1
  AND ps.section_key IN ('planificacio', 'tasques', 'bastides', 'notes', 'observacions_aula')
  AND NOT EXISTS (
      SELECT 1
      FROM project_section_roles psr
      WHERE psr.project_section_id = ps.id
        AND psr.role_id = r.id
  );
