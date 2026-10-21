-- Additive administrative regions; existing addresses and city values are untouched.
CREATE TABLE IF NOT EXISTS gh_region_provinces (
 id CHAR(2) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_region_regencies (
 id CHAR(4) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 province_id CHAR(2) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 name VARCHAR(255) NOT NULL,
 INDEX (province_id),
 FOREIGN KEY (province_id) REFERENCES gh_region_provinces(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gh_region_districts (
 id CHAR(7) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 regency_id CHAR(4) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 name VARCHAR(255) NOT NULL,
 INDEX (regency_id),
 FOREIGN KEY (regency_id) REFERENCES gh_region_regencies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE gh_properties
 ADD COLUMN IF NOT EXISTS region_province_id CHAR(2) NULL,
 ADD COLUMN IF NOT EXISTS region_regency_id CHAR(4) NULL,
 ADD COLUMN IF NOT EXISTS region_district_id CHAR(7) NULL;
ALTER TABLE gh_rooms
 ADD COLUMN IF NOT EXISTS region_province_id CHAR(2) NULL,
 ADD COLUMN IF NOT EXISTS region_regency_id CHAR(4) NULL,
 ADD COLUMN IF NOT EXISTS region_district_id CHAR(7) NULL;
