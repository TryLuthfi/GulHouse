-- Backup gh_properties first. Additive, rerunnable, no existing city overwritten.
ALTER TABLE gh_properties ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER address;
UPDATE gh_properties SET city = 'Jakarta Barat'
WHERE REPLACE(UPPER(code), ' ', '') IN ('GH1', 'GH2') AND (city IS NULL OR TRIM(city) = '');
UPDATE gh_properties SET city = 'Balikpapan'
WHERE REPLACE(UPPER(code), ' ', '') = 'GH3' AND address LIKE '%Kota Balikpapan%' AND (city IS NULL OR TRIM(city) = '');
