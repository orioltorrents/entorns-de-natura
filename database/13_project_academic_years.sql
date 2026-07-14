-- --------------------------------------------------------
-- Entorns de Natura - Vincle entre projectes i anys academics
-- Migracio no destructiva per a bases existents.
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS project_academic_years (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    academic_year_id INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'actiu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_project_academic_years (project_id, academic_year_id),
    KEY idx_project_academic_years_project_id (project_id),
    KEY idx_project_academic_years_academic_year_id (academic_year_id),
    CONSTRAINT fk_project_academic_years_project
        FOREIGN KEY (project_id) REFERENCES projects (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_project_academic_years_academic_year
        FOREIGN KEY (academic_year_id) REFERENCES academic_years (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name = '2024-2025'
WHERE p.slug = 'projecte-rius'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name = '2025-2026'
WHERE p.slug = 'projecte-rius'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name IN ('2023-2024', '2024-2025', '2025-2026')
WHERE p.slug = 'mat-penedes'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name IN ('2023-2024', '2024-2025')
WHERE p.slug = 'agroparc'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name IN ('2023-2024', '2024-2025', '2025-2026')
WHERE p.slug = 'liquencity'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name IN ('2023-2024', '2024-2025', '2025-2026')
WHERE p.slug = 'vespa-velutina'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, y.id, 'actiu'
FROM projects p
JOIN academic_years y ON y.name IN ('2023-2024', '2024-2025', '2025-2026')
WHERE p.slug = 'projecte-orenetes'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = y.id
  );
