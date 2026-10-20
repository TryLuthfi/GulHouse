-- Explicit additive booking retention; never classify an existing row as demo.
ALTER TABLE gh_booking_requests ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL;
ALTER TABLE gh_booking_requests ADD COLUMN IF NOT EXISTS archived_by INT UNSIGNED NULL;
ALTER TABLE gh_booking_requests ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(500) NULL;
ALTER TABLE gh_tenants ADD COLUMN IF NOT EXISTS source_booking_id BIGINT UNSIGNED NULL;
ALTER TABLE gh_tenant_stays ADD COLUMN IF NOT EXISTS source_booking_id BIGINT UNSIGNED NULL;
ALTER TABLE gh_contracts ADD COLUMN IF NOT EXISTS source_booking_id BIGINT UNSIGNED NULL;
CREATE TABLE IF NOT EXISTS gh_booking_demo_registry (
 booking_id BIGINT UNSIGNED PRIMARY KEY, identity_hash CHAR(64) NOT NULL,
 registration_kind VARCHAR(30) NOT NULL, registered_by VARCHAR(120) NOT NULL,
 attestation VARCHAR(500) NOT NULL, registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_booking_retention_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, booking_id BIGINT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NULL, action VARCHAR(40) NOT NULL, reason VARCHAR(500) NOT NULL,
 plan_hash CHAR(64) NULL, counts_json LONGTEXT NULL, backup_name VARCHAR(120) NULL,
 backup_sha256 CHAR(64) NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_booking_file_gc (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, booking_id BIGINT UNSIGNED NOT NULL,
 storage_kind VARCHAR(30) NOT NULL, storage_name VARCHAR(200) NOT NULL, content_sha256 CHAR(64) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 finished_at DATETIME NULL, UNIQUE KEY uq_booking_gc(booking_id,storage_kind,storage_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gh_booking_file_gc ADD COLUMN IF NOT EXISTS content_sha256 CHAR(64) NOT NULL DEFAULT '';
