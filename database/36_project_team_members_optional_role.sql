-- --------------------------------------------------------
-- Entorns de Natura - Rol opcional en membres d'equip
-- --------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE project_team_members
    MODIFY COLUMN project_role_id INT UNSIGNED NULL;
