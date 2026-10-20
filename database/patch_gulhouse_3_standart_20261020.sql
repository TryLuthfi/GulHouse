-- GulHouse 3: 15 kamar Standart, Rp1.000.000/bulan, awal tersedia/kosong.
-- Jalankan pada database GulHouse yang benar setelah backup.
-- Deposit Rp500.000/kamar; lantai 1: S01-S05, lantai 2: S06-S10, lantai 3: S11-S15.
-- Insert-only: data existing (termasuk status hunian) tidak ditimpa saat rerun.
-- Tidak mengubah gh_room_database legacy; master dan katalog menggunakan gh_rooms.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO gh_properties (code, name, property_type, address, is_active)
SELECT 'GH 3', 'GulHouse 3', 'house',
       'Centra Bizpark 2, Jl. Syarifuddin Yoes.3, Blok CRM No.21, Sepinggan, Kecamatan Balikpapan Selatan, Kota Balikpapan, Kalimantan Timur 76115', 1
WHERE NOT EXISTS (SELECT 1 FROM gh_properties WHERE code = 'GH 3');

INSERT INTO gh_room_types (name, is_active)
SELECT 'Standart', 1
WHERE NOT EXISTS (SELECT 1 FROM gh_room_types WHERE name = 'Standart');

INSERT INTO gh_rooms
  (property_id, room_type_id, room_code, room_name, room_label,
   floor_no, deposit, monthly_price, status, is_public)
SELECT p.id, rt.id, CONCAT('S', LPAD(n.no, 2, '0')),
       CONCAT('Standart ', LPAD(n.no, 2, '0')),
       CONCAT('GH 3 - Standart ', LPAD(n.no, 2, '0')),
       FLOOR((n.no - 1) / 5) + 1, 500000, 1000000, 'available', 1
FROM gh_properties p
JOIN gh_room_types rt ON rt.name = 'Standart'
CROSS JOIN (
  SELECT 1 AS no UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
  UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
  UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
  UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
) n
WHERE p.code = 'GH 3'
  AND NOT EXISTS (
    SELECT 1 FROM gh_rooms existing
    WHERE existing.property_id = p.id
      AND existing.room_code = CONCAT('S', LPAD(n.no, 2, '0'))
  );

COMMIT;

-- Verifikasi: 15 baris available, harga 1000000, deposit 500000, 5 kamar per lantai.
-- Bila ada kode kamar existing, periksa hasilnya; patch tidak menimpa data tersebut.
SELECT p.code AS property_code, p.name AS property_name, p.address,
       rt.name AS room_type, r.room_code, r.room_label,
       r.monthly_price, r.deposit, r.floor_no, r.status, r.is_public
FROM gh_rooms r
JOIN gh_properties p ON p.id = r.property_id
JOIN gh_room_types rt ON rt.id = r.room_type_id
WHERE p.code = 'GH 3'
  AND r.room_code IN ('S01','S02','S03','S04','S05','S06','S07','S08',
                      'S09','S10','S11','S12','S13','S14','S15')
ORDER BY r.room_code;
