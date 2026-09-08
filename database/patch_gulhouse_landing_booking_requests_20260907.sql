CREATE TABLE IF NOT EXISTS `gh_booking_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `room_interest` VARCHAR(150) DEFAULT NULL,
  `move_in_plan` VARCHAR(60) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `source` VARCHAR(40) NOT NULL DEFAULT 'landing',
  `status` ENUM('new','contacted','survey','converted','cancelled') NOT NULL DEFAULT 'new',
  `created_at` DATETIME NOT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
