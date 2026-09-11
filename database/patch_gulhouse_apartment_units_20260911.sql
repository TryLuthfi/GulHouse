START TRANSACTION;

INSERT INTO `gh_properties` (`code`, `name`, `property_type`, `is_active`)
VALUES ('APT', 'Apartemen', 'apartment', 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `property_type` = VALUES(`property_type`),
  `is_active` = VALUES(`is_active`);

INSERT INTO `gh_room_types` (`name`, `is_active`)
VALUES
  ('The Nest', 1),
  ('Mejestic', 1),
  ('PIK 2', 1),
  ('Taman Anggrek', 1)
ON DUPLICATE KEY UPDATE `is_active` = VALUES(`is_active`);

INSERT INTO `gh_rooms`
  (`property_id`, `room_type_id`, `room_code`, `room_name`, `room_label`, `floor_no`, `deposit`, `monthly_price`, `status`, `is_public`)
SELECT p.`id`, rt.`id`, 'E-1606', 'The Nest', 'E 16/06', 16, 500000, 2500000, 'occupied', 0
FROM `gh_properties` p JOIN `gh_room_types` rt ON rt.`name` = 'The Nest'
WHERE p.`code` = 'APT'
ON DUPLICATE KEY UPDATE
  `room_type_id` = VALUES(`room_type_id`),
  `room_name` = VALUES(`room_name`),
  `room_label` = VALUES(`room_label`),
  `floor_no` = VALUES(`floor_no`),
  `deposit` = VALUES(`deposit`),
  `monthly_price` = VALUES(`monthly_price`);

INSERT INTO `gh_rooms`
  (`property_id`, `room_type_id`, `room_code`, `room_name`, `room_label`, `floor_no`, `deposit`, `monthly_price`, `status`, `is_public`)
SELECT p.`id`, rt.`id`, 'E-1607', 'The Nest', 'E 16/07', 16, 500000, 2500000, 'occupied', 0
FROM `gh_properties` p JOIN `gh_room_types` rt ON rt.`name` = 'The Nest'
WHERE p.`code` = 'APT'
ON DUPLICATE KEY UPDATE
  `room_type_id` = VALUES(`room_type_id`),
  `room_name` = VALUES(`room_name`),
  `room_label` = VALUES(`room_label`),
  `floor_no` = VALUES(`floor_no`),
  `deposit` = VALUES(`deposit`),
  `monthly_price` = VALUES(`monthly_price`);

INSERT INTO `gh_rooms`
  (`property_id`, `room_type_id`, `room_code`, `room_name`, `room_label`, `floor_no`, `deposit`, `monthly_price`, `status`, `is_public`)
SELECT p.`id`, rt.`id`, 'L-1003', 'Mejestic', 'L-1003', 10, 500000, 2500000, 'occupied', 0
FROM `gh_properties` p JOIN `gh_room_types` rt ON rt.`name` = 'Mejestic'
WHERE p.`code` = 'APT'
ON DUPLICATE KEY UPDATE
  `room_type_id` = VALUES(`room_type_id`),
  `room_name` = VALUES(`room_name`),
  `room_label` = VALUES(`room_label`),
  `floor_no` = VALUES(`floor_no`),
  `deposit` = VALUES(`deposit`),
  `monthly_price` = VALUES(`monthly_price`);

INSERT INTO `gh_rooms`
  (`property_id`, `room_type_id`, `room_code`, `room_name`, `room_label`, `floor_no`, `deposit`, `monthly_price`, `status`, `is_public`)
SELECT p.`id`, rt.`id`, 'TRBP-68-59', 'PIK 2', 'TRBP 68 -59', NULL, 500000, 1200000, 'occupied', 0
FROM `gh_properties` p JOIN `gh_room_types` rt ON rt.`name` = 'PIK 2'
WHERE p.`code` = 'APT'
ON DUPLICATE KEY UPDATE
  `room_type_id` = VALUES(`room_type_id`),
  `room_name` = VALUES(`room_name`),
  `room_label` = VALUES(`room_label`),
  `floor_no` = VALUES(`floor_no`),
  `deposit` = VALUES(`deposit`),
  `monthly_price` = VALUES(`monthly_price`);

INSERT INTO `gh_rooms`
  (`property_id`, `room_type_id`, `room_code`, `room_name`, `room_label`, `floor_no`, `deposit`, `monthly_price`, `status`, `is_public`)
SELECT p.`id`, rt.`id`, 'STD-50P', 'Taman Anggrek', 'STD-50P', NULL, 500000, NULL, 'occupied', 0
FROM `gh_properties` p JOIN `gh_room_types` rt ON rt.`name` = 'Taman Anggrek'
WHERE p.`code` = 'APT'
ON DUPLICATE KEY UPDATE
  `room_type_id` = VALUES(`room_type_id`),
  `room_name` = VALUES(`room_name`),
  `room_label` = VALUES(`room_label`),
  `floor_no` = VALUES(`floor_no`),
  `deposit` = VALUES(`deposit`),
  `monthly_price` = VALUES(`monthly_price`);

COMMIT;
