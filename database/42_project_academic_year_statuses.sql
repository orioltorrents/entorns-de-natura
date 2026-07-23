-- --------------------------------------------------------
-- Entorns de Natura - Estats d'edició de projecte
-- Documenta i normalitza valors de project_academic_years.status.
-- La columna és VARCHAR per mantenir compatibilitat amb bases existents.
-- --------------------------------------------------------

UPDATE project_academic_years
SET status = CASE LOWER(TRIM(status))
    WHEN 'pendent' THEN 'pendent'
    WHEN 'planned' THEN 'pendent'
    WHEN 'previst' THEN 'pendent'
    WHEN 'actiu' THEN 'actiu'
    WHEN 'active' THEN 'actiu'
    WHEN 'realitzat' THEN 'realitzat'
    WHEN 'completed' THEN 'realitzat'
    WHEN 'completat' THEN 'realitzat'
    WHEN 'arxivat' THEN 'arxivat'
    WHEN 'archived' THEN 'arxivat'
    WHEN 'archive' THEN 'arxivat'
    ELSE 'actiu'
END;
