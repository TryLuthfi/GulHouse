CREATE TABLE IF NOT EXISTS `gh_properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `property_type` ENUM('house','apartment','other') NOT NULL DEFAULT 'house',
  `address` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_room_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_rooms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `room_type_id` INT UNSIGNED NOT NULL,
  `room_code` VARCHAR(30) NOT NULL,
  `room_name` VARCHAR(100) NOT NULL,
  `room_label` VARCHAR(150) NOT NULL,
  `floor_no` TINYINT UNSIGNED DEFAULT NULL,
  `deposit` BIGINT UNSIGNED DEFAULT NULL,
  `monthly_price` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('available','occupied','reserved','maintenance','mess','inactive') NOT NULL DEFAULT 'available',
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `source_room_database_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_property_room_code` (`property_id`, `room_code`),
  KEY `idx_room_type_status` (`room_type_id`, `status`),
  KEY `idx_public_status` (`is_public`, `status`),
  CONSTRAINT `fk_gh_rooms_property` FOREIGN KEY (`property_id`) REFERENCES `gh_properties` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_gh_rooms_type` FOREIGN KEY (`room_type_id`) REFERENCES `gh_room_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin','admin','viewer') NOT NULL DEFAULT 'admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_created` (`admin_user_id`, `created_at`),
  CONSTRAINT `fk_gh_logs_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `gh_admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `gh_properties` (`code`, `name`, `property_type`)
SELECT DISTINCT `property_code`, `property_code`, 'house'
FROM `gh_room_database`
WHERE `property_code` IS NOT NULL AND `property_code` <> ''
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `is_active` = 1;

INSERT INTO `gh_room_types` (`name`)
SELECT DISTINCT `room_type`
FROM `gh_room_database`
WHERE `room_type` IS NOT NULL AND `room_type` <> ''
ON DUPLICATE KEY UPDATE `is_active` = 1;

INSERT INTO `gh_rooms`
  (`property_id`, `room_type_id`, `room_code`, `room_name`, `room_label`, `floor_no`, `deposit`, `monthly_price`, `status`, `is_public`, `source_room_database_id`)
SELECT
  p.`id`,
  rt.`id`,
  rd.`room_code`,
  rd.`room_name`,
  rd.`room_label`,
  rd.`floor_no`,
  rd.`deposit`,
  rd.`monthly_price`,
  CASE WHEN rd.`is_mess` = 1 THEN 'mess' WHEN rd.`monthly_price` IS NULL THEN 'maintenance' ELSE 'available' END,
  CASE WHEN rd.`is_mess` = 1 THEN 0 ELSE 1 END,
  rd.`id`
FROM `gh_room_database` rd
JOIN `gh_properties` p ON p.`code` = rd.`property_code`
JOIN `gh_room_types` rt ON rt.`name` = rd.`room_type`
ON DUPLICATE KEY UPDATE
  `room_type_id` = VALUES(`room_type_id`),
  `room_name` = VALUES(`room_name`),
  `room_label` = VALUES(`room_label`),
  `floor_no` = VALUES(`floor_no`),
  `deposit` = VALUES(`deposit`),
  `monthly_price` = VALUES(`monthly_price`),
  `status` = VALUES(`status`),
  `is_public` = VALUES(`is_public`),
  `source_room_database_id` = VALUES(`source_room_database_id`);

INSERT INTO `gh_admin_users` (`fullname`, `username`, `email`, `password_hash`, `role`)
VALUES ('Gul House Admin', 'admin', 'admin@gulhouse.co.id', '$2y$10$ZGEx3J/kq1N6fBrMZqGxMu4lNkyKUrnUrv59EFv9qTN5U83Yr3gyW', 'super_admin')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `is_active` = 1;
