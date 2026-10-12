-- Sync local gulhouse_db to the VPS snapshot exported as gulhouse_co_id.sql on 2026-10-12.
-- Differences found:
-- 1. Local database is missing gh_booking_requests.
-- 2. gh_admin_users differs only by last_login_at / updated_at.
-- 3. gh_activity_logs has local-only login rows 15-23; VPS snapshot has production rows 15-16.
--
-- Review before applying. The activity log section intentionally replaces log rows >= 15
-- to match the VPS snapshot exactly.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `gh_booking_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(150) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `room_interest` varchar(150) DEFAULT NULL,
  `move_in_plan` varchar(60) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `source` varchar(40) NOT NULL DEFAULT 'landing',
  `status` enum('new','contacted','survey','converted','cancelled') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `gh_booking_requests`
  (`id`, `fullname`, `phone`, `room_interest`, `move_in_plan`, `message`, `source`, `status`, `created_at`, `updated_at`)
VALUES
  (1, 'Davidlealt', '89384933223', 'GH 2 - Standart', '', 'Hey there! gulhouse.co.id, \r\nI recently encountered gulhouse.co.id online. \r\nWe provide  that helps businesses reach website owners through contact forms. \r\nThe platform helps businesses communicate with relevant websites. \r\nThe platform provides flexible pricing depending on usage. \r\n  \r\nIf this looks interesting, feel free to contact us. \r\n \r\nThanks for reading. \r\nContact us. \r\nTelegram - https://t.me/FeedbackFormEU \r\nWhatsApp - +375259112693 \r\nWhatsApp  https://wa.me/+375259112693 \r\nWe only use chat for communication.', 'landing', 'new', '2026-09-16 07:03:46', '2026-09-16 07:03:46'),
  (2, 'AndrewFlipt', '83427938588', 'GH 2 - Standart', '', 'Hai! \r\n \r\nTahukah Anda bahwa pesan bisnis dapat dikirim sesuai dengan hukum yang berlaku? \r\nData pribadi penerima tidak digunakan untuk mengirim pesan ini. Ini dikirim melalui formulir kontak yang ditujukan untuk permintaan masuk. Ini dapat mengurangi kemungkinan pesan ditandai sebagai spam. \r\nTes gratis tersedia jika Anda ingin mencoba layanannya. \r\nAnda dapat mengirim hingga 50.000 pesan sebagai bagian dari uji coba gratis. \r\nMengirim 1.000.000 pesan biayanya $59. \r\n \r\n Anda dapat menghubungi kami melalui Telegram atau WhatsApp untuk informasi lebih lanjut. \r\nTelegram - https://t.me/FeedbackFormEU \r\nWhatsApp - https://wa.me/375259112693 \r\n Terima kasih atas waktu Anda.', 'landing', 'new', '2026-10-07 05:41:30', '2026-10-07 05:41:30')
ON DUPLICATE KEY UPDATE
  `fullname` = VALUES(`fullname`),
  `phone` = VALUES(`phone`),
  `room_interest` = VALUES(`room_interest`),
  `move_in_plan` = VALUES(`move_in_plan`),
  `message` = VALUES(`message`),
  `source` = VALUES(`source`),
  `status` = VALUES(`status`),
  `created_at` = VALUES(`created_at`),
  `updated_at` = VALUES(`updated_at`);

ALTER TABLE `gh_booking_requests` AUTO_INCREMENT = 3;

UPDATE `gh_admin_users`
SET
  `last_login_at` = '2026-10-12 11:24:14',
  `updated_at` = '2026-10-12 11:24:14'
WHERE `id` = 1;

DELETE FROM `gh_activity_logs`
WHERE `id` >= 15;

INSERT INTO `gh_activity_logs`
  (`id`, `admin_user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`)
VALUES
  (15, 1, 'login', 'Admin masuk ke web master.', '125.165.88.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-12 11:24:04'),
  (16, 1, 'login', 'Admin masuk ke web master.', '125.165.88.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-12 11:24:14');

ALTER TABLE `gh_activity_logs` AUTO_INCREMENT = 17;

COMMIT;
