-- Explicit additive mobile authentication; no legacy table changes.
CREATE TABLE IF NOT EXISTS gh_mobile_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, token_hash CHAR(64) NOT NULL UNIQUE,
 subject_kind VARCHAR(16) NOT NULL, subject_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, expires_at DATETIME NOT NULL,
 revoked_at DATETIME NULL, device_label VARCHAR(120) NULL,
 KEY idx_mobile_subject(subject_kind,subject_id), KEY idx_mobile_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
