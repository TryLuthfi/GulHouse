-- Additive resident lifecycle. No legacy occupant records are rewritten.
CREATE TABLE IF NOT EXISTS gh_lifecycle_facility_catalog (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, item_name VARCHAR(140) NOT NULL UNIQUE,
 category VARCHAR(80) NOT NULL DEFAULT 'Fasilitas Kamar', standard_qty INT UNSIGNED NOT NULL DEFAULT 1,
 unit_name VARCHAR(40) NOT NULL DEFAULT 'unit', is_active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO gh_lifecycle_facility_catalog (id,item_name,standard_qty) VALUES
 (1,'AC',1),(2,'TV',1),(3,'Kulkas',1),(4,'Lemari',1),(5,'Rak Sepatu',1),(6,'Dipan',1),
 (7,'Kasur',1),(8,'Bantal',2),(9,'Sprei Set',1),(10,'Bedcover',1),(11,'Kunci Kamar',1),
 (12,'Pintu Kamar',1),(13,'Wastafel',1),(14,'Shower',1),(15,'Closet',1),(16,'Tempat Sampah',1),
 (17,'Cermin',1),(18,'Keset',1),(19,'Lampu',1);
CREATE TABLE IF NOT EXISTS gh_resident_cases (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, access_hash CHAR(64) NOT NULL UNIQUE,
 full_name VARCHAR(160) NOT NULL, phone VARCHAR(40) NOT NULL, source VARCHAR(30) NOT NULL,
 room_id INT UNSIGNED NOT NULL, planned_start DATE NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'intake',
 tenant_id INT UNSIGNED NULL, stay_id INT UNSIGNED NULL UNIQUE, payment_verified_at DATETIME NULL,
 profile_json LONGTEXT NULL, profile_completed_at DATETIME NULL, ktp_file_id BIGINT UNSIGNED NULL,
 marriage_file_id BIGINT UNSIGNED NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_resident_files (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL, inspection_id BIGINT UNSIGNED NULL,
 kind VARCHAR(30) NOT NULL, original_name VARCHAR(180) NOT NULL, storage_name VARCHAR(80) NOT NULL,
 mime VARCHAR(80) NOT NULL, sha256 CHAR(64) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_case_kind_hash(case_id,kind,sha256), KEY idx_file_case(case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_facility_inspections (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL, tenant_id INT UNSIGNED NOT NULL,
 stay_id INT UNSIGNED NOT NULL, room_id INT UNSIGNED NOT NULL, stage VARCHAR(20) NOT NULL,
 check_date DATE NOT NULL, period CHAR(7) NOT NULL, officer_name VARCHAR(120) NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'draft', revision_of BIGINT UNSIGNED NULL, notes TEXT NULL,
 resident_signed TINYINT NOT NULL DEFAULT 0, manager_signed TINYINT NOT NULL DEFAULT 0, supervisor_signed TINYINT NOT NULL DEFAULT 0,
 finalized_at DATETIME NULL, finalized_by INT UNSIGNED NULL, ocr_status VARCHAR(30) NOT NULL DEFAULT 'manual_required',
 ocr_json LONGTEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, KEY idx_inspection_case(case_id,stage,period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_facility_inspection_rows (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, inspection_id BIGINT UNSIGNED NOT NULL, item_id INT UNSIGNED NOT NULL,
 item_name VARCHAR(140) NOT NULL, standard_qty INT UNSIGNED NOT NULL, actual_qty INT UNSIGNED NULL,
 condition_status VARCHAR(25) NOT NULL DEFAULT 'uninspected', notes TEXT NULL, maintenance_ticket_id BIGINT UNSIGNED NULL,
 UNIQUE KEY uniq_inspection_item(inspection_id,item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_checkout_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL UNIQUE,
 notice_date DATE NOT NULL, planned_exit DATE NOT NULL, late_notice TINYINT NOT NULL DEFAULT 0, reason TEXT NULL,
 actual_date DATE NULL, refund_due DATE NULL, deposit_snapshot BIGINT UNSIGNED NOT NULL DEFAULT 0,
 deduction_amount BIGINT UNSIGNED NOT NULL DEFAULT 0, deduction_reason TEXT NULL, disputed_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
 refundable_amount BIGINT UNSIGNED NOT NULL DEFAULT 0, paid_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
 refund_bank VARCHAR(100) NULL, refund_account VARCHAR(100) NULL, resolution_note TEXT NULL,
 status VARCHAR(25) NOT NULL DEFAULT 'notice', created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_refund_payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 amount BIGINT UNSIGNED NOT NULL, transfer_date DATE NOT NULL, reference_no VARCHAR(120) NOT NULL UNIQUE,
 proof_file_id BIGINT UNSIGNED NOT NULL, ledger_id BIGINT UNSIGNED NOT NULL UNIQUE, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_resident_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
 event_type VARCHAR(60) NOT NULL, description TEXT NOT NULL, actor VARCHAR(80) NOT NULL,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP, KEY idx_event_case(case_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
