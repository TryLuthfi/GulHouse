START TRANSACTION;

UPDATE `gh_room_database`
SET `room_code` = 'B-309',
    `room_class_code` = 'B',
    `room_type` = 'Deluxe',
    `monthly_price` = 2500000
WHERE `property_code` = 'GH 2' AND `room_label` = 'Antalya 309';

UPDATE `gh_room_database`
SET `room_code` = 'C-303',
    `room_class_code` = 'C',
    `room_type` = 'VIP',
    `monthly_price` = 3000000
WHERE `property_code` = 'GH 2' AND `room_label` = 'Antalya 303';

UPDATE `gh_room_database`
SET `room_code` = 'B-317',
    `room_class_code` = 'B',
    `room_type` = 'Deluxe',
    `monthly_price` = 2500000
WHERE `property_code` = 'GH 2' AND `room_label` = 'Antalya 317';

UPDATE `gh_room_database`
SET `room_code` = 'B-310',
    `room_class_code` = 'B',
    `room_type` = 'Deluxe',
    `monthly_price` = 2500000
WHERE `property_code` = 'GH 2' AND `room_label` = 'Antalya 310';

UPDATE `gh_rooms` r
JOIN `gh_room_types` rt ON rt.`name` = 'Deluxe'
SET r.`room_code` = 'B-309',
    r.`room_type_id` = rt.`id`,
    r.`monthly_price` = 2500000
WHERE r.`room_label` = 'Antalya 309';

UPDATE `gh_rooms` r
JOIN `gh_room_types` rt ON rt.`name` = 'VIP'
SET r.`room_code` = 'C-303',
    r.`room_type_id` = rt.`id`,
    r.`monthly_price` = 3000000
WHERE r.`room_label` = 'Antalya 303';

UPDATE `gh_rooms` r
JOIN `gh_room_types` rt ON rt.`name` = 'Deluxe'
SET r.`room_code` = 'B-317',
    r.`room_type_id` = rt.`id`,
    r.`monthly_price` = 2500000
WHERE r.`room_label` = 'Antalya 317';

UPDATE `gh_rooms` r
JOIN `gh_room_types` rt ON rt.`name` = 'Deluxe'
SET r.`room_code` = 'B-310',
    r.`room_type_id` = rt.`id`,
    r.`monthly_price` = 2500000
WHERE r.`room_label` = 'Antalya 310';

INSERT INTO `gh_tenants` (`fullname`, `phone`, `source_label`)
VALUES ('Thesa Lestari', '0895335008051', 'Manual correction 2026-09-11')
ON DUPLICATE KEY UPDATE
  `fullname` = VALUES(`fullname`),
  `source_label` = VALUES(`source_label`);

UPDATE `gh_tenant_stays` s
JOIN `gh_rooms` r ON r.`id` = s.`room_id`
JOIN `gh_tenants` t ON t.`phone` = '0895335008051'
SET s.`tenant_id` = t.`id`,
    s.`stay_status` = 'active',
    s.`notes` = NULL
WHERE r.`room_label` = 'Ephesus 203' AND s.`check_out_date` IS NULL;

UPDATE `gh_rooms`
SET `status` = 'occupied',
    `is_public` = 0
WHERE `room_label` = 'Ephesus 203';

UPDATE `gh_tenants`
SET `phone` = '081360316116'
WHERE `fullname` = 'Farouk Muhammad Arifin';

UPDATE `gh_tenants`
SET `fullname` = 'M. Tegar'
WHERE `phone` = '0877743131780';

UPDATE `gh_tenants`
SET `fullname` = 'Nico Paresha'
WHERE `phone` = '085281101824';

COMMIT;
