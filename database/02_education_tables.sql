-- --------------------------------------------------------
-- Entorns de Natura - Bloc educatiu (no toca users, roles ni user_roles)
-- --------------------------------------------------------

SET NAMES utf8mb4;

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
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_year_name (academic_year_id, name),
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
    CONSTRAINT fk_class_members_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_class_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
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
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_projects_slug (slug)
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

INSERT INTO classes (academic_year_id, name, code)
SELECT ay.id, '4ESO A', '4ESO-A'
FROM academic_years ay
WHERE ay.name = '2025-2026'
  AND NOT EXISTS (
      SELECT 1 FROM classes c
      WHERE c.academic_year_id = ay.id AND c.name = '4ESO A'
  );

INSERT INTO classes (academic_year_id, name, code)
SELECT ay.id, '4ESO B', '4ESO-B'
FROM academic_years ay
WHERE ay.name = '2025-2026'
  AND NOT EXISTS (
      SELECT 1 FROM classes c
      WHERE c.academic_year_id = ay.id AND c.name = '4ESO B'
  );

INSERT INTO settings (`key`, `value`)
SELECT 'current_academic_year', '2025-2026'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE `key` = 'current_academic_year');

-- Projectes inicials
INSERT INTO projects (slug, name, is_active)
SELECT 'projecte-rius', 'Projecte Rius', 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'projecte-rius');

INSERT INTO projects (slug, name, is_active)
SELECT 'mat-penedes', 'MAT Penedès', 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'mat-penedes');

INSERT INTO projects (slug, name, is_active)
SELECT 'agroparc', 'Agroparc', 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'agroparc');

INSERT INTO projects (slug, name, is_active)
SELECT 'projecte-orenetes', 'Projecte Orenetes', 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'projecte-orenetes');

INSERT INTO projects (slug, name, is_active)
SELECT 'liquencity', 'Liquencity', 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'liquencity');

INSERT INTO projects (slug, name, is_active)
SELECT 'vespa-velutina', 'Vespa velutina', 1
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
