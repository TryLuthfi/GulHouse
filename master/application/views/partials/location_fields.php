<?php defined('BASEPATH') OR exit('No direct script access allowed');
$locationRow = isset($location_row) ? $location_row : array();
$locationScope = isset($location_scope) && $location_scope === 'unit' ? 'unit' : 'property';
$unitLocation = $locationScope === 'unit';
$provinceId = (string) ($locationRow['region_province_id'] ?? '');
$regencyId = (string) ($locationRow['region_regency_id'] ?? '');
$districtId = (string) ($locationRow['region_district_id'] ?? '');
$legacyCity = trim((string) ($locationRow[$unitLocation ? 'unit_city' : 'city'] ?? ''));
$locationData = isset($region_data) ? $region_data : array();
$addressName = $unitLocation ? 'unit_address' : 'address';
?>
<div class="form-section" data-location-scope="<?= $locationScope; ?>">
    <h3 class="form-section-title"><?= $unitLocation ? 'Lokasi Unit (Apartemen)' : 'Lokasi Properti'; ?></h3>
    <p class="full"><?= $unitLocation ? 'Atur lokasi masing-masing unit apartemen. Alamat lengkap diisi manual.' : 'Pilih wilayah sampai kecamatan. Alamat lengkap diisi manual.'; ?></p>
    <label>Provinsi
        <select name="region_province_id" data-region-province autocomplete="address-level1">
            <option value="">Pilih provinsi</option>
            <?php foreach (($locationData['provinces'] ?? array()) as $region): ?>
                <option value="<?= html_escape((string) $region['id']); ?>" <?= (string) $region['id'] === $provinceId ? 'selected' : ''; ?>><?= html_escape($region['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Kabupaten/Kota
        <select name="region_regency_id" data-region-regency autocomplete="address-level2" <?= $provinceId === '' ? 'disabled' : ''; ?>>
            <option value=""><?= $provinceId === '' ? 'Pilih provinsi dahulu' : 'Pilih kabupaten/kota'; ?></option>
            <?php foreach (($locationData['regencies'] ?? array()) as $region): if ((string) $region['province_id'] !== $provinceId) continue; ?>
                <option value="<?= html_escape((string) $region['id']); ?>" <?= (string) $region['id'] === $regencyId ? 'selected' : ''; ?>><?= html_escape($region['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="full">Kecamatan
        <select name="region_district_id" data-region-district autocomplete="address-level3" <?= $regencyId === '' ? 'disabled' : ''; ?>>
            <option value=""><?= $regencyId === '' ? 'Pilih kabupaten/kota dahulu' : 'Pilih kecamatan'; ?></option>
            <?php foreach (($locationData['districts'] ?? array()) as $region): if ((string) $region['regency_id'] !== $regencyId) continue; ?>
                <option value="<?= html_escape((string) $region['id']); ?>" <?= (string) $region['id'] === $districtId ? 'selected' : ''; ?>><?= html_escape($region['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ($legacyCity !== '' && $regencyId === ''): ?>
        <p class="full" data-region-legacy>Kota tersimpan: <strong><?= html_escape($legacyCity); ?></strong>. Data ini tetap digunakan sampai wilayah baru dipilih.</p>
    <?php endif; ?>
    <label class="full"><?= $unitLocation ? 'Alamat Lengkap Unit' : 'Alamat Lengkap'; ?><textarea name="<?= $addressName; ?>" rows="3" maxlength="2000" autocomplete="street-address" placeholder="Kelurahan, jalan, nomor, blok, tower, lantai, atau nomor unit"><?= html_escape((string) ($locationRow[$addressName] ?? '')); ?></textarea></label>
    <?php if (empty($locationData['provinces'])): ?><p class="full" role="status">Pilihan wilayah belum tersedia. Alamat tersimpan tetap dipertahankan.</p><?php endif; ?>
</div>
