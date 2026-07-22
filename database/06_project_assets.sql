-- --------------------------------------------------------
-- Entorns de Natura - Assets/llistes de logos de projectes
-- Migració no destructiva per associar logos, softwares,
-- apps i altres recursos visuals als projectes.
-- --------------------------------------------------------

SET NAMES utf8mb4;

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

-- --------------------------------------------------------
-- Assets inicials dels projectes
-- --------------------------------------------------------

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'projecte-rius-logo', 'Projecte Rius', 'project', 'assets/logos/projectes/projecte-rius.png', NULL, NULL, 10, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'projecte-rius-logo');

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'mat-penedes-logo', 'MAT Penedès', 'project', 'assets/logos/projectes/mat-penedes.png', NULL, NULL, 20, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'mat-penedes-logo');

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'agroparc-logo', 'Agroparc', 'project', 'assets/logos/projectes/agroparc.png', NULL, NULL, 30, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'agroparc-logo');

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'projecte-orenetes-logo', 'Projecte Orenetes', 'project', 'assets/logos/projectes/projecte-orenetes.png', NULL, NULL, 40, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'projecte-orenetes-logo');

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'liquencity-logo', 'Liquencity', 'project', 'assets/logos/projectes/liquencity.png', NULL, NULL, 50, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'liquencity-logo');

INSERT INTO project_assets (slug, name, asset_type, logo_path, website_url, description, display_order, is_active)
SELECT 'vespa-velutina-logo', 'Vespa velutina', 'project', 'assets/logos/projectes/vespa-velutina.png', NULL, NULL, 60, 1
WHERE NOT EXISTS (SELECT 1 FROM project_assets WHERE slug = 'vespa-velutina-logo');

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 10, 1
FROM projects p
JOIN project_assets a
WHERE p.slug = 'projecte-rius'
  AND a.slug = 'projecte-rius-logo'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 20, 1
FROM projects p
JOIN project_assets a
WHERE p.slug = 'mat-penedes'
  AND a.slug = 'mat-penedes-logo'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 30, 1
FROM projects p
JOIN project_assets a
WHERE p.slug = 'agroparc'
  AND a.slug = 'agroparc-logo'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 40, 1
FROM projects p
JOIN project_assets a
WHERE p.slug = 'projecte-orenetes'
  AND a.slug = 'projecte-orenetes-logo'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 50, 1
FROM projects p
JOIN project_assets a
WHERE p.slug = 'liquencity'
  AND a.slug = 'liquencity-logo'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );

INSERT INTO project_asset_links (project_id, asset_id, display_order, is_visible)
SELECT p.id, a.id, 60, 1
FROM projects p
JOIN project_assets a
WHERE p.slug = 'vespa-velutina'
  AND a.slug = 'vespa-velutina-logo'
  AND NOT EXISTS (
      SELECT 1
      FROM project_asset_links pal
      WHERE pal.project_id = p.id
        AND pal.asset_id = a.id
  );
