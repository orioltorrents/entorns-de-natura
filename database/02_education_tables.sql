-- --------------------------------------------------------
-- Entorns de Natura - Esquema actual de base de dades
-- Utilitzar com a referència oficial per recrear o revisar
-- l’estructura del projecte.
--
-- Aquest fitxer conté les taules base (16).
-- Les taules d'avaluació i assets complementaris viuen a:
-- - database/03_assessment_tables.sql
-- - database/04_assessment_structure_tables.sql
-- - database/06_project_assets.sql
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) NULL,
    password_hash VARCHAR(255) NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    password_changed_at TIMESTAMP NULL DEFAULT NULL,
    avatar_url VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    gender VARCHAR(50) NULL,
    article VARCHAR(255) NULL,
    inaturalist_user_login VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_profiles_user (user_id),
    CONSTRAINT fk_student_profiles_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_web_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_visits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(100) NULL,
    user_id INT UNSIGNED NULL,
    visited_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    path VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    country_code VARCHAR(10) NULL,
    region VARCHAR(255) NULL,
    device_type VARCHAR(50) NULL,
    os_family VARCHAR(50) NULL,
    browser VARCHAR(100) NULL,
    user_agent TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_site_visits_visited_at (visited_at),
    KEY idx_site_visits_user_id (user_id),
    KEY idx_site_visits_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_web_roles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_web_roles (user_id, role_id),
    CONSTRAINT fk_user_web_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_user_web_roles_role
        FOREIGN KEY (role_id) REFERENCES web_roles (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS languages (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_languages_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS academic_years (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL,
    start_year YEAR NOT NULL,
    end_year YEAR NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_academic_years_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    academic_year_id INT UNSIGNED NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_year_name (academic_year_id, class_name),
    CONSTRAINT fk_classes_academic_year
        FOREIGN KEY (academic_year_id) REFERENCES academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS class_members (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_class_members (class_id, user_id),
    UNIQUE KEY uq_class_members_user (user_id),
    KEY idx_class_members_class (class_id),
    CONSTRAINT fk_class_members_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_class_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS class_teachers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_class_teachers (class_id, user_id),
    CONSTRAINT fk_class_teachers_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_class_teachers_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_projects_slug (slug),
    KEY idx_projects_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_translations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    language_id SMALLINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_translations (project_id, language_id),
    CONSTRAINT fk_project_translations_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_translations_language
        FOREIGN KEY (language_id) REFERENCES languages (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_assets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    asset_type VARCHAR(50) NOT NULL DEFAULT 'software',
    logo_path VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    description VARCHAR(255) NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_assets_slug (slug),
    KEY idx_project_assets_type (asset_type),
    KEY idx_project_assets_display_order (display_order),
    KEY idx_project_assets_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_asset_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_asset_links (project_id, asset_id),
    KEY idx_project_asset_links_project_id (project_id),
    KEY idx_project_asset_links_asset_id (asset_id),
    CONSTRAINT fk_project_asset_links_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_asset_links_asset
        FOREIGN KEY (asset_id) REFERENCES project_assets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Inserts inicials
-- --------------------------------------------------------

INSERT INTO languages (code, name, is_default)
SELECT 'ca', 'Català', 1
WHERE NOT EXISTS (SELECT 1 FROM languages WHERE code = 'ca');

INSERT INTO languages (code, name, is_default)
SELECT 'es', 'Castellano', 0
WHERE NOT EXISTS (SELECT 1 FROM languages WHERE code = 'es');

INSERT INTO languages (code, name, is_default)
SELECT 'en', 'English', 0
WHERE NOT EXISTS (SELECT 1 FROM languages WHERE code = 'en');

INSERT INTO academic_years (name, start_year, end_year, is_current)
SELECT '2025-2026', 2025, 2026, 1
WHERE NOT EXISTS (SELECT 1 FROM academic_years WHERE name = '2025-2026');

INSERT INTO classes (academic_year_id, class_name, class_code)
SELECT ay.id, '4ESO A', '4ESO-A'
FROM academic_years ay
WHERE ay.name = '2025-2026'
  AND NOT EXISTS (
      SELECT 1 FROM classes c
      WHERE c.academic_year_id = ay.id AND c.class_name = '4ESO A'
  );

INSERT INTO classes (academic_year_id, class_name, class_code)
SELECT ay.id, '4ESO B', '4ESO-B'
FROM academic_years ay
WHERE ay.name = '2025-2026'
  AND NOT EXISTS (
      SELECT 1 FROM classes c
      WHERE c.academic_year_id = ay.id AND c.class_name = '4ESO B'
  );

INSERT INTO settings (`key`, `value`)
SELECT 'current_academic_year', '2025-2026'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'current_academic_year');

-- Projectes inicials
INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'projecte-rius', 'Projecte Rius', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'projecte-rius');

INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'mat-penedes', 'MAT Penedès', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'mat-penedes');

INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'agroparc', 'Agroparc', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'agroparc');

INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'projecte-orenetes', 'Projecte Orenetes', 40, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'projecte-orenetes');

INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'liquencity', 'Liquencity', 50, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'liquencity');

INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'vespa-velutina', 'Vespa velutina', 60, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'vespa-velutina');

-- Traduccions dels projectes
INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Projecte Rius', 'Projecte educatiu sobre la gestió i recuperació dels rius.'
FROM projects p
JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'projecte-rius'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Proyecto Ríos', 'Proyecto educativo sobre la gestión y recuperación de los ríos.'
FROM projects p
JOIN languages l ON l.code = 'es'
WHERE p.slug = 'projecte-rius'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Rivers Project', 'Educational project about river management and recovery.'
FROM projects p
JOIN languages l ON l.code = 'en'
WHERE p.slug = 'projecte-rius'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'MAT Penedès', 'Programa d’educació ambiental al Penedès.'
FROM projects p
JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'mat-penedes'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'MAT Penedès', 'Programa de educación ambiental en el Penedès.'
FROM projects p
JOIN languages l ON l.code = 'es'
WHERE p.slug = 'mat-penedes'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'MAT Penedès', 'Environmental education programme in the Penedès area.'
FROM projects p
JOIN languages l ON l.code = 'en'
WHERE p.slug = 'mat-penedes'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Agroparc', 'Projecte sobre agricultura, sostenibilitat i territori.'
FROM projects p
JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'agroparc'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Agroparc', 'Proyecto sobre agricultura, sostenibilidad y territorio.'
FROM projects p
JOIN languages l ON l.code = 'es'
WHERE p.slug = 'agroparc'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Agroparc', 'Project about agriculture, sustainability and territory.'
FROM projects p
JOIN languages l ON l.code = 'en'
WHERE p.slug = 'agroparc'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Projecte Orenetes', 'Projecte de conservació i observació de les orenetes.'
FROM projects p
JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'projecte-orenetes'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Proyecto Orenetes', 'Proyecto de conservación y observación de las golondrinas.'
FROM projects p
JOIN languages l ON l.code = 'es'
WHERE p.slug = 'projecte-orenetes'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Swallow Project', 'Conservation and observation project for swallows.'
FROM projects p
JOIN languages l ON l.code = 'en'
WHERE p.slug = 'projecte-orenetes'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Liquencity', 'Projecte de ciència ciutadana centrat en líquens.'
FROM projects p
JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'liquencity'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Liquencity', 'Proyecto de ciencia ciudadana centrado en líquenes.'
FROM projects p
JOIN languages l ON l.code = 'es'
WHERE p.slug = 'liquencity'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Liquencity', 'Citizen science project focused on lichens.'
FROM projects p
JOIN languages l ON l.code = 'en'
WHERE p.slug = 'liquencity'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Vespa velutina', 'Projecte de seguiment de la vespa velutina.'
FROM projects p
JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'vespa-velutina'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Vespa velutina', 'Proyecto de seguimiento de la avispa velutina.'
FROM projects p
JOIN languages l ON l.code = 'es'
WHERE p.slug = 'vespa-velutina'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Asian hornet', 'Monitoring project for the Asian hornet.'
FROM projects p
JOIN languages l ON l.code = 'en'
WHERE p.slug = 'vespa-velutina'
  AND NOT EXISTS (
      SELECT 1 FROM project_translations pt
      WHERE pt.project_id = p.id AND pt.language_id = l.id
  );
