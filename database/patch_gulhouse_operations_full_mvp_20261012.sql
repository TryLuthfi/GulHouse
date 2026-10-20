-- Full MVP operational modules for GUL HOUSE Management System.
-- Adds contracts, deposits, maintenance, inventory, complaints, facilities,
-- housekeeping, audits, finance expenses, notifications, and document templates.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `gh_contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_no` varchar(60) NOT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `stay_id` int(10) unsigned DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_price` bigint(20) unsigned DEFAULT NULL,
  `deposit_amount` bigint(20) unsigned DEFAULT NULL,
  `status` enum('draft','active','expiring','ended','cancelled') NOT NULL DEFAULT 'draft',
  `signed_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_contract_no` (`contract_no`),
  KEY `idx_status_dates` (`status`,`start_date`,`end_date`),
  KEY `idx_tenant` (`tenant_id`),
  KEY `idx_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_contract_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned DEFAULT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `document_type` enum('ktp','contract','statement','handover','inventory','vehicle','other') NOT NULL DEFAULT 'other',
  `document_title` varchar(160) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('needed','received','verified','rejected') NOT NULL DEFAULT 'needed',
  `received_at` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_contract` (`contract_id`),
  KEY `idx_tenant_status` (`tenant_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_deposit_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `stay_id` int(10) unsigned DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `transaction_type` enum('received','deduction','refund','adjustment') NOT NULL DEFAULT 'received',
  `amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `method` varchar(60) DEFAULT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_date` (`tenant_id`,`transaction_date`),
  KEY `idx_room_date` (`room_id`,`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_maintenance_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(60) NOT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `category` enum('ac','electric','plumbing','key','furniture','cleaning','internet','other') NOT NULL DEFAULT 'other',
  `issue_title` varchar(180) NOT NULL,
  `issue_detail` text DEFAULT NULL,
  `pic_name` varchar(120) DEFAULT NULL,
  `vendor_name` varchar(120) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','assigned','in_progress','done','cancelled') NOT NULL DEFAULT 'open',
  `reported_at` date DEFAULT NULL,
  `deadline_at` date DEFAULT NULL,
  `finished_at` date DEFAULT NULL,
  `cost_amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ticket_no` (`ticket_no`),
  KEY `idx_status_deadline` (`status`,`deadline_at`),
  KEY `idx_room_status` (`room_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_inventory_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(140) NOT NULL,
  `category` varchar(80) DEFAULT NULL,
  `standard_qty` int(10) unsigned NOT NULL DEFAULT 1,
  `unit_name` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_item_name` (`item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_inventory_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned DEFAULT NULL,
  `item_id` int(10) unsigned DEFAULT NULL,
  `item_name_snapshot` varchar(140) NOT NULL,
  `check_stage` enum('check_in','routine','check_out') NOT NULL DEFAULT 'routine',
  `standard_qty` int(10) unsigned NOT NULL DEFAULT 1,
  `actual_qty` int(10) unsigned NOT NULL DEFAULT 1,
  `condition_status` enum('good','needs_repair','damaged','missing') NOT NULL DEFAULT 'good',
  `check_date` date DEFAULT NULL,
  `officer_name` varchar(120) DEFAULT NULL,
  `repair_cost` bigint(20) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_room_stage` (`room_id`,`check_stage`),
  KEY `idx_condition` (`condition_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_complaints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(60) NOT NULL,
  `tenant_id` int(10) unsigned DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `category` enum('facility','noise','billing','security','cleanliness','other') NOT NULL DEFAULT 'other',
  `complaint_title` varchar(180) NOT NULL,
  `complaint_detail` text DEFAULT NULL,
  `pic_name` varchar(120) DEFAULT NULL,
  `status` enum('open','in_progress','resolved','cancelled') NOT NULL DEFAULT 'open',
  `reported_at` date DEFAULT NULL,
  `deadline_at` date DEFAULT NULL,
  `resolved_at` date DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ticket_no` (`ticket_no`),
  KEY `idx_status_deadline` (`status`,`deadline_at`),
  KEY `idx_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_facility_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `facility_name` varchar(140) NOT NULL,
  `location_label` varchar(140) DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `condition_status` enum('good','needs_maintenance','damaged') NOT NULL DEFAULT 'good',
  `followup_status` enum('open','in_progress','done','cancelled') NOT NULL DEFAULT 'done',
  `check_date` date DEFAULT NULL,
  `officer_name` varchar(120) DEFAULT NULL,
  `pic_name` varchar(120) DEFAULT NULL,
  `deadline_at` date DEFAULT NULL,
  `photo_link` varchar(255) DEFAULT NULL,
  `issue_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_condition_status` (`condition_status`,`followup_status`),
  KEY `idx_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_housekeeping_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `check_date` date DEFAULT NULL,
  `location_label` varchar(140) NOT NULL,
  `area_name` varchar(120) DEFAULT NULL,
  `check_item` varchar(140) NOT NULL,
  `condition_status` enum('good','needs_attention','dirty','damaged') NOT NULL DEFAULT 'good',
  `status` enum('open','done','cancelled') NOT NULL DEFAULT 'done',
  `officer_name` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_date_status` (`check_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_monthly_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_date` date DEFAULT NULL,
  `period_label` varchar(40) DEFAULT NULL,
  `location_label` varchar(140) NOT NULL,
  `audit_item` varchar(140) NOT NULL,
  `result_status` enum('ok','finding','critical') NOT NULL DEFAULT 'ok',
  `finding_notes` text DEFAULT NULL,
  `action_plan` text DEFAULT NULL,
  `pic_name` varchar(120) DEFAULT NULL,
  `deadline_at` date DEFAULT NULL,
  `status` enum('open','in_progress','done','cancelled') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_period_status` (`period_label`,`status`),
  KEY `idx_deadline` (`deadline_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_date` date DEFAULT NULL,
  `category` enum('maintenance','operational','utility','salary','supplies','tax','other') NOT NULL DEFAULT 'other',
  `description` varchar(220) NOT NULL,
  `property_id` int(10) unsigned DEFAULT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `amount` bigint(20) unsigned NOT NULL DEFAULT 0,
  `payment_status` enum('unpaid','paid','cancelled') NOT NULL DEFAULT 'paid',
  `vendor_name` varchar(120) DEFAULT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_date_category` (`expense_date`,`category`),
  KEY `idx_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_notification_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_key` varchar(80) NOT NULL,
  `title` varchar(140) NOT NULL,
  `channel` enum('whatsapp','email','manual') NOT NULL DEFAULT 'whatsapp',
  `body_template` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_notification_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(10) unsigned DEFAULT NULL,
  `recipient_name` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(40) DEFAULT NULL,
  `subject` varchar(180) DEFAULT NULL,
  `message_body` text NOT NULL,
  `status` enum('draft','queued','sent','failed','cancelled') NOT NULL DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_schedule` (`status`,`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gh_document_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_key` varchar(80) NOT NULL,
  `title` varchar(160) NOT NULL,
  `document_type` enum('contract','statement','rules','handover','tenant_data','receipt','other') NOT NULL DEFAULT 'other',
  `body_template` mediumtext NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `gh_inventory_items` (`item_name`, `category`, `standard_qty`, `unit_name`)
VALUES
  ('Kunci kamar', 'Akses', 1, 'pcs'),
  ('Kartu akses', 'Akses', 1, 'pcs'),
  ('Kasur', 'Furniture', 1, 'unit'),
  ('Ranjang', 'Furniture', 1, 'unit'),
  ('Lemari', 'Furniture', 1, 'unit'),
  ('Meja', 'Furniture', 1, 'unit'),
  ('Kursi', 'Furniture', 1, 'unit'),
  ('AC/Kipas', 'Elektronik', 1, 'unit'),
  ('Lampu', 'Elektrik', 1, 'unit'),
  ('Gorden', 'Furniture', 1, 'set'),
  ('Remote', 'Elektronik', 1, 'pcs')
ON DUPLICATE KEY UPDATE `is_active` = 1;

INSERT INTO `gh_notification_templates` (`template_key`, `title`, `channel`, `body_template`)
VALUES
  ('rent_due_reminder', 'Reminder Jatuh Tempo Sewa', 'whatsapp', 'Yth. {nama}, tagihan sewa kamar {kamar} GUL HOUSE sebesar {nominal} akan jatuh tempo pada {tanggal_jatuh_tempo}.'),
  ('payment_received', 'Bukti Pembayaran Diterima', 'whatsapp', 'Terima kasih {nama}. Pembayaran kamar {kamar} sebesar {nominal} sudah diterima pada {tanggal_bayar}.'),
  ('contract_expiring', 'Reminder Kontrak Akan Berakhir', 'whatsapp', 'Kontrak kamar {kamar} atas nama {nama} akan berakhir pada {tanggal_akhir}. Mohon konfirmasi perpanjangan.')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `body_template` = VALUES(`body_template`), `is_active` = 1;

INSERT INTO `gh_document_templates` (`template_key`, `title`, `document_type`, `body_template`)
VALUES
  ('rental_contract', 'Perjanjian Sewa Kamar Kos', 'contract', 'PERJANJIAN SEWA KAMAR KOS GUL HOUSE\n\nNomor: {contract_no}\n\nPemilik/Pengelola: {owner_name}\nPenghuni: {tenant_name}\nNomor Kamar: {room_label}\nMasa sewa: {start_date} sampai {end_date}\nHarga sewa: {monthly_price}\nDeposit: {deposit_amount}\n\nPenghuni wajib mematuhi tata tertib, menjaga inventaris, dan menyelesaikan kewajiban pembayaran.'),
  ('tenant_statement', 'Surat Pernyataan Penghuni', 'statement', 'Saya {tenant_name} menyatakan telah membaca, memahami, dan bersedia mematuhi Perjanjian Sewa serta Tata Tertib GUL HOUSE.'),
  ('house_rules', 'Tata Tertib Penghuni', 'rules', 'Penghuni wajib menjaga kebersihan, ketertiban, keamanan, fasilitas, dan tidak menggunakan kamar untuk kegiatan yang melanggar hukum.'),
  ('handover_inventory', 'Form Serah Terima Kamar dan Inventaris', 'handover', 'Nama Penghuni: {tenant_name}\nNomor Kamar: {room_label}\nTanggal Masuk: {start_date}\nTanggal Keluar: {end_date}\n\nChecklist inventaris dicatat pada kondisi awal dan kondisi akhir.'),
  ('tenant_data', 'Form Data Penghuni dan Kontak Darurat', 'tenant_data', 'Nama Lengkap: {tenant_name}\nNIK/KTP: {identity_number}\nNo. HP: {phone}\nKontak Darurat: {emergency_contact}')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `body_template` = VALUES(`body_template`), `is_active` = 1;

COMMIT;
