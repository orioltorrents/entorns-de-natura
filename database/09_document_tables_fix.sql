-- --------------------------------------------------------
-- Entorns de Natura - Ajustos no destructius per documents
-- Afegit de camps que poden faltar en bases creades abans de
-- la capa de documents.
-- --------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE documents
    ADD COLUMN notes TEXT NULL AFTER default_visibility;

ALTER TABLE document_sources
    ADD COLUMN source_fingerprint VARCHAR(64) NOT NULL AFTER document_id,
    ADD COLUMN notes TEXT NULL AFTER sync_mode,
    ADD UNIQUE KEY uq_document_sources_fingerprint (document_id, source_fingerprint);

ALTER TABLE document_fragments
    ADD COLUMN notes TEXT NULL AFTER content_format;

ALTER TABLE document_visibility_rules
    ADD COLUMN rule_fingerprint VARCHAR(64) NOT NULL AFTER fragment_id,
    ADD UNIQUE KEY uq_document_visibility_rules_fingerprint (document_id, rule_fingerprint);
