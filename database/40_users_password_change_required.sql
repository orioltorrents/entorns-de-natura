-- --------------------------------------------------------
-- Entorns de Natura - Canvi obligatori de contrasenya inicial
-- Aplica a bases existents. En reconstruccions netes ja queda incorporat
-- a database/02_education_tables.sql.
-- --------------------------------------------------------

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash,
    ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL DEFAULT NULL AFTER must_change_password;
