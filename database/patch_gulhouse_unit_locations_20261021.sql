-- Additive unit-level location; house properties retain property-level location.
ALTER TABLE gh_rooms ADD COLUMN IF NOT EXISTS unit_city VARCHAR(120) NULL AFTER floor_no;
ALTER TABLE gh_rooms ADD COLUMN IF NOT EXISTS unit_address TEXT NULL AFTER unit_city;
