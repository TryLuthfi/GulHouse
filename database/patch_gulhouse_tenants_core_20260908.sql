CREATE TABLE IF NOT EXISTS `gh_tenants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(150) NOT NULL,
  `birth_place_date` VARCHAR(150) DEFAULT NULL,
  `religion` VARCHAR(60) DEFAULT NULL,
  `identity_number` VARCHAR(32) DEFAULT NULL,
  `phone` VARCHAR(32) DEFAULT NULL,
  `marital_status` VARCHAR(40) DEFAULT NULL,
  `occupation` VARCHAR(120) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `source_label` VARCHAR(120) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_identity_number` (`identity_number`),
  KEY `idx_phone` (`phone`),
  KEY `idx_fullname` (`fullname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_tenant_emergency_contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED NOT NULL,
  `contact_name` VARCHAR(120) DEFAULT NULL,
  `relationship` VARCHAR(80) DEFAULT NULL,
  `phone` VARCHAR(32) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant` (`tenant_id`),
  CONSTRAINT `fk_gh_emergency_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `gh_tenants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_tenant_stays` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` INT UNSIGNED DEFAULT NULL,
  `room_id` INT UNSIGNED NOT NULL,
  `stay_status` ENUM('active','reserved','ended','internal','empty') NOT NULL DEFAULT 'active',
  `check_in_date` DATE DEFAULT NULL,
  `check_out_date` DATE DEFAULT NULL,
  `monthly_price` BIGINT UNSIGNED DEFAULT NULL,
  `deposit` BIGINT UNSIGNED DEFAULT NULL,
  `source_period` VARCHAR(40) DEFAULT NULL,
  `source_unit_name` VARCHAR(120) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_room_open_stay` (`room_id`, `check_out_date`),
  KEY `idx_tenant_status` (`tenant_id`, `stay_status`),
  KEY `idx_room_status` (`room_id`, `stay_status`),
  CONSTRAINT `fk_gh_stays_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `gh_tenants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_gh_stays_room` FOREIGN KEY (`room_id`) REFERENCES `gh_rooms` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
