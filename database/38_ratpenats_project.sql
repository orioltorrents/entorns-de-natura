-- --------------------------------------------------------
-- Entorns de Natura - Projecte Ratpenats 2023-2024
-- --------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO projects (slug, name, display_order, is_active)
SELECT 'ratpenats', 'Ratpenats', 70, 1
WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = 'ratpenats');

UPDATE projects
SET name = 'Ratpenats', display_order = 70, is_active = 1
WHERE slug = 'ratpenats';

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Ratpenats', 'Projecte educatiu sobre ratpenats.'
FROM projects p
INNER JOIN languages l ON l.code = 'ca'
WHERE p.slug = 'ratpenats'
  AND NOT EXISTS (
      SELECT 1
      FROM project_translations pt
      WHERE pt.project_id = p.id
        AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Ratpenats', 'Proyecto educativo sobre murciélagos.'
FROM projects p
INNER JOIN languages l ON l.code = 'es'
WHERE p.slug = 'ratpenats'
  AND NOT EXISTS (
      SELECT 1
      FROM project_translations pt
      WHERE pt.project_id = p.id
        AND pt.language_id = l.id
  );

INSERT INTO project_translations (project_id, language_id, title, description)
SELECT p.id, l.id, 'Bats', 'Educational project about bats.'
FROM projects p
INNER JOIN languages l ON l.code = 'en'
WHERE p.slug = 'ratpenats'
  AND NOT EXISTS (
      SELECT 1
      FROM project_translations pt
      WHERE pt.project_id = p.id
        AND pt.language_id = l.id
  );

INSERT INTO project_academic_years (project_id, academic_year_id, status)
SELECT p.id, ay.id, 'realitzat'
FROM projects p
INNER JOIN academic_years ay ON ay.name = '2023-2024'
WHERE p.slug = 'ratpenats'
  AND NOT EXISTS (
      SELECT 1
      FROM project_academic_years pay
      WHERE pay.project_id = p.id
        AND pay.academic_year_id = ay.id
  );

UPDATE project_academic_years pay
INNER JOIN projects p ON p.id = pay.project_id
INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
SET pay.status = 'realitzat'
WHERE p.slug = 'ratpenats'
  AND ay.name = '2023-2024';

INSERT INTO project_class_assignments (project_academic_year_id, class_id, status, created_at)
SELECT pay.id, c.id, 'realitzat', NOW()
FROM project_academic_years pay
INNER JOIN projects p ON p.id = pay.project_id
INNER JOIN academic_years ay ON ay.id = pay.academic_year_id
INNER JOIN classes c ON c.academic_year_id = ay.id
WHERE p.slug = 'ratpenats'
  AND ay.name = '2023-2024'
  AND c.class_code = '23-24_4ESOA'
  AND NOT EXISTS (
      SELECT 1
      FROM project_class_assignments pca
      WHERE pca.project_academic_year_id = pay.id
        AND pca.class_id = c.id
  );

UPDATE project_class_assignments pca
INNER JOIN project_academic_years pay ON pay.id = pca.project_academic_year_id
INNER JOIN projects p ON p.id = pay.project_id
INNER JOIN classes c ON c.id = pca.class_id
SET pca.status = 'realitzat'
WHERE p.slug = 'ratpenats'
  AND c.class_code = '23-24_4ESOA';

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'ratpenats-logo', 'Ratpenats', 'project', 'assets/logos/projectes/ratpenats.png', NULL, NULL, 70, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'ratpenats-logo');

UPDATE project_assets
SET name = 'Ratpenats',
    asset_type = 'project',
    logo_path = 'assets/logos/projectes/ratpenats.png',
    display_order = 70,
    is_active = 1
WHERE slug = 'ratpenats-logo';

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 70, 1
FROM projects p
INNER JOIN project_assets a ON a.slug = 'ratpenats-logo'
WHERE p.slug = 'ratpenats'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );

UPDATE project_asset_links pal
INNER JOIN projects p ON p.id = pal.project_id
INNER JOIN project_assets a ON a.id = pal.asset_id
SET pal.display_order = 70,
    pal.is_visible = 1
WHERE p.slug = 'ratpenats'
  AND a.slug = 'ratpenats-logo';
