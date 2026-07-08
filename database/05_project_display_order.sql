-- --------------------------------------------------------
-- Entorns de Natura - Ordre de visualització dels projectes
-- Canvi no destructiu per a bases de dades existents.
-- --------------------------------------------------------

ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS display_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER name,
    ADD KEY IF NOT EXISTS idx_projects_display_order (display_order);

UPDATE projects SET display_order = 10 WHERE slug = 'projecte-rius' AND display_order = 0;
UPDATE projects SET display_order = 20 WHERE slug = 'mat-penedes' AND display_order = 0;
UPDATE projects SET display_order = 30 WHERE slug = 'agroparc' AND display_order = 0;
UPDATE projects SET display_order = 40 WHERE slug = 'projecte-orenetes' AND display_order = 0;
UPDATE projects SET display_order = 50 WHERE slug = 'liquencity' AND display_order = 0;
UPDATE projects SET display_order = 60 WHERE slug = 'vespa-velutina' AND display_order = 0;
