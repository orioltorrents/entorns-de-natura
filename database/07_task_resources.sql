-- --------------------------------------------------------
-- Entorns de Natura - Recursos de tasques i bastides
-- Catàleg reutilitzable de bastides/ajudes i relació amb
-- les tasques d'avaluació i els recursos dels projectes.
-- --------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_supports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    support_type ENUM('scaffold', 'help', 'hint', 'example') NOT NULL DEFAULT 'help',
    content LONGTEXT NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_supports_slug (slug),
    KEY idx_assessment_supports_type (support_type),
    KEY idx_assessment_supports_display_order (display_order),
    KEY idx_assessment_supports_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_task_resources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    support_id BIGINT UNSIGNED NULL,
    display_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_assessment_task_resources (task_id, asset_id),
    KEY idx_assessment_task_resources_task_id (task_id),
    KEY idx_assessment_task_resources_asset_id (asset_id),
    KEY idx_assessment_task_resources_support_id (support_id),
    CONSTRAINT fk_assessment_task_resources_task
        FOREIGN KEY (task_id) REFERENCES assessment_tasks (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assessment_task_resources_asset
        FOREIGN KEY (asset_id) REFERENCES project_assets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assessment_task_resources_support
        FOREIGN KEY (support_id) REFERENCES assessment_supports (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
